<template>
	<div>
		<v-btn color="primary" size="small" variant="tonal" :disabled="isSubmitting" @click="openDialog">Update</v-btn>

		<v-dialog v-model="isDialogOpen" max-width="480">
			<v-card rounded="lg">
				<v-card-title>Update Portfolio</v-card-title>
				<v-card-text>
					<v-form ref="formRef" @submit.prevent="submitPortfolioForm">
						<PortfolioNameField v-model="formName" :is-disabled="isSubmitting" :error-messages="formNameErrors" :rules="rules.name" @submit="submitPortfolioForm" />
					</v-form>
				</v-card-text>
				<v-card-actions>
					<v-spacer />
					<v-btn variant="text" :disabled="isSubmitting" @click="closeDialog">Cancel</v-btn>
					<v-btn color="primary" :loading="isSubmitting" @click="submitPortfolioForm">Update</v-btn>
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
import { required } from '@/services/rules'
import { updatePortfolio } from '@/services/portfolio'
import { validateVuetifyForm } from '@/services/vuetify-form'
import type { PortfolioPayloadData, PortfolioResponseData } from '@/types/generated'
import type { VForm } from 'vuetify/components'

const props = defineProps<{
	portfolio: PortfolioResponseData
}>()

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
	formName.value = props.portfolio.name
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
		await updatePortfolio(props.portfolio.id, payload)
		isDialogOpen.value = false
		emit('saved')
	} catch (error) {
		const fieldErrors = getFieldErrors<PortfolioPayloadData>(error)
		formNameErrors.value = fieldErrors.name ?? []
		emit('error', resolveErrorMessage(error, 'Failed to update portfolio.'))
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
