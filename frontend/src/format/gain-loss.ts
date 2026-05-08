import { formatDecimal } from './number'

export const formatGainLossAmount = (value: number | null, currencySymbol?: string): string => {
	if (value === null) {
		return '—'
	}

	const sign = value > 0 ? '+' : ''

	return `${sign}${formatDecimal(value)}${currencySymbol ? ` ${currencySymbol}` : ''}`
}

export const formatReturnPercent = (value: number | null): string => {
	if (value === null) {
		return '—'
	}

	const sign = value > 0 ? '+' : ''

	return `${sign}${formatDecimal(value)}%`
}

export const getGainLossTextClass = (value: number | null): string => {
	if (value === null || value === 0) {
		return ''
	}

	return value > 0 ? 'text-success' : 'text-error'
}
