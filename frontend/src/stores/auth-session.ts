import { ref } from 'vue'
import axios from '@/services/axios'
import type { ApiSuccessResponse } from '@/types/api'
import type { LoginUserResponseData } from '@/types/generated'

const currentUser = ref<LoginUserResponseData | null>(null)
const authReady = ref(false)
let authRequest: Promise<LoginUserResponseData | null> | null = null

const fetchCurrentUser = async (): Promise<LoginUserResponseData | null> => {
	try {
		const { data } = await axios.get<ApiSuccessResponse<LoginUserResponseData>>('/api/user')
		currentUser.value = data.data ?? null
	} catch {
		currentUser.value = null
	}

	authReady.value = true
	return currentUser.value
}

export const initializeAuthState = async (): Promise<LoginUserResponseData | null> => {
	if (authReady.value) {
		return currentUser.value
	}

	if (authRequest === null) {
		authRequest = fetchCurrentUser().finally(() => {
			authRequest = null
		})
	}

	return authRequest
}

export const refreshAuthState = async (): Promise<LoginUserResponseData | null> => {
	authReady.value = false
	authRequest = fetchCurrentUser().finally(() => {
		authRequest = null
	})
	return authRequest
}

export const clearAuthState = (): void => {
	currentUser.value = null
	authReady.value = true
}

export const setAuthUser = (user: LoginUserResponseData | null): void => {
	currentUser.value = user
	authReady.value = true
}

export const useAuthSession = () => ({
	currentUser,
	authReady,
	initializeAuthState,
	refreshAuthState,
	clearAuthState,
	setAuthUser,
})
