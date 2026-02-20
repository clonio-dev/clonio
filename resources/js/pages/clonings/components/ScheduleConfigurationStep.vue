<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { CronInput } from '@/components/ui/cron-input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, ArrowRight, Calendar, Clock } from 'lucide-vue-next';
import { ref } from 'vue';

export interface ScheduleData {
    executeNow: boolean;
    isScheduled: boolean;
    schedule: string;
}

interface Props {
    mode: 'create' | 'edit';
    initialSchedule?: string | null;
    initialIsScheduled?: boolean;
    initialExecuteNow?: boolean;
}

interface Emits {
    (e: 'back'): void;
    (e: 'next', data: ScheduleData): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Schedule state
const executeNow = ref(props.initialExecuteNow ?? props.mode === 'create');
const isScheduled = ref(props.initialIsScheduled ?? false);
const schedule = ref(props.initialSchedule ?? '0 0 * * *');

function handleNext() {
    emit('next', {
        executeNow: executeNow.value && props.mode === 'create',
        isScheduled: isScheduled.value,
        schedule: isScheduled.value ? schedule.value : '',
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Step header -->
        <HeadingSmall
            title="Configure execution"
            description="Choose when to run this cloning - immediately and/or on a schedule."
        />

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
                            :model-value="executeNow"
                            @update:model-value="executeNow = !!$event"
                        />
                        <Label
                            for="execute_now_checkbox"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Execute cloning immediately after saving
                        </Label>
                    </div>
                    <p class="mt-2 text-sm text-muted-foreground">
                        When enabled, the cloning will start as soon as you
                        save.
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
                            :model-value="isScheduled"
                            @update:model-value="isScheduled = !!$event"
                        />
                        <Label
                            for="is_scheduled_checkbox"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Enable scheduled execution
                        </Label>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        When enabled, this cloning will automatically run on the
                        configured schedule.
                    </p>

                    <!-- Cron Input Component -->
                    <div v-if="isScheduled" class="pt-2">
                        <CronInput v-model="schedule" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <Separator />

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between">
            <Button type="button" variant="outline" @click="emit('back')">
                <ArrowLeft class="mr-2 size-4" />
                Back
            </Button>

            <Button @click="handleNext">
                Next
                <ArrowRight class="ml-2 size-4" />
            </Button>
        </div>
    </div>
</template>
