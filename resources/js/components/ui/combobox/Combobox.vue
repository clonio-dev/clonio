<script setup lang="ts">
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next'
import { computed, ref, useSlots, watch } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import { Separator } from '@/components/ui/separator'
import type { ComboboxItem, ComboboxItems } from '@/components/ui/combobox/index';

interface Props {
    modelValue?: string|number|null
    items?: ComboboxItems
    class?: string
    required?: boolean
    placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Select item...',
});

defineSlots<{
    selected?(props: { item: ComboboxItem }): unknown
    item?(props: { item: ComboboxItem; selected: boolean }): unknown
    footer?(): unknown
}>();

const slots = useSlots();
const emits = defineEmits<{
    (e: "update:modelValue", payload: string | number): void
}>()

const open = ref(false)
const value = ref(props.modelValue ?? '')

const itemsList = computed(() => props.items ?? [])

const selectedItem = computed(() =>
    itemsList.value.find(item => item.value === value.value),
)

// Auto-select single item when required and only one option exists
watch(itemsList, (items) => {
    if (props.required && items.length === 1 && !value.value) {
        value.value = items[0].value
        emits('update:modelValue', value.value)
    }
}, { immediate: true })

function selectItem(selectedValue: string) {
    value.value = selectedValue === value.value.toString() ? '' : selectedValue
    open.value = false
    emits('update:modelValue', value.value)
}

function closePopover() {
    open.value = false
}

defineExpose({ closePopover })
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                role="combobox"
                :aria-expanded="open"
                class="w-full justify-between"
                :class="props.class"
            >
                <template v-if="selectedItem">
                    <slot name="selected" :item="selectedItem">
                        {{ selectedItem.label }}
                    </slot>
                </template>
                <template v-else>
                    {{ placeholder }}
                </template>
                <ChevronsUpDownIcon class="opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-full p-0" :class="props.class">
            <Command>
                <CommandInput class="h-9" placeholder="Search item..." />
                <CommandList>
                    <CommandEmpty>No item found.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="item in itemsList"
                            :key="item.value"
                            :value="item.value"
                            @select="(ev) => { selectItem(ev.detail.value as string) }"
                        >
                            <slot name="item" :item="item" :selected="value === item.value">
                                {{ item.label }}
                            </slot>
                            <CheckIcon
                                :class="cn('ml-auto', value === item.value ? 'opacity-100' : 'opacity-0',)"
                            />
                        </CommandItem>
                    </CommandGroup>
                    <template v-if="slots.footer">
                        <Separator />
                        <div class="p-1">
                            <slot name="footer" />
                        </div>
                    </template>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
