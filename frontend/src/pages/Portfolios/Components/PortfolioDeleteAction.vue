<template>
	<div>
		<v-btn size="small" variant="tonal" color="error" :loading="isSubmitting" :disabled="isDisabled" @click="openDialog"> Delete </v-btn>

		<v-dialog v-model="isDialogOpen" max-width="480">
			<v-card rounded="lg">
				<v-card-title>Delete Portfolio</v-card-title>
				<v-card-text>
					Are you sure you want to delete portfolio and all its positions:
					<strong>{{ portfolio.name }}</strong
					>?
				</v-card-text>
				<v-card-actions>
					<v-spacer />
					<v-btn variant="text" :disabled="isSubmitting" @click="closeDialog">Cancel</v-btn>
					<v-btn color="error" :loading="isSubmitting" @click="confirmDelete">Delete</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</div>
</template>

<script setup lang="ts">
import { isAxiosError } from 'axios'
import { ref } from 'vue'
import { deletePortfolio } from '@/services/portfolio'
import type { PortfolioResponseData } from '@/types/generated'

const props = withDefaults(
	defineProps<{
		portfolio: PortfolioResponseData
		isDisabled?: boolean
	}>(),
	{
		isDisabled: false,
	},
)

const emit = defineEmits<{
	deleted: []
	error: [message: string]
}>()

const isDialogOpen = ref(false)
const isSubmitting = ref(false)

const openDialog = (): void => {
	isDialogOpen.value = true
}

const closeDialog = (): void => {
	if (isSubmitting.value) {
		return
	}

	isDialogOpen.value = false
}

const confirmDelete = async (): Promise<void> => {
	isSubmitting.value = true

	try {
		await deletePortfolio(props.portfolio.id)
		isDialogOpen.value = false
		emit('deleted')
	} catch (error) {
		emit('error', resolveErrorMessage(error, 'Failed to delete portfolio.'))
	} finally {
		isSubmitting.value = false
	}
}

const resolveErrorMessage = (error: unknown, fallbackMessage: string): string => {
	if (isAxiosError<{ message?: string }>(error)) {
		return error.response?.data?.message ?? fallbackMessage
	}

	return error instanceof Error ? error.message : fallbackMessage
}
</script>
