import type { WholeShareBucketResponseData } from '@/types/generated'
import { formatGainLossAmount } from './gain-loss'

export const getCurrencySymbolFromBuckets = (
	buyBucket: WholeShareBucketResponseData | null,
	sellBucket: WholeShareBucketResponseData | null,
): string => {
	return sellBucket?.segments?.[0]?.currencySymbol ?? buyBucket?.segments?.[0]?.currencySymbol ?? ''
}

export const formatGainLossAmountFromBuckets = (
	value: number | null,
	buyBucket: WholeShareBucketResponseData | null,
	sellBucket: WholeShareBucketResponseData | null,
): string => {
	const currencySymbol = getCurrencySymbolFromBuckets(buyBucket, sellBucket)

	return formatGainLossAmount(value, currencySymbol)
}
