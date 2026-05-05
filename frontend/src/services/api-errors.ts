import { isAxiosError } from 'axios'
import type { ApiErrorResponse } from '@/types/api'

export type FieldErrors<TInput extends Record<string, unknown>> = Partial<Record<keyof TInput, string[]>>

export const getFieldErrors = <TInput extends Record<string, unknown>>(error: unknown): FieldErrors<TInput> => {
	if (!isAxiosError<ApiErrorResponse<Extract<keyof TInput, string>>>(error)) {
		return {}
	}

	return (error.response?.data?.errors as FieldErrors<TInput>) ?? {}
}

/** Uses Laravel validation `errors` when present (422), then top-level `message`, then fallback. */
export const getApiUserFacingMessage = (error: unknown, fallbackMessage: string): string => {
	if (!isAxiosError<ApiErrorResponse>(error)) {
		return error instanceof Error ? error.message : fallbackMessage
	}

	const data = error.response?.data

	if (!data) {
		return fallbackMessage
	}

	const errors = data.errors

	if (errors && typeof errors === 'object') {
		const parts: string[] = []

		for (const messages of Object.values(errors)) {
			if (Array.isArray(messages)) {
				for (const msg of messages) {
					if (typeof msg === 'string' && msg !== '') {
						parts.push(msg)
					}
				}
			}
		}

		if (parts.length > 0) {
			return parts.join(' ')
		}
	}

	if (typeof data.message === 'string' && data.message !== '') {
		return data.message
	}

	return fallbackMessage
}
