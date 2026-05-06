<template>
	<AppDialog v-model="isDialogOpen" :size="1080" close-text="Close">
		<template #title> Whole share buy/sell groups{{ titleTicker }} </template>

		<template #body>
			<v-progress-linear v-if="isLoadingSegments" class="mb-4" color="primary" height="6" indeterminate />

			<div v-else-if="!effectivePosition" class="text-medium-emphasis">No position selected.</div>

			<div v-else-if="effectivePosition && !didWholeShareSegmentsRequestFail && wholeShareGroups.length === 0" class="text-medium-emphasis py-4 text-center">
				No buy or sell transactions to split for this position.
			</div>

			<div v-else-if="effectivePosition && !didWholeShareSegmentsRequestFail">
				<v-data-table :headers="groupHeaders" :items="wholeShareGroups" item-value="groupIndex" show-expand density="comfortable">
					<template #item.groupIndex="{ item }">{{ item.groupIndex + 1 }}</template>
					<template #item.buyDate="{ item }">{{ formatDateValue(item.buyDate) }}</template>
					<template #item.sellDate="{ item }">
						<span :class="getHypotheticalTextClass(item)">{{ formatDateValue(item.sellDate) }}</span>
					</template>
					<template #item.holdPeriodDays="{ item }">
						<span :class="getHypotheticalTextClass(item)">{{ formatHoldPeriodDays(item.holdPeriodDays) }}</span>
					</template>
					<template #item.weightedBuyPricePerShare="{ item }">{{ formatDecimalValue(item.weightedBuyPricePerShare) }}</template>
					<template #item.weightedSellPricePerShare="{ item }">{{ formatDecimalValue(item.weightedSellPricePerShare) }}</template>
					<template #item.yieldPercent="{ item }">
						<span :class="getYieldPercentTextClass(item.yieldPercent)">
							{{ formatYieldPercent(item.yieldPercent) }}
						</span>
					</template>
					<template #item.yieldAmount="{ item }">
						<span :class="getYieldAmountTextClass(item.yieldAmount)">
							{{ formatYieldAmount(item.yieldAmount, item.buyBucket, item.sellBucket) }}
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
						<tr>
							<td :colspan="columns.length" class="bg-surface">
								<div class="pa-4">
									<v-row>
										<v-col cols="12" md="6">
											<v-card variant="outlined" rounded="lg">
												<v-card-title class="text-subtitle-2">Buys · {{ formatDecimal(sumBucketShares(item.buyBucket)) }} of 1.0</v-card-title>
												<v-divider />
												<v-card-text class="py-2">
													<div v-if="narrowSegments(item.buyBucket).length === 0" class="text-medium-emphasis">No buy segments in this group.</div>
													<div v-else class="timeline-scroll">
														<v-timeline density="compact" side="end" truncate-line="both">
															<v-timeline-item
																v-for="(segment, segmentIndex) in narrowSegments(item.buyBucket)"
																:key="`buy-${item.groupIndex}-${segmentIndex}-${segment.sourceTransactionId}`"
																dot-color="success"
																size="small"
															>
																<div class="text-body-2">{{ formatDate(segment.executedAt) }} · #{{ segment.sourceTransactionId }}</div>
																<div class="text-body-2">
																	{{ formatDecimal(segment.numberOfShares) }} shares @ {{ formatDecimal(segment.pricePerShare) }} ·
																	{{ formatDecimal(segment.totalAmount) }} {{ segment.currencySymbol }}
																</div>
																<div class="text-caption text-medium-emphasis">External: {{ segment.externalTransactionId }}</div>
															</v-timeline-item>
														</v-timeline>
													</div>
												</v-card-text>
											</v-card>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="outlined" rounded="lg">
												<v-card-title class="text-subtitle-2">Sells · {{ formatDecimal(sumBucketShares(item.sellBucket)) }} of 1.0</v-card-title>
												<v-divider />
												<v-card-text class="py-2">
													<div v-if="narrowSegments(item.sellBucket).length === 0" class="text-medium-emphasis">No sell segments in this group.</div>
													<div v-else class="timeline-scroll">
														<v-timeline density="compact" side="end" truncate-line="both">
															<v-timeline-item
																v-for="(segment, segmentIndex) in narrowSegments(item.sellBucket)"
																:key="`sell-${item.groupIndex}-${segmentIndex}-${segment.sourceTransactionId}`"
																dot-color="error"
																size="small"
															>
																<div class="text-body-2">{{ formatDate(segment.executedAt) }} · #{{ segment.sourceTransactionId }}</div>
																<div class="text-body-2">
																	{{ formatDecimal(segment.numberOfShares) }} shares @ {{ formatDecimal(segment.pricePerShare) }} ·
																	{{ formatDecimal(segment.totalAmount) }} {{ segment.currencySymbol }}
																</div>
																<div class="text-caption text-medium-emphasis">External: {{ segment.externalTransactionId }}</div>
															</v-timeline-item>
														</v-timeline>
													</div>
												</v-card-text>
											</v-card>
										</v-col>
									</v-row>
								</div>
							</td>
						</tr>
					</template>
				</v-data-table>
			</div>
		</template>
	</AppDialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { toast } from 'vue3-toastify'
import AppDialog from '@/components/AppDialog.vue'
import { formatDate } from '@/format/date'
import { formatDecimal } from '@/format/number'
import { fetchWholeShareSegments } from '@/services/transaction'
import type { PortfolioPositionResponseData, WholeShareBucketResponseData, WholeShareGroupResponseData, WholeShareSegmentResponseData } from '@/types/generated'

const props = defineProps<{
	portfolioId: number
	position: PortfolioPositionResponseData | null
}>()

const isDialogOpen = defineModel<boolean>({ required: true })

const wholeShareGroups = ref<WholeShareGroupResponseData[]>([])
const isLoadingSegments = ref(false)
const didWholeShareSegmentsRequestFail = ref(false)

const effectivePosition = computed(() => props.position)
const groupHeaders = [
	{ title: 'Group Index', key: 'groupIndex' },
	{ title: 'Buy date', key: 'buyDate' },
	{ title: 'Sell date', key: 'sellDate' },
	{ title: 'Hold period', key: 'holdPeriodDays' },
	{ title: 'Weighted buy', key: 'weightedBuyPricePerShare', align: 'end' },
	{ title: 'Weighted sell', key: 'weightedSellPricePerShare', align: 'end' },
	{ title: 'Yield %', key: 'yieldPercent', align: 'end' },
	{ title: 'Yield', key: 'yieldAmount', align: 'end' },
	{ title: 'Tax-Free', key: 'isSellTaxable', align: 'center' },
] as const

const titleTicker = computed(() => {
	const ticker = effectivePosition.value?.ticker
	return ticker ? ` — ${ticker}` : ''
})

const narrowSegments = (bucket: WholeShareBucketResponseData | null): WholeShareSegmentResponseData[] => {
	if (!bucket) {
		return []
	}

	return bucket.segments as WholeShareSegmentResponseData[]
}

const sumBucketShares = (bucket: WholeShareBucketResponseData | null): number => {
	return narrowSegments(bucket).reduce((sum, segment) => sum + segment.numberOfShares, 0)
}

const formatDateValue = (value: string | null): string => {
	return value ? formatDate(value) : '—'
}

const formatHoldPeriodDays = (value: number | null): string => {
	return value === null ? '—' : `${value}`
}

const formatDecimalValue = (value: number | null): string => {
	return value === null ? '—' : formatDecimal(value)
}

const formatYieldPercent = (value: number | null): string => {
	if (value === null) {
		return '—'
	}

	const sign = value > 0 ? '+' : ''

	return `${sign}${formatDecimal(value)}%`
}

const formatYieldAmount = (value: number | null, buyBucket: WholeShareBucketResponseData | null, sellBucket: WholeShareBucketResponseData | null): string => {
	if (value === null) {
		return '—'
	}

	const sign = value > 0 ? '+' : ''
	const currencySymbol = sellBucket?.segments?.[0]?.currencySymbol ?? buyBucket?.segments?.[0]?.currencySymbol ?? ''

	return `${sign}${formatDecimal(value)}${currencySymbol ? ` ${currencySymbol}` : ''}`
}

const getYieldPercentTextClass = (value: number | null): string => {
	if (value === null || value === 0) {
		return ''
	}

	return value > 0 ? 'text-success' : 'text-error'
}

const getYieldAmountTextClass = (value: number | null): string => {
	if (value === null || value === 0) {
		return ''
	}

	return value > 0 ? 'text-success' : 'text-error'
}

const isHypotheticalSoldGroup = (group: WholeShareGroupResponseData): boolean => {
	return group.buyDate !== null && group.sellBucket === null && group.sellDate !== null
}

const getHypotheticalTextClass = (group: WholeShareGroupResponseData): string => {
	return isHypotheticalSoldGroup(group) ? 'text-info' : ''
}

const loadWholeShareSegments = async (): Promise<void> => {
	const position = effectivePosition.value
	if (!position) {
		return
	}

	isLoadingSegments.value = true
	didWholeShareSegmentsRequestFail.value = false

	try {
		const data = await fetchWholeShareSegments(props.portfolioId, position.securityId, position.currencyId)
		wholeShareGroups.value = data.groups as WholeShareGroupResponseData[]
	} catch (error) {
		console.error('Whole share segments fetch failed', error)
		toast.error('Something went wrong when loading whole share groups.')
		wholeShareGroups.value = []
		didWholeShareSegmentsRequestFail.value = true
	} finally {
		isLoadingSegments.value = false
	}
}

watch(
	() => [isDialogOpen.value, props.portfolioId, props.position?.securityId, props.position?.currencyId] as const,
	async ([isOpen]) => {
		if (!isOpen || !effectivePosition.value) {
			return
		}

		await loadWholeShareSegments()
	},
)
</script>

<style scoped>
.timeline-scroll {
	max-height: 260px;
	overflow-y: auto;
}
</style>
