<script setup lang="ts">
import CloningController from '@/actions/App/Http/Controllers/CloningController';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Form } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Bell,
    BellOff,
    Copy,
    Globe,
    Loader2,
    Webhook,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import type { ScheduleData } from './ScheduleConfigurationStep.vue';

interface WebhookConfig {
    enabled: boolean;
    url: string;
    method: 'POST' | 'PUT' | 'PATCH';
    headers: Record<string, string>;
    secret: string;
}

interface TriggerConfig {
    webhookOnSuccess: WebhookConfig;
    webhookOnFailure: WebhookConfig;
    apiTrigger: { enabled: boolean };
}

interface Props {
    sourceConnectionId: string | number;
    targetConnectionId: string | number;
    cloningTitle: string;
    anonymizationConfig: string;
    scheduleData: ScheduleData;
    cloningId?: number;
    mode: 'create' | 'edit';
    initialTriggerConfig?: TriggerConfig | null;
    apiTriggerUrl?: string | null;
}

interface Emits {
    (e: 'back'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

function defaultWebhookConfig(): WebhookConfig {
    return {
        enabled: false,
        url: '',
        method: 'POST',
        headers: {},
        secret: '',
    };
}

// Webhook on success
const webhookOnSuccess = ref<WebhookConfig>(
    props.initialTriggerConfig?.webhookOnSuccess
        ? { ...props.initialTriggerConfig.webhookOnSuccess }
        : defaultWebhookConfig(),
);

// Webhook on failure
const webhookOnFailure = ref<WebhookConfig>(
    props.initialTriggerConfig?.webhookOnFailure
        ? { ...props.initialTriggerConfig.webhookOnFailure }
        : defaultWebhookConfig(),
);

// API trigger
const apiTriggerEnabled = ref(
    props.initialTriggerConfig?.apiTrigger?.enabled ?? false,
);

// Compute trigger config JSON
const triggerConfigPayload = computed(() => {
    const config: TriggerConfig = {
        webhookOnSuccess: webhookOnSuccess.value,
        webhookOnFailure: webhookOnFailure.value,
        apiTrigger: { enabled: apiTriggerEnabled.value },
    };

    // Only include trigger config if anything is enabled
    const hasEnabledTrigger =
        config.webhookOnSuccess.enabled ||
        config.webhookOnFailure.enabled ||
        config.apiTrigger.enabled;

    return hasEnabledTrigger ? JSON.stringify(config) : '';
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

// Reset webhook values when unchecked
watch(
    () => webhookOnSuccess.value.enabled,
    (enabled) => {
        if (!enabled) {
            webhookOnSuccess.value.url = '';
            webhookOnSuccess.value.method = 'POST';
            webhookOnSuccess.value.headers = {};
            webhookOnSuccess.value.secret = '';
        }
    },
);

watch(
    () => webhookOnFailure.value.enabled,
    (enabled) => {
        if (!enabled) {
            webhookOnFailure.value.url = '';
            webhookOnFailure.value.method = 'POST';
            webhookOnFailure.value.headers = {};
            webhookOnFailure.value.secret = '';
        }
    },
);

// Copy API trigger URL to clipboard
const copied = ref(false);
function copyApiUrl() {
    if (props.apiTriggerUrl) {
        navigator.clipboard.writeText(props.apiTriggerUrl);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Step header -->
        <HeadingSmall
            title="Configure triggers"
            description="Set up webhooks to notify external services and enable API-based triggering."
        />

        <div class="space-y-6">
            <!-- Webhook on Success -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Bell class="size-4" />
                        Webhook on Success
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center gap-3">
                        <Checkbox
                            id="webhook_success_checkbox"
                            :model-value="webhookOnSuccess.enabled"
                            @update:model-value="webhookOnSuccess.enabled = !!$event"
                        />
                        <Label
                            for="webhook_success_checkbox"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Call webhook after a successful cloning run
                        </Label>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        When enabled, an HTTP request will be sent to the
                        configured URL after every successful run.
                    </p>

                    <div v-if="webhookOnSuccess.enabled" class="space-y-4 pt-2">
                        <div class="grid gap-2">
                            <Label for="success_url">URL</Label>
                            <Input
                                id="success_url"
                                v-model="webhookOnSuccess.url"
                                placeholder="https://example.com/webhook"
                                type="url"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="success_method">HTTP Method</Label>
                                <Select
                                    :model-value="webhookOnSuccess.method"
                                    @update:model-value="
                                        webhookOnSuccess.method =
                                            String($event ?? 'POST')
                                    "
                                >
                                    <SelectTrigger id="success_method">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="POST"
                                            >POST</SelectItem
                                        >
                                        <SelectItem value="PUT">PUT</SelectItem>
                                        <SelectItem value="PATCH"
                                            >PATCH</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="success_secret"
                                    >Signing Secret</Label
                                >
                                <Input
                                    id="success_secret"
                                    v-model="webhookOnSuccess.secret"
                                    placeholder="Optional HMAC secret"
                                    type="password"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Webhook on Failure -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <BellOff class="size-4" />
                        Webhook on Failure
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center gap-3">
                        <Checkbox
                            id="webhook_failure_checkbox"
                            :model-value="webhookOnFailure.enabled"
                            @update:model-value="webhookOnFailure.enabled = !!$event"
                        />
                        <Label
                            for="webhook_failure_checkbox"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Call webhook after a failed cloning run
                        </Label>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        When enabled, an HTTP request will be sent to the
                        configured URL after every failed run.
                    </p>

                    <div v-if="webhookOnFailure.enabled" class="space-y-4 pt-2">
                        <div class="grid gap-2">
                            <Label for="failure_url">URL</Label>
                            <Input
                                id="failure_url"
                                v-model="webhookOnFailure.url"
                                placeholder="https://example.com/webhook"
                                type="url"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="failure_method">HTTP Method</Label>
                                <Select
                                    :model-value="webhookOnFailure.method"
                                    @update:model-value="
                                        webhookOnFailure.method =
                                            String($event ?? 'POST')
                                    "
                                >
                                    <SelectTrigger id="failure_method">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="POST"
                                            >POST</SelectItem
                                        >
                                        <SelectItem value="PUT">PUT</SelectItem>
                                        <SelectItem value="PATCH"
                                            >PATCH</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="failure_secret"
                                    >Signing Secret</Label
                                >
                                <Input
                                    id="failure_secret"
                                    v-model="webhookOnFailure.secret"
                                    placeholder="Optional HMAC secret"
                                    type="password"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- API Trigger -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Globe class="size-4" />
                        Incoming API Trigger
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center gap-3">
                        <Checkbox
                            id="api_trigger_checkbox"
                            :model-value="apiTriggerEnabled"
                            @update:model-value="apiTriggerEnabled = !!$event"
                        />
                        <Label
                            for="api_trigger_checkbox"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Allow triggering this cloning via API
                        </Label>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        When enabled, a unique URL is generated that can be
                        called to trigger a cloning run externally (e.g., from
                        CI/CD pipelines).
                    </p>

                    <div v-if="apiTriggerEnabled" class="pt-2">
                        <template v-if="apiTriggerUrl">
                            <div class="grid gap-2">
                                <Label>Trigger URL</Label>
                                <div
                                    class="flex items-center gap-2 rounded-md border bg-muted/30 p-3 dark:bg-muted/10"
                                >
                                    <Webhook
                                        class="size-4 shrink-0 text-muted-foreground"
                                    />
                                    <code
                                        class="flex-1 text-xs break-all text-foreground"
                                    >
                                        POST {{ apiTriggerUrl }}
                                    </code>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="shrink-0"
                                        @click="copyApiUrl"
                                    >
                                        <Copy class="size-4" />
                                        {{ copied ? 'Copied!' : 'Copy' }}
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Send a POST request to this URL to trigger a
                                    cloning run. Keep this URL secret.
                                </p>

                                <Tabs default-value="curl" class="mt-3">
                                    <TabsList>
                                        <TabsTrigger value="curl">
                                            curl
                                        </TabsTrigger>
                                        <TabsTrigger value="wget">
                                            wget
                                        </TabsTrigger>
                                    </TabsList>
                                    <TabsContent value="curl">
                                        <pre
                                            class="overflow-x-auto rounded-md bg-zinc-900 p-3 text-xs text-zinc-100 dark:bg-zinc-950"
                                        >
curl -X POST {{ apiTriggerUrl }}</pre
                                        >
                                    </TabsContent>
                                    <TabsContent value="wget">
                                        <pre
                                            class="overflow-x-auto rounded-md bg-zinc-900 p-3 text-xs text-zinc-100 dark:bg-zinc-950"
                                        >
wget --method=POST {{ apiTriggerUrl }}</pre
                                        >
                                    </TabsContent>
                                </Tabs>
                            </div>
                        </template>
                        <template v-else>
                            <div
                                class="rounded-md border border-dashed bg-muted/20 p-4 text-center dark:bg-muted/5"
                            >
                                <p class="text-sm text-muted-foreground">
                                    The trigger URL will be generated after
                                    saving.
                                </p>
                            </div>
                        </template>
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
                :value="scheduleData.executeNow ? '1' : '0'"
            />
            <input
                type="hidden"
                name="is_scheduled"
                :value="scheduleData.isScheduled ? '1' : '0'"
            />
            <input
                type="hidden"
                name="schedule"
                :value="scheduleData.schedule"
            />
            <input
                type="hidden"
                name="trigger_config"
                :value="triggerConfigPayload"
            />

            <InputError :message="errors.title" />
            <InputError :message="errors.source_connection_id" />
            <InputError :message="errors.target_connection_id" />
            <InputError :message="errors.anonymization_config" />
            <InputError :message="errors.schedule" />
            <InputError :message="errors.trigger_config" />

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
