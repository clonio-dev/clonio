<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';

import TransferRunController from '@/actions/App/Http/Controllers/TransferRunController';
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import StepNumber from '@/components/StepNumber.vue';
import { Button } from '@/components/ui/button';
import { Combobox, ComboboxItems } from '@/components/ui/combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { ref } from 'vue';

interface Props {
    prod_connections: ComboboxItems;
    test_connections: ComboboxItems;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Transfer Runs',
        href: TransferRunController.index().url,
    },
];

const selectedSourceConnection = ref<string | number | null>(null);
const selectedTargetConnection = ref<string | number | null>(null);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create a new transfer run" />

        <div class="px-4 py-6">
            <Heading
                title="Create a new transfer"
                description="Transfer anonymized data from your production data into your test environments."
            />

            <Form
                v-bind="TransferRunController.store.form()"
                class="space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="flex flex-col space-y-6">
                    <div class="flex w-full gap-4">
                        <StepNumber step="1" />
                        <HeadingSmall
                            title="Select or create your source connection"
                            description="Your production data that you want to transfer to your test stages."
                        />
                    </div>
                </div>

                <div class="grid max-w-120 gap-2">
                    <Label for="source_connection_id">Source</Label>
                    <Combobox
                        id="source_connection_id"
                        :items="props.prod_connections"
                        class="w-120"
                        @update:modelValue="selectedSourceConnection = $event"
                        required
                    />
                    <Input
                        type="hidden"
                        name="source_connection_id"
                        :value="selectedSourceConnection"
                    />
                    <InputError
                        class="mt-2"
                        :message="errors.source_connection_id"
                    />
                </div>

                <Separator class="my-4 size-1" />

                <div class="flex flex-col space-y-6">
                    <div class="flex w-full gap-4">
                        <StepNumber step="2" />
                        <HeadingSmall
                            title="Select or create your target connection"
                            description="Your test stage the production data should be transferred to."
                        />
                    </div>
                </div>

                <div class="grid max-w-120 gap-2">
                    <Label for="target_connection_id">Target</Label>
                    <Combobox
                        :items="props.test_connections"
                        class="w-120"
                        @update:modelValue="selectedTargetConnection = $event"
                        required
                    />
                    <Input
                        type="hidden"
                        name="target_connection_id"
                        :value="selectedTargetConnection"
                    />
                    <InputError
                        class="mt-2"
                        :message="errors.target_connection_id"
                    />
                </div>

                <Separator class="my-4 size-1" />

                <div class="flex flex-col space-y-6">
                    <div class="flex w-full gap-4">
                        <StepNumber step="3" />
                        <HeadingSmall
                            title="Set your transfer options"
                            description="Choose privacy data columns to anonymize during transfer."
                        />
                    </div>
                </div>

                <div class="grid max-w-120 gap-2">
                    <Label for="script">Transformation options global and for all known tables</Label>
                    <Input
                        type="text"
                        id="script"
                        name="script"
                    />
                    <InputError
                        class="mt-2"
                        :message="errors.script"
                    />
                </div>

                <Separator class="my-4 size-1" />

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="create-transfer-button"
                        >Save</Button
                    >

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-neutral-600"
                        >
                            Saved.
                        </p>
                    </Transition>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
