import axios from '@/services/axios'
import type { ApiSuccessResponse } from '@/types/api'
import type { PortfolioCollectionResponseData, PortfolioPayloadData, PortfolioResponseData } from '@/types/generated'

export const fetchPortfolios = async (): Promise<PortfolioResponseData[]> => {
	const { data } = await axios.get<ApiSuccessResponse<PortfolioCollectionResponseData>>('/api/portfolios')
	return data.data?.portfolios ?? []
}

export const createPortfolio = async (payload: PortfolioPayloadData): Promise<PortfolioResponseData> => {
	const { data } = await axios.post<ApiSuccessResponse<PortfolioResponseData>>('/api/portfolios', payload)

	if (!data.data) {
		throw new Error('Missing portfolio payload in API response.')
	}

	return data.data
}

export const fetchPortfolio = async (portfolioId: number): Promise<PortfolioResponseData> => {
	const { data } = await axios.get<ApiSuccessResponse<PortfolioResponseData>>(`/api/portfolios/${portfolioId}`)

	if (!data.data) {
		throw new Error('Missing portfolio payload in API response.')
	}

	return data.data
}

export const updatePortfolio = async (portfolioId: number, payload: PortfolioPayloadData): Promise<PortfolioResponseData> => {
	const { data } = await axios.put<ApiSuccessResponse<PortfolioResponseData>>(`/api/portfolios/${portfolioId}`, payload)

	if (!data.data) {
		throw new Error('Missing portfolio payload in API response.')
	}

	return data.data
}

export const deletePortfolio = async (portfolioId: number): Promise<void> => {
	await axios.delete(`/api/portfolios/${portfolioId}`)
}
