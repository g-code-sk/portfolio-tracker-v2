<template>
	<AppDialog v-model="isDialogOpen" :size="920" close-text="Close">
		<template #title> Buy splits by whole share{{ titleTicker }} </template>

		<template #body>
			<v-progress-linear v-if="isLoadingSegments" class="mb-4" color="primary" height="6" indeterminate />

			<div v-else-if="!effectivePosition" class="text-medium-emphasis">No position selected.</div>

			<div v-else-if="effectivePosition && !didWholeShareSegmentsRequestFail && wholeShareBuckets.length === 0" class="text-medium-emphasis py-4 text-center">
				No buy transactions to split for this position.
			</div>

			<div v-else-if="effectivePosition && !didWholeShareSegmentsRequestFail" class="d-flex flex-column ga-4">
				<v-card v-for="bucket in wholeShareBuckets" :key="bucket.wholeShareBucketIndex" variant="outlined" rounded="lg">
					<v-card-title class="text-subtitle-1">
						Whole share {{ bucket.wholeShareBucketIndex + 1 }}
						<span class="text-medium-emphasis font-weight-regular"> · {{ formatDecimal(sumBucketShares(bucket)) }} shares toward 1.0</span>
					</v-card-title>
					<v-divider />
					<v-table density="compact">
						<thead>
							<tr>
								<th class="text-left">Date</th>
								<th class="text-left">Transaction id</th>
								<th class="text-left">External id</th>
								<th class="text-right">Shares</th>
								<th class="text-right">Price / share</th>
								<th class="text-right">Total</th>
								<th class="text-left">Currency</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="(segment, segmentIndex) in narrowSegments(bucket)"
								:key="`${bucket.wholeShareBucketIndex}-${segmentIndex}-${segment.sourceTransactionId}`"
							>
								<td>{{ formatDate(segment.executedAt) }}</td>
								<td>{{ segment.sourceTransactionId }}</td>
								<td>{{ segment.externalTransactionId }}</td>
								<td class="text-right">{{ formatDecimal(segment.numberOfShares) }}</td>
								<td class="text-right">{{ formatDecimal(segment.pricePerShare) }}</td>
								<td class="text-right">{{ formatDecimal(segment.totalAmount) }}</td>
								<td>{{ segment.currencySymbol }}</td>
							</tr>
						</tbody>
					</v-table>
				</v-card>
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
import { fetchWholeShareBuySegments } from '@/services/transaction'
import type { PortfolioPositionResponseData, WholeShareBuyBucketResponseData, WholeShareBuySegmentResponseData } from '@/types/generated'

const props = defineProps<{
	portfolioId: number
	position: PortfolioPositionResponseData | null
}>()

const isDialogOpen = defineModel<boolean>({ required: true })

const wholeShareBuckets = ref<WholeShareBuyBucketResponseData[]>([])
const isLoadingSegments = ref(false)
const didWholeShareSegmentsRequestFail = ref(false)

const effectivePosition = computed(() => props.position)

const titleTicker = computed(() => {
	const ticker = effectivePosition.value?.ticker
	return ticker ? ` — ${ticker}` : ''
})

const narrowSegments = (bucket: WholeShareBuyBucketResponseData): WholeShareBuySegmentResponseData[] => {
	return bucket.segments as WholeShareBuySegmentResponseData[]
}

const sumBucketShares = (bucket: WholeShareBuyBucketResponseData): number => {
	return narrowSegments(bucket).reduce((sum, segment) => sum + segment.numberOfShares, 0)
}

const loadWholeShareSegments = async (): Promise<void> => {
	const position = effectivePosition.value
	if (!position) {
		return
	}

	isLoadingSegments.value = true
	didWholeShareSegmentsRequestFail.value = false

	try {
		const data = await fetchWholeShareBuySegments(props.portfolioId, position.securityId, position.currencyId)
		wholeShareBuckets.value = data.buckets as WholeShareBuyBucketResponseData[]
	} catch (error) {
		console.error('Whole share buy segments fetch failed', error)
		toast.error('Something went wrong when loading whole share buy splits.')
		wholeShareBuckets.value = []
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
