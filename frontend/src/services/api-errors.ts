import { isAxiosError } from 'axios'
import type { ApiErrorResponse } from '@/types/api'

export type FieldErrors<TInput extends Record<string, unknown>> = Partial<Record<keyof TInput, string[]>>

export const getFieldErrors = <TInput extends Record<string, unknown>>(error: unknown): FieldErrors<TInput> => {
	if (!isAxiosError<ApiErrorResponse<Extract<keyof TInput, string>>>(error)) {
		return {}
	}

	return (error.response?.data?.errors as FieldErrors<TInput>) ?? {}
}
