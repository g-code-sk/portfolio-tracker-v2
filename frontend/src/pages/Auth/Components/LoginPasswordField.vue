<template>
	<AppTextField
		v-model="model"
		:label="label"
		type="password"
		:error-messages="errorMessages"
		:rules="rules"
		required
		:autocomplete="autocomplete"
	/>
</template>

<script setup lang="ts">
import AppTextField from '@/components/AppTextField.vue'

const model = defineModel<string>({ required: true })

withDefaults(
	defineProps<{
		autocomplete?: string
		errorMessages?: string[]
		label?: string
		rules?: Array<(value: unknown) => true | string>
	}>(),
	{
		autocomplete: 'current-password',
		label: 'Password',
		errorMessages: () => [],
		rules: () => [],
	},
)
</script>
