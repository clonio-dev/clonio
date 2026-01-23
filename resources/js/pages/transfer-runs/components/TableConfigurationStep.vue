<script setup lang="ts">
import TransferRunController from '@/actions/App/Http/Controllers/TransferRunController';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import StepNumber from '@/components/StepNumber.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Form } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Database,
    Loader2,
    Table2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SchemaColumn {
    name: string;
    type: string;
    nullable: boolean;
}

interface SchemaData {
    [tableName: string]: SchemaColumn[];
}

interface Props {
    sourceSchema: SchemaData;
    targetSchema: SchemaData;
    sourceConnectionId: string | number;
    targetConnectionId: string | number;
    sourceConnectionName: string;
    targetConnectionName: string;
}

interface Emits {
    (e: 'back'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Get all unique table names from source schema
const availableTables = computed(() => {
    return Object.keys(props.sourceSchema).sort();
});

// Track selected tables
const selectedTables = ref<Set<string>>(new Set(availableTables.value));

// Toggle table selection
function toggleTable(tableName: string) {
    if (selectedTables.value.has(tableName)) {
        selectedTables.value.delete(tableName);
    } else {
        selectedTables.value.add(tableName);
    }
    // Force reactivity
    selectedTables.value = new Set(selectedTables.value);
}

// Select/deselect all tables
function toggleAllTables() {
    if (selectedTables.value.size === availableTables.value.length) {
        selectedTables.value = new Set();
    } else {
        selectedTables.value = new Set(availableTables.value);
    }
}

// Check if all tables are selected
const allTablesSelected = computed(() => {
    return selectedTables.value.size === availableTables.value.length;
});

// Check if some (but not all) tables are selected
const someTablesSelected = computed(() => {
    return (
        selectedTables.value.size > 0 &&
        selectedTables.value.size < availableTables.value.length
    );
});

// Can proceed with submission
const canSubmit = computed(() => {
    return selectedTables.value.size > 0;
});

// Generate the script payload (JSON representation of selected tables)
const scriptPayload = computed(() => {
    const tables = Array.from(selectedTables.value).map((tableName) => ({
        name: tableName,
        columns: props.sourceSchema[tableName]?.map((col) => col.name) || [],
    }));

    return JSON.stringify({
        tables,
        version: '1.0',
    });
});

// Get column count for a table
function getColumnCount(tableName: string): number {
    return props.sourceSchema[tableName]?.length || 0;
}

// Check if table exists in target schema
function existsInTarget(tableName: string): boolean {
    return tableName in props.targetSchema;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Step header -->
        <div class="flex flex-col space-y-6">
            <div class="flex w-full gap-4">
                <StepNumber step="3" />
                <HeadingSmall
                    title="Configure tables to transfer"
                    description="Select the tables you want to transfer from source to target."
                />
            </div>
        </div>

        <!-- Connection info cards -->
        <div class="grid gap-4 md:grid-cols-2">
            <Card class="border-emerald-500/20 bg-emerald-500/5 dark:bg-emerald-500/5">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-sm font-medium">
                        <Database class="size-4 text-emerald-600 dark:text-emerald-400" />
                        Source
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-medium">{{ sourceConnectionName }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ availableTables.length }} tables available
                    </p>
                </CardContent>
            </Card>

            <Card class="border-blue-500/20 bg-blue-500/5 dark:bg-blue-500/5">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-sm font-medium">
                        <Database class="size-4 text-blue-600 dark:text-blue-400" />
                        Target
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-medium">{{ targetConnectionName }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ Object.keys(targetSchema).length }} tables available
                    </p>
                </CardContent>
            </Card>
        </div>

        <Separator />

        <!-- Table selection -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Table2 class="size-4 text-muted-foreground" />
                    <span class="text-sm font-medium">Tables to Transfer</span>
                    <span class="text-sm text-muted-foreground">
                        ({{ selectedTables.size }} of {{ availableTables.length }} selected)
                    </span>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    @click="toggleAllTables"
                >
                    {{ allTablesSelected ? 'Deselect All' : 'Select All' }}
                </Button>
            </div>

            <div
                class="grid gap-2 rounded-lg border p-4 md:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="tableName in availableTables"
                    :key="tableName"
                    class="flex items-center gap-3 rounded-md border p-3 transition-colors"
                    :class="{
                        'border-primary/50 bg-primary/5': selectedTables.has(tableName),
                        'border-transparent hover:bg-muted/50': !selectedTables.has(tableName),
                    }"
                >
                    <Checkbox
                        :id="`table-${tableName}`"
                        :checked="selectedTables.has(tableName)"
                        @update:checked="toggleTable(tableName)"
                    />
                    <Label
                        :for="`table-${tableName}`"
                        class="flex flex-1 cursor-pointer flex-col gap-0.5"
                    >
                        <span class="font-mono text-sm font-medium">
                            {{ tableName }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ getColumnCount(tableName) }} columns
                            <span
                                v-if="!existsInTarget(tableName)"
                                class="ml-1 text-amber-600 dark:text-amber-400"
                            >
                                (will be created)
                            </span>
                        </span>
                    </Label>
                </div>
            </div>

            <p
                v-if="availableTables.length === 0"
                class="text-center text-sm text-muted-foreground"
            >
                No tables found in source database.
            </p>
        </div>

        <Separator />

        <!-- Form submission -->
        <Form
            v-bind="TransferRunController.store()"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <input
                type="hidden"
                name="source_connection_id"
                :value="sourceConnectionId"
            />
            <input
                type="hidden"
                name="target_connection_id"
                :value="targetConnectionId"
            />
            <input type="hidden" name="script" :value="scriptPayload" />

            <InputError :message="errors.source_connection_id" />
            <InputError :message="errors.target_connection_id" />
            <InputError :message="errors.script" />

            <div class="flex items-center justify-between">
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('back')"
                    :disabled="processing"
                >
                    <ArrowLeft class="mr-2 size-4" />
                    Back
                </Button>

                <Button type="submit" :disabled="!canSubmit || processing">
                    <Loader2 v-if="processing" class="mr-2 size-4 animate-spin" />
                    <template v-else>
                        Start Transfer
                        <ArrowRight class="ml-2 size-4" />
                    </template>
                </Button>
            </div>
        </Form>
    </div>
</template>
