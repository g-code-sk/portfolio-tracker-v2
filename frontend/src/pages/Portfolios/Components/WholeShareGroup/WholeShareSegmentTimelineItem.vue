<template>
	<v-timeline-item :dot-color="dotColor" size="small">
		<div class="text-body-2">
			<span :title="formatDateTime(segment.executedAt)">{{ formatDate(segment.executedAt) }}</span>
			· #{{ segment.sourceTransactionId }}
		</div>
		<div class="text-body-2">
			{{ formatDecimal(segment.numberOfShares) }} shares @ {{ formatDecimal(segment.pricePerShare) }} ·
			{{ formatDecimal(segment.totalAmount) }} {{ segment.currencySymbol }}
		</div>
		<div class="text-caption text-medium-emphasis">External: {{ segment.externalTransactionId }}</div>
	</v-timeline-item>
</template>

<script setup lang="ts">
import { formatDate, formatDateTime } from '@/format/date'
import { formatDecimal } from '@/format/number'
import type { WholeShareSegmentResponseData } from '@/types/generated'

defineProps<{
	segment: WholeShareSegmentResponseData
	dotColor: 'success' | 'error'
}>()
</script>
