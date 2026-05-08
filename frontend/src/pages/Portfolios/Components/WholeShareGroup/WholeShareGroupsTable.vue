<template>
	<v-data-table class="whole-share-groups-table" :headers="groupHeaders" :items="groups" item-value="groupIndex" show-expand density="comfortable">
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
			<span>{{ formatNullableInteger(item.holdPeriodDays) }}</span>
		</template>
		<template #item.weightedBuyPricePerShare="{ item }">{{ formatNullableDecimal(item.weightedBuyPricePerShare) }}</template>
		<template #item.weightedSellPricePerShare="{ item }">{{ formatNullableDecimal(item.weightedSellPricePerShare) }}</template>
		<template #item.returnPercent="{ item }">
			<span :class="getGainLossTextClass(item.returnPercent)">
				{{ formatReturnPercent(item.returnPercent) }}
			</span>
		</template>
		<template #item.gainLossAmount="{ item }">
			<span :class="getGainLossTextClass(item.gainLossAmount)">
				{{ formatGainLossAmountFromBuckets(item.gainLossAmount, item.buyBucket, item.sellBucket) }}
			</span>
		</template>
		<template #item.isSellTaxable="{ item }">
			<div class="d-flex justify-center">
				<span v-if="item.isSellTaxable === null">—</span>
				<v-tooltip v-else location="top">
					<template #activator="{ props: tooltipProps }">
						<v-icon v-bind="tooltipProps" :color="item.isSellTaxable ? 'error' : 'success'" :icon="item.isSellTaxable ? 'mdi-alert-circle' : 'mdi-check-circle'" />
					</template>
					{{ item.isSellTaxable ? 'Taxable sell' : 'Tax-free sell' }}
				</v-tooltip>
			</div>
		</template>

		<template #expanded-row="{ columns, item }">
			<WholeShareGroupExpandedRow :columns-length="columns.length" :group="item" />
		</template>
	</v-data-table>
</template>

<script setup lang="ts">
import WholeShareGroupExpandedRow from './WholeShareGroupExpandedRow.vue'
import { formatDate, formatDateTime } from '@/format/date'
import { formatReturnPercent, getGainLossTextClass } from '@/format/gain-loss'
import { formatNullableDecimal, formatNullableInteger } from '@/format/number'
import { formatGainLossAmountFromBuckets } from '@/format/whole-share-group'
import type { WholeShareGroupResponseData } from '@/types/generated'

defineProps<{
	groups: WholeShareGroupResponseData[]
}>()

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
</script>

<style scoped>
:deep(.whole-share-groups-table th),
:deep(.whole-share-groups-table td) {
	white-space: nowrap;
}
</style>
