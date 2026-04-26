<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<v-btn class="mb-4" variant="text" prepend-icon="mdi-arrow-left" :to="{ name: 'portfolios' }"> Back to Portfolios </v-btn>

			<v-row v-if="isLoadingPortfolio">
				<v-col cols="12" class="d-flex justify-center py-10">
					<v-progress-circular indeterminate color="primary" size="56" />
				</v-col>
			</v-row>

			<template v-else-if="portfolio">
				<v-row class="align-center">
					<v-col cols="12" md="8">
						<h1 class="text-h3 font-weight-bold mb-2">{{ portfolio.name }}</h1>
						<p class="text-subtitle-1 text-medium-emphasis">Portfolio details, positions, and transactions.</p>
					</v-col>
					<v-col cols="12" md="4" class="d-flex justify-md-end">
						<PortfolioImportAction :portfolio-id="portfolio.id" />
					</v-col>
				</v-row>

				<v-row class="mt-4">
					<v-col cols="12" md="6">
						<v-card rounded="lg" elevation="1">
							<v-card-item>
								<v-card-title>Positions</v-card-title>
								<v-card-subtitle>Dummy content</v-card-subtitle>
							</v-card-item>
							<v-divider />
							<v-list lines="two">
								<v-list-item title="AAPL" subtitle="10 shares - Avg. price $165.20" />
								<v-list-item title="MSFT" subtitle="6 shares - Avg. price $402.10" />
								<v-list-item title="NVDA" subtitle="3 shares - Avg. price $850.50" />
							</v-list>
						</v-card>
					</v-col>

					<v-col cols="12" md="6">
						<v-card rounded="lg" elevation="1">
							<v-card-item>
								<v-card-title>Transactions</v-card-title>
								<v-card-subtitle>Dummy content</v-card-subtitle>
							</v-card-item>
							<v-divider />
							<v-list lines="two">
								<v-list-item title="Buy AAPL" subtitle="2026-04-01 - 5 shares - $160.00" />
								<v-list-item title="Buy MSFT" subtitle="2026-04-08 - 6 shares - $398.00" />
								<v-list-item title="Buy NVDA" subtitle="2026-04-15 - 3 shares - $845.00" />
							</v-list>
						</v-card>
					</v-col>
				</v-row>
			</template>
		</v-container>
	</v-main>
</template>

<script setup lang="ts">
import { isAxiosError } from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import PortfolioImportAction from './Components/PortfolioImportAction.vue'
import { fetchPortfolio } from '@/services/portfolio'
import type { PortfolioResponseData } from '@/types/generated'

const route = useRoute()
const portfolio = ref<PortfolioResponseData | null>(null)
const isLoadingPortfolio = ref(false)

const portfolioId = computed(() => Number(route.params.portfolioId))

const loadPortfolio = async (): Promise<void> => {
	isLoadingPortfolio.value = true

	try {
		portfolio.value = await fetchPortfolio(portfolioId.value)
	} catch (error) {
		toast.error(resolveErrorMessage(error, 'Failed to fetch portfolio details.'))
	} finally {
		isLoadingPortfolio.value = false
	}
}

const resolveErrorMessage = (error: unknown, fallbackMessage: string): string => {
	if (isAxiosError<{ message?: string }>(error)) {
		return error.response?.data?.message ?? fallbackMessage
	}

	return error instanceof Error ? error.message : fallbackMessage
}

onMounted(loadPortfolio)
</script>
