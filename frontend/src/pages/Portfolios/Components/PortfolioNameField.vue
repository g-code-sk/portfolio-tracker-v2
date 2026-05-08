<template>
	<AppTextField
		v-model="model"
		label="Portfolio Name"
		placeholder="Portfolio name"
		:disabled="isDisabled"
		:error-messages="errorMessages"
		:rules="rules"
		@keyup.enter="emit('submit')"
	/>
</template>

<script setup lang="ts">
import AppTextField from '@/components/AppTextField.vue'

withDefaults(
	defineProps<{
		isDisabled?: boolean
		errorMessages?: string[]
		rules?: Array<(value: unknown) => true | string>
	}>(),
	{
		isDisabled: false,
		errorMessages: () => [],
		rules: () => [],
	},
)

const emit = defineEmits<{
	submit: []
}>()
const model = defineModel<string>({ required: true })
</script>
