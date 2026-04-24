<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<v-row>
				<v-col cols="12" md="8">
					<h1 class="text-h3 font-weight-bold mb-2">Dashboard Overview</h1>
					<p class="text-subtitle-1 text-medium-emphasis">Monitor portfolio snapshot data from your backend test endpoint.</p>
				</v-col>
				<v-col cols="12" md="4" class="d-flex justify-md-end align-center">
					<v-btn color="primary" prepend-icon="mdi-refresh" :loading="loading" @click="fetchTestData"> Refresh Data </v-btn>
				</v-col>
			</v-row>

			<v-alert v-if="error" class="mt-4" type="error" variant="tonal" title="Could not load data" :text="error" />

			<v-row v-if="loading && !response" class="mt-2">
				<v-col cols="12" class="d-flex justify-center py-10">
					<v-progress-circular indeterminate color="primary" size="56" />
				</v-col>
			</v-row>

			<template v-else-if="response">
				<v-row class="mt-2">
					<v-col cols="12" sm="6" lg="3">
						<v-card rounded="lg" elevation="1">
							<v-card-text>
								<p class="text-caption text-medium-emphasis">Status</p>
								<p class="text-h6 font-weight-bold text-capitalize">
									{{ response.status }}
								</p>
							</v-card-text>
						</v-card>
					</v-col>
					<v-col cols="12" sm="6" lg="3">
						<v-card rounded="lg" elevation="1">
							<v-card-text>
								<p class="text-caption text-medium-emphasis">Items</p>
								<p class="text-h6 font-weight-bold">{{ response.items.length }}</p>
							</v-card-text>
						</v-card>
					</v-col>
					<v-col cols="12" sm="6" lg="3">
						<v-card rounded="lg" elevation="1">
							<v-card-text>
								<p class="text-caption text-medium-emphasis">Total Value</p>
								<p class="text-h6 font-weight-bold">{{ formatCurrency(totalValue) }}</p>
							</v-card-text>
						</v-card>
					</v-col>
					<v-col cols="12" sm="6" lg="3">
						<v-card rounded="lg" elevation="1">
							<v-card-text>
								<p class="text-caption text-medium-emphasis">Average Price</p>
								<p class="text-h6 font-weight-bold">{{ formatCurrency(averagePrice) }}</p>
							</v-card-text>
						</v-card>
					</v-col>
				</v-row>

				<v-row class="mt-1">
					<v-col cols="12" lg="8">
						<v-card rounded="lg" elevation="1">
							<v-card-item>
								<v-card-title>Portfolio Items</v-card-title>
								<v-card-subtitle>
									Endpoint: <code>{{ endpoint }}</code>
								</v-card-subtitle>
							</v-card-item>
							<v-divider />
							<v-list lines="two">
								<v-list-item v-for="item in response.items" :key="item.id" :title="item.name" :subtitle="`ID: ${item.id}`">
									<template #append>
										<v-chip color="primary" variant="tonal">
											{{ formatCurrency(item.price) }}
										</v-chip>
									</template>
								</v-list-item>
							</v-list>
						</v-card>
					</v-col>
					<v-col cols="12" lg="4">
						<v-card rounded="lg" elevation="1" class="mb-4">
							<v-card-item title="Snapshot" subtitle="Latest sync details" />
							<v-divider />
							<v-card-text class="d-flex flex-column ga-3">
								<div>
									<p class="text-caption text-medium-emphasis mb-1">Message</p>
									<p class="text-body-1">{{ response.message }}</p>
								</div>
								<div>
									<p class="text-caption text-medium-emphasis mb-1">Last Updated</p>
									<p class="text-body-2">{{ refreshTime }}</p>
								</div>
							</v-card-text>
						</v-card>
						<v-card rounded="lg" elevation="1">
							<v-card-item title="Top Performing Item" />
							<v-divider />
							<v-card-text v-if="topItem">
								<p class="text-subtitle-1 font-weight-medium">{{ topItem.name }}</p>
								<p class="text-h6 font-weight-bold text-primary">
									{{ formatCurrency(topItem.price) }}
								</p>
							</v-card-text>
							<v-card-text v-else> No items available. </v-card-text>
						</v-card>
					</v-col>
				</v-row>
			</template>
		</v-container>
	</v-main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import axios from '../services/axios'
import type { TestApiResponseData, TestItemData } from '../types/generated'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000'

const loading = ref(false)
const error = ref('')
const response = ref<TestApiResponseData | null>(null)

const endpoint = computed(() => `${apiBaseUrl}/api/test-data`)
const refreshTime = computed(() => (response.value ? new Date(response.value.timestamp).toLocaleString() : '-'))
const totalValue = computed(() => (response.value ? response.value.items.reduce((sum, item) => sum + item.price, 0) : 0))
const averagePrice = computed(() => (response.value && response.value.items.length > 0 ? totalValue.value / response.value.items.length : 0))
const topItem = computed(() => {
	if (!response.value?.items.length) return null

	return response.value.items.reduce((currentTop: TestItemData, item: TestItemData) => (item.price > currentTop.price ? item : currentTop))
})

const formatCurrency = (value: number) =>
	new Intl.NumberFormat('en-US', {
		style: 'currency',
		currency: 'USD',
		maximumFractionDigits: 2,
	}).format(value)

const fetchTestData = async () => {
	loading.value = true
	error.value = ''

	try {
		const { data } = await axios.get<TestApiResponseData>('/api/test-data')
		response.value = data
	} catch (err) {
		error.value = err instanceof Error ? err.message : 'Unknown error'
	} finally {
		loading.value = false
	}
}

onMounted(fetchTestData)
</script>
