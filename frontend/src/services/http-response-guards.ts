export const isUnauthorizedStatus = (status: number | undefined): boolean => status === 401

export const isCsrfMismatchStatus = (status: number | undefined): boolean => status === 419

/** Session already invalid or CSRF token mismatch (treat like logged out for UX). */
export const isAuthOrCsrfMismatchStatus = (status: number | undefined): boolean =>
	isUnauthorizedStatus(status) || isCsrfMismatchStatus(status)
