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

interface Props {
    open: boolean;
    submitLabel?: string;
    defaultProduction?: boolean;
}

interface Emits {
    (e: 'close'): void;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    submitLabel: 'Create Connection',
    defaultProduction: false,
});
const emit = defineEmits<Emits>();

const isProduction = ref(false);

function handleOpenChange(open: boolean) {
    if (!open) {
        emit('close');
    }
}

function handleSubmitComplete() {
    emit('close');
}

watch(
    () => props.open,
    (newVal) => {
        if (newVal) {
            // Set default production state when opening
            isProduction.value = props.defaultProduction;
        } else {
            // Reset when closing
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

            <Form
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
