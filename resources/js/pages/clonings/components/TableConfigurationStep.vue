<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import StepNumber from '@/components/StepNumber.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    ArrowLeft,
    ArrowRight,
    ChevronDown,
    ChevronRight,
    Copy,
    Database,
} from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

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
    cloningTitle: string;
    cloningId?: number;
    mode: 'create' | 'edit';
    initialConfig?: AllTablesConfig;
}

interface Emits {
    (e: 'back'): void;
    (e: 'next', config: string): void;
}

// Strategy enum values matching backend
type StrategyType = 'keep' | 'fake' | 'mask' | 'hash' | 'null' | 'static';

interface ColumnConfig {
    strategy: StrategyType;
    options: {
        // Fake options
        fakerMethod?: string;
        fakerMethodArguments?: unknown[];
        // Mask options
        visibleChars?: number;
        maskChar?: string;
        preserveFormat?: boolean;
        // Hash options
        algorithm?: string;
        salt?: string;
        // Static options
        value?: string | number | null;
    };
}

interface TableConfig {
    [columnName: string]: ColumnConfig;
}

interface AllTablesConfig {
    [tableName: string]: TableConfig;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Get all unique table names from source schema
const availableTables = computed(() => {
    return Object.keys(props.sourceSchema).sort();
});

// Track which table is currently open
const openTableIndex = ref(0);

// Track table configurations
const tableConfigs = reactive<AllTablesConfig>({});

// Initialize configs for all tables and columns with default "keep" or from initial config
function initializeConfigs() {
    for (const tableName of availableTables.value) {
        if (!tableConfigs[tableName]) {
            tableConfigs[tableName] = {};
        }
        for (const column of props.sourceSchema[tableName] || []) {
            if (!tableConfigs[tableName][column.name]) {
                // Check if there's an initial config for this column
                const initialConfig =
                    props.initialConfig?.[tableName]?.[column.name];
                if (initialConfig) {
                    // If the column is not nullable but has 'null' strategy, fall back to 'keep'
                    if (!column.nullable && initialConfig.strategy === 'null') {
                        tableConfigs[tableName][column.name] = {
                            strategy: 'keep',
                            options: {},
                        };
                    } else {
                        tableConfigs[tableName][column.name] = {
                            ...initialConfig,
                        };
                    }
                } else {
                    tableConfigs[tableName][column.name] = {
                        strategy: 'keep',
                        options: {},
                    };
                }
            }
        }
    }
}

// Initialize on mount
initializeConfigs();

// Watch for schema changes
watch(() => props.sourceSchema, initializeConfigs, { deep: true });

// Strategy display info
interface StrategyOption {
    value: StrategyType;
    label: string;
    description: string;
}

const allStrategyOptions: StrategyOption[] = [
    { value: 'keep', label: 'Keep identical', description: 'Copy value as-is' },
    { value: 'fake', label: 'Fake', description: 'Generate fake data' },
    { value: 'mask', label: 'Mask', description: 'Partially hide value' },
    { value: 'hash', label: 'Hash', description: 'Hash the value' },
    { value: 'null', label: 'Set to NULL', description: 'Replace with null' },
    {
        value: 'static',
        label: 'Static value',
        description: 'Replace with constant',
    },
];

// Get strategy options for a column, filtering out 'null' for non-nullable columns
function getStrategyOptionsForColumn(column: SchemaColumn): StrategyOption[] {
    if (column.nullable) {
        return allStrategyOptions;
    }
    return allStrategyOptions.filter((option) => option.value !== 'null');
}

// Common faker methods
const fakerMethods = [
    { value: 'name', label: 'Name' },
    { value: 'firstName', label: 'First Name' },
    { value: 'lastName', label: 'Last Name' },
    { value: 'email', label: 'Email' },
    { value: 'safeEmail', label: 'Safe Email' },
    { value: 'phoneNumber', label: 'Phone Number' },
    { value: 'address', label: 'Address' },
    { value: 'city', label: 'City' },
    { value: 'country', label: 'Country' },
    { value: 'postcode', label: 'Postcode' },
    { value: 'company', label: 'Company' },
    { value: 'jobTitle', label: 'Job Title' },
    { value: 'text', label: 'Text' },
    { value: 'sentence', label: 'Sentence' },
    { value: 'paragraph', label: 'Paragraph' },
    { value: 'word', label: 'Word' },
    { value: 'uuid', label: 'UUID' },
    { value: 'url', label: 'URL' },
    { value: 'ipv4', label: 'IPv4' },
    { value: 'userName', label: 'Username' },
    { value: 'password', label: 'Password' },
    { value: 'dateTime', label: 'DateTime' },
    { value: 'date', label: 'Date' },
    { value: 'randomNumber', label: 'Random Number' },
    { value: 'randomFloat', label: 'Random Float' },
    { value: 'boolean', label: 'Boolean' },
];

// Hash algorithms
const hashAlgorithms = [
    { value: 'sha256', label: 'SHA-256' },
    { value: 'sha512', label: 'SHA-512' },
    { value: 'md5', label: 'MD5' },
    { value: 'sha1', label: 'SHA-1' },
];

// Get column config
function getColumnConfig(tableName: string, columnName: string): ColumnConfig {
    return (
        tableConfigs[tableName]?.[columnName] || {
            strategy: 'keep',
            options: {},
        }
    );
}

// Update column strategy
function updateColumnStrategy(
    tableName: string,
    columnName: string,
    strategy: StrategyType,
) {
    if (!tableConfigs[tableName]) {
        tableConfigs[tableName] = {};
    }
    tableConfigs[tableName][columnName] = {
        strategy,
        options: getDefaultOptionsForStrategy(strategy),
    };
}

// Get default options for a strategy
function getDefaultOptionsForStrategy(
    strategy: StrategyType,
): ColumnConfig['options'] {
    switch (strategy) {
        case 'fake':
            return { fakerMethod: 'word', fakerMethodArguments: [] };
        case 'mask':
            return { visibleChars: 2, maskChar: '*', preserveFormat: false };
        case 'hash':
            return { algorithm: 'sha256', salt: '' };
        case 'static':
            return { value: '' };
        default:
            return {};
    }
}

// Update column option
function updateColumnOption<K extends keyof ColumnConfig['options']>(
    tableName: string,
    columnName: string,
    optionKey: K,
    value: ColumnConfig['options'][K],
) {
    if (tableConfigs[tableName]?.[columnName]) {
        tableConfigs[tableName][columnName].options[optionKey] = value;
    }
}

// Apply "Keep identical" to all columns in a table
function applyKeepIdenticalToTable(tableName: string) {
    for (const column of props.sourceSchema[tableName] || []) {
        updateColumnStrategy(tableName, column.name, 'keep');
    }
}

// Generate preview value for a column config
function getPreviewValue(config: ColumnConfig, column: SchemaColumn): string {
    switch (config.strategy) {
        case 'keep':
            return getOriginalPreview(column);
        case 'fake':
            return getFakePreview(config.options.fakerMethod || 'word');
        case 'mask':
            return getMaskPreview(column, config.options);
        case 'hash':
            return getHashPreview(config.options.algorithm || 'sha256');
        case 'null':
            return 'NULL';
        case 'static':
            return String(config.options.value || '(empty)');
        default:
            return '-';
    }
}

// Get preview for original value based on column type
function getOriginalPreview(column: SchemaColumn): string {
    const type = column.type.toLowerCase();
    if (type.includes('int')) return '42';
    if (
        type.includes('varchar') ||
        type.includes('text') ||
        type.includes('char')
    )
        return 'Example text';
    if (type.includes('date')) return '2024-01-15';
    if (type.includes('time')) return '14:30:00';
    if (type.includes('bool')) return 'true';
    if (
        type.includes('decimal') ||
        type.includes('float') ||
        type.includes('double')
    )
        return '19.99';
    if (type.includes('json')) return '{"key": "value"}';
    return 'value';
}

// Get preview for fake strategy
function getFakePreview(fakerMethod: string): string {
    const previews: Record<string, string> = {
        name: 'John Doe',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        safeEmail: 'john.doe@example.org',
        phoneNumber: '+1-555-123-4567',
        address: '123 Main St, Suite 100',
        city: 'New York',
        country: 'United States',
        postcode: '10001',
        company: 'Acme Corp',
        jobTitle: 'Software Engineer',
        text: 'Lorem ipsum dolor sit amet...',
        sentence: 'The quick brown fox jumps.',
        paragraph: 'Lorem ipsum dolor sit amet, consectetur...',
        word: 'example',
        uuid: '550e8400-e29b-41d4-a716-446655440000',
        url: 'https://example.com',
        ipv4: '192.168.1.1',
        userName: 'johndoe42',
        password: '********',
        dateTime: '2024-01-15 14:30:00',
        date: '2024-01-15',
        randomNumber: '12345',
        randomFloat: '123.45',
        boolean: 'true',
    };
    return previews[fakerMethod] || 'fake_value';
}

// Get preview for mask strategy
function getMaskPreview(
    column: SchemaColumn,
    options: ColumnConfig['options'],
): string {
    const original = getOriginalPreview(column);
    const visibleChars = options.visibleChars || 2;
    const maskChar = options.maskChar || '*';

    if (original.length <= visibleChars) {
        return maskChar.repeat(original.length);
    }

    return (
        original.substring(0, visibleChars) +
        maskChar.repeat(original.length - visibleChars)
    );
}

// Get preview for hash strategy
function getHashPreview(algorithm: string): string {
    const hashLengths: Record<string, number> = {
        sha256: 64,
        sha512: 128,
        md5: 32,
        sha1: 40,
    };
    const length = hashLengths[algorithm] || 64;
    return 'a'.repeat(Math.min(length, 16)) + '...';
}

// Navigation
function goToPreviousTable() {
    if (openTableIndex.value > 0) {
        openTableIndex.value--;
    }
}

function goToNextTable() {
    if (openTableIndex.value < availableTables.value.length - 1) {
        openTableIndex.value++;
    }
}

function toggleTable(index: number) {
    openTableIndex.value = openTableIndex.value === index ? -1 : index;
}

// Check if on first/last table
const isFirstTable = computed(() => openTableIndex.value === 0);
const isLastTable = computed(
    () => openTableIndex.value === availableTables.value.length - 1,
);

// Generate the config payload for submission
const configPayload = computed(() => {
    const tables: Array<{
        tableName: string;
        columnMutations: Array<{
            columnName: string;
            strategy: string;
            options: ColumnConfig['options'];
        }>;
    }> = [];

    for (const tableName of availableTables.value) {
        const columns = props.sourceSchema[tableName] || [];
        const columnMutations = columns.map((col) => {
            const config = getColumnConfig(tableName, col.name);
            return {
                columnName: col.name,
                strategy: config.strategy,
                options: config.options,
            };
        });

        tables.push({
            tableName,
            columnMutations,
        });
    }

    return JSON.stringify({
        tables,
        version: '1.0',
    });
});

// Get column type badge color
function getTypeColor(type: string): string {
    const t = type.toLowerCase();
    if (t.includes('int')) return 'text-blue-600 dark:text-blue-400';
    if (t.includes('varchar') || t.includes('text') || t.includes('char'))
        return 'text-emerald-600 dark:text-emerald-400';
    if (t.includes('date') || t.includes('time'))
        return 'text-purple-600 dark:text-purple-400';
    if (t.includes('bool')) return 'text-amber-600 dark:text-amber-400';
    if (t.includes('decimal') || t.includes('float') || t.includes('double'))
        return 'text-cyan-600 dark:text-cyan-400';
    return 'text-muted-foreground';
}
</script>

<template>
    <div class="space-y-6">
        <!-- Step header -->
        <div class="flex flex-col space-y-6">
            <div class="flex w-full gap-4">
                <StepNumber step="4" />
                <HeadingSmall
                    title="Configure transformation rules"
                    description="Define how each column should be transformed during transfer."
                />
            </div>
        </div>

        <!-- Connection info cards -->
        <div class="grid gap-4 md:grid-cols-2">
            <Card
                class="border-emerald-500/20 bg-emerald-500/5 dark:bg-emerald-500/5"
            >
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-medium"
                    >
                        <Database
                            class="size-4 text-emerald-600 dark:text-emerald-400"
                        />
                        Source
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-medium">{{ sourceConnectionName }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ availableTables.length }} tables
                    </p>
                </CardContent>
            </Card>

            <Card class="border-blue-500/20 bg-blue-500/5 dark:bg-blue-500/5">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="flex items-center gap-2 text-sm font-medium"
                    >
                        <Database
                            class="size-4 text-blue-600 dark:text-blue-400"
                        />
                        Target
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="font-medium">{{ targetConnectionName }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ Object.keys(targetSchema).length }} tables
                    </p>
                </CardContent>
            </Card>
        </div>

        <Separator />

        <!-- Tables configuration -->
        <div class="space-y-3">
            <Collapsible
                v-for="(tableName, index) in availableTables"
                :key="tableName"
                :open="openTableIndex === index"
                class="rounded-lg border"
            >
                <CollapsibleTrigger
                    class="flex w-full items-center justify-between rounded-t-lg px-4 py-3 hover:bg-muted/50"
                    :class="{ 'rounded-b-lg': openTableIndex !== index }"
                    @click="toggleTable(index)"
                >
                    <div class="flex items-center gap-3">
                        <component
                            :is="
                                openTableIndex === index
                                    ? ChevronDown
                                    : ChevronRight
                            "
                            class="size-4 text-muted-foreground"
                        />
                        <span class="font-mono text-sm font-medium">{{
                            tableName
                        }}</span>
                        <span class="text-xs text-muted-foreground">
                            ({{ sourceSchema[tableName]?.length || 0 }} columns)
                        </span>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-xs"
                        @click.stop="applyKeepIdenticalToTable(tableName)"
                    >
                        <Copy class="mr-1 size-3" />
                        Apply "Keep identical"
                    </Button>
                </CollapsibleTrigger>

                <CollapsibleContent>
                    <div class="border-t px-4 py-4">
                        <!-- Column headers -->
                        <div
                            class="mb-3 grid grid-cols-12 gap-4 border-b pb-2 text-xs font-medium text-muted-foreground"
                        >
                            <div class="col-span-3">Column</div>
                            <div class="col-span-2">Type</div>
                            <div class="col-span-3">Transformation</div>
                            <div class="col-span-2">Options</div>
                            <div class="col-span-2">Preview</div>
                        </div>

                        <!-- Column rows -->
                        <div class="space-y-3">
                            <div
                                v-for="column in sourceSchema[tableName]"
                                :key="column.name"
                                class="grid grid-cols-12 items-start gap-4"
                            >
                                <!-- Column name -->
                                <div class="col-span-3 flex flex-col gap-0.5">
                                    <span class="font-mono text-sm">{{
                                        column.name
                                    }}</span>
                                    <span
                                        v-if="column.nullable"
                                        class="text-xs text-muted-foreground"
                                    >
                                        nullable
                                    </span>
                                </div>

                                <!-- Column type -->
                                <div class="col-span-2">
                                    <span
                                        class="font-mono text-xs"
                                        :class="getTypeColor(column.type)"
                                    >
                                        {{ column.type }}
                                    </span>
                                </div>

                                <!-- Transformation dropdown -->
                                <div class="col-span-3">
                                    <Select
                                        :model-value="
                                            getColumnConfig(
                                                tableName,
                                                column.name,
                                            ).strategy
                                        "
                                        @update:model-value="
                                            (v) =>
                                                v &&
                                                updateColumnStrategy(
                                                    tableName,
                                                    column.name,
                                                    v as StrategyType,
                                                )
                                        "
                                    >
                                        <SelectTrigger
                                            class="h-8 w-full text-xs"
                                        >
                                            <SelectValue
                                                placeholder="Select rule"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in getStrategyOptionsForColumn(
                                                    column,
                                                )"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                <div class="flex flex-col">
                                                    <span>{{
                                                        option.label
                                                    }}</span>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Options based on strategy -->
                                <div class="col-span-2">
                                    <!-- Fake options -->
                                    <template
                                        v-if="
                                            getColumnConfig(
                                                tableName,
                                                column.name,
                                            ).strategy === 'fake'
                                        "
                                    >
                                        <Select
                                            :model-value="
                                                getColumnConfig(
                                                    tableName,
                                                    column.name,
                                                ).options.fakerMethod || 'word'
                                            "
                                            @update:model-value="
                                                (v) =>
                                                    v &&
                                                    updateColumnOption(
                                                        tableName,
                                                        column.name,
                                                        'fakerMethod',
                                                        String(v),
                                                    )
                                            "
                                        >
                                            <SelectTrigger
                                                class="h-8 w-full text-xs"
                                            >
                                                <SelectValue
                                                    placeholder="Method"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="method in fakerMethods"
                                                    :key="method.value"
                                                    :value="method.value"
                                                >
                                                    {{ method.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </template>

                                    <!-- Mask options -->
                                    <template
                                        v-else-if="
                                            getColumnConfig(
                                                tableName,
                                                column.name,
                                            ).strategy === 'mask'
                                        "
                                    >
                                        <div class="flex items-center gap-1">
                                            <Input
                                                type="number"
                                                :model-value="
                                                    getColumnConfig(
                                                        tableName,
                                                        column.name,
                                                    ).options.visibleChars || 2
                                                "
                                                @update:model-value="
                                                    (v) =>
                                                        updateColumnOption(
                                                            tableName,
                                                            column.name,
                                                            'visibleChars',
                                                            Number(v),
                                                        )
                                                "
                                                class="h-8 w-12 px-2 text-xs"
                                                min="0"
                                                max="10"
                                                placeholder="2"
                                            />
                                            <Input
                                                type="text"
                                                :model-value="
                                                    getColumnConfig(
                                                        tableName,
                                                        column.name,
                                                    ).options.maskChar || '*'
                                                "
                                                @update:model-value="
                                                    (v) =>
                                                        updateColumnOption(
                                                            tableName,
                                                            column.name,
                                                            'maskChar',
                                                            String(v).charAt(
                                                                0,
                                                            ) || '*',
                                                        )
                                                "
                                                class="h-8 w-10 px-2 text-center text-xs"
                                                maxlength="1"
                                                placeholder="*"
                                            />
                                        </div>
                                    </template>

                                    <!-- Hash options -->
                                    <template
                                        v-else-if="
                                            getColumnConfig(
                                                tableName,
                                                column.name,
                                            ).strategy === 'hash'
                                        "
                                    >
                                        <Select
                                            :model-value="
                                                getColumnConfig(
                                                    tableName,
                                                    column.name,
                                                ).options.algorithm || 'sha256'
                                            "
                                            @update:model-value="
                                                (v) =>
                                                    v &&
                                                    updateColumnOption(
                                                        tableName,
                                                        column.name,
                                                        'algorithm',
                                                        String(v),
                                                    )
                                            "
                                        >
                                            <SelectTrigger
                                                class="h-8 w-full text-xs"
                                            >
                                                <SelectValue
                                                    placeholder="Algorithm"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="algo in hashAlgorithms"
                                                    :key="algo.value"
                                                    :value="algo.value"
                                                >
                                                    {{ algo.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </template>

                                    <!-- Static options -->
                                    <template
                                        v-else-if="
                                            getColumnConfig(
                                                tableName,
                                                column.name,
                                            ).strategy === 'static'
                                        "
                                    >
                                        <Input
                                            type="text"
                                            :model-value="
                                                String(
                                                    getColumnConfig(
                                                        tableName,
                                                        column.name,
                                                    ).options.value || '',
                                                )
                                            "
                                            @update:model-value="
                                                (v) =>
                                                    updateColumnOption(
                                                        tableName,
                                                        column.name,
                                                        'value',
                                                        String(v),
                                                    )
                                            "
                                            class="h-8 w-full text-xs"
                                            placeholder="Value"
                                        />
                                    </template>

                                    <!-- No options for keep and null -->
                                    <template v-else>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >-</span
                                        >
                                    </template>
                                </div>

                                <!-- Preview -->
                                <div class="col-span-2">
                                    <span
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{
                                            getPreviewValue(
                                                getColumnConfig(
                                                    tableName,
                                                    column.name,
                                                ),
                                                column,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation buttons within collapsible -->
                        <div
                            class="mt-6 flex items-center justify-between border-t pt-4"
                        >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="isFirstTable"
                                @click="goToPreviousTable"
                            >
                                <ArrowLeft class="mr-2 size-4" />
                                Previous
                            </Button>

                            <span class="text-sm text-muted-foreground">
                                Table {{ index + 1 }} of
                                {{ availableTables.length }}
                            </span>

                            <Button
                                v-if="!isLastTable"
                                variant="outline"
                                size="sm"
                                @click="goToNextTable"
                            >
                                Next
                                <ArrowRight class="ml-2 size-4" />
                            </Button>
                        </div>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>

        <Separator />

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between">
            <Button type="button" variant="outline" @click="emit('back')">
                <ArrowLeft class="mr-2 size-4" />
                Back
            </Button>

            <Button @click="emit('next', configPayload)">
                Next
                <ArrowRight class="ml-2 size-4" />
            </Button>
        </div>
    </div>
</template>
