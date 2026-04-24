<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

type TestItem = {
  id: number
  name: string
  price: number
}

type TestApiResponse = {
  status: string
  message: string
  timestamp: string
  items: TestItem[]
}

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000'

const loading = ref(false)
const error = ref('')
const response = ref<TestApiResponse | null>(null)

const endpoint = computed(() => `${apiBaseUrl}/api/test-data`)

const fetchTestData = async () => {
  loading.value = true
  error.value = ''

  try {
    const res = await fetch(endpoint.value)

    if (!res.ok) {
      throw new Error(`Request failed with status ${res.status}`)
    }

    response.value = (await res.json()) as TestApiResponse
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unknown error'
  } finally {
    loading.value = false
  }
}

onMounted(fetchTestData)
</script>

<template>
  <main>
    <h1>Frontend ↔ Backend Test</h1>
    <p><strong>Endpoint:</strong> {{ endpoint }}</p>

    <button :disabled="loading" @click="fetchTestData">
      {{ loading ? 'Loading...' : 'Fetch test data' }}
    </button>

    <p v-if="error" style="color: #b91c1c; margin-top: 1rem">
      {{ error }}
    </p>

    <section v-else-if="response" style="margin-top: 1rem">
      <p><strong>Status:</strong> {{ response.status }}</p>
      <p><strong>Message:</strong> {{ response.message }}</p>
      <p><strong>Timestamp:</strong> {{ response.timestamp }}</p>

      <h2>Items</h2>
      <ul>
        <li v-for="item in response.items" :key="item.id">
          {{ item.name }} - ${{ item.price }}
        </li>
      </ul>
    </section>
  </main>
</template>
