import type { Ref } from 'vue'
import { toast } from 'vue3-toastify'
import type { VForm } from 'vuetify/components'

export const VUETIFY_FORM_CLIENT_VALIDATION_MESSAGE = 'Please double check form fields and correct any errors'

/**
 * Runs Vuetify `<v-form>` validation; shows a toast when the form is invalid.
 *
 * @returns `true` if the form ref exists and validation passed, otherwise `false`.
 */
export async function validateVuetifyForm(
	formRef: Ref<VForm | null>,
	options?: { invalidMessage?: string },
): Promise<boolean> {
	if (!formRef.value) {
		return false
	}

	const { valid } = await formRef.value.validate()

	if (!valid) {
		toast.error(options?.invalidMessage ?? VUETIFY_FORM_CLIENT_VALIDATION_MESSAGE)
		return false
	}

	return true
}
