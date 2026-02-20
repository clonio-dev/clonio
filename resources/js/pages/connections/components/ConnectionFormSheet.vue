<script setup lang="ts">
import DatabaseConnectionController from '@/actions/App/Http/Controllers/DatabaseConnectionController';
import {
    MariadbIcon,
    MysqlIcon,
    PostgresqlIcon,
    SqlserverIcon,
} from '@/components/icons/databases';
import InfoComponent from '@/components/InfoComponent.vue';
import InputError from '@/components/InputError.vue';
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
import type { Connection } from '@/pages/connections/types';
import { Form, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Database,
    Loader2,
    ShieldCheckIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    connection?: Connection | null;
    submitLabel?: string;
    defaultProduction?: boolean;
}

interface Emits {
    (e: 'close'): void;
    (
        e: 'created',
        connection: {
            id: number;
            name: string;
            type: string;
            is_production_stage: boolean;
        },
    ): void;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    connection: null,
    submitLabel: undefined,
    defaultProduction: false,
});
const emit = defineEmits<Emits>();

const isEditing = computed(() => !!props.connection);

const resolvedSubmitLabel = computed(() => {
    if (props.submitLabel) {
        return props.submitLabel;
    }
    return isEditing.value ? 'Update Connection' : 'Create Connection';
});

const formBinding = computed(() => {
    if (isEditing.value && props.connection) {
        return DatabaseConnectionController.update.form(props.connection.id);
    }
    return DatabaseConnectionController.store.form();
});

const isProduction = ref(false);

function handleOpenChange(open: boolean) {
    if (!open) {
        emit('close');
    }
}

function handleSubmitComplete() {
    if (!isEditing.value) {
        const flash = usePage().props.flash as {
            created_connection?: {
                id: number;
                name: string;
                type: string;
                is_production_stage: boolean;
            };
        };
        if (flash?.created_connection) {
            emit('created', flash.created_connection);
        }
    }
    emit('close');
}

watch(
    [() => props.open, () => props.connection],
    ([newOpen, newConnection]) => {
        if (newOpen) {
            isProduction.value = newConnection
                ? Boolean(newConnection.is_production_stage)
                : props.defaultProduction;
        } else {
            isProduction.value = false;
        }
    },
);
</script>

<template>
    <Sheet :open="props.open" @update:open="handleOpenChange">
        <SheetContent class="flex flex-col overflow-hidden sm:max-w-lg">
            <SheetHeader class="space-y-3 pb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-green-400/10 ring-1 ring-green-500/30 dark:bg-green-500/10"
                    >
                        <Database
                            class="size-5 text-green-600 dark:text-green-400"
                        />
                    </div>
                    <div>
                        <SheetTitle class="text-lg">
                            {{
                                isEditing ? 'Edit Connection' : 'New Connection'
                            }}
                        </SheetTitle>
                        <SheetDescription class="text-xs">
                            {{
                                isEditing
                                    ? 'Update the database connection settings'
                                    : 'Configure a database connection for data transfers'
                            }}
                        </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <Separator class="-mt-4" />

            <Form
                v-bind="formBinding"
                class="flex flex-1 flex-col gap-5 overflow-y-auto p-4 pt-0"
                v-slot="{ errors, processing, recentlySuccessful }"
                :reset-on-error="['username', 'password']"
                :onSuccess="handleSubmitComplete"
                autocomplete="off"
            >
                <!-- Connection Identity Section -->
                <div class="space-y-4">
                    <div>
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
                            :default-value="connection?.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label
                                for="type"
                                class="text-xs text-muted-foreground"
                                >Database Type</Label
                            >
                            <Select
                                name="type"
                                :default-value="connection?.type ?? 'mysql'"
                            >
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

                        <div>
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
                                <input
                                    type="hidden"
                                    name="is_production_stage"
                                    :value="isProduction ? '1' : '0'"
                                />
                                <Checkbox
                                    id="is_production_stage"
                                    :model-value="isProduction"
                                    @update:model-value="
                                        isProduction = !!$event
                                    "
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
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
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
                                :default-value="connection?.host"
                            />
                            <InputError :message="errors.host" />
                        </div>

                        <div>
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
                                :default-value="connection?.port?.toString()"
                            />
                            <InputError :message="errors.port" />
                        </div>
                    </div>

                    <div>
                        <Label
                            for="database"
                            class="text-xs text-muted-foreground"
                        >
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
                            :default-value="connection?.database"
                        />
                        <InputError :message="errors.database" />
                    </div>
                </div>

                <Separator />

                <!-- Authentication Section -->
                <div class="space-y-4">
                    <div>
                        <Label
                            for="username"
                            class="text-xs text-muted-foreground"
                        >
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
                            :default-value="connection?.username"
                        />
                        <InputError :message="errors.username" />
                    </div>

                    <div>
                        <Label
                            for="password"
                            class="text-xs text-muted-foreground"
                            >Password</Label
                        >
                        <Input
                            id="password"
                            name="password"
                            type="password"
                            :placeholder="
                                isEditing
                                    ? 'Leave blank to keep current'
                                    : '••••••••'
                            "
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

                <InfoComponent
                    title="Security &amp; Privacy"
                    :icon="ShieldCheckIcon"
                    description="All database credentials are encrypted at rest using AES-256. Sensitive information such as passwords and secret keys are never displayed in the interface after initial configuration. Access to these profiles is governed by Clonio's policy."
                />

                <!-- Submit button -->
                <div class="mt-auto pt-4">
                    <Button
                        type="submit"
                        :disabled="processing || recentlySuccessful"
                    >
                        <Loader2
                            v-if="processing"
                            class="size-4 animate-spin"
                        />
                        <Database v-else class="size-4" />
                        {{
                            processing
                                ? isEditing
                                    ? 'Updating...'
                                    : 'Creating...'
                                : resolvedSubmitLabel
                        }}
                    </Button>
                </div>
            </Form>
        </SheetContent>
    </Sheet>
</template>
