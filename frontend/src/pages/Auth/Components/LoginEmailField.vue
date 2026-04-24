<template>
	<AppTextField
		v-model="model"
		label="Email"
		type="email"
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
		rules?: Array<(value: unknown) => true | string>
	}>(),
	{
		autocomplete: 'username',
		errorMessages: () => [],
		rules: () => [],
	},
)
</script>
