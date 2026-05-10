<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<AppBreadcrumbs :items="breadcrumbItems" />

			<v-row class="align-center">
				<v-col cols="12" class="d-flex flex-wrap align-center ga-4">
					<div class="flex-grow-1">
						<h1 class="text-h3 font-weight-bold mb-2">{{ positionTitle }}</h1>
						<p class="text-subtitle-1 text-medium-emphasis">Transactions for the selected portfolio position.</p>
					</div>
					<v-btn
						v-if="canViewWholeShareGroups"
						color="primary"
						variant="tonal"
						prepend-icon="mdi-view-split-vertical"
						:to="wholeShareGroupsRoute"
					>
						Whole share buy/sell groups
					</v-btn>
				</v-col>
			</v-row>

			<v-row class="mt-4">
				<v-col cols="12">
					<v-card rounded="lg" elevation="1">
						<v-card-item>
							<v-card-title>Transactions</v-card-title>
							<v-card-subtitle>{{ transactions.length }} total</v-card-subtitle>
						</v-card-item>
						<v-divider />

						<v-card-text v-if="isLoadingTransactions" class="d-flex justify-center py-8">
							<v-progress-circular indeterminate color="primary" size="32" />
						</v-card-text>

						<v-card-text v-else-if="!transactions.length" class="text-medium-emphasis py-8 text-center">
							No matching transactions found for this position.
						</v-card-text>

						<v-data-table v-else :headers="headers" :items="transactions">
							<template #item.executedAt="{ item }">
								<span :title="formatDateTime(item.executedAt)">{{ formatDate(item.executedAt) }}</span>
							</template>
							<template #item.numberOfShares="{ item }">
								<span class="d-flex justify-end">{{ formatDecimal(item.numberOfShares) }}</span>
							</template>
							<template #item.pricePerShare="{ item }">
								<span class="d-flex justify-end">{{ formatDecimal(item.pricePerShare) }} {{ item.currencySymbol }}</span>
							</template>
							<template #item.totalAmount="{ item }">
								<span class="d-flex justify-end">{{ formatDecimal(item.totalAmount) }} {{ item.currencySymbol }}</span>
							</template>
						</v-data-table>
					</v-card>
				</v-col>
			</v-row>
		</v-container>
	</v-main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import AppBreadcrumbs, { type AppBreadcrumbItem } from '@/components/AppBreadcrumbs.vue'
import { fetchPositionTransactions } from '@/services/transaction'
import { formatDecimal } from '@/format/number'
import { formatDate, formatDateTime } from '@/format/date'
import type { PortfolioTransactionResponseData, PortfolioTransactionsResponseData } from '@/types/generated'

const route = useRoute()
const transactions = ref<PortfolioTransactionResponseData[]>([])
const transactionsResponse = ref<PortfolioTransactionsResponseData | null>(null)
const isLoadingTransactions = ref(false)

const portfolioId = computed(() => Number(route.params.portfolioId))
const securityId = computed(() => Number(route.params.securityId))
const currencyId = computed(() => {
	const queryValue = route.query.currencyId
	if (queryValue === undefined) {
		return null
	}

	const parsedValue = Number(queryValue)

	return Number.isNaN(parsedValue) ? null : parsedValue
})
const firstTransaction = computed(() => transactions.value[0])
const securityTicker = computed(() => firstTransaction.value?.ticker ?? null)
const securityName = computed(() => firstTransaction.value?.name ?? null)
const currencySymbol = computed(() => firstTransaction.value?.currencySymbol ?? '')
const positionTitle = computed(() => {
	const ticker = securityTicker.value ?? 'Position'
	const name = securityName.value ? ` — ${securityName.value}` : ''
	const label = currencySymbol.value ? ` (${currencySymbol.value})` : ''
	return `${ticker}${name}${label}`
})

const canViewWholeShareGroups = computed(() => currencyId.value !== null)
const wholeShareGroupsRoute = computed(() => ({
	name: 'portfolio-position-whole-share-buy-segments',
	params: { portfolioId: portfolioId.value, securityId: securityId.value },
	query: currencyId.value !== null ? { currencyId: currencyId.value } : {},
}))

const breadcrumbItems = computed<AppBreadcrumbItem[]>(() => [
	{ title: 'Portfolios', to: { name: 'portfolios' } },
	{
		title: transactionsResponse.value?.portfolioName ?? 'Portfolio',
		to: { name: 'portfolio-details', params: { portfolioId: portfolioId.value } },
	},
	{ title: securityTicker.value ?? 'Position', disabled: true },
])

const headers = [
	{ title: 'Date', key: 'executedAt', sortable: true },
	{ title: 'Type', key: 'typeName', sortable: true },
	{ title: 'Shares', key: 'numberOfShares', sortable: true, align: 'end' as const },
	{ title: 'Price / Share', key: 'pricePerShare', sortable: true, align: 'end' as const },
	{ title: 'Total', key: 'totalAmount', sortable: true, align: 'end' as const },
]

const loadTransactions = async (): Promise<void> => {
	isLoadingTransactions.value = true

	try {
		transactionsResponse.value = await fetchPositionTransactions(portfolioId.value, securityId.value, currencyId.value)
		transactions.value = transactionsResponse.value.transactions
	} catch (error) {
		console.error('Position transactions fetch failed', error)
		toast.error('Something went wrong when loading position transactions.')
	} finally {
		isLoadingTransactions.value = false
	}
}

onMounted(async () => {
	await loadTransactions()
})
</script>
