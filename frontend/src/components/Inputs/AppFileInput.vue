<template>
	<v-file-input
		v-model="model"
		:label="props.label"
		:accept="props.accept"
		:disabled="props.disabled"
		:rules="props.rules"
		:error-messages="props.errorMessages"
		:multiple="props.multiple"
		:show-size="props.showSize"
		variant="outlined"
		hide-details="auto"
		v-bind="$attrs"
	/>
</template>

<script setup lang="ts">
import { useModel } from 'vue'

type ModelValue = File | File[] | null

const props = withDefaults(
	defineProps<{
		modelValue: ModelValue
		label?: string
		accept?: string
		disabled?: boolean
		rules?: Array<(value: unknown) => true | string>
		errorMessages?: string[]
		multiple?: boolean
		showSize?: boolean
	}>(),
	{
		label: '',
		accept: undefined,
		disabled: false,
		rules: () => [],
		errorMessages: () => [],
		multiple: false,
		showSize: false,
	},
)

defineEmits<{
	(event: 'update:modelValue', value: ModelValue): void
}>()

const model = useModel(props, 'modelValue')
</script>
