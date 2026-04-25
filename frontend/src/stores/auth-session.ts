import { isAxiosError } from 'axios'
import { ref } from 'vue'
import axios from '@/services/axios'
import type { ApiSuccessResponse } from '@/types/api'
import type { AuthSessionData, AuthUserSessionResponseData, LoginUserResponseData } from '@/types/generated'

const HTTP_STATUS_UNAUTHORIZED = 401
const HTTP_STATUS_CSRF_TOKEN_MISMATCH = 419

const currentUser = ref<LoginUserResponseData | null>(null)
const currentSession = ref<AuthSessionData | null>(null)
const isAuthReady = ref(false)
let authInitializationRequest: Promise<LoginUserResponseData | null> | null = null

const setAuthPayload = (payload: AuthUserSessionResponseData | null): void => {
	currentUser.value = payload?.user ?? null
	currentSession.value = payload?.session ?? null
}

const fetchCurrentUser = async (): Promise<LoginUserResponseData | null> => {
	try {
		const { data } = await axios.get<ApiSuccessResponse<AuthUserSessionResponseData>>('/api/user')
		setAuthPayload(data.data ?? null)
	} catch (error) {
		const status = isAxiosError(error) ? error.response?.status : undefined

		// Only downgrade to guest for explicit unauthenticated responses.
		if (status === HTTP_STATUS_UNAUTHORIZED || status === HTTP_STATUS_CSRF_TOKEN_MISMATCH) {
			setAuthPayload(null)
		}
	}

	isAuthReady.value = true
	return currentUser.value
}

export const initializeAuthState = async (): Promise<LoginUserResponseData | null> => {
	if (isAuthReady.value) {
		return currentUser.value
	}

	if (authInitializationRequest === null) {
		authInitializationRequest = fetchCurrentUser().finally(() => {
			authInitializationRequest = null
		})
	}

	return authInitializationRequest
}

export const refreshAuthState = async (): Promise<LoginUserResponseData | null> => {
	isAuthReady.value = false
	authInitializationRequest = fetchCurrentUser().finally(() => {
		authInitializationRequest = null
	})
	return authInitializationRequest
}

export const clearAuthState = (): void => {
	setAuthPayload(null)
	isAuthReady.value = true
}

export const setAuthState = (payload: AuthUserSessionResponseData | null): void => {
	setAuthPayload(payload)
	isAuthReady.value = true
}

export const useAuthSession = () => ({
	currentUser,
	currentSession,
	isAuthReady,
	initializeAuthState,
	refreshAuthState,
	clearAuthState,
	setAuthState,
})
