import a from 'axios'

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

async function ensureCsrfCookie(): Promise<void> {
	if (csrfInitialized) {
		return
	}
	await axios.get('/sanctum/csrf-cookie')
	csrfInitialized = true
}

axios.interceptors.request.use(async (config) => {
	const m = config.method?.toLowerCase()
	if (m && ['post', 'put', 'patch', 'delete'].includes(m)) {
		await ensureCsrfCookie()
	}
	return config
})

axios.interceptors.response.use(
	(response) => response,
	async (error) => {
		const status = error.response?.status
		const originalConfig = error.config as (typeof error.config & { _retry?: boolean }) | undefined
		if (status === 419 && originalConfig && !originalConfig._retry) {
			originalConfig._retry = true
			csrfInitialized = false
			await ensureCsrfCookie()
			return axios.request(originalConfig)
		}
		return Promise.reject(error)
	}
)

export default axios
