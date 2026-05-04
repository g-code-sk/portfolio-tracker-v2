<template>
	<v-select
		v-model="model"
		label="Import Type"
		variant="outlined"
		hide-details="auto"
		:items="items"
		:disabled="isDisabled"
		:rules="[required]"
	/>
</template>

<script setup lang="ts">
import type { TransactionImportType } from '@/types/generated'
import { required } from '@/services/rules'

const model = defineModel<TransactionImportType | null>({ required: true })

withDefaults(
	defineProps<{
		items?: string[]
		isDisabled?: boolean
	}>(),
	{
		items: () => [],
		isDisabled: false,
	},
)
</script>
