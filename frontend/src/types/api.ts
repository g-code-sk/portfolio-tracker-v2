export type ApiSuccessResponse<TData> = {
	message: string
	data?: TData
}

export type ApiErrorResponse<TField extends string = string> = {
	message: string
	errors?: Partial<Record<TField, string[]>>
}
