<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<AppBreadcrumbs :items="breadcrumbItems" />

			<v-row class="align-center">
				<v-col cols="12">
					<h1 class="text-h3 font-weight-bold mb-2">{{ ticker }} — Whole share buy/sell groups</h1>
					<p class="text-subtitle-1 text-medium-emphasis">Buy/sell groups split at whole-share boundaries for this position.</p>
				</v-col>
			</v-row>

			<v-row class="mt-4">
				<v-col cols="12">
					<v-card rounded="lg" elevation="1">
						<v-card-item>
							<div class="d-flex align-start justify-space-between ga-4 flex-wrap">
								<v-card-title>Groups</v-card-title>
								<div class="d-flex flex-column flex-sm-row ga-4">
									<div class="text-sm-right">
										<div class="text-caption text-medium-emphasis">Realized Gain/Loss</div>
										<div :class="['text-subtitle-1 font-weight-medium', getGainLossTextClass(wholeShareRealizedGainLossAmount)]">
											{{ formatGainLossAmount(wholeShareRealizedGainLossAmount, null, null, wholeShareCurrencySymbol) }}
										</div>
									</div>
									<div class="text-sm-right">
										<div class="text-caption text-medium-emphasis">Realized Return</div>
										<div :class="['text-subtitle-1 font-weight-medium', getGainLossTextClass(wholeShareRealizedReturnPercent)]">
											{{ formatReturnPercent(wholeShareRealizedReturnPercent) }}
										</div>
									</div>
								</div>
							</div>
						</v-card-item>
						<v-divider />

						<v-card-text v-if="isLoadingSegments || isLoadingPortfolio" class="d-flex justify-center py-8">
							<v-progress-circular indeterminate color="primary" size="32" />
						</v-card-text>

						<v-card-text v-else-if="didWholeShareSegmentsRequestFail" class="text-medium-emphasis py-8 text-center">
							Could not load whole share groups.
						</v-card-text>

						<v-card-text v-else-if="!wholeShareGroups.length" class="text-medium-emphasis py-8 text-center">
							No buy or sell transactions to split for this position.
						</v-card-text>

						<template v-else>
							<v-card-text class="pb-0">
								<div class="d-flex flex-column flex-sm-row ga-4">
									<v-select
										v-model="selectedBuyYear"
										:items="buyYearItems"
										item-title="label"
										item-value="value"
										label="Buy year"
										variant="outlined"
										hide-details="auto"
										density="comfortable"
										clearable
									/>
									<v-select
										v-model="selectedSellYear"
										:items="sellYearItems"
										item-title="label"
										item-value="value"
										label="Sell year"
										variant="outlined"
										hide-details="auto"
										density="comfortable"
										clearable
									/>
								</div>
							</v-card-text>

							<v-card-text class="pt-2">
								<v-data-table
									class="whole-share-groups-table"
									:headers="groupHeaders"
									:items="filteredWholeShareGroups"
									item-value="groupIndex"
									show-expand
									density="comfortable"
								>
									<template #item.groupIndex="{ item }">{{ item.groupIndex + 1 }}</template>
									<template #item.buyDate="{ item }">
										<span v-if="item.buyDate" :title="formatDateTime(item.buyDate)">{{ formatDate(item.buyDate) }}</span>
										<span v-else>—</span>
									</template>
									<template #item.sellDate="{ item }">
										<span v-if="item.sellDate" :title="formatDateTime(item.sellDate)">{{ formatDate(item.sellDate) }}</span>
										<span v-else>—</span>
									</template>
									<template #item.holdPeriodDays="{ item }">
										<span>{{ formatHoldPeriodDays(item.holdPeriodDays) }}</span>
									</template>
									<template #item.weightedBuyPricePerShare="{ item }">{{ formatDecimalValue(item.weightedBuyPricePerShare) }}</template>
									<template #item.weightedSellPricePerShare="{ item }">{{ formatDecimalValue(item.weightedSellPricePerShare) }}</template>
									<template #item.returnPercent="{ item }">
										<span :class="getGainLossTextClass(item.returnPercent)">
											{{ formatReturnPercent(item.returnPercent) }}
										</span>
									</template>
									<template #item.gainLossAmount="{ item }">
										<span :class="getGainLossTextClass(item.gainLossAmount)">
											{{ formatGainLossAmount(item.gainLossAmount, item.buyBucket, item.sellBucket) }}
										</span>
									</template>
									<template #item.isSellTaxable="{ item }">
										<div class="d-flex justify-center">
											<span v-if="item.isSellTaxable === null">—</span>
											<v-tooltip v-else location="top">
												<template #activator="{ props: tooltipProps }">
													<v-icon
														v-bind="tooltipProps"
														:color="item.isSellTaxable ? 'error' : 'success'"
														:icon="item.isSellTaxable ? 'mdi-alert-circle' : 'mdi-check-circle'"
													/>
												</template>
												{{ item.isSellTaxable ? 'Taxable sell' : 'Tax-free sell' }}
											</v-tooltip>
										</div>
									</template>

									<template #expanded-row="{ columns, item }">
										<WholeShareGroupExpandedRow :columns-length="columns.length" :group="item" />
									</template>
								</v-data-table>
							</v-card-text>
						</template>
					</v-card>
				</v-col>
			</v-row>
		</v-container>
	</v-main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue3-toastify'
import AppBreadcrumbs, { type AppBreadcrumbItem } from '@/components/AppBreadcrumbs.vue'
import WholeShareGroupExpandedRow from './Components/WholeShareGroupExpandedRow.vue'
import { formatDate, formatDateTime } from '@/format/date'
import { formatDecimal } from '@/format/number'
import { fetchPortfolio } from '@/services/portfolio'
import { fetchWholeShareSegments } from '@/services/transaction'
import type {
	PortfolioResponseData,
	WholeShareBucketResponseData,
	WholeShareGroupResponseData,
	WholeShareGroupsResponseData,
	WholeShareSegmentResponseData,
} from '@/types/generated'

const route = useRoute()

const portfolio = ref<PortfolioResponseData | null>(null)
const isLoadingPortfolio = ref(false)
const wholeShareGroups = ref<WholeShareGroupResponseData[]>([])
const wholeShareGroupsResponse = ref<WholeShareGroupsResponseData | null>(null)
const isLoadingSegments = ref(false)
const didWholeShareSegmentsRequestFail = ref(false)

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

const ticker = computed(() => {
	const groups = wholeShareGroups.value

	if (groups.length === 0) {
		return 'Position'
	}

	const firstBuy = groups[0].buyBucket?.segments?.[0] as WholeShareSegmentResponseData | undefined
	const firstSell = groups[0].sellBucket?.segments?.[0] as WholeShareSegmentResponseData | undefined

	return firstBuy?.ticker ?? firstSell?.ticker ?? 'Position'
})

const selectedBuyYear = ref<number | null>(null)
const selectedSellYear = ref<number | null>(null)

const availableBuyYears = computed<number[]>(() => {
	const yearSet = new Set<number>()

	for (const group of wholeShareGroups.value) {
		if (group.buyDate === null) {
			continue
		}

		yearSet.add(new Date(group.buyDate).getFullYear())
	}

	return Array.from(yearSet).sort((a, b) => b - a)
})

const buyYearItems = computed(() => {
	const allYearsItem = { label: 'All years', value: null as number | null }
	const yearItems = availableBuyYears.value.map((year) => ({ label: String(year), value: year }))

	return [allYearsItem, ...yearItems]
})

const availableSellYears = computed<number[]>(() => {
	const yearSet = new Set<number>()

	for (const group of wholeShareGroups.value) {
		if (group.sellDate === null) {
			continue
		}

		yearSet.add(new Date(group.sellDate).getFullYear())
	}

	return Array.from(yearSet).sort((a, b) => b - a)
})

const sellYearItems = computed(() => {
	const allYearsItem = { label: 'All years', value: null as number | null }
	const yearItems = availableSellYears.value.map((year) => ({ label: String(year), value: year }))

	return [allYearsItem, ...yearItems]
})

const filteredWholeShareGroups = computed(() => {
	return wholeShareGroups.value.filter((group) => {
		if (selectedBuyYear.value !== null) {
			if (group.buyDate === null) {
				return false
			}

			if (new Date(group.buyDate).getFullYear() !== selectedBuyYear.value) {
				return false
			}
		}

		if (selectedSellYear.value !== null) {
			if (group.sellDate === null) {
				return false
			}

			if (new Date(group.sellDate).getFullYear() !== selectedSellYear.value) {
				return false
			}
		}

		return true
	})
})

const wholeShareRealizedGainLossAmount = computed(() => wholeShareGroupsResponse.value?.realizedGainLossAmount ?? null)
const wholeShareRealizedReturnPercent = computed(() => wholeShareGroupsResponse.value?.realizedReturnPercent ?? null)
const wholeShareCurrencySymbol = computed(() => {
	const firstGroup = wholeShareGroups.value[0]
	const firstBuy = firstGroup?.buyBucket?.segments?.[0] as WholeShareSegmentResponseData | undefined
	const firstSell = firstGroup?.sellBucket?.segments?.[0] as WholeShareSegmentResponseData | undefined

	return firstSell?.currencySymbol ?? firstBuy?.currencySymbol ?? ''
})

watch(wholeShareGroups, () => {
	if (selectedBuyYear.value !== null && !availableBuyYears.value.includes(selectedBuyYear.value)) {
		selectedBuyYear.value = null
	}

	if (selectedSellYear.value !== null && !availableSellYears.value.includes(selectedSellYear.value)) {
		selectedSellYear.value = null
	}
})

const breadcrumbItems = computed<AppBreadcrumbItem[]>(() => [
	{ title: 'Portfolios', to: { name: 'portfolios' } },
	{
		title: portfolio.value?.name ?? 'Portfolio',
		to: { name: 'portfolio-details', params: { portfolioId: portfolioId.value } },
	},
	{
		title: ticker.value,
		to: {
			name: 'portfolio-position-transactions',
			params: { portfolioId: portfolioId.value, securityId: securityId.value },
			query: currencyId.value !== null ? { currencyId: currencyId.value } : {},
		},
	},
	{ title: 'Whole share buy/sell groups', disabled: true },
])

const groupHeaders = [
	{ title: 'Group Index', key: 'groupIndex' },
	{ title: 'Buy date', key: 'buyDate' },
	{ title: 'Sell date', key: 'sellDate' },
	{ title: 'Hold period', key: 'holdPeriodDays' },
	{ title: 'Weighted buy', key: 'weightedBuyPricePerShare', align: 'end' },
	{ title: 'Weighted sell', key: 'weightedSellPricePerShare', align: 'end' },
	{ title: 'Return %', key: 'returnPercent', align: 'end' },
	{ title: 'Gain/Loss', key: 'gainLossAmount', align: 'end' },
	{ title: 'Tax-Free', key: 'isSellTaxable', align: 'center' },
] as const

const formatHoldPeriodDays = (value: number | null): string => {
	return value === null ? '—' : `${value}`
}

const formatDecimalValue = (value: number | null): string => {
	return value === null ? '—' : formatDecimal(value)
}

const formatReturnPercent = (value: number | null): string => {
	if (value === null) {
		return '—'
	}

	const sign = value > 0 ? '+' : ''

	return `${sign}${formatDecimal(value)}%`
}

const formatGainLossAmount = (
	value: number | null,
	buyBucket: WholeShareBucketResponseData | null,
	sellBucket: WholeShareBucketResponseData | null,
	currencySymbolOverride?: string,
): string => {
	if (value === null) {
		return '—'
	}

	const sign = value > 0 ? '+' : ''
	const currencySymbol = currencySymbolOverride ?? sellBucket?.segments?.[0]?.currencySymbol ?? buyBucket?.segments?.[0]?.currencySymbol ?? ''

	return `${sign}${formatDecimal(value)}${currencySymbol ? ` ${currencySymbol}` : ''}`
}

const getGainLossTextClass = (value: number | null): string => {
	if (value === null || value === 0) {
		return ''
	}

	return value > 0 ? 'text-success' : 'text-error'
}

const loadPortfolio = async (): Promise<void> => {
	isLoadingPortfolio.value = true

	try {
		portfolio.value = await fetchPortfolio(portfolioId.value)
	} catch (error) {
		console.error('Portfolio fetch failed', error)
		toast.error('Something went wrong when loading portfolio details.')
		portfolio.value = null
	} finally {
		isLoadingPortfolio.value = false
	}
}

const loadWholeShareSegments = async (): Promise<void> => {
	if (currencyId.value === null) {
		wholeShareGroupsResponse.value = null
		wholeShareGroups.value = []
		didWholeShareSegmentsRequestFail.value = true
		toast.error('Missing currency for this position.')

		return
	}

	isLoadingSegments.value = true
	didWholeShareSegmentsRequestFail.value = false

	try {
		const data = await fetchWholeShareSegments(portfolioId.value, securityId.value, currencyId.value)
		wholeShareGroupsResponse.value = data
		wholeShareGroups.value = data.groups as WholeShareGroupResponseData[]
	} catch (error) {
		console.error('Whole share segments fetch failed', error)
		toast.error('Something went wrong when loading whole share groups.')
		wholeShareGroupsResponse.value = null
		wholeShareGroups.value = []
		didWholeShareSegmentsRequestFail.value = true
	} finally {
		isLoadingSegments.value = false
	}
}

onMounted(async () => {
	await Promise.all([loadPortfolio(), loadWholeShareSegments()])
})
</script>

<style scoped>
:deep(.whole-share-groups-table th),
:deep(.whole-share-groups-table td) {
	white-space: nowrap;
}
</style>
