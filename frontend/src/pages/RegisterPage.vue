<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<v-row justify="center">
				<v-col cols="12" md="8" lg="6">
					<v-card rounded="lg" elevation="1">
						<v-card-item>
							<v-card-title>Create your account</v-card-title>
							<v-card-subtitle>Registration ends at success, without login.</v-card-subtitle>
						</v-card-item>
						<v-divider />
						<v-card-text>
							<v-card v-if="registeredUser" class="mb-4" variant="tonal" color="info">
								<v-card-item title="Registered User (Test Output)" />
								<v-card-text>
									<p><strong>ID:</strong> {{ registeredUser.id }}</p>
									<p><strong>Name:</strong> {{ registeredUser.name }}</p>
									<p><strong>Email:</strong> {{ registeredUser.email }}</p>
								</v-card-text>
							</v-card>
							<v-form class="d-flex flex-column" @submit.prevent="submit">
								<v-text-field v-model="form.name" label="Name" variant="outlined" :error-messages="fieldErrors.name ?? []" required />
								<v-text-field v-model="form.email" label="Email" type="email" variant="outlined" :error-messages="fieldErrors.email ?? []" required />
								<v-text-field v-model="form.password" label="Password" type="password" variant="outlined" :error-messages="fieldErrors.password ?? []" required />
								<v-text-field
									v-model="form.password_confirmation"
									label="Confirm Password"
									type="password"
									variant="outlined"
									:error-messages="fieldErrors.password_confirmation ?? []"
									required
								/>
								<v-btn type="submit" color="primary" :loading="loading" block> Register </v-btn>
							</v-form>
						</v-card-text>
					</v-card>
				</v-col>
			</v-row>
		</v-container>
	</v-main>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { toast } from 'vue3-toastify'
import axios from '../services/axios'
import { getFieldErrors, type FieldErrors } from '../services/api-errors'
import type { ApiSuccessResponse } from '../types/api'
import type { RegisterUserPayloadData, RegisterUserResponseData } from '../types/generated'

const loading = ref(false)
const fieldErrors = ref<FieldErrors<RegisterUserPayloadData>>({})
const registeredUser = ref<RegisterUserResponseData | null>(null)

const form = reactive<RegisterUserPayloadData>({
	name: '',
	email: '',
	password: '',
	password_confirmation: '',
})

const resetForm = () => {
	form.name = ''
	form.email = ''
	form.password = ''
	form.password_confirmation = ''
}

const submit = async () => {
	loading.value = true
	fieldErrors.value = {}
	registeredUser.value = null

	try {
		const { data } = await axios.post<ApiSuccessResponse<RegisterUserResponseData>>('/api/register', form)

		toast.success(data.message)
		registeredUser.value = data.data ?? null
		resetForm()
	} catch (err) {
		fieldErrors.value = getFieldErrors<RegisterUserPayloadData>(err)
		toast.error('Something went wrong while creating your account.')
	} finally {
		loading.value = false
	}
}
</script>
