<script setup lang="ts">
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';
import {
    MariadbIcon,
    MysqlIcon,
    PostgresqlIcon,
    SqlserverIcon,
} from '@/components/icons/databases';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Connection } from '@/pages/connections/types';
import { Form } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Cable,
    Database,
    Key,
    Loader2,
    Server,
    Tag,
    User,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import axios from 'axios';

interface Props {
    open: boolean;
    submitLabel?: string;
    emitOnCreate?: boolean;
}

interface Emits {
    (e: 'close'): void;
    (e: 'created', connection: Connection): void;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    submitLabel: 'Create Connection',
    emitOnCreate: false,
});
const emit = defineEmits<Emits>();

const isProduction = ref(false);

// Form state for AJAX submission
const formData = ref({
    name: '',
    type: 'mysql',
    host: '',
    port: '',
    database: '',
    username: '',
    password: '',
    is_production_stage: false,
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const recentlySuccessful = ref(false);

function handleOpenChange(open: boolean) {
    if (!open) {
        emit('close');
    }
}

function handleSubmitComplete() {
    emit('close');
}

function resetForm() {
    formData.value = {
        name: '',
        type: 'mysql',
        host: '',
        port: '',
        database: '',
        username: '',
        password: '',
        is_production_stage: false,
    };
    errors.value = {};
    isProduction.value = false;
}

async function handleAjaxSubmit(event: Event) {
    event.preventDefault();

    processing.value = true;
    errors.value = {};

    try {
        const response = await axios.post(
            DatabaseConnectionController.store().url,
            {
                ...formData.value,
                is_production_stage: isProduction.value,
            },
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        recentlySuccessful.value = true;
        setTimeout(() => {
            recentlySuccessful.value = false;
        }, 2000);

        // Emit the created connection
        emit('created', response.data.connection);
        emit('close');
        resetForm();
    } catch (error: any) {
        if (error.response?.status === 422) {
            const validationErrors = error.response.data.errors || {};
            // Flatten the errors object (Laravel returns arrays)
            for (const key in validationErrors) {
                errors.value[key] = Array.isArray(validationErrors[key])
                    ? validationErrors[key][0]
                    : validationErrors[key];
            }
        } else {
            errors.value.name = 'An unexpected error occurred. Please try again.';
        }
    } finally {
        processing.value = false;
    }
}

watch(
    () => props.open,
    (newVal) => {
        if (!newVal) {
            resetForm();
        }
    },
);

watch(
    () => isProduction.value,
    (newVal) => {
        formData.value.is_production_stage = newVal;
    },
);
</script>

<template>
    <Sheet :open="props.open" @update:open="handleOpenChange">
        <SheetContent class="flex flex-col overflow-hidden sm:max-w-lg">
            <SheetHeader class="space-y-3 pb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500/20 to-teal-500/20 ring-1 ring-emerald-500/30 dark:from-emerald-500/10 dark:to-teal-500/10"
                    >
                        <Database
                            class="size-5 text-emerald-600 dark:text-emerald-400"
                        />
                    </div>
                    <div>
                        <SheetTitle class="text-lg">New Connection</SheetTitle>
                        <SheetDescription class="text-xs">
                            Configure a database connection for data transfers
                        </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <Separator class="-mt-4" />

            <!-- AJAX Form for on-the-fly creation -->
            <form
                v-if="props.emitOnCreate"
                class="flex flex-1 flex-col gap-5 overflow-y-auto p-4 pt-0"
                autocomplete="off"
                @submit="handleAjaxSubmit"
            >
                <!-- Connection Identity Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Tag class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium text-foreground"
                            >Identity</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label for="name" class="text-xs text-muted-foreground"
                            >Connection Name</Label
                        >
                        <Input
                            id="name"
                            v-model="formData.name"
                            placeholder="e.g., Production MySQL, Staging DB"
                            class="h-9"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.name,
                            }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label
                                for="type"
                                class="text-xs text-muted-foreground"
                                >Database Type</Label
                            >
                            <Select v-model="formData.type" default-value="mysql">
                                <SelectTrigger id="type" class="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mysql">
                                        <div class="flex items-center gap-2">
                                            <MysqlIcon />
                                            MySQL
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="mariadb">
                                        <div class="flex items-center gap-2">
                                            <MariadbIcon />
                                            MariaDB
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="pgsql">
                                        <div class="flex items-center gap-2">
                                            <PostgresqlIcon />
                                            PostgreSQL
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="sqlserver">
                                        <div class="flex items-center gap-2">
                                            <SqlserverIcon />
                                            SQL Server
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs text-muted-foreground"
                                >Environment</Label
                            >
                            <div
                                class="flex h-9 items-center gap-2 rounded-md border border-input bg-background px-3"
                                :class="{
                                    'border-amber-500/50 bg-amber-500/5':
                                        isProduction,
                                }"
                            >
                                <Checkbox
                                    id="is_production_stage_ajax"
                                    :checked="isProduction"
                                    @update:checked="isProduction = $event"
                                    class="data-[state=checked]:border-amber-500 data-[state=checked]:bg-amber-500"
                                />
                                <Label
                                    for="is_production_stage_ajax"
                                    class="flex cursor-pointer items-center gap-1.5 text-xs font-normal"
                                >
                                    <AlertTriangle
                                        v-if="isProduction"
                                        class="size-3 text-amber-500"
                                    />
                                    Production
                                </Label>
                            </div>
                            <InputError :message="errors.is_production_stage" />
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Server Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Server class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium text-foreground"
                            >Server</span
                        >
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2 space-y-1.5">
                            <Label
                                for="host"
                                class="text-xs text-muted-foreground"
                                >Host</Label
                            >
                            <Input
                                id="host"
                                v-model="formData.host"
                                placeholder="localhost or 127.0.0.1"
                                class="h-9 font-mono text-sm"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive/30':
                                        errors.host,
                                }"
                            />
                            <InputError :message="errors.host" />
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="port"
                                class="text-xs text-muted-foreground"
                                >Port</Label
                            >
                            <Input
                                id="port"
                                v-model="formData.port"
                                type="number"
                                placeholder="3306"
                                class="h-9 font-mono text-sm"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive/30':
                                        errors.port,
                                }"
                            />
                            <InputError :message="errors.port" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="database"
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <Cable class="size-3" />
                            Database Name
                        </Label>
                        <Input
                            id="database"
                            v-model="formData.database"
                            placeholder="my_database"
                            class="h-9 font-mono text-sm"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.database,
                            }"
                        />
                        <InputError :message="errors.database" />
                    </div>
                </div>

                <Separator />

                <!-- Authentication Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Key class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium text-foreground"
                            >Authentication</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="username"
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <User class="size-3" />
                            Username
                        </Label>
                        <Input
                            id="username"
                            v-model="formData.username"
                            placeholder="db_user"
                            autocomplete="off"
                            class="h-9 font-mono text-sm"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.username,
                            }"
                        />
                        <InputError :message="errors.username" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="password"
                            class="text-xs text-muted-foreground"
                            >Password</Label
                        >
                        <Input
                            id="password"
                            v-model="formData.password"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                            class="h-9"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.password,
                            }"
                        />
                        <InputError :message="errors.password" />
                    </div>
                </div>

                <!-- Production warning -->
                <Alert
                    v-if="isProduction"
                    class="border-amber-500/30 bg-amber-500/5"
                >
                    <AlertTriangle class="size-4 text-amber-500" />
                    <AlertDescription
                        class="text-xs text-amber-700 dark:text-amber-400"
                    >
                        This connection is marked as production. Extra
                        confirmation will be required for destructive
                        operations.
                    </AlertDescription>
                </Alert>

                <!-- Submit button -->
                <div class="mt-auto pt-4">
                    <Button
                        type="submit"
                        :disabled="processing || recentlySuccessful"
                        class="w-full gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/20 transition-all hover:from-emerald-500 hover:to-teal-500 hover:shadow-lg hover:shadow-emerald-500/30 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="processing"
                            class="size-4 animate-spin"
                        />
                        <Database v-else class="size-4" />
                        {{ processing ? 'Creating...' : props.submitLabel }}
                    </Button>
                </div>
            </form>

            <!-- Inertia Form for standard flow -->
            <Form
                v-else
                v-bind="DatabaseConnectionController.store()"
                class="flex flex-1 flex-col gap-5 overflow-y-auto p-4 pt-0"
                v-slot="{ errors, processing, recentlySuccessful }"
                :reset-on-error="['username', 'password']"
                :onSuccess="handleSubmitComplete"
                autocomplete="off"
            >
                <!-- Connection Identity Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Tag class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium text-foreground"
                            >Identity</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label for="name" class="text-xs text-muted-foreground"
                            >Connection Name</Label
                        >
                        <Input
                            id="name"
                            name="name"
                            placeholder="e.g., Production MySQL, Staging DB"
                            class="h-9"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.name,
                            }"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label
                                for="type"
                                class="text-xs text-muted-foreground"
                                >Database Type</Label
                            >
                            <Select name="type" default-value="mysql">
                                <SelectTrigger id="type" class="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mysql">
                                        <div class="flex items-center gap-2">
                                            <MysqlIcon />
                                            MySQL
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="mariadb">
                                        <div class="flex items-center gap-2">
                                            <MariadbIcon />
                                            MariaDB
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="pgsql">
                                        <div class="flex items-center gap-2">
                                            <PostgresqlIcon />
                                            PostgreSQL
                                        </div>
                                    </SelectItem>
                                    <SelectItem value="sqlserver">
                                        <div class="flex items-center gap-2">
                                            <SqlserverIcon />
                                            SQL Server
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs text-muted-foreground"
                                >Environment</Label
                            >
                            <div
                                class="flex h-9 items-center gap-2 rounded-md border border-input bg-background px-3"
                                :class="{
                                    'border-amber-500/50 bg-amber-500/5':
                                        isProduction,
                                }"
                            >
                                <Checkbox
                                    id="is_production_stage"
                                    name="is_production_stage"
                                    :checked="isProduction"
                                    @update:checked="isProduction = $event"
                                    class="data-[state=checked]:border-amber-500 data-[state=checked]:bg-amber-500"
                                />
                                <Label
                                    for="is_production_stage"
                                    class="flex cursor-pointer items-center gap-1.5 text-xs font-normal"
                                >
                                    <AlertTriangle
                                        v-if="isProduction"
                                        class="size-3 text-amber-500"
                                    />
                                    Production
                                </Label>
                            </div>
                            <InputError :message="errors.is_production_stage" />
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Server Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Server class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium text-foreground"
                            >Server</span
                        >
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2 space-y-1.5">
                            <Label
                                for="host"
                                class="text-xs text-muted-foreground"
                                >Host</Label
                            >
                            <Input
                                id="host"
                                name="host"
                                placeholder="localhost or 127.0.0.1"
                                class="h-9 font-mono text-sm"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive/30':
                                        errors.host,
                                }"
                            />
                            <InputError :message="errors.host" />
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="port"
                                class="text-xs text-muted-foreground"
                                >Port</Label
                            >
                            <Input
                                id="port"
                                name="port"
                                type="number"
                                placeholder="3306"
                                class="h-9 font-mono text-sm"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive/30':
                                        errors.port,
                                }"
                            />
                            <InputError :message="errors.port" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="database"
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <Cable class="size-3" />
                            Database Name
                        </Label>
                        <Input
                            id="database"
                            name="database"
                            placeholder="my_database"
                            class="h-9 font-mono text-sm"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.database,
                            }"
                        />
                        <InputError :message="errors.database" />
                    </div>
                </div>

                <Separator />

                <!-- Authentication Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <Key class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium text-foreground"
                            >Authentication</span
                        >
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="username"
                            class="flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <User class="size-3" />
                            Username
                        </Label>
                        <Input
                            id="username"
                            name="username"
                            placeholder="db_user"
                            autocomplete="off"
                            class="h-9 font-mono text-sm"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.username,
                            }"
                        />
                        <InputError :message="errors.username" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="password"
                            class="text-xs text-muted-foreground"
                            >Password</Label
                        >
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                            class="h-9"
                            :class="{
                                'border-destructive focus-visible:ring-destructive/30':
                                    errors.password,
                            }"
                        />
                        <InputError :message="errors.password" />
                    </div>
                </div>

                <!-- Production warning -->
                <Alert
                    v-if="isProduction"
                    class="border-amber-500/30 bg-amber-500/5"
                >
                    <AlertTriangle class="size-4 text-amber-500" />
                    <AlertDescription
                        class="text-xs text-amber-700 dark:text-amber-400"
                    >
                        This connection is marked as production. Extra
                        confirmation will be required for destructive
                        operations.
                    </AlertDescription>
                </Alert>

                <!-- Submit button -->
                <div class="mt-auto pt-4">
                    <Button
                        type="submit"
                        :disabled="processing || recentlySuccessful"
                        class="w-full gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/20 transition-all hover:from-emerald-500 hover:to-teal-500 hover:shadow-lg hover:shadow-emerald-500/30 disabled:opacity-50"
                    >
                        <Loader2
                            v-if="processing"
                            class="size-4 animate-spin"
                        />
                        <Database v-else class="size-4" />
                        {{ processing ? 'Creating...' : props.submitLabel }}
                    </Button>
                </div>
            </Form>
        </SheetContent>
    </Sheet>
</template>
