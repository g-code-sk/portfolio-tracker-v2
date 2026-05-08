<template>
	<div class="d-flex align-start justify-space-between ga-4 flex-wrap">
		<v-card-title>Groups</v-card-title>
		<div class="d-flex flex-column flex-sm-row ga-4">
			<div class="text-sm-right">
				<div class="text-caption text-medium-emphasis">Realized Gain/Loss</div>
				<div :class="['text-subtitle-1 font-weight-medium', getGainLossTextClass(realizedGainLossAmount)]">
					{{ formatGainLossAmount(realizedGainLossAmount, currencySymbol) }}
				</div>
			</div>
			<div class="text-sm-right">
				<div class="text-caption text-medium-emphasis">Realized Return</div>
				<div :class="['text-subtitle-1 font-weight-medium', getGainLossTextClass(realizedReturnPercent)]">
					{{ formatReturnPercent(realizedReturnPercent) }}
				</div>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { formatGainLossAmount, formatReturnPercent, getGainLossTextClass } from '@/format/gain-loss'

type Props = {
	realizedGainLossAmount: number | null
	realizedReturnPercent: number | null
	currencySymbol: string
}

defineProps<Props>()
</script>
