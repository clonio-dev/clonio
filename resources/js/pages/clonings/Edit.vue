<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Cloning, CloningFormProps } from '@/types/cloning.types';
import { Head, router, usePage } from '@inertiajs/vue3';

import CloningController from '@/actions/App/Http/Controllers/CloningController';
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import StepNumber from '@/components/StepNumber.vue';
import { Button } from '@/components/ui/button';
import { Combobox, ComboboxItems } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import ConnectionFormSheet from '@/pages/connections/components/ConnectionFormSheet.vue';
import { ArrowRight, Loader2, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ScheduleConfigurationStep from './components/ScheduleConfigurationStep.vue';
import TableConfigurationStep from './components/TableConfigurationStep.vue';

interface EditProps extends CloningFormProps {
    cloning: Cloning;
}

const props = defineProps<EditProps>();

interface SchemaColumn {
    name: string;
    type: string;
    nullable: boolean;
}

interface SchemaData {
    [tableName: string]: SchemaColumn[];
}

interface ValidatedConnectionsFlash {
    source_connection: { id: number; name: string };
    target_connection: { id: number; name: string };
    source_schema: SchemaData;
    target_schema: SchemaData;
}

interface CreatedConnectionFlash {
    id: number;
    name: string;
    type: string;
    is_production_stage: boolean;
}

const page = usePage();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Clonings',
        href: CloningController.index().url,
    },
    {
        title: props.cloning.title,
        href: CloningController.show(props.cloning.id).url,
    },
    {
        title: 'Edit',
        href: CloningController.edit(props.cloning.id).url,
    },
]);

// Step state
const currentStep = ref(1);

// Connection selection state - pre-populated from cloning
const selectedSourceConnection = ref<string | number | null>(
    props.cloning.source_connection_id,
);
const selectedTargetConnection = ref<string | number | null>(
    props.cloning.target_connection_id,
);

// Title for the cloning - pre-populated
const cloningTitle = ref(props.cloning.title);

// Local copies of connections that we can modify
const prodConnections = ref<ComboboxItems>([...props.prod_connections]);
const testConnections = ref<ComboboxItems>([...props.test_connections]);

// Schema data from validation
const sourceSchema = ref<SchemaData | null>(null);
const targetSchema = ref<SchemaData | null>(null);

// Anonymization config from step 2
const anonymizationConfig = ref<string>('');

// Sheet state for on-the-fly connection creation
const showSourceConnectionSheet = ref(false);
const showTargetConnectionSheet = ref(false);
const pendingConnectionType = ref<'source' | 'target' | null>(null);

// Validation state
const isValidating = ref(false);
const validationErrors = ref<{
    source_connection_id?: string;
    target_connection_id?: string;
    title?: string;
}>({});

// Computed
const canProceedToStep2 = computed(() => {
    return (
        selectedSourceConnection.value !== null &&
        selectedTargetConnection.value !== null &&
        cloningTitle.value.trim().length > 0
    );
});

// Parse the existing anonymization config for the TableConfigurationStep
const initialTableConfig = computed(() => {
    if (!props.cloning.anonymization_config) {
        return undefined;
    }

    const config = props.cloning.anonymization_config as {
        tables?: Array<{
            tableName: string;
            columnMutations?: Array<{
                columnName: string;
                strategy: string;
                options: Record<string, unknown>;
            }>;
        }>;
    };

    if (!config.tables) {
        return undefined;
    }

    const result: Record<
        string,
        Record<string, { strategy: string; options: Record<string, unknown> }>
    > = {};

    for (const table of config.tables) {
        result[table.tableName] = {};
        for (const mutation of table.columnMutations || []) {
            result[table.tableName][mutation.columnName] = {
                strategy: mutation.strategy,
                options: mutation.options || {},
            };
        }
    }

    return result;
});

// Watch for flash data from validation
watch(
    () =>
        page.props.flash as {
            validated_connections?: ValidatedConnectionsFlash;
            created_connection?: CreatedConnectionFlash;
        },
    (flash) => {
        if (flash?.validated_connections) {
            sourceSchema.value = flash.validated_connections.source_schema;
            targetSchema.value = flash.validated_connections.target_schema;
            currentStep.value = 2;
            isValidating.value = false;
        }

        if (flash?.created_connection) {
            const conn = flash.created_connection;
            const newItem = { value: conn.id, label: conn.name };

            if (
                pendingConnectionType.value === 'source' &&
                conn.is_production_stage
            ) {
                prodConnections.value = [...prodConnections.value, newItem];
                selectedSourceConnection.value = conn.id;
                showSourceConnectionSheet.value = false;
            } else if (
                pendingConnectionType.value === 'target' &&
                !conn.is_production_stage
            ) {
                testConnections.value = [...testConnections.value, newItem];
                selectedTargetConnection.value = conn.id;
                showTargetConnectionSheet.value = false;
            }
            pendingConnectionType.value = null;
        }
    },
    { immediate: true },
);

// Open connection sheet
function openSourceConnectionSheet() {
    pendingConnectionType.value = 'source';
    showSourceConnectionSheet.value = true;
}

function openTargetConnectionSheet() {
    pendingConnectionType.value = 'target';
    showTargetConnectionSheet.value = true;
}

// Navigate to next step with validation
function goToStep2() {
    if (!canProceedToStep2.value) {
        return;
    }

    isValidating.value = true;
    validationErrors.value = {};

    router.post(
        CloningController.validateConnections().url,
        {
            source_connection_id: selectedSourceConnection.value,
            target_connection_id: selectedTargetConnection.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (successPage) => {
                const flash = successPage.props.flash as {
                    validated_connections?: ValidatedConnectionsFlash;
                };
                if (flash?.validated_connections) {
                    sourceSchema.value =
                        flash.validated_connections.source_schema;
                    targetSchema.value =
                        flash.validated_connections.target_schema;
                    currentStep.value = 2;
                }
                isValidating.value = false;
            },
            onError: (errors) => {
                validationErrors.value =
                    errors as typeof validationErrors.value;
                isValidating.value = false;
            },
            onFinish: () => {
                // Only set to false if not already moved to step 2
                if (currentStep.value === 1) {
                    isValidating.value = false;
                }
            },
        },
    );
}

// Go back to step 1
function goToStep1() {
    currentStep.value = 1;
}

// Go to step 3 with anonymization config
function goToStep3(config: string) {
    anonymizationConfig.value = config;
    currentStep.value = 3;
}

// Go back to step 2
function goToStep2FromStep3() {
    currentStep.value = 2;
}

// Get selected connection names for display
const selectedSourceName = computed(() => {
    const conn = prodConnections.value.find(
        (c) => c.value === selectedSourceConnection.value,
    );
    return conn?.label || props.cloning.sourceConnection?.name || '';
});

const selectedTargetName = computed(() => {
    const conn = testConnections.value.find(
        (c) => c.value === selectedTargetConnection.value,
    );
    return conn?.label || props.cloning.targetConnection?.name || '';
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Edit ${cloning.title}`" />

        <div class="px-4 py-6">
            <Heading
                :title="`Edit ${cloning.title}`"
                description="Update your cloning configuration settings."
            />

            <!-- Step indicator -->
            <div class="mb-8 flex items-center gap-4">
                <div
                    class="flex items-center gap-2"
                    :class="{ 'opacity-50': currentStep !== 1 }"
                >
                    <div
                        class="flex size-8 items-center justify-center rounded-full text-sm font-medium"
                        :class="
                            currentStep >= 1
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        1
                    </div>
                    <span class="text-sm font-medium">Configuration</span>
                </div>
                <div class="h-px w-8 bg-border" />
                <div
                    class="flex items-center gap-2"
                    :class="{ 'opacity-50': currentStep !== 2 }"
                >
                    <div
                        class="flex size-8 items-center justify-center rounded-full text-sm font-medium"
                        :class="
                            currentStep >= 2
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        2
                    </div>
                    <span class="text-sm font-medium">Configure Tables</span>
                </div>
                <div class="h-px w-8 bg-border" />
                <div
                    class="flex items-center gap-2"
                    :class="{ 'opacity-50': currentStep !== 3 }"
                >
                    <div
                        class="flex size-8 items-center justify-center rounded-full text-sm font-medium"
                        :class="
                            currentStep >= 3
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        3
                    </div>
                    <span class="text-sm font-medium">Schedule</span>
                </div>
            </div>

            <!-- Step 1: Connection Selection -->
            <div v-if="currentStep === 1" class="space-y-6">
                <!-- Title Input -->
                <div class="flex flex-col space-y-6">
                    <div class="flex w-full gap-4">
                        <StepNumber step="1" />
                        <HeadingSmall
                            title="Name your cloning configuration"
                            description="Give your cloning a descriptive name for easy identification."
                        />
                    </div>
                </div>

                <div class="grid max-w-120 gap-2">
                    <Label for="title">Title</Label>
                    <Input
                        id="title"
                        v-model="cloningTitle"
                        placeholder="e.g., Production to Staging Sync"
                        class="w-96"
                        @input="validationErrors.title = undefined"
                    />
                    <InputError
                        class="mt-2"
                        :message="validationErrors.title"
                    />
                </div>

                <Separator class="my-4" />

                <div class="flex flex-col space-y-6">
                    <div class="flex w-full gap-4">
                        <StepNumber step="2" />
                        <HeadingSmall
                            title="Select or create your source connection"
                            description="Your production data that you want to transfer to your test stages."
                        />
                    </div>
                </div>

                <div class="grid max-w-120 gap-2">
                    <Label for="source_connection_id">Source</Label>
                    <div class="flex items-center gap-2">
                        <Combobox
                            id="source_connection_id"
                            :items="prodConnections"
                            :model-value="selectedSourceConnection"
                            class="w-96"
                            @update:modelValue="
                                selectedSourceConnection = $event;
                                validationErrors.source_connection_id =
                                    undefined;
                            "
                        />
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openSourceConnectionSheet"
                        >
                            <Plus class="mr-1 size-4" />
                            New
                        </Button>
                    </div>
                    <InputError
                        class="mt-2"
                        :message="validationErrors.source_connection_id"
                    />
                </div>

                <Separator class="my-4" />

                <div class="flex flex-col space-y-6">
                    <div class="flex w-full gap-4">
                        <StepNumber step="3" />
                        <HeadingSmall
                            title="Select or create your target connection"
                            description="Your test stage the production data should be transferred to."
                        />
                    </div>
                </div>

                <div class="grid max-w-120 gap-2">
                    <Label for="target_connection_id">Target</Label>
                    <div class="flex items-center gap-2">
                        <Combobox
                            id="target_connection_id"
                            :items="testConnections"
                            :model-value="selectedTargetConnection"
                            class="w-96"
                            @update:modelValue="
                                selectedTargetConnection = $event;
                                validationErrors.target_connection_id =
                                    undefined;
                            "
                        />
                        <Button
                            variant="outline"
                            size="sm"
                            @click="openTargetConnectionSheet"
                        >
                            <Plus class="mr-1 size-4" />
                            New
                        </Button>
                    </div>
                    <InputError
                        class="mt-2"
                        :message="validationErrors.target_connection_id"
                    />
                </div>

                <Separator class="my-4" />

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="!canProceedToStep2 || isValidating"
                        @click="goToStep2"
                    >
                        <Loader2
                            v-if="isValidating"
                            class="mr-2 size-4 animate-spin"
                        />
                        <template v-else>
                            Next
                            <ArrowRight class="ml-2 size-4" />
                        </template>
                    </Button>
                    <span
                        v-if="isValidating"
                        class="text-sm text-muted-foreground"
                    >
                        Validating connections...
                    </span>
                </div>
            </div>

            <!-- Step 2: Configure Tables -->
            <TableConfigurationStep
                v-if="currentStep === 2"
                :source-schema="sourceSchema!"
                :target-schema="targetSchema!"
                :source-connection-id="selectedSourceConnection!"
                :target-connection-id="selectedTargetConnection!"
                :source-connection-name="selectedSourceName"
                :target-connection-name="selectedTargetName"
                :cloning-title="cloningTitle"
                :cloning-id="cloning.id"
                :initial-config="initialTableConfig"
                mode="edit"
                @back="goToStep1"
                @next="goToStep3"
            />

            <!-- Step 3: Schedule Configuration -->
            <ScheduleConfigurationStep
                v-if="currentStep === 3"
                :source-connection-id="selectedSourceConnection!"
                :target-connection-id="selectedTargetConnection!"
                :cloning-title="cloningTitle"
                :anonymization-config="anonymizationConfig"
                :cloning-id="cloning.id"
                :initial-schedule="cloning.schedule"
                :initial-is-scheduled="cloning.is_scheduled"
                mode="edit"
                @back="goToStep2FromStep3"
            />
        </div>

        <!-- Connection creation sheets -->
        <ConnectionFormSheet
            :open="showSourceConnectionSheet"
            submit-label="Create Source Connection"
            default-production
            @close="
                showSourceConnectionSheet = false;
                pendingConnectionType = null;
            "
        />

        <ConnectionFormSheet
            :open="showTargetConnectionSheet"
            submit-label="Create Target Connection"
            @close="
                showTargetConnectionSheet = false;
                pendingConnectionType = null;
            "
        />
    </AppLayout>
</template>
