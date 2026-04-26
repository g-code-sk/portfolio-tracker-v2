<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<v-row justify="center">
				<v-col cols="12" md="8" lg="6">
					<v-card rounded="lg" elevation="1">
						<v-card-item>
							<v-card-title>Log in</v-card-title>
							<v-card-subtitle>Enter your email and password to continue.</v-card-subtitle>
						</v-card-item>
						<v-divider />
						<v-card-text>
							<v-form ref="formRef" class="d-flex flex-column ga-3" @submit.prevent="submit">
								<LoginEmailField v-model="form.email" :error-messages="fieldErrors.email" :rules="rules.email" />
								<LoginPasswordField v-model="form.password" :error-messages="fieldErrors.password" :rules="rules.password" />
								<v-btn type="submit" color="primary" :loading="isLoading" block> Log in </v-btn>
							</v-form>
						</v-card-text>
					</v-card>
				</v-col>
			</v-row>
		</v-container>
	</v-main>
</template>

<script setup lang="ts">
import { isAxiosError } from 'axios'
import { reactive, ref } from 'vue'
import { toast } from 'vue3-toastify'
import { useRouter } from 'vue-router'
import LoginEmailField from '@/pages/Auth/Components/LoginEmailField.vue'
import LoginPasswordField from '@/pages/Auth/Components/LoginPasswordField.vue'
import axios from '@/services/axios'
import { setAuthState } from '@/stores/auth-session'
import { getFieldErrors, type FieldErrors } from '@/services/api-errors'
import { validateVuetifyForm, VUETIFY_FORM_CLIENT_VALIDATION_MESSAGE } from '@/services/vuetify-form'
import { email, minLength, required } from '@/services/rules'
import type { ApiErrorResponse, ApiSuccessResponse } from '@/types/api'
import type { AuthUserSessionResponseData, LoginUserPayloadData } from '@/types/generated'
import type { VForm } from 'vuetify/components'

const router = useRouter()
const isLoading = ref(false)
const fieldErrors = ref<FieldErrors<LoginUserPayloadData>>({})
const formRef = ref<VForm | null>(null)

const form = reactive<LoginUserPayloadData>({
	email: '',
	password: '',
})

const rules = {
	email: [required, email],
	password: [required, minLength(8)],
}

const submit = async () => {
	if (!(await validateVuetifyForm(formRef))) {
		return
	}

	isLoading.value = true
	fieldErrors.value = {}

	try {
		const { data } = await axios.post<ApiSuccessResponse<AuthUserSessionResponseData>>('/api/login', form)
		toast.success(data.message)
		formRef.value?.reset()
		setAuthState(data.data ?? null)

		await router.push({ name: 'portfolios' })
	} catch (err) {
		if (isAxiosError<ApiErrorResponse<keyof LoginUserPayloadData>>(err) && err.response?.status === 401) {
			toast.error(err.response.data?.message ?? 'Invalid email or password.')
		} else {
			fieldErrors.value = getFieldErrors<LoginUserPayloadData>(err)

			if (Object.keys(fieldErrors.value).length === 0) {
				toast.error('Something went wrong while logging in.')
			} else {
				toast.error(VUETIFY_FORM_CLIENT_VALIDATION_MESSAGE)
			}
		}
	} finally {
		isLoading.value = false
	}
}
</script>
