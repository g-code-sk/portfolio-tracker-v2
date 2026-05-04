type Rule = (value: unknown) => true | string

export const required: Rule = (value) =>
	typeof value === 'string' && value.trim().length > 0 ? true : 'This field is required.'

export const requiredFile: Rule = (value) => (value instanceof File ? true : 'This field is required.')

export const email: Rule = (value) =>
	typeof value === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
		? true
		: 'This field must be a valid email address.'

export const minLength = (min: number): Rule => (value) =>
	typeof value === 'string' && value.length >= min
		? true
		: `This field must be at least ${min} characters.`

export const matches = (getExpected: () => string): Rule => (value) =>
	typeof value === 'string' && value === getExpected() ? true : 'This field does not match.'

export const fileExtension = (extensions: string[]): Rule => (value) => {
	if (!(value instanceof File)) {
		return true
	}

	const normalizedExtensions = extensions.map((extension) => extension.toLowerCase().replace(/^\./, ''))
	const lowerCaseFileName = value.name.toLowerCase()
	const hasAllowedExtension = normalizedExtensions.some((extension) => lowerCaseFileName.endsWith(`.${extension}`))

	return hasAllowedExtension ? true : `Allowed file types: ${normalizedExtensions.join(', ')}.`
}
