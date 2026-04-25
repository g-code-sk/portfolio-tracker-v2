<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-8">
			<v-row justify="center">
				<v-col cols="12" md="8" lg="6">
					<v-card rounded="lg" elevation="1">
						<v-card-item>
							<v-card-title>Create your account</v-card-title>
							<v-card-subtitle>After you register, you will be taken to the dashboard.</v-card-subtitle>
						</v-card-item>
						<v-divider />
						<v-card-text>
							<v-form ref="formRef" class="d-flex flex-column ga-3" @submit.prevent="submit">
								<AppTextField v-model="form.name" label="Name" :error-messages="fieldErrors.name" :rules="rules.name" required />
								<LoginEmailField v-model="form.email" autocomplete="email" :error-messages="fieldErrors.email" :rules="rules.email" />
								<LoginPasswordField v-model="form.password" autocomplete="new-password" :error-messages="fieldErrors.password" :rules="rules.password" />
								<LoginPasswordField
									v-model="form.password_confirmation"
									label="Confirm Password"
									autocomplete="new-password"
									:error-messages="fieldErrors.password_confirmation"
									:rules="rules.password_confirmation"
								/>
								<v-btn type="submit" color="primary" :loading="isLoading" block> Register </v-btn>
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
import { useRouter } from 'vue-router'
import AppTextField from '@/components/AppTextField.vue'
import LoginEmailField from '@/pages/Auth/Components/LoginEmailField.vue'
import LoginPasswordField from '@/pages/Auth/Components/LoginPasswordField.vue'
import axios from '@/services/axios'
import { setAuthState } from '@/stores/auth-session'
import { getFieldErrors, type FieldErrors } from '@/services/api-errors'
import { validateVuetifyForm } from '@/services/vuetify-form'
import { email, matches, minLength, required } from '@/services/rules'
import type { ApiSuccessResponse } from '@/types/api'
import type { AuthUserSessionResponseData, RegisterUserPayloadData } from '@/types/generated'
import type { VForm } from 'vuetify/components'

const router = useRouter()
const isLoading = ref(false)
const fieldErrors = ref<FieldErrors<RegisterUserPayloadData>>({})
const formRef = ref<VForm | null>(null)

const form = reactive<RegisterUserPayloadData>({
	name: '',
	email: '',
	password: '',
	password_confirmation: '',
})

const createRegisterRules = (getPassword: () => string) => ({
	name: [required],
	email: [required, email],
	password: [required, minLength(8)],
	password_confirmation: [required, matches(getPassword)],
})

const rules = createRegisterRules(() => form.password)

const submit = async () => {
	if (!(await validateVuetifyForm(formRef))) {
		return
	}

	isLoading.value = true
	fieldErrors.value = {}

	try {
		const { data } = await axios.post<ApiSuccessResponse<AuthUserSessionResponseData>>('/api/register', form)

		toast.success(data.message)
		formRef.value?.reset()
		setAuthState(data.data ?? null)

		await router.push({ name: 'dashboard' })
	} catch (err) {
		fieldErrors.value = getFieldErrors<RegisterUserPayloadData>(err)
		toast.error('Something went wrong while creating your account.')
	} finally {
		isLoading.value = false
	}
}
</script>
