<template>
	<div>
		<v-btn color="primary" size="small" variant="tonal" :disabled="isSubmitting" @click="openDialog">Update</v-btn>

		<AppDialog v-model="isDialogOpen" :disabled="isSubmitting" :size="480">
			<template #title>Update Portfolio</template>
			<template #body>
				<v-form ref="formRef" :disabled="isSubmitting" @submit.prevent="submitPortfolioForm">
					<PortfolioNameField v-model="formName" :is-disabled="isSubmitting" :error-messages="formNameErrors" :rules="rules.name" @submit="submitPortfolioForm" />
				</v-form>
			</template>
			<template #actions>
				<v-btn color="primary" :loading="isSubmitting" @click="submitPortfolioForm">Update</v-btn>
			</template>
		</AppDialog>
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { getFieldErrors } from '@/services/api-errors'
import { required } from '@/services/rules'
import { updatePortfolio } from '@/services/portfolio'
import { validateVuetifyForm } from '@/services/vuetify-form'
import type { PortfolioPayloadData, PortfolioResponseData } from '@/types/generated'
import type { VForm } from 'vuetify/components'
import AppDialog from '@/components/AppDialog.vue'
import PortfolioNameField from './PortfolioNameField.vue'

const props = defineProps<{
	portfolio: PortfolioResponseData
}>()

const emit = defineEmits<{
	saved: []
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
		console.error('Portfolio update failed', error)
		toast.error('Something went wrong when updating a portfolio.')
	} finally {
		isSubmitting.value = false
	}
}
</script>
