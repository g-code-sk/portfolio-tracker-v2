<template>
	<v-app-bar color="white" elevation="1">
		<v-app-bar-title class="font-weight-bold">Portfolio Tracker</v-app-bar-title>
		<v-spacer />
		<v-btn color="primary" variant="flat" @click="goToRegister"> Register </v-btn>
	</v-app-bar>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const goToRegister = () => {
	void router.push({ name: 'register' })
}
</script>
