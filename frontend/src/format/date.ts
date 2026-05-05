export const formatDate = (dateString: string | null | undefined): string => {
	if (!dateString) {
		return '—'
	}

	const date = new Date(dateString)

	if (isNaN(date.getTime())) {
		return dateString
	}

	return date.toLocaleDateString(undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	})
}
