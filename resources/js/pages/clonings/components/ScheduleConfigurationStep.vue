<script setup lang="ts">
import CloningController from '@/actions/App/Http/Controllers/CloningController';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import StepNumber from '@/components/StepNumber.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { CronInput } from '@/components/ui/cron-input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Form } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Calendar,
    Clock,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
const schedule = ref(props.initialSchedule ?? '0 0 * * *');

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
