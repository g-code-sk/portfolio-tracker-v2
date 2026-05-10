import a from 'axios'
import { isCsrfMismatchStatus } from '@/services/http-response-guards'

// In Vite dev, default to same-origin (empty) so requests go through the dev-server proxy
// to Laravel — avoids CORS for /api and /sanctum. Set VITE_API_BASE_URL for a remote API.
const apiBaseUrl =
	import.meta.env.VITE_API_BASE_URL ?? (import.meta.env.DEV ? '' : 'http://127.0.0.1:8000')

const axios = a.create({
	baseURL: apiBaseUrl,
	withCredentials: true,
	withXSRFToken: true,
	xsrfCookieName: 'XSRF-TOKEN',
	xsrfHeaderName: 'X-XSRF-TOKEN',
	headers: {
		Accept: 'application/json',
		'Content-Type': 'application/json',
		'X-Requested-With': 'XMLHttpRequest',
	},
})

let csrfInitialized = false

const MUTATING_METHODS = new Set(['post', 'put', 'patch', 'delete'])

export const isMutatingMethod = (method: string | undefined): boolean =>
	method !== undefined && MUTATING_METHODS.has(method.toLowerCase())

async function ensureCsrfCookie(): Promise<void> {
	if (csrfInitialized) {
		return
	}
	await axios.get('/sanctum/csrf-cookie')
	csrfInitialized = true
}

axios.interceptors.request.use(async (config) => {
	if (isMutatingMethod(config.method)) {
		await ensureCsrfCookie()
	}
	return config
})

axios.interceptors.response.use(
	(response) => response,
	async (error) => {
		const status = error.response?.status
		const originalConfig = error.config as (typeof error.config & { _retry?: boolean }) | undefined
		if (isCsrfMismatchStatus(status) && originalConfig && !originalConfig._retry) {
			originalConfig._retry = true
			csrfInitialized = false
			await ensureCsrfCookie()
			return axios.request(originalConfig)
		}
		return Promise.reject(error)
	}
)

export default axios
