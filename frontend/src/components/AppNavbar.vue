<template>
	<v-app-bar color="white" elevation="1">
		<v-app-bar-title class="font-weight-bold cursor-pointer" role="link" @click="goHome"> Portfolio Tracker </v-app-bar-title>
		<v-spacer />
		<template v-if="currentUser">
			<v-menu>
				<template #activator="{ props }">
					<v-btn color="primary" variant="text" v-bind="props">{{ currentUser.name }}</v-btn>
				</template>
				<v-list>
					<v-list-item :title="currentUser.email" />
					<v-divider />
					<v-list-item title="Logout" :disabled="isLoggingOut" @click="logout" />
				</v-list>
			</v-menu>
		</template>
		<template v-else>
			<v-btn :to="{ name: 'login' }" color="primary" variant="tonal"> Log in </v-btn>
			<v-btn color="primary" variant="flat" :to="{ name: 'register' }"> Register </v-btn>
		</template>
	</v-app-bar>
</template>

<script setup lang="ts">
import { isAxiosError } from 'axios'
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import { useAuthSession } from '@/stores/auth-session'
import type { ApiSuccessResponse } from '@/types/api'

const router = useRouter()
const isLoggingOut = ref(false)
const { currentUser, clearAuthState, refreshAuthState } = useAuthSession()

const goHome = () => {
	void router.push({ name: 'home' })
}

const logout = async () => {
	isLoggingOut.value = true

	try {
		const { data } = await axios.post<ApiSuccessResponse<null>>('/api/logout')
		toast.success(data.message)
		clearAuthState()
		await router.push({ name: 'login' })
	} catch (error) {
		const user = await refreshAuthState()

		if (isAxiosError(error) && error.response?.status === 401) {
			clearAuthState()
			await router.push({ name: 'login' })
			return
		}

		if (user === null) {
			await router.push({ name: 'login' })
		}

		toast.error('Something went wrong while logging out.')
	} finally {
		isLoggingOut.value = false
	}
}

</script>
