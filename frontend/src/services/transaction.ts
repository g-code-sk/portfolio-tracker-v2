import axios from '@/services/axios'
import type { ApiSuccessResponse } from '@/types/api'
import type { TransactionImportPayloadData, TransactionImportType, TransactionImportTypesResponseData } from '@/types/generated'

type TransactionImportUploadPayload = Omit<TransactionImportPayloadData, 'importType' | 'file'> & {
	importType: TransactionImportType
	file: File
}

export const fetchTransactionImportTypes = async (portfolioId: number): Promise<string[]> => {
	const { data } = await axios.get<ApiSuccessResponse<TransactionImportTypesResponseData>>(`/api/portfolios/${portfolioId}/transactions/import-types`)
	const importTypes = data.data?.importTypes ?? []

	return importTypes
}

export const uploadTransactionImport = async (portfolioId: number, payload: TransactionImportUploadPayload): Promise<void> => {
	const formData = new FormData()
	formData.append('importType', payload.importType)
	formData.append('file', payload.file)

	await axios.post<ApiSuccessResponse<undefined>>(`/api/portfolios/${portfolioId}/transactions/import`, formData, {
		headers: {
			'Content-Type': 'multipart/form-data',
		},
	})
}
