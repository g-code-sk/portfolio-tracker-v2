<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<AppBreadcrumbs :items="breadcrumbItems" />

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
							<v-card-text v-else class="d-flex flex-column ga-4">
								<AppCollectionFilter
									v-model="filteredPositions"
									:items="portfolioPositions"
									:filter-data="positionFilterDefinitions"
								/>
								<v-data-table :headers="positionHeaders" :items="positionTableItems" item-value="positionRowKey">
									<template #item.ticker="{ item }">
										<div class="d-flex align-center ga-2">
											<AppLink
												style="display: inline-block; min-width: 60px"
												:to="{
													name: 'portfolio-position-transactions',
													params: { portfolioId, securityId: item.securityId },
													query: { currencyId: item.currencyId },
												}"
											>
												{{ item.ticker }}
											</AppLink>
											<v-tooltip text="Buy splits by whole share" location="top">
												<template #activator="{ props: tooltipActivatorProps }">
													<v-btn
														v-bind="tooltipActivatorProps"
														icon
														variant="text"
														density="compact"
														aria-label="Buy splits by whole share"
														:to="{
															name: 'portfolio-position-whole-share-buy-segments',
															params: { portfolioId, securityId: item.securityId },
															query: { currencyId: item.currencyId },
														}"
													>
														<v-icon>mdi-view-split-vertical</v-icon>
													</v-btn>
												</template>
											</v-tooltip>
										</div>
									</template>
									<template #item.sharesBought="{ item }">
										<span class="d-flex justify-end">{{ formatDecimal(item.sharesBought) }}</span>
									</template>
									<template #item.sharesSold="{ item }">
										<span class="d-flex justify-end">{{ formatDecimal(item.sharesSold) }}</span>
									</template>
									<template #item.investedAmount="{ item }">
										<span class="d-flex justify-end">{{ formatAmountWithCurrency(item.investedAmount, item.currencySymbol) }}</span>
									</template>
									<template #item.soldAmount="{ item }">
										<span class="d-flex justify-end">{{ formatAmountWithCurrency(item.soldAmount, item.currencySymbol) }}</span>
									</template>
									<template #item.totalShares="{ item }">
										<span class="d-flex justify-end">{{ formatDecimal(item.totalShares) }}</span>
									</template>
									<template #item.currentPrice="{ item }">
										<span class="d-flex justify-end">{{ formatQuotePrice(item) }}</span>
									</template>
									<template #item.totalGainLossAmount="{ item }">
										<span class="d-flex justify-end" :class="getGainLossTextClass(item.totalGainLossAmount)">
											{{ formatGainLossAmount(item.totalGainLossAmount, item.currencySymbol) }}
										</span>
									</template>
									<template #item.totalReturnPercent="{ item }">
										<span class="d-flex justify-end" :class="getGainLossTextClass(item.totalReturnPercent)">
											{{ formatReturnPercent(item.totalReturnPercent) }}
										</span>
									</template>
								</v-data-table>
							</v-card-text>
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
import AppBreadcrumbs, { type AppBreadcrumbItem } from '@/components/AppBreadcrumbs.vue'
import AppCollectionFilter from '@/components/Filters/AppCollectionFilter.vue'
import AppLink from '@/components/AppLink.vue'
import { fetchPortfolio, fetchPortfolioPositions } from '@/services/portfolio'
import { formatGainLossAmount, formatReturnPercent, getGainLossTextClass } from '@/format/gain-loss'
import { formatDecimal } from '@/format/number'
import { FilterType, type FilterDefinition } from '@/types/filter'
import type { PortfolioPositionResponseData, PortfolioResponseData } from '@/types/generated'

type PositionTableItem = PortfolioPositionResponseData & { positionRowKey: string }

const route = useRoute()
const portfolio = ref<PortfolioResponseData | null>(null)
const portfolioPositions = ref<PortfolioPositionResponseData[]>([])
const filteredPositions = ref<PortfolioPositionResponseData[]>([])

const positionFilterDefinitions: FilterDefinition<PortfolioPositionResponseData>[] = [
	{
		type: FilterType.String,
		keys: ['ticker', 'name'],
		label: 'Search positions',
	},
]
const isLoadingPortfolio = ref(false)
const isLoadingPositions = ref(false)

const portfolioId = computed(() => Number(route.params.portfolioId))

const breadcrumbItems = computed<AppBreadcrumbItem[]>(() => [
	{ title: 'Portfolios', to: { name: 'portfolios' } },
	{ title: portfolio.value?.name ?? 'Portfolio', disabled: true },
])

const positionHeaders = [
	{ title: 'Ticker', key: 'ticker', sortable: true },
	{ title: 'Name', key: 'name', sortable: true },
	// { title: 'Shares Bought', key: 'sharesBought', sortable: true, align: 'end' as const },
	// { title: 'Shares Sold', key: 'sharesSold', sortable: true, align: 'end' as const },
	{ title: 'Invested Amount', key: 'investedAmount', sortable: true, align: 'end' as const },
	{ title: 'Sold Amount', key: 'soldAmount', sortable: true, align: 'end' as const },
	{ title: 'Total Shares', key: 'totalShares', sortable: true, align: 'end' as const },
	{ title: 'Current price', key: 'currentPrice', sortable: true, align: 'end' as const },
	{ title: 'Gain/Loss', key: 'totalGainLossAmount', sortable: true, align: 'end' as const },
	{ title: 'Return %', key: 'totalReturnPercent', sortable: true, align: 'end' as const },
]

const positionTableItems = computed<PositionTableItem[]>(() =>
	filteredPositions.value.map((position) => ({
		...position,
		positionRowKey: `${position.securityId}-${position.currencyId}`,
	})),
)

const formatAmountWithCurrency = (amount: number, currencySymbol: string): string => `${formatDecimal(amount)} ${currencySymbol}`

const formatQuotePrice = (item: PortfolioPositionResponseData): string => {
	if (item.currentPrice === null) {
		return '—'
	}

	const amount = formatDecimal(item.currentPrice)

	return item.currentPriceCurrency ? `${amount} ${item.currentPriceCurrency}` : amount
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
