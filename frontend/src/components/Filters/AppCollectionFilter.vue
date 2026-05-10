<template>
	<div class="d-flex flex-column flex-sm-row flex-wrap ga-4">
		<AppTextField
			v-for="(def, index) in props.filterData"
			:key="index"
			v-model="queryStrings[index]"
			:label="def.label ?? defaultStringFilterLabel(def)"
			clearable
		/>
	</div>
</template>

<script setup lang="ts" generic="T extends Record<string, unknown>">
import { ref, watch } from 'vue'
import AppTextField from '@/components/Inputs/AppTextField.vue'
import { FilterType, type FilterDefinition, type StringFilterDefinition } from '@/types/filter'

const props = defineProps<{
	items: T[]
	filterData: FilterDefinition<T>[]
}>()

const filteredModel = defineModel<T[]>({ required: true })

const queryStrings = ref<string[]>([])

watch(
	() => props.filterData.length,
	(length) => {
		const next = [...queryStrings.value]
		while (next.length < length) {
			next.push('')
		}
		next.length = length
		queryStrings.value = next
	},
	{ immediate: true },
)

function defaultStringFilterLabel(def: StringFilterDefinition<T>): string {
	return def.keys.join(', ')
}

function rowMatchesStringFilter(row: T, def: StringFilterDefinition<T>, needle: string): boolean {
	return def.keys.some((key) => {
		const value = row[key]
		if (value === null || value === undefined) {
			return false
		}

		return String(value).toLowerCase().includes(needle)
	})
}

function applyFilters(source: T[]): T[] {
	let result = [...source]

	for (let index = 0; index < props.filterData.length; index++) {
		const definition = props.filterData[index]
		const rawQuery = queryStrings.value[index] ?? ''
		const needle = rawQuery.trim().toLowerCase()

		if (needle === '') {
			continue
		}

		if (definition.type === FilterType.String) {
			result = result.filter((row) => rowMatchesStringFilter(row, definition, needle))
		}
	}

	return result
}

watch(
	[() => props.items, queryStrings, () => props.filterData],
	() => {
		filteredModel.value = applyFilters(props.items)
	},
	{ deep: true, immediate: true },
)
</script>
