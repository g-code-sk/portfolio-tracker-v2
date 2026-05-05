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
						<PortfolioImportAction :portfolio-id="portfolio.id" @imported="loadPortfolioPositions" />
					</v-col>
				</v-row>

				<v-row class="mt-4">
					<v-col cols="12">
						<v-card rounded="lg" elevation="1">
							<v-card-item>
								<v-card-title>Positions</v-card-title>
								<v-card-subtitle>Grouped by security and currency</v-card-subtitle>
							</v-card-item>
							<v-divider />
							<v-card-text v-if="isLoadingPositions" class="d-flex justify-center py-8">
								<v-progress-circular indeterminate color="primary" size="32" />
							</v-card-text>
							<v-card-text v-else-if="!portfolioPositions.length" class="text-medium-emphasis py-8 text-center"> No positions yet. </v-card-text>
							<v-table v-else>
								<thead>
									<tr>
										<th class="text-left">Ticker</th>
										<th class="text-left">Name</th>
										<th class="text-right">Shares Bought</th>
										<th class="text-right">Shares Sold</th>
										<th class="text-right">Invested Amount</th>
										<th class="text-right">Sold Amount</th>
										<th class="text-right">Total Shares</th>
										<th class="text-left">Currency</th>
										<th class="text-end">Actions</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="position in portfolioPositions" :key="`${position.securityId}-${position.currencySymbol}`">
										<td>
											<AppLink
												:to="{
													name: 'portfolio-position-transactions',
													params: { portfolioId, securityId: position.securityId },
													query: { currencyId: position.currencyId, ticker: position.ticker },
												}"
											>
												{{ position.ticker }}
											</AppLink>
										</td>
										<td>{{ position.name }}</td>
										<td class="text-right">{{ formatDecimal(position.sharesBought) }}</td>
										<td class="text-right">{{ formatDecimal(position.sharesSold) }}</td>
										<td class="text-right">{{ formatDecimal(position.investedAmount) }}</td>
										<td class="text-right">{{ formatDecimal(position.soldAmount) }}</td>
										<td class="text-right">{{ formatDecimal(position.totalShares) }}</td>
										<td>{{ position.currencySymbol }}</td>
										<td class="text-end">
											<v-tooltip text="Buy splits by whole share" location="top">
												<template #activator="{ props: tooltipActivatorProps }">
													<v-btn
														v-bind="tooltipActivatorProps"
														icon
														variant="text"
														density="compact"
														aria-label="Buy splits by whole share"
														@click="openWholeShareSegmentsDialog(position)"
													>
														<v-icon>mdi-view-split-vertical</v-icon>
													</v-btn>
												</template>
											</v-tooltip>
										</td>
									</tr>
								</tbody>
							</v-table>
						</v-card>
					</v-col>
				</v-row>
				<WholeShareBuySegmentsDialog v-model="isWholeShareSegmentsDialogOpen" :portfolio-id="portfolio.id" :position="wholeShareSegmentsDialogPosition" />
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
import WholeShareBuySegmentsDialog from './Components/WholeShareBuySegmentsDialog.vue'
import AppLink from '@/components/AppLink.vue'
import { fetchPortfolio, fetchPortfolioPositions } from '@/services/portfolio'
import { formatDecimal } from '@/format/number'
import type { PortfolioPositionResponseData, PortfolioResponseData } from '@/types/generated'

const route = useRoute()
const portfolio = ref<PortfolioResponseData | null>(null)
const portfolioPositions = ref<PortfolioPositionResponseData[]>([])
const isLoadingPortfolio = ref(false)
const isLoadingPositions = ref(false)

const portfolioId = computed(() => Number(route.params.portfolioId))

const isWholeShareSegmentsDialogOpen = ref(false)
const wholeShareSegmentsDialogPosition = ref<PortfolioPositionResponseData | null>(null)

const openWholeShareSegmentsDialog = (position: PortfolioPositionResponseData): void => {
	wholeShareSegmentsDialogPosition.value = position
	isWholeShareSegmentsDialogOpen.value = true
}

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

const loadPortfolioPositions = async (): Promise<void> => {
	isLoadingPositions.value = true

	try {
		portfolioPositions.value = await fetchPortfolioPositions(portfolioId.value)
	} catch (error) {
		toast.error(resolveErrorMessage(error, 'Failed to fetch portfolio positions.'))
	} finally {
		isLoadingPositions.value = false
	}
}

const resolveErrorMessage = (error: unknown, fallbackMessage: string): string => {
	if (isAxiosError<{ message?: string }>(error)) {
		return error.response?.data?.message ?? fallbackMessage
	}

	return error instanceof Error ? error.message : fallbackMessage
}

onMounted(async () => {
	await Promise.all([loadPortfolio(), loadPortfolioPositions()])
})
</script>
