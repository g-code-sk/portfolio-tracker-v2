<template>
	<AppSelectInput v-model="model" label="Import Type" :items="items" :disabled="isDisabled" :rules="[required]" />
</template>

<script setup lang="ts">
import AppSelectInput from '@/components/Inputs/AppSelectInput.vue'
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
