<template>
	<v-app-bar color="white" elevation="1">
		<v-app-bar-title class="font-weight-bold cursor-pointer" role="link" @click="goHome"> Portfolio Tracker </v-app-bar-title>
		<v-spacer />
		<v-btn :to="{ name: 'login' }" color="primary" variant="tonal"> Log in </v-btn>
		<v-btn color="primary" variant="flat" :to="{ name: 'register' }"> Register </v-btn>
	</v-app-bar>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const goHome = () => {
	void router.push({ name: 'home' })
}
</script>
