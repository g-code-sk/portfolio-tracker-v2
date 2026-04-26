<template>
	<div>
		<v-btn size="small" variant="tonal" color="error" :loading="isSubmitting" :disabled="isDisabled" @click="openDialog"> Delete </v-btn>

		<AppDialog v-model="isDialogOpen" :disabled="isSubmitting" :size="480">
			<template #title>Delete Portfolio</template>
			<template #body>
				Are you sure you want to delete portfolio and all its positions:
				<strong>{{ portfolio.name }}</strong
				>?
			</template>
			<template #actions>
				<v-btn color="error" variant="tonal" :loading="isSubmitting" @click="confirmDelete">Delete</v-btn>
			</template>
		</AppDialog>
	</div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import AppDialog from '@/components/AppDialog.vue'
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
}>()

const isDialogOpen = ref(false)
const isSubmitting = ref(false)

const openDialog = (): void => {
	isDialogOpen.value = true
}

const confirmDelete = async (): Promise<void> => {
	isSubmitting.value = true

	try {
		await deletePortfolio(props.portfolio.id)
		isDialogOpen.value = false
		emit('deleted')
	} catch (error) {
		console.error('Portfolio delete failed', error)
		toast.error('Something went wrong when deleting a portfolio.')
	} finally {
		isSubmitting.value = false
	}
}
</script>
