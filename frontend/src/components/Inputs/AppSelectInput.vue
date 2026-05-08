<template>
	<v-select
		v-model="model"
		:items="props.items"
		:label="props.label"
		:item-title="props.itemTitle"
		:item-value="props.itemValue"
		:rules="props.rules"
		:error-messages="props.errorMessages"
		:disabled="props.disabled"
		:clearable="props.clearable"
		:multiple="props.multiple"
		:chips="props.chips"
		:closable-chips="props.closableChips"
		:return-object="props.returnObject"
		:placeholder="props.placeholder"
		:loading="props.loading"
		:no-data-text="props.noDataText"
		variant="outlined"
		hide-details="auto"
		density="comfortable"
		v-bind="$attrs"
	/>
</template>

<script setup lang="ts">
import { useModel } from 'vue'

type ModelValue = unknown
type SelectItem = Record<string, unknown> | string | number | boolean | null

const props = withDefaults(
	defineProps<{
		modelValue: ModelValue
		items?: SelectItem[]
		label?: string
		itemTitle?: string
		itemValue?: string
		rules?: Array<(value: unknown) => true | string>
		errorMessages?: string[]
		disabled?: boolean
		clearable?: boolean
		multiple?: boolean
		chips?: boolean
		closableChips?: boolean
		returnObject?: boolean
		placeholder?: string
		loading?: boolean
		noDataText?: string
	}>(),
	{
		items: () => [],
		label: '',
		itemTitle: 'title',
		itemValue: 'value',
		rules: () => [],
		errorMessages: () => [],
		disabled: false,
		clearable: false,
		multiple: false,
		chips: false,
		closableChips: false,
		returnObject: false,
		placeholder: undefined,
		loading: false,
		noDataText: undefined,
	},
)

defineEmits<{
	(event: 'update:modelValue', value: ModelValue): void
}>()

const model = useModel(props, 'modelValue')
</script>
