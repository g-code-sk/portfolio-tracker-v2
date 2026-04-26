<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<v-row>
				<v-col cols="12" md="8">
					<h1 class="text-h3 font-weight-bold mb-2">Your Portfolios</h1>
					<p class="text-subtitle-1 text-medium-emphasis">Manage the portfolios connected to your account.</p>
				</v-col>
				<v-col cols="12" md="4" class="d-flex justify-md-end align-center">
					<PortfolioCreateAction @saved="loadPortfolios" @error="setErrorMessage" />
				</v-col>
			</v-row>

			<v-alert v-if="errorMessage" class="mt-4" type="error" variant="tonal" title="Could not load portfolios" :text="errorMessage" />

			<v-row v-if="isLoadingPortfolios" class="mt-2">
				<v-col cols="12" class="d-flex justify-center py-10">
					<v-progress-circular indeterminate color="primary" size="56" />
				</v-col>
			</v-row>

			<template v-else>
				<v-card v-if="!portfolios.length" class="mt-4" rounded="lg" elevation="1">
					<v-card-text class="py-8 text-center">
						<p class="text-h6 mb-2">No portfolios yet</p>
						<p class="text-body-1 text-medium-emphasis mb-6">Create your first portfolio to start tracking your investments.</p>
						<PortfolioCreateAction @saved="loadPortfolios" @error="setErrorMessage" />
					</v-card-text>
				</v-card>

				<v-card v-else class="mt-4" rounded="lg" elevation="1">
					<v-card-item>
						<v-card-title>Portfolio List</v-card-title>
						<v-card-subtitle>{{ portfolios.length }} total</v-card-subtitle>
					</v-card-item>
					<v-divider />
					<v-list lines="one">
						<v-list-item v-for="portfolio in portfolios" :key="portfolio.id" :title="portfolio.name">
							<template #append>
								<div class="d-flex ga-2">
									<PortfolioUpdateAction
										:portfolio="portfolio"
										@saved="loadPortfolios"
										@error="setErrorMessage"
									/>
									<PortfolioDeleteAction :portfolio="portfolio" @deleted="loadPortfolios" @error="setErrorMessage" />
								</div>
							</template>
						</v-list-item>
					</v-list>
				</v-card>
			</template>
		</v-container>
	</v-main>
</template>

<script setup lang="ts">
import { isAxiosError } from 'axios'
import { onMounted, ref } from 'vue'
import PortfolioCreateAction from './Components/PortfolioCreateAction.vue'
import PortfolioDeleteAction from './Components/PortfolioDeleteAction.vue'
import PortfolioUpdateAction from './Components/PortfolioUpdateAction.vue'
import { fetchPortfolios } from '@/services/portfolio'
import type { PortfolioResponseData } from '@/types/generated'

const portfolios = ref<PortfolioResponseData[]>([])
const errorMessage = ref('')
const isLoadingPortfolios = ref(false)

const loadPortfolios = async (): Promise<void> => {
	isLoadingPortfolios.value = true
	errorMessage.value = ''

	try {
		portfolios.value = await fetchPortfolios()
	} catch (error) {
		errorMessage.value = resolveErrorMessage(error, 'Failed to fetch portfolios.')
	} finally {
		isLoadingPortfolios.value = false
	}
}

const setErrorMessage = (message: string): void => {
	errorMessage.value = message
}

const resolveErrorMessage = (error: unknown, fallbackMessage: string): string => {
	if (isAxiosError<{ message?: string }>(error)) {
		return error.response?.data?.message ?? fallbackMessage
	}

	return error instanceof Error ? error.message : fallbackMessage
}

onMounted(loadPortfolios)
</script>
