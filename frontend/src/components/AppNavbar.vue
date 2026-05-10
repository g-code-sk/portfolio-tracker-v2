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
			<v-btn :to="{ name: 'login' }" color="primary"> Log in </v-btn>
			<v-btn color="primary" :to="{ name: 'register' }"> Register </v-btn>
		</template>
	</v-app-bar>
</template>

<script setup lang="ts">
import { isAxiosError } from 'axios'
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import { isAuthOrCsrfMismatchStatus } from '@/services/http-response-guards'
import { useAuthSession } from '@/stores/auth-session'
import type { ApiSuccessResponse } from '@/types/api'
import type { AuthUserSessionResponseData } from '@/types/generated'

const router = useRouter()
const isLoggingOut = ref(false)
const { currentUser, clearAuthState } = useAuthSession()

const goHome = () => {
	void router.push({ name: 'home' })
}

const logout = async () => {
	isLoggingOut.value = true

	try {
		const { data } = await axios.post<ApiSuccessResponse<AuthUserSessionResponseData>>('/api/logout')
		toast.success(data.message)
		clearAuthState()
		await router.push({ name: 'login' })
	} catch (error) {
		if (isAxiosError(error) && isAuthOrCsrfMismatchStatus(error.response?.status)) {
			clearAuthState()
			await router.push({ name: 'login' })
			return
		}

		toast.error('Something went wrong while logging out.')
	} finally {
		isLoggingOut.value = false
	}
}
</script>
