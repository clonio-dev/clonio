<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import { Calendar, Clock, Code, Sparkles } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    modelValue?: string | null;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Tab state
const activeTab = ref<'simple' | 'advanced'>('simple');

// Simple mode state
const frequency = ref<'hourly' | 'daily' | 'weekly' | 'monthly' | 'custom'>(
    'daily',
);
const hour = ref('0');
const minute = ref('0');
const dayOfWeek = ref('0'); // 0 = Sunday
const dayOfMonth = ref('1');

// Advanced mode state
const advancedCron = ref('');

// Days of week options
const daysOfWeek = [
    { value: '0', label: 'Sunday' },
    { value: '1', label: 'Monday' },
    { value: '2', label: 'Tuesday' },
    { value: '3', label: 'Wednesday' },
    { value: '4', label: 'Thursday' },
    { value: '5', label: 'Friday' },
    { value: '6', label: 'Saturday' },
];

// Days of month options (1-31)
const daysOfMonth = Array.from({ length: 31 }, (_, i) => ({
    value: String(i + 1),
    label: String(i + 1),
}));

// Hours options (0-23)
const hours = Array.from({ length: 24 }, (_, i) => ({
    value: String(i),
    label: i.toString().padStart(2, '0') + ':00',
}));

// Minutes options (0, 15, 30, 45)
const minutes = [
    { value: '0', label: ':00' },
    { value: '15', label: ':15' },
    { value: '30', label: ':30' },
    { value: '45', label: ':45' },
];

// Quick presets
const presets = [
    { label: 'Every hour', cron: '0 * * * *', frequency: 'hourly' as const },
    {
        label: 'Daily at midnight',
        cron: '0 0 * * *',
        frequency: 'daily' as const,
    },
    {
        label: 'Daily at 2 AM',
        cron: '0 2 * * *',
        frequency: 'daily' as const,
    },
    {
        label: 'Daily at 6 AM',
        cron: '0 6 * * *',
        frequency: 'daily' as const,
    },
    {
        label: 'Weekly on Sunday',
        cron: '0 0 * * 0',
        frequency: 'weekly' as const,
    },
    {
        label: 'Weekly on Monday',
        cron: '0 0 * * 1',
        frequency: 'weekly' as const,
    },
    {
        label: 'Monthly on 1st',
        cron: '0 0 1 * *',
        frequency: 'monthly' as const,
    },
    {
        label: 'Weekdays at 3 AM',
        cron: '0 3 * * 1-5',
        frequency: 'custom' as const,
    },
];

// Generate cron expression from simple form
const generatedCron = computed(() => {
    switch (frequency.value) {
        case 'hourly':
            return `${minute.value} * * * *`;
        case 'daily':
            return `${minute.value} ${hour.value} * * *`;
        case 'weekly':
            return `${minute.value} ${hour.value} * * ${dayOfWeek.value}`;
        case 'monthly':
            return `${minute.value} ${hour.value} ${dayOfMonth.value} * *`;
        case 'custom':
            return advancedCron.value;
        default:
            return '0 0 * * *';
    }
});

// Current cron value (from either mode)
const currentCron = computed(() => {
    if (activeTab.value === 'advanced') {
        return advancedCron.value;
    }
    return generatedCron.value;
});

// Parse incoming model value to set form state
function parseCronExpression(cron: string) {
    if (!cron) return;

    const parts = cron.split(' ');
    if (parts.length !== 5) {
        // Invalid, switch to advanced mode
        activeTab.value = 'advanced';
        advancedCron.value = cron;
        return;
    }

    const [min, hr, dom, , dow] = parts;

    // Try to detect pattern
    if (hr === '*' && dom === '*' && dow === '*') {
        // Hourly
        frequency.value = 'hourly';
        minute.value = min;
    } else if (dom === '*' && dow === '*') {
        // Daily
        frequency.value = 'daily';
        minute.value = min;
        hour.value = hr;
    } else if (dom === '*' && dow !== '*') {
        // Weekly
        frequency.value = 'weekly';
        minute.value = min;
        hour.value = hr;
        dayOfWeek.value = dow;
    } else if (dom !== '*' && dow === '*') {
        // Monthly
        frequency.value = 'monthly';
        minute.value = min;
        hour.value = hr;
        dayOfMonth.value = dom;
    } else {
        // Complex expression, switch to advanced
        frequency.value = 'custom';
        advancedCron.value = cron;
    }
}

// Initialize from model value
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue) {
            parseCronExpression(newValue);
            advancedCron.value = newValue;
        }
    },
    { immediate: true },
);

// Emit changes
watch(currentCron, (newValue) => {
    if (newValue && newValue !== props.modelValue) {
        emit('update:modelValue', newValue);
    }
});

// Apply preset
function applyPreset(preset: (typeof presets)[0]) {
    parseCronExpression(preset.cron);
    advancedCron.value = preset.cron;
    emit('update:modelValue', preset.cron);
}

// Human-readable description
const cronDescription = computed(() => {
    const cron = currentCron.value;
    if (!cron) return 'No schedule configured';

    const parts = cron.split(' ');
    if (parts.length !== 5) return 'Invalid cron expression';

    const [min, hr, dom, mon, dow] = parts;

    // Simple patterns
    if (hr === '*' && dom === '*' && mon === '*' && dow === '*') {
        if (min === '0') return 'Every hour at the start of the hour';
        return `Every hour at minute ${min}`;
    }

    if (dom === '*' && mon === '*' && dow === '*') {
        return `Daily at ${hr.padStart(2, '0')}:${min.padStart(2, '0')}`;
    }

    if (dom === '*' && mon === '*' && dow !== '*') {
        const dayName =
            daysOfWeek.find((d) => d.value === dow)?.label || `day ${dow}`;
        if (dow === '1-5') {
            return `Weekdays at ${hr.padStart(2, '0')}:${min.padStart(2, '0')}`;
        }
        return `Every ${dayName} at ${hr.padStart(2, '0')}:${min.padStart(2, '0')}`;
    }

    if (dom !== '*' && mon === '*' && dow === '*') {
        const suffix = getOrdinalSuffix(parseInt(dom));
        return `Monthly on the ${dom}${suffix} at ${hr.padStart(2, '0')}:${min.padStart(2, '0')}`;
    }

    return `Custom: ${cron}`;
});

function getOrdinalSuffix(n: number): string {
    const s = ['th', 'st', 'nd', 'rd'];
    const v = n % 100;
    return s[(v - 20) % 10] || s[v] || s[0];
}

// Validate cron expression
const isValidCron = computed(() => {
    const cron = currentCron.value;
    if (!cron) return false;

    const parts = cron.split(' ');
    if (parts.length !== 5) return false;

    // Basic validation for each field
    const patterns = [
        /^(\*|[0-5]?\d)(\/\d+)?$/, // minute (0-59)
        /^(\*|[01]?\d|2[0-3])(\/\d+)?$/, // hour (0-23)
        /^(\*|[1-9]|[12]\d|3[01])(\/\d+)?$/, // day of month (1-31)
        /^(\*|[1-9]|1[0-2])(\/\d+)?$/, // month (1-12)
        /^(\*|[0-6](-[0-6])?(,[0-6])*)(\/\d+)?$/, // day of week (0-6)
    ];

    return parts.every((part, i) => patterns[i].test(part));
});
</script>

<template>
    <div
        :class="
            cn(
                'rounded-lg border bg-card p-4',
                disabled && 'pointer-events-none opacity-60',
            )
        "
    >
        <Tabs v-model="activeTab" class="w-full">
            <TabsList class="mb-4 grid w-full grid-cols-2">
                <TabsTrigger value="simple" class="gap-2">
                    <Sparkles class="size-4" />
                    Simple
                </TabsTrigger>
                <TabsTrigger value="advanced" class="gap-2">
                    <Code class="size-4" />
                    Advanced
                </TabsTrigger>
            </TabsList>

            <!-- Simple Mode -->
            <TabsContent value="simple" class="space-y-4">
                <!-- Quick Presets -->
                <div class="space-y-2">
                    <Label class="text-xs text-muted-foreground"
                        >Quick presets</Label
                    >
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="preset in presets"
                            :key="preset.cron"
                            variant="outline"
                            size="sm"
                            :class="
                                cn(
                                    'h-7 text-xs',
                                    currentCron === preset.cron &&
                                        'border-primary bg-primary/10',
                                )
                            "
                            @click="applyPreset(preset)"
                        >
                            {{ preset.label }}
                        </Button>
                    </div>
                </div>

                <!-- Frequency Selection -->
                <div class="space-y-2">
                    <Label>Frequency</Label>
                    <Select v-model="frequency">
                        <SelectTrigger>
                            <SelectValue placeholder="Select frequency" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="hourly">
                                <div class="flex items-center gap-2">
                                    <Clock class="size-4" />
                                    Hourly
                                </div>
                            </SelectItem>
                            <SelectItem value="daily">
                                <div class="flex items-center gap-2">
                                    <Calendar class="size-4" />
                                    Daily
                                </div>
                            </SelectItem>
                            <SelectItem value="weekly">
                                <div class="flex items-center gap-2">
                                    <Calendar class="size-4" />
                                    Weekly
                                </div>
                            </SelectItem>
                            <SelectItem value="monthly">
                                <div class="flex items-center gap-2">
                                    <Calendar class="size-4" />
                                    Monthly
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Time Selection (for daily, weekly, monthly) -->
                <div
                    v-if="frequency !== 'hourly'"
                    class="grid grid-cols-2 gap-4"
                >
                    <div class="space-y-2">
                        <Label>Hour</Label>
                        <Select v-model="hour">
                            <SelectTrigger>
                                <SelectValue placeholder="Hour" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="h in hours"
                                    :key="h.value"
                                    :value="h.value"
                                >
                                    {{ h.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <Label>Minute</Label>
                        <Select v-model="minute">
                            <SelectTrigger>
                                <SelectValue placeholder="Minute" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="m in minutes"
                                    :key="m.value"
                                    :value="m.value"
                                >
                                    {{ m.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Minute only for hourly -->
                <div v-if="frequency === 'hourly'" class="space-y-2">
                    <Label>At minute</Label>
                    <Select v-model="minute">
                        <SelectTrigger class="w-32">
                            <SelectValue placeholder="Minute" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="m in minutes"
                                :key="m.value"
                                :value="m.value"
                            >
                                {{ m.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Day of Week (for weekly) -->
                <div v-if="frequency === 'weekly'" class="space-y-2">
                    <Label>Day of week</Label>
                    <Select v-model="dayOfWeek">
                        <SelectTrigger>
                            <SelectValue placeholder="Select day" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="day in daysOfWeek"
                                :key="day.value"
                                :value="day.value"
                            >
                                {{ day.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Day of Month (for monthly) -->
                <div v-if="frequency === 'monthly'" class="space-y-2">
                    <Label>Day of month</Label>
                    <Select v-model="dayOfMonth">
                        <SelectTrigger>
                            <SelectValue placeholder="Select day" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="day in daysOfMonth"
                                :key="day.value"
                                :value="day.value"
                            >
                                {{ day.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </TabsContent>

            <!-- Advanced Mode -->
            <TabsContent value="advanced" class="space-y-4">
                <div class="space-y-2">
                    <Label>Cron expression</Label>
                    <Input
                        v-model="advancedCron"
                        placeholder="* * * * *"
                        class="font-mono"
                        :class="
                            cn(
                                advancedCron &&
                                    !isValidCron &&
                                    'border-destructive',
                            )
                        "
                    />
                    <p class="text-xs text-muted-foreground">
                        Format: minute (0-59) hour (0-23) day (1-31) month (1-12)
                        weekday (0-6)
                    </p>
                </div>

                <!-- Cron field reference -->
                <div
                    class="rounded-md border bg-muted/30 p-3 font-mono text-xs"
                >
                    <div class="mb-2 grid grid-cols-5 gap-2 text-center">
                        <span class="text-muted-foreground">MIN</span>
                        <span class="text-muted-foreground">HOUR</span>
                        <span class="text-muted-foreground">DAY</span>
                        <span class="text-muted-foreground">MON</span>
                        <span class="text-muted-foreground">DOW</span>
                    </div>
                    <div class="grid grid-cols-5 gap-2 text-center">
                        <span
                            v-for="(part, i) in (advancedCron || '* * * * *')
                                .split(' ')
                                .slice(0, 5)"
                            :key="i"
                            class="rounded bg-background px-2 py-1"
                        >
                            {{ part || '*' }}
                        </span>
                    </div>
                </div>

                <!-- Common patterns reference -->
                <details class="text-sm">
                    <summary
                        class="cursor-pointer font-medium text-muted-foreground hover:text-foreground"
                    >
                        Common cron patterns
                    </summary>
                    <div
                        class="mt-2 space-y-1 rounded-md bg-muted/50 p-3 font-mono text-xs"
                    >
                        <p>
                            <code class="rounded bg-background px-1"
                                >0 0 * * *</code
                            >
                            - Daily at midnight
                        </p>
                        <p>
                            <code class="rounded bg-background px-1"
                                >0 2 * * *</code
                            >
                            - Daily at 2:00 AM
                        </p>
                        <p>
                            <code class="rounded bg-background px-1"
                                >0 */6 * * *</code
                            >
                            - Every 6 hours
                        </p>
                        <p>
                            <code class="rounded bg-background px-1"
                                >0 0 * * 0</code
                            >
                            - Weekly on Sunday
                        </p>
                        <p>
                            <code class="rounded bg-background px-1"
                                >0 0 1 * *</code
                            >
                            - Monthly on the 1st
                        </p>
                        <p>
                            <code class="rounded bg-background px-1"
                                >0 3 * * 1-5</code
                            >
                            - Weekdays at 3:00 AM
                        </p>
                        <p>
                            <code class="rounded bg-background px-1"
                                >30 4 * * 1,3,5</code
                            >
                            - Mon/Wed/Fri at 4:30 AM
                        </p>
                        <p>
                            <code class="rounded bg-background px-1"
                                >*/15 * * * *</code
                            >
                            - Every 15 minutes
                        </p>
                    </div>
                </details>
            </TabsContent>
        </Tabs>

        <!-- Schedule Preview -->
        <div
            class="mt-4 rounded-md border p-3"
            :class="
                cn(
                    isValidCron
                        ? 'border-emerald-500/30 bg-emerald-500/5'
                        : 'border-amber-500/30 bg-amber-500/5',
                )
            "
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p
                        class="text-sm font-medium"
                        :class="
                            cn(
                                isValidCron
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : 'text-amber-700 dark:text-amber-300',
                            )
                        "
                    >
                        {{ cronDescription }}
                    </p>
                    <p
                        v-if="currentCron"
                        class="mt-1 font-mono text-xs"
                        :class="
                            cn(
                                isValidCron
                                    ? 'text-emerald-600/70 dark:text-emerald-400/70'
                                    : 'text-amber-600/70 dark:text-amber-400/70',
                            )
                        "
                    >
                        {{ currentCron }}
                    </p>
                </div>
                <div
                    v-if="!isValidCron && currentCron"
                    class="shrink-0 text-xs text-amber-600 dark:text-amber-400"
                >
                    Invalid expression
                </div>
            </div>
        </div>
    </div>
</template>
