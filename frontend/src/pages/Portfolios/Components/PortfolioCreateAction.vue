<template>
	<div>
		<v-btn color="primary" prepend-icon="mdi-plus" :disabled="isSubmitting" @click="openDialog"> Create Portfolio </v-btn>

		<AppDialog v-model="isDialogOpen" :disabled="isSubmitting" :size="480">
			<template #title>Create Portfolio</template>
			<template #body>
				<v-form ref="formRef" :disabled="isSubmitting" @submit.prevent="submitPortfolioForm">
					<PortfolioNameField v-model="formName" :is-disabled="isSubmitting" :error-messages="formNameErrors" :rules="rules.name" @submit="submitPortfolioForm" />
				</v-form>
			</template>
			<template #actions>
				<v-btn color="primary" variant="tonal" :loading="isSubmitting" @click="submitPortfolioForm">Create</v-btn>
			</template>
		</AppDialog>
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { getFieldErrors } from '@/services/api-errors'
import { createPortfolio } from '@/services/portfolio'
import { required } from '@/services/rules'
import { validateVuetifyForm } from '@/services/vuetify-form'
import type { PortfolioPayloadData } from '@/types/generated'
import type { VForm } from 'vuetify/components'
import AppDialog from '@/components/AppDialog.vue'
import PortfolioNameField from './PortfolioNameField.vue'

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
	formName.value = ''
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
		await createPortfolio(payload)
		isDialogOpen.value = false
		emit('saved')
	} catch (error) {
		const fieldErrors = getFieldErrors<PortfolioPayloadData>(error)
		formNameErrors.value = fieldErrors.name ?? []
		console.error('Portfolio create failed', error)
		toast.error('Something went wrong when creating a portfolio.')
	} finally {
		isSubmitting.value = false
	}
}
</script>
