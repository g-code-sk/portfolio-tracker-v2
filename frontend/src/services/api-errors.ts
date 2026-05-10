import { isAxiosError } from 'axios'
import type { ApiErrorResponse } from '@/types/api'

export type FieldErrors<TInput extends Record<string, unknown>> = Partial<Record<keyof TInput, string[]>>

export const getFieldErrors = <TInput extends Record<string, unknown>>(error: unknown): FieldErrors<TInput> => {
	if (!isAxiosError<ApiErrorResponse<Extract<keyof TInput, string>>>(error)) {
		return {}
	}

	return (error.response?.data?.errors as FieldErrors<TInput>) ?? {}
}

const isNonEmptyString = (value: unknown): value is string => typeof value === 'string' && value !== ''

/**
 * Laravel validation `errors` object: field keys → string messages or arrays of strings.
 */
const collectValidationErrorMessages = (errors: unknown): string[] => {
	if (!errors || typeof errors !== 'object') {
		return []
	}

	const parts: string[] = []

	for (const messages of Object.values(errors)) {
		if (!Array.isArray(messages)) {
			continue
		}

		for (const msg of messages) {
			if (isNonEmptyString(msg)) {
				parts.push(msg)
			}
		}
	}

	return parts
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

	const validationMessages = collectValidationErrorMessages(data.errors)

	if (validationMessages.length > 0) {
		return validationMessages.join(' ')
	}

	if (isNonEmptyString(data.message)) {
		return data.message
	}

	return fallbackMessage
}
