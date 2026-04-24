import a from 'axios'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000'

const axios = a.create({
	baseURL: apiBaseUrl,
	headers: {
		Accept: 'application/json',
		'Content-Type': 'application/json',
	},
})

export default axios
