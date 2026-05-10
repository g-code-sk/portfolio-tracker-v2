<template>
	<div class="d-flex flex-column flex-sm-row ga-4">
		<AppSelectInput v-model="selectedBuyYear" :items="buyYearItems" item-title="label" item-value="value" label="Buy year" clearable />
		<AppSelectInput v-model="selectedSellYear" :items="sellYearItems" item-title="label" item-value="value" label="Sell year" clearable />
	</div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import AppSelectInput from '@/components/Inputs/AppSelectInput.vue'
import { getYearFromDateString } from '@/format/date'
import type { WholeShareGroupResponseData } from '@/types/generated'

type YearItem = {
	label: string
	value: number | null
}

const props = withDefaults(
	defineProps<{
		groups?: WholeShareGroupResponseData[]
	}>(),
	{
		groups: () => [],
	},
)

const selectedBuyYear = defineModel<number | null>('selectedBuyYear', { required: true })
const selectedSellYear = defineModel<number | null>('selectedSellYear', { required: true })

const sortedYearsFromGroups = (
	groups: WholeShareGroupResponseData[],
	getDateString: (group: WholeShareGroupResponseData) => string | null | undefined,
): number[] => {
	const yearSet = new Set<number>()

	for (const group of groups) {
		const year = getYearFromDateString(getDateString(group))

		if (year !== null) {
			yearSet.add(year)
		}
	}

	return Array.from(yearSet).sort((a, b) => b - a)
}

const shouldClearYearSelection = (selected: number | null, available: number[]): boolean =>
	selected !== null && !available.includes(selected)

const availableBuyYears = computed<number[]>(() => sortedYearsFromGroups(props.groups, (group) => group.buyDate))

const availableSellYears = computed<number[]>(() => sortedYearsFromGroups(props.groups, (group) => group.sellDate))

const buyYearItems = computed<YearItem[]>(() => {
	const allYearsItem: YearItem = { label: 'All years', value: null }
	const yearItems = availableBuyYears.value.map((year) => ({ label: String(year), value: year }))

	return [allYearsItem, ...yearItems]
})

const sellYearItems = computed<YearItem[]>(() => {
	const allYearsItem: YearItem = { label: 'All years', value: null }
	const yearItems = availableSellYears.value.map((year) => ({ label: String(year), value: year }))

	return [allYearsItem, ...yearItems]
})

watch(
	() => props.groups,
	() => {
		if (shouldClearYearSelection(selectedBuyYear.value, availableBuyYears.value)) {
			selectedBuyYear.value = null
		}

		if (shouldClearYearSelection(selectedSellYear.value, availableSellYears.value)) {
			selectedSellYear.value = null
		}
	},
)
</script>
