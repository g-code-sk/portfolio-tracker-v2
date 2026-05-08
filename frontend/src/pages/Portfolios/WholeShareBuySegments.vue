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
							<WholeShareGroupsSummary
								:realized-gain-loss-amount="wholeShareRealizedGainLossAmount"
								:realized-return-percent="wholeShareRealizedReturnPercent"
								:currency-symbol="wholeShareCurrencySymbol"
							/>
						</v-card-item>
						<v-divider />

						<v-card-text v-if="isLoadingSegments" class="d-flex justify-center py-8">
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
								<WholeShareYearFilters v-model:selected-buy-year="selectedBuyYear" v-model:selected-sell-year="selectedSellYear" :groups="wholeShareGroups" />
							</v-card-text>

							<v-card-text class="pt-2">
								<WholeShareGroupsTable :groups="filteredWholeShareGroups" />
							</v-card-text>
						</template>
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
import WholeShareGroupsTable from './Components/WholeShareGroup/WholeShareGroupsTable.vue'
import WholeShareGroupsSummary from './Components/WholeShareGroup/WholeShareGroupsSummary.vue'
import WholeShareYearFilters from './Components/WholeShareGroup/WholeShareYearFilters.vue'
import { getYearFromDateString } from '@/format/date'
import { fetchWholeShareSegments } from '@/services/transaction'
import type { WholeShareGroupResponseData, WholeShareGroupsResponseData } from '@/types/generated'

const route = useRoute()

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
	return wholeShareGroupsResponse.value?.ticker ?? 'Position'
})

const selectedBuyYear = ref<number | null>(null)
const selectedSellYear = ref<number | null>(null)

const filteredWholeShareGroups = computed(() => {
	return wholeShareGroups.value.filter((group) => {
		if (selectedBuyYear.value !== null) {
			const buyYear = getYearFromDateString(group.buyDate)
			if (buyYear === null) {
				return false
			}

			if (buyYear !== selectedBuyYear.value) {
				return false
			}
		}

		if (selectedSellYear.value !== null) {
			const sellYear = getYearFromDateString(group.sellDate)
			if (sellYear === null) {
				return false
			}

			if (sellYear !== selectedSellYear.value) {
				return false
			}
		}

		return true
	})
})

const wholeShareRealizedGainLossAmount = computed(() => wholeShareGroupsResponse.value?.realizedGainLossAmount ?? null)
const wholeShareRealizedReturnPercent = computed(() => wholeShareGroupsResponse.value?.realizedReturnPercent ?? null)
const wholeShareCurrencySymbol = computed(() => wholeShareGroupsResponse.value?.currencySymbol ?? '')

const breadcrumbItems = computed<AppBreadcrumbItem[]>(() => [
	{ title: 'Portfolios', to: { name: 'portfolios' } },
	{
		title: wholeShareGroupsResponse.value?.portfolioName ?? 'Portfolio',
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
	await loadWholeShareSegments()
})
</script>
