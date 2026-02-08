export { default as Combobox } from "./Combobox.vue"

export type ComboboxItem = {
    value: string|number;
    label: string;
}

export type ComboboxItems = Array<ComboboxItem>
