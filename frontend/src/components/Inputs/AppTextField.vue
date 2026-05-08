<template>
	<v-text-field
		v-model="model"
		:label="props.label"
		:type="props.type"
		:error-messages="props.errorMessages"
		:rules="props.rules"
		:required="props.required"
		:autocomplete="props.autocomplete"
		variant="outlined"
		hide-details="auto"
		v-bind="$attrs"
	/>
</template>

<script setup lang="ts">
import { useModel } from 'vue'

type ModelValue = string | number | null

const props = withDefaults(
	defineProps<{
		modelValue: ModelValue
		label?: string
		type?: string
		errorMessages?: string[]
		rules?: Array<(value: unknown) => true | string>
		required?: boolean
		autocomplete?: string
	}>(),
	{
		label: '',
		type: 'text',
		errorMessages: () => [],
		rules: () => [],
		required: false,
		autocomplete: undefined,
	},
)

const emit = defineEmits<{
	(event: 'update:modelValue', value: ModelValue): void
}>()

const model = useModel(props, 'modelValue')
</script>
