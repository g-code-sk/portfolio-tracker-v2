<template>
	<AppFileInput
		v-model="model"
		label="Import File"
		accept=".csv,.xls,.xlsx"
		:disabled="isDisabled"
		:rules="[requiredFile, fileExtension(['csv', 'xls', 'xlsx'])]"
		:show-size="true"
	/>
</template>

<script setup lang="ts">
import AppFileInput from '@/components/Inputs/AppFileInput.vue'
import { fileExtension, requiredFile } from '@/services/rules'

const model = defineModel<File | null>({ required: true })

withDefaults(
	defineProps<{
		isDisabled?: boolean
	}>(),
	{
		isDisabled: false,
	},
)
</script>
