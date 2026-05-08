export const formatDecimal = (value: number, fractionDigits: number = 2): string => {
	return value.toFixed(fractionDigits)
}

export const formatNullableDecimal = (value: number | null, fractionDigits: number = 2): string => {
	return value === null ? '—' : formatDecimal(value, fractionDigits)
}

export const formatNullableInteger = (value: number | null): string => {
	return value === null ? '—' : `${value}`
}
