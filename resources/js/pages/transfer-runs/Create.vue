<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';

import TransferRunController from '@/actions/App/Http/Controllers/TransferRunController';
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import StepNumber from '@/components/StepNumber.vue';
import { Button } from '@/components/ui/button';
import { Combobox, ComboboxItems } from '@/components/ui/combobox';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import ConnectionFormSheet from '@/pages/connections/components/ConnectionFormSheet.vue';
import { computed, ref, watch } from 'vue';
import { ArrowRight, Loader2, Plus } from 'lucide-vue-next';
import TableConfigurationStep from './components/TableConfigurationStep.vue';

interface Props {
    prod_connections: ComboboxItems;
    test_connections: ComboboxItems;
}

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

const props = defineProps<Props>();
const page = usePage();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Transfer Runs',
        href: TransferRunController.index().url,
    },
];

// Step state
const currentStep = ref(1);

// Connection selection state
const selectedSourceConnection = ref<string | number | null>(null);
const selectedTargetConnection = ref<string | number | null>(null);

// Local copies of connections that we can modify
const prodConnections = ref<ComboboxItems>([...props.prod_connections]);
const testConnections = ref<ComboboxItems>([...props.test_connections]);

// Schema data from validation
const sourceSchema = ref<SchemaData | null>(null);
const targetSchema = ref<SchemaData | null>(null);

// Sheet state for on-the-fly connection creation
const showSourceConnectionSheet = ref(false);
const showTargetConnectionSheet = ref(false);
const pendingConnectionType = ref<'source' | 'target' | null>(null);

// Validation state
const isValidating = ref(false);
const validationErrors = ref<{ source_connection_id?: string; target_connection_id?: string }>({});

// Computed
const canProceedToStep2 = computed(() => {
    return selectedSourceConnection.value !== null && selectedTargetConnection.value !== null;
});

// Watch for flash data from validation
watch(
    () => page.props.flash as { validated_connections?: ValidatedConnectionsFlash; created_connection?: CreatedConnectionFlash },
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

            if (pendingConnectionType.value === 'source' && conn.is_production_stage) {
                prodConnections.value = [...prodConnections.value, newItem];
                selectedSourceConnection.value = conn.id;
                showSourceConnectionSheet.value = false;
            } else if (pendingConnectionType.value === 'target' && !conn.is_production_stage) {
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
        TransferRunController.validateConnections().url,
        {
            source_connection_id: selectedSourceConnection.value,
            target_connection_id: selectedTargetConnection.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                validationErrors.value = errors as typeof validationErrors.value;
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

// Get selected connection names for display
const selectedSourceName = computed(() => {
    const conn = prodConnections.value.find(c => c.value === selectedSourceConnection.value);
    return conn?.label || '';
});

const selectedTargetName = computed(() => {
    const conn = testConnections.value.find(c => c.value === selectedTargetConnection.value);
    return conn?.label || '';
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create a new transfer run" />

        <div class="px-4 py-6">
            <Heading
                title="Create a new transfer"
                description="Transfer anonymized data from your production data into your test environments."
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
                    <span class="text-sm font-medium">Select Connections</span>
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
            </div>

            <!-- Step 1: Connection Selection -->
            <div v-if="currentStep === 1" class="space-y-6">
                <div class="flex flex-col space-y-6">
                    <div class="flex w-full gap-4">
                        <StepNumber step="1" />
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
                                validationErrors.source_connection_id = undefined;
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
                        <StepNumber step="2" />
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
                                validationErrors.target_connection_id = undefined;
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
                @back="goToStep1"
            />
        </div>

        <!-- Connection creation sheets -->
        <ConnectionFormSheet
            :open="showSourceConnectionSheet"
            submit-label="Create Source Connection"
            default-production
            @close="showSourceConnectionSheet = false; pendingConnectionType = null"
        />

        <ConnectionFormSheet
            :open="showTargetConnectionSheet"
            submit-label="Create Target Connection"
            @close="showTargetConnectionSheet = false; pendingConnectionType = null"
        />
    </AppLayout>
</template>
