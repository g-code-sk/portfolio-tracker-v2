import axios from '@/services/axios'
import type { ApiSuccessResponse } from '@/types/api'
import type {
	PortfolioTransactionResponseData,
	PortfolioTransactionsResponseData,
	TransactionImportPayloadData,
	TransactionImportType,
	TransactionImportTypesResponseData,
	WholeShareGroupsResponseData,
} from '@/types/generated'

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

export const fetchPositionTransactions = async (portfolioId: number, securityId: number, currencyId: number | null): Promise<PortfolioTransactionResponseData[]> => {
	const params: { securityId: number; currencyId?: number } = { securityId }

	if (currencyId !== null) {
		params.currencyId = currencyId
	}

	const { data } = await axios.get<ApiSuccessResponse<PortfolioTransactionsResponseData>>(`/api/portfolios/${portfolioId}/transactions`, {
		params,
	})

	return data.data?.transactions ?? []
}

export const fetchWholeShareSegments = async (portfolioId: number, securityId: number, currencyId: number): Promise<WholeShareGroupsResponseData> => {
	const { data } = await axios.get<ApiSuccessResponse<WholeShareGroupsResponseData>>(`/api/portfolios/${portfolioId}/transactions/whole-share-buy-segments`, {
		params: { securityId, currencyId },
	})

	return data.data ?? { groups: [] }
}
