<script setup lang="ts">
import CloningController from '@/actions/App/Http/Controllers/CloningController';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import StepNumber from '@/components/StepNumber.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Form } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Calendar, Clock, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    sourceConnectionId: string | number;
    targetConnectionId: string | number;
    cloningTitle: string;
    anonymizationConfig: string;
    cloningId?: number;
    mode: 'create' | 'edit';
    initialSchedule?: string | null;
    initialIsScheduled?: boolean;
}

interface Emits {
    (e: 'back'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Schedule state
const executeNow = ref(props.mode === 'create');
const isScheduled = ref(props.initialIsScheduled ?? false);
const schedule = ref(props.initialSchedule ?? '');

// Schedule preset options
const schedulePresets = [
    { value: 'custom', label: 'Custom cron expression' },
    { value: '0 0 * * *', label: 'Daily at midnight' },
    { value: '0 0 * * 0', label: 'Weekly on Sunday at midnight' },
    { value: '0 0 1 * *', label: 'Monthly on the 1st at midnight' },
    { value: '0 */6 * * *', label: 'Every 6 hours' },
    { value: '0 */12 * * *', label: 'Every 12 hours' },
    { value: '0 2 * * *', label: 'Daily at 2:00 AM' },
    { value: '0 3 * * 1-5', label: 'Weekdays at 3:00 AM' },
];

const selectedPreset = ref<string>(
    schedulePresets.find((p) => p.value === props.initialSchedule)?.value ?? 'custom'
);

// Custom cron state (when custom is selected)
const customCron = ref(
    schedulePresets.find((p) => p.value === props.initialSchedule) ? '' : (props.initialSchedule ?? '')
);

// Cron field helpers for custom cron
const cronParts = ref({
    minute: '0',
    hour: '0',
    dayOfMonth: '*',
    month: '*',
    dayOfWeek: '*',
});

// Watch preset changes
watch(selectedPreset, (newValue) => {
    if (newValue !== 'custom') {
        schedule.value = newValue;
        customCron.value = '';
    } else {
        schedule.value = customCron.value;
    }
});

// Watch custom cron changes
watch(customCron, (newValue) => {
    if (selectedPreset.value === 'custom') {
        schedule.value = newValue;
    }
});

// Generate human-readable schedule description
const scheduleDescription = computed(() => {
    if (!schedule.value) {
        return 'No schedule configured';
    }

    const parts = schedule.value.split(' ');
    if (parts.length !== 5) {
        return 'Invalid cron expression';
    }

    const [minute, hour, dayOfMonth, month, dayOfWeek] = parts;

    // Find matching preset
    const preset = schedulePresets.find((p) => p.value === schedule.value);
    if (preset && preset.value !== 'custom') {
        return preset.label;
    }

    // Generate description for common patterns
    if (dayOfMonth === '*' && month === '*') {
        if (dayOfWeek === '*') {
            if (hour === '*' && minute === '0') {
                return 'Every hour at the start';
            }
            if (hour.startsWith('*/')) {
                return `Every ${hour.slice(2)} hours`;
            }
            return `Daily at ${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`;
        }
        if (dayOfWeek === '0') {
            return `Weekly on Sunday at ${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`;
        }
        if (dayOfWeek === '1-5') {
            return `Weekdays at ${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`;
        }
    }

    return `Custom schedule: ${schedule.value}`;
});

// Form action based on mode
const formAction = computed(() => {
    if (props.mode === 'edit' && props.cloningId) {
        return CloningController.update(props.cloningId).url;
    }
    return CloningController.store().url;
});

const formMethod = computed(() => {
    return props.mode === 'edit' ? 'put' : 'post';
});

// Computed schedule value for form
const formSchedule = computed(() => {
    return isScheduled.value ? schedule.value : '';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Step header -->
        <div class="flex flex-col space-y-6">
            <div class="flex w-full gap-4">
                <StepNumber step="5" />
                <HeadingSmall
                    title="Configure execution"
                    description="Choose when to run this cloning - immediately and/or on a schedule."
                />
            </div>
        </div>

        <!-- Execution options -->
        <div class="space-y-6">
            <!-- Immediate execution (only for create mode) -->
            <Card v-if="mode === 'create'">
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Clock class="size-4" />
                        Immediate Execution
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-3">
                        <Checkbox
                            id="execute_now_checkbox"
                            :checked="executeNow"
                            @update:checked="executeNow = $event"
                        />
                        <Label
                            for="execute_now_checkbox"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Execute cloning immediately after saving
                        </Label>
                    </div>
                    <p class="mt-2 text-sm text-muted-foreground">
                        When enabled, the cloning will start as soon as you save.
                    </p>
                </CardContent>
            </Card>

            <!-- Schedule configuration -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Calendar class="size-4" />
                        Scheduled Execution
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center gap-3">
                        <Checkbox
                            id="is_scheduled_checkbox"
                            :checked="isScheduled"
                            @update:checked="isScheduled = $event"
                        />
                        <Label
                            for="is_scheduled_checkbox"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Enable scheduled execution
                        </Label>
                    </div>

                    <div v-if="isScheduled" class="space-y-4 pt-2">
                        <!-- Schedule preset selector -->
                        <div class="grid gap-2">
                            <Label for="schedule_preset">Schedule</Label>
                            <Select v-model="selectedPreset">
                                <SelectTrigger class="w-full max-w-md">
                                    <SelectValue placeholder="Select a schedule" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="preset in schedulePresets"
                                        :key="preset.value"
                                        :value="preset.value"
                                    >
                                        {{ preset.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Custom cron input (when custom is selected) -->
                        <div v-if="selectedPreset === 'custom'" class="grid gap-2">
                            <Label for="custom_cron">Cron expression</Label>
                            <Input
                                id="custom_cron"
                                v-model="customCron"
                                placeholder="* * * * * (minute hour day month weekday)"
                                class="max-w-md font-mono"
                            />
                            <p class="text-xs text-muted-foreground">
                                Format: minute (0-59) hour (0-23) day-of-month (1-31) month (1-12) day-of-week (0-6, 0=Sunday)
                            </p>
                        </div>

                        <!-- Schedule preview -->
                        <div
                            class="rounded-md border border-blue-500/20 bg-blue-500/5 p-3 dark:bg-blue-500/10"
                        >
                            <p class="text-sm font-medium text-blue-700 dark:text-blue-300">
                                {{ scheduleDescription }}
                            </p>
                            <p
                                v-if="schedule"
                                class="mt-1 font-mono text-xs text-blue-600/70 dark:text-blue-400/70"
                            >
                                {{ schedule }}
                            </p>
                        </div>

                        <!-- Common cron patterns help -->
                        <details class="text-sm">
                            <summary class="cursor-pointer font-medium text-muted-foreground hover:text-foreground">
                                Cron expression examples
                            </summary>
                            <div class="mt-2 space-y-1 rounded-md bg-muted/50 p-3 font-mono text-xs">
                                <p><code>0 0 * * *</code> - Daily at midnight</p>
                                <p><code>0 2 * * *</code> - Daily at 2:00 AM</p>
                                <p><code>0 */6 * * *</code> - Every 6 hours</p>
                                <p><code>0 0 * * 0</code> - Weekly on Sunday</p>
                                <p><code>0 0 1 * *</code> - Monthly on the 1st</p>
                                <p><code>0 3 * * 1-5</code> - Weekdays at 3:00 AM</p>
                                <p><code>30 4 * * 1,3,5</code> - Mon, Wed, Fri at 4:30 AM</p>
                            </div>
                        </details>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Separator />

        <!-- Form submission -->
        <Form
            :action="formAction"
            :method="formMethod"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <input type="hidden" name="title" :value="cloningTitle" />
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
            <input
                type="hidden"
                name="anonymization_config"
                :value="anonymizationConfig"
            />
            <input
                type="hidden"
                name="execute_now"
                :value="executeNow && mode === 'create' ? '1' : '0'"
            />
            <input
                type="hidden"
                name="is_scheduled"
                :value="isScheduled ? '1' : '0'"
            />
            <input type="hidden" name="schedule" :value="formSchedule" />

            <InputError :message="errors.title" />
            <InputError :message="errors.source_connection_id" />
            <InputError :message="errors.target_connection_id" />
            <InputError :message="errors.anonymization_config" />
            <InputError :message="errors.schedule" />

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

                <Button type="submit" :disabled="processing">
                    <Loader2
                        v-if="processing"
                        class="mr-2 size-4 animate-spin"
                    />
                    <template v-else>
                        {{
                            mode === 'create'
                                ? 'Save Cloning'
                                : 'Update Cloning'
                        }}
                        <ArrowRight class="ml-2 size-4" />
                    </template>
                </Button>
            </div>
        </Form>
    </div>
</template>
