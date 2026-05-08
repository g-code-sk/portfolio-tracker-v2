<template>
	<v-breadcrumbs class="px-0 mb-4" :items="items" density="comfortable">
		<template #divider>
			<v-icon icon="mdi-chevron-right" size="small" />
		</template>
	</v-breadcrumbs>
</template>

<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

export type AppBreadcrumbItem = {
	title: string
	to?: RouteLocationRaw
	disabled?: boolean
}

defineProps<{
	items: AppBreadcrumbItem[]
}>()
</script>
