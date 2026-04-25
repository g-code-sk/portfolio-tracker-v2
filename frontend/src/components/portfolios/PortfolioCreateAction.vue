<template>
	<div>
		<v-btn color="primary" prepend-icon="mdi-plus" :disabled="isSubmitting" @click="openDialog"> Create Portfolio </v-btn>

		<v-dialog v-model="isDialogOpen" max-width="480">
			<v-card rounded="lg">
				<v-card-title>Create Portfolio</v-card-title>
				<v-card-text>
					<v-form ref="formRef" @submit.prevent="submitPortfolioForm">
						<PortfolioNameField v-model="formName" :is-disabled="isSubmitting" :error-messages="formNameErrors" :rules="rules.name" @submit="submitPortfolioForm" />
					</v-form>
				</v-card-text>
				<v-card-actions>
					<v-spacer />
					<v-btn variant="text" :disabled="isSubmitting" @click="closeDialog">Cancel</v-btn>
					<v-btn color="primary" :loading="isSubmitting" @click="submitPortfolioForm">Create</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</div>
</template>

<script setup lang="ts">
import { isAxiosError } from 'axios'
import { ref } from 'vue'
import PortfolioNameField from '@/components/portfolios/PortfolioNameField.vue'
import { getFieldErrors } from '@/services/api-errors'
import { createPortfolio } from '@/services/portfolio'
import { required } from '@/services/rules'
import { validateVuetifyForm } from '@/services/vuetify-form'
import type { PortfolioPayloadData } from '@/types/generated'
import type { VForm } from 'vuetify/components'

const emit = defineEmits<{
	saved: []
	error: [message: string]
}>()

const isDialogOpen = ref(false)
const isSubmitting = ref(false)
const formName = ref('')
const formNameErrors = ref<string[]>([])
const formRef = ref<VForm | null>(null)

const rules = {
	name: [required],
}

const openDialog = (): void => {
	formName.value = ''
	formNameErrors.value = []
	isDialogOpen.value = true
}

const closeDialog = (): void => {
	if (isSubmitting.value) {
		return
	}

	isDialogOpen.value = false
}

const submitPortfolioForm = async (): Promise<void> => {
	if (!(await validateVuetifyForm(formRef))) {
		return
	}

	const payload: PortfolioPayloadData = {
		name: formName.value.trim(),
	}

	formNameErrors.value = []

	isSubmitting.value = true

	try {
		await createPortfolio(payload)
		isDialogOpen.value = false
		emit('saved')
	} catch (error) {
		const fieldErrors = getFieldErrors<PortfolioPayloadData>(error)
		formNameErrors.value = fieldErrors.name ?? []
		emit('error', resolveErrorMessage(error, 'Failed to create portfolio.'))
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
