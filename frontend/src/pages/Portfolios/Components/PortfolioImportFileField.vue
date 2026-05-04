<template>
	<v-file-input
		v-model="model"
		label="Import File"
		variant="outlined"
		hide-details="auto"
		accept=".csv,.xls,.xlsx"
		:disabled="isDisabled"
		:rules="[requiredFile, fileExtension(['csv', 'xls', 'xlsx'])]"
		show-size
	/>
</template>

<script setup lang="ts">
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
