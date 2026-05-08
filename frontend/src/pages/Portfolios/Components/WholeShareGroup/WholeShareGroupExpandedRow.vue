<template>
	<tr>
		<td :colspan="columnsLength" class="bg-surface">
			<div class="pa-4">
				<v-row>
					<v-col cols="12" md="6">
						<v-card variant="outlined" rounded="lg">
							<v-card-title class="text-subtitle-2">Buys · {{ formatDecimal(sumBucketShares(group.buyBucket)) }} of 1.0</v-card-title>
							<v-divider />
							<v-card-text class="py-2">
								<div v-if="narrowSegments(group.buyBucket).length === 0" class="text-medium-emphasis">No buy segments in this group.</div>
								<div v-else class="timeline-scroll">
									<v-timeline density="compact" side="end" truncate-line="both">
										<WholeShareSegmentTimelineItem
											v-for="(segment, segmentIndex) in narrowSegments(group.buyBucket)"
											:key="`buy-${group.groupIndex}-${segmentIndex}-${segment.sourceTransactionId}`"
											:segment="segment"
											dot-color="success"
										/>
									</v-timeline>
								</div>
							</v-card-text>
						</v-card>
					</v-col>

					<v-col cols="12" md="6">
						<v-card variant="outlined" rounded="lg">
							<v-card-title class="text-subtitle-2">Sells · {{ formatDecimal(sumBucketShares(group.sellBucket)) }} of 1.0</v-card-title>
							<v-divider />
							<v-card-text class="py-2">
								<div v-if="narrowSegments(group.sellBucket).length === 0" class="text-medium-emphasis">No sell segments in this group.</div>
								<div v-else class="timeline-scroll">
									<v-timeline density="compact" side="end" truncate-line="both">
										<WholeShareSegmentTimelineItem
											v-for="(segment, segmentIndex) in narrowSegments(group.sellBucket)"
											:key="`sell-${group.groupIndex}-${segmentIndex}-${segment.sourceTransactionId}`"
											:segment="segment"
											dot-color="error"
										/>
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

<script setup lang="ts">
import { formatDecimal } from '@/format/number'
import WholeShareSegmentTimelineItem from './WholeShareSegmentTimelineItem.vue'
import type { WholeShareBucketResponseData, WholeShareGroupResponseData, WholeShareSegmentResponseData } from '@/types/generated'

defineProps<{
	columnsLength: number
	group: WholeShareGroupResponseData
}>()

const narrowSegments = (bucket: WholeShareBucketResponseData | null): WholeShareSegmentResponseData[] => {
	if (!bucket) {
		return []
	}

	return bucket.segments as WholeShareSegmentResponseData[]
}

const sumBucketShares = (bucket: WholeShareBucketResponseData | null): number => {
	return narrowSegments(bucket).reduce((sum, segment) => sum + segment.numberOfShares, 0)
}
</script>

<style scoped>
.timeline-scroll {
	max-height: 260px;
	overflow-y: auto;
}
</style>
