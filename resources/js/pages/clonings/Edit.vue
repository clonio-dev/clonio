<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Cloning, CloningFormProps } from '@/types/cloning.types';
import { Head, router, usePage } from '@inertiajs/vue3';

import CloningController from '@/actions/App/Http/Controllers/CloningController';
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Combobox, ComboboxItems } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import ConnectionFormSheet from '@/pages/connections/components/ConnectionFormSheet.vue';
import ConnectionTypeIcon from '@/pages/connections/components/ConnectionTypeIcon.vue';
import { ArrowRight, Database, Loader2, Plus } from 'lucide-vue-next';
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

interface TableSchemaData {
    columns: SchemaColumn[];
    primaryKeyColumns: string[];
    foreignKeys: Array<{
        columns: string[];
        referencedTable: string;
        referencedColumns: string[];
    }>;
    piiMatches?: Record<
        string,
        {
            name: string;
            transformation: {
                strategy: string;
                options: Record<string, unknown>;
            };
        }
    >;
}

interface SchemaData {
    [tableName: string]: TableSchemaData;
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

// Combobox refs
const sourceCombobox = ref<InstanceType<typeof Combobox> | null>(null);
const targetCombobox = ref<InstanceType<typeof Combobox> | null>(null);

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

// Parse row selections from existing config
const initialRowSelections = computed(() => {
    if (!props.cloning.anonymization_config) {
        return undefined;
    }

    const config = props.cloning.anonymization_config as {
        tables?: Array<{
            tableName: string;
            rowSelection?: {
                strategy: 'full_table' | 'first_x' | 'last_x';
                limit: number;
                sortColumn: string;
            };
        }>;
    };

    if (!config.tables) {
        return undefined;
    }

    const result: Record<
        string,
        {
            strategy: 'full_table' | 'first_x' | 'last_x';
            limit: number;
            sortColumn: string;
        }
    > = {};

    for (const table of config.tables) {
        if (table.rowSelection) {
            result[table.tableName] = table.rowSelection;
        }
    }

    return Object.keys(result).length > 0 ? result : undefined;
});

// Parse keepUnknownTablesOnTarget from existing config
const initialKeepUnknownTablesOnTarget = computed(() => {
    if (!props.cloning.anonymization_config) {
        return undefined;
    }

    const config = props.cloning.anonymization_config as {
        keepUnknownTablesOnTarget?: boolean;
    };

    return config.keepUnknownTablesOnTarget;
});

// Watch for flash data from validation
watch(
    () =>
        page.props.flash as {
            validated_connections?: ValidatedConnectionsFlash;
        },
    (flash) => {
        if (flash?.validated_connections) {
            sourceSchema.value = flash.validated_connections.source_schema;
            targetSchema.value = flash.validated_connections.target_schema;
            currentStep.value = 2;
            isValidating.value = false;
        }
    },
    { immediate: true },
);

// Handle connection creation from sheets
function handleSourceConnectionCreated(connection: CreatedConnectionFlash) {
    const newItem = { value: connection.id, label: connection.name };
    prodConnections.value = [...prodConnections.value, newItem];
    selectedSourceConnection.value = connection.id;
    showSourceConnectionSheet.value = false;
}

function handleTargetConnectionCreated(connection: CreatedConnectionFlash) {
    const newItem = { value: connection.id, label: connection.name };
    testConnections.value = [...testConnections.value, newItem];
    selectedTargetConnection.value = connection.id;
    showTargetConnectionSheet.value = false;
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
    return conn?.label || props.cloning.source_connection?.name || '';
});

const selectedTargetName = computed(() => {
    const conn = testConnections.value.find(
        (c) => c.value === selectedTargetConnection.value,
    );
    return conn?.label || props.cloning.target_connection?.name || '';
});

// Get selected connection types for display
const selectedSourceType = computed(() => {
    const conn = props.prod_connections.find(
        (c) => c.value === selectedSourceConnection.value,
    );
    return conn?.type || props.cloning.source_connection?.type || '';
});

const selectedTargetType = computed(() => {
    const conn = props.test_connections.find(
        (c) => c.value === selectedTargetConnection.value,
    );
    return conn?.type || props.cloning.target_connection?.type || '';
});

function getSourceConnectionType(connectionValue: string | number): string {
    const conn = props.prod_connections.find((c) => c.value === connectionValue);
    return conn?.type || '';
}

function getTargetConnectionType(connectionValue: string | number): string {
    const conn = props.test_connections.find((c) => c.value === connectionValue);
    return conn?.type || '';
}
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
                <HeadingSmall
                    title="Name your cloning configuration"
                    description="Give your cloning a descriptive name for easy identification."
                />

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

                <!-- Connections -->
                <HeadingSmall
                    title="Select your connections"
                    description="Choose the source (production) and target (test) connections for data transfer."
                />

                <div class="grid gap-6 md:grid-cols-[1fr_auto_1fr]">
                    <!-- Source Connection Card -->
                    <Card class="border-emerald-500/20 bg-emerald-500/5 dark:border-emerald-500/15 dark:bg-emerald-500/5">
                        <CardHeader class="pb-4">
                            <div class="flex items-center gap-3">
                                <div v-if="selectedSourceType" class="shrink-0">
                                    <ConnectionTypeIcon :type="selectedSourceType" size="8" />
                                </div>
                                <div v-else class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 ring-1 ring-emerald-500/20 dark:bg-emerald-900/30 dark:ring-emerald-500/15">
                                    <Database class="size-5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div>
                                    <CardTitle class="text-sm font-semibold">Source Connection</CardTitle>
                                    <p class="text-xs text-muted-foreground">Production database</p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-2">
                                <Label for="source_connection_id">Connection</Label>
                                <Combobox
                                    ref="sourceCombobox"
                                    id="source_connection_id"
                                    :items="prodConnections"
                                    :model-value="selectedSourceConnection"
                                    placeholder="Select source..."
                                    class="w-full"
                                    @update:modelValue="
                                        selectedSourceConnection = $event;
                                        validationErrors.source_connection_id = undefined;
                                    "
                                >
                                    <template #selected="{ item }">
                                        <span class="flex items-center gap-2">
                                            <ConnectionTypeIcon :type="getSourceConnectionType(item.value)" size="4" />
                                            <span class="truncate">{{ item.label }}</span>
                                        </span>
                                    </template>
                                    <template #item="{ item }">
                                        <span class="flex items-center gap-2">
                                            <ConnectionTypeIcon :type="getSourceConnectionType(item.value)" size="4" />
                                            <span>{{ item.label }}</span>
                                        </span>
                                    </template>
                                    <template #footer>
                                        <div
                                            class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                                            @click="sourceCombobox?.closePopover(); showSourceConnectionSheet = true"
                                        >
                                            <Plus class="size-4" />
                                            Add new connection
                                        </div>
                                    </template>
                                </Combobox>
                                <InputError
                                    class="mt-1"
                                    :message="validationErrors.source_connection_id"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Arrow indicator -->
                    <div class="hidden items-center justify-center md:flex">
                        <div class="flex size-10 items-center justify-center rounded-full bg-muted">
                            <ArrowRight class="size-5 text-muted-foreground" />
                        </div>
                    </div>

                    <!-- Target Connection Card -->
                    <Card class="border-blue-500/20 bg-blue-500/5 dark:border-blue-500/15 dark:bg-blue-500/5">
                        <CardHeader class="pb-4">
                            <div class="flex items-center gap-3">
                                <div v-if="selectedTargetType" class="shrink-0">
                                    <ConnectionTypeIcon :type="selectedTargetType" size="8" />
                                </div>
                                <div v-else class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-blue-100 ring-1 ring-blue-500/20 dark:bg-blue-900/30 dark:ring-blue-500/15">
                                    <Database class="size-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <CardTitle class="text-sm font-semibold">Target Connection</CardTitle>
                                    <p class="text-xs text-muted-foreground">Test database</p>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-2">
                                <Label for="target_connection_id">Connection</Label>
                                <Combobox
                                    ref="targetCombobox"
                                    id="target_connection_id"
                                    :items="testConnections"
                                    :model-value="selectedTargetConnection"
                                    placeholder="Select target..."
                                    class="w-full"
                                    @update:modelValue="
                                        selectedTargetConnection = $event;
                                        validationErrors.target_connection_id = undefined;
                                    "
                                >
                                    <template #selected="{ item }">
                                        <span class="flex items-center gap-2">
                                            <ConnectionTypeIcon :type="getTargetConnectionType(item.value)" size="4" />
                                            <span class="truncate">{{ item.label }}</span>
                                        </span>
                                    </template>
                                    <template #item="{ item }">
                                        <span class="flex items-center gap-2">
                                            <ConnectionTypeIcon :type="getTargetConnectionType(item.value)" size="4" />
                                            <span>{{ item.label }}</span>
                                        </span>
                                    </template>
                                    <template #footer>
                                        <div
                                            class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                                            @click="targetCombobox?.closePopover(); showTargetConnectionSheet = true"
                                        >
                                            <Plus class="size-4" />
                                            Add new connection
                                        </div>
                                    </template>
                                </Combobox>
                                <InputError
                                    class="mt-1"
                                    :message="validationErrors.target_connection_id"
                                />
                            </div>
                        </CardContent>
                    </Card>
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
                :source-connection-type="selectedSourceType"
                :target-connection-type="selectedTargetType"
                :cloning-title="cloningTitle"
                :cloning-id="cloning.id"
                :initial-config="initialTableConfig"
                :initial-row-selections="initialRowSelections"
                :initial-keep-unknown-tables-on-target="initialKeepUnknownTablesOnTarget"
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
            @close="showSourceConnectionSheet = false"
            @created="handleSourceConnectionCreated"
        />

        <ConnectionFormSheet
            :open="showTargetConnectionSheet"
            submit-label="Create Target Connection"
            @close="showTargetConnectionSheet = false"
            @created="handleTargetConnectionCreated"
        />
    </AppLayout>
</template>
