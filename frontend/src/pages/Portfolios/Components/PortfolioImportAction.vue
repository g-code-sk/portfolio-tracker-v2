<template>
	<div>
		<v-btn color="primary" prepend-icon="mdi-upload" :disabled="isSubmitting" @click="openDialog"> Import </v-btn>

		<AppDialog v-model="isDialogOpen" :disabled="isSubmitting" :size="560">
			<template #title>Import Portfolio Data</template>
			<template #body>
				<div v-if="isLoadingImportTypes" class="d-flex align-center ga-3 py-6">
					<v-progress-circular indeterminate color="primary" size="24" />
					<span class="text-body-2">Loading import types...</span>
				</div>

				<v-form ref="formRef" v-else class="d-flex flex-column ga-3" :disabled="isSubmitting" @submit.prevent="submitImportForm">
					<PortfolioImportTypeField v-model="formImportType" :items="importTypes" :is-disabled="isSubmitting" />
					<PortfolioImportFileField v-model="formFile" :is-disabled="isSubmitting" />
				</v-form>
			</template>
			<template #actions>
				<v-btn color="primary" variant="tonal" :disabled="isLoadingImportTypes" :loading="isSubmitting" @click="submitImportForm">Upload</v-btn>
			</template>
		</AppDialog>
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import AppDialog from '@/components/AppDialog.vue'
import { fetchTransactionImportTypes, uploadTransactionImport } from '@/services/transaction'
import { validateVuetifyForm } from '@/services/vuetify-form'
import type { TransactionImportType } from '@/types/generated'
import type { VForm } from 'vuetify/components'
import PortfolioImportFileField from './PortfolioImportFileField.vue'
import PortfolioImportTypeField from './PortfolioImportTypeField.vue'

const props = defineProps<{
	portfolioId: number
}>()
const emit = defineEmits<{
	imported: []
}>()

const isDialogOpen = ref(false)
const isLoadingImportTypes = ref(false)
const isSubmitting = ref(false)
const hasLoadedImportTypes = ref(false)
const importTypes = ref<string[]>([])
const formImportType = ref<TransactionImportType | null>(null)
const formFile = ref<File | null>(null)
const formRef = ref<VForm | null>(null)

const resetFormState = (): void => {
	formImportType.value = null
	formFile.value = null
}

const openDialog = async (): Promise<void> => {
	isDialogOpen.value = true
	resetFormState()

	if (!hasLoadedImportTypes.value) {
		await loadImportTypes()
	}
}

const loadImportTypes = async (): Promise<void> => {
	isLoadingImportTypes.value = true

	try {
		importTypes.value = await fetchTransactionImportTypes(props.portfolioId)
		hasLoadedImportTypes.value = true
	} catch (error) {
		console.error('Transaction import types fetch failed', error)
		toast.error('Something went wrong when loading import types.')
	} finally {
		isLoadingImportTypes.value = false
	}
}

const submitImportForm = async (): Promise<void> => {
	if (!(await validateVuetifyForm(formRef))) {
		return
	}

	if (!formImportType.value || !formFile.value) {
		return
	}

	isSubmitting.value = true

	try {
		await uploadTransactionImport(props.portfolioId, {
			importType: formImportType.value,
			file: formFile.value,
		})
		isDialogOpen.value = false
		emit('imported')
	} catch (error) {
		console.error('Transaction import upload failed', error)
		toast.error('Something went wrong when importing transactions.')
	} finally {
		isSubmitting.value = false
	}
}
</script>
