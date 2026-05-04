<template>
	<RouterLink class="text-primary text-decoration-none font-weight-medium" :to="to">
		<slot />
	</RouterLink>
</template>

<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

defineProps<{
	to: RouteLocationRaw
}>()
</script>
