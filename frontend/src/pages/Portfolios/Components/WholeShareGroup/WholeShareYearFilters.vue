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

const availableBuyYears = computed<number[]>(() => {
	const yearSet = new Set<number>()

	for (const group of props.groups) {
		const buyYear = getYearFromDateString(group.buyDate)
		if (buyYear !== null) {
			yearSet.add(buyYear)
		}
	}

	return Array.from(yearSet).sort((a, b) => b - a)
})

const availableSellYears = computed<number[]>(() => {
	const yearSet = new Set<number>()

	for (const group of props.groups) {
		const sellYear = getYearFromDateString(group.sellDate)
		if (sellYear !== null) {
			yearSet.add(sellYear)
		}
	}

	return Array.from(yearSet).sort((a, b) => b - a)
})

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
		if (selectedBuyYear.value !== null && !availableBuyYears.value.includes(selectedBuyYear.value)) {
			selectedBuyYear.value = null
		}

		if (selectedSellYear.value !== null && !availableSellYears.value.includes(selectedSellYear.value)) {
			selectedSellYear.value = null
		}
	},
)
</script>
