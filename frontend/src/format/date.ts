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

export const formatDateTime = (dateString: string | null | undefined): string => {
	if (!dateString) {
		return '—'
	}

	const date = new Date(dateString)

	if (isNaN(date.getTime())) {
		return dateString
	}

	return date.toLocaleString(undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
		second: '2-digit',
	})
}

export const getYearFromDateString = (value: string | null | undefined): number | null => {
	if (!value) {
		return null
	}

	const parsedDate = new Date(value)

	if (Number.isNaN(parsedDate.getTime())) {
		return null
	}

	return parsedDate.getFullYear()
}
