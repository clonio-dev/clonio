<script setup lang="ts">
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Loader2 } from 'lucide-vue-next';
import { Connection } from '@/pages/connections/types';
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import CreateController from '@/actions/App/Http/Controllers/DatabaseConnections/CreateController';

interface Props {
    open: boolean;
    submitLabel?: string;
}

interface Emits {
    (e: 'close'): void;
    (e: 'created', connection: Connection): void;
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
    submitLabel: 'Create',
});
const emit = defineEmits<Emits>();

function handleClose() {
    emit('close');
}
</script>

<template>
    <Sheet :open="props.open" @update:open="handleClose">
        <SheetContent class="sm:max-w-md overflow-y-auto">
            <SheetHeader>
                <SheetTitle>New Database Connection</SheetTitle>
                <SheetDescription>
                    Add a new database connection to use in your transfer configs
                </SheetDescription>
            </SheetHeader>

            <Form
                v-bind="CreateController.post()"
                class="space-y-4 mt-6 mx-4"
                v-slot="{ errors, processing, recentlySuccessful }"
                :reset-on-error="['username', 'password']"
                :onSubmitComplete="handleClose"
            >
                <div class="space-y-2">
                    <Label for="name">Connection Name *</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="e.g., Production MySQL"
                        :class="{ 'border-destructive': errors.name }"
                    />
                    <InputError class="mt-2" :message="errors.name" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="type">Database Type *</Label>
                        <Select name="type" default-value="mysql">
                            <SelectTrigger id="type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="mysql">MySQL</SelectItem>
                                <SelectItem value="pgsql">PostgreSQL</SelectItem>
                                <SelectItem value="sqlserver">SQL Server</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError class="mt-2" :message="errors.type" />
                    </div>

                    <div class="space-y-2">
                        <Label for="is_production_stage">Stage? *</Label>
                        <div class="flex items-center gap-2 mt-4">
                            <Input type="hidden" name="is_production_stage" :value="false" />
                            <Checkbox
                                id="is_production_stage"
                                name="is_production_stage"
                                :class="{ 'border-destructive': errors.is_production_stage }"
                                :default-value="false"
                            />
                            <Label for="is_production_stage">Is it a production stage?</Label>
                        </div>
                        <InputError class="mt-2" :message="errors.is_production_stage" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="host">Host *</Label>
                        <Input
                            id="host"
                            name="host"
                            placeholder="localhost"
                            :class="{ 'border-destructive': errors.host }"
                        />
                        <InputError class="mt-2" :message="errors.host" />
                    </div>

                    <div class="space-y-2">
                        <Label for="port">Port *</Label>
                        <Input
                            id="port"
                            name="port"
                            type="number"
                            placeholder="3306"
                            :class="{ 'border-destructive': errors.port }"
                        />
                        <InputError class="mt-2" :message="errors.port" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="database">Database Name *</Label>
                    <Input
                        id="database"
                        name="database"
                        placeholder="myapp_db"
                        :class="{ 'border-destructive': errors.database }"
                    />
                    <InputError class="mt-2" :message="errors.database" />
                </div>

                <div class="space-y-2">
                    <Label for="username">Username *</Label>
                    <Input
                        id="username"
                        name="username"
                        autocomplete="off"
                        :class="{ 'border-destructive': errors.username }"
                    />
                    <InputError
                        class="mt-2"
                        :message="errors.username"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="password">Password *</Label>
                    <Input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        :class="{ 'border-destructive': errors.password }"
                    />
                    <InputError class="mt-2" :message="errors.password" />
                </div>

                <div class="flex gap-2">
                    <Button
                        type="submit"
                        :disabled="processing || recentlySuccessful"
                        class="flex-1"
                    >
                        <Loader2 v-if="processing" class="w-4 h-4 mr-2 animate-spin" />
                        {{ props.submitLabel }}
                    </Button>
                </div>
            </Form>
        </SheetContent>
    </Sheet>
</template>
