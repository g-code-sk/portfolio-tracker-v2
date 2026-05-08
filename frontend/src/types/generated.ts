export type AuthSessionData = {
isActive: boolean,
lifetimeMinutes: number,
expiresAt: string | null,
sessionId: string | null,
};
export type AuthUserSessionResponseData = {
user: LoginUserResponseData | null,
session: AuthSessionData,
};
export type LoginUserPayloadData = {
email: string,
password: string,
};
export type LoginUserResponseData = {
id: number,
name: string,
email: string,
};
export type PortfolioCollectionResponseData = {
portfolios: Array<any>,
};
export type PortfolioPayloadData = {
name: string,
};
export type PortfolioPositionResponseData = {
securityId: number,
currencyId: number,
ticker: string,
name: string,
currencySymbol: string,
sharesBought: number,
sharesSold: number,
investedAmount: number,
soldAmount: number,
totalShares: number,
};
export type PortfolioPositionsResponseData = {
positions: Array<any>,
};
export type PortfolioResponseData = {
id: number,
name: string,
};
export type PortfolioTransactionResponseData = {
id: number,
externalTransactionId: string,
ticker: string,
name: string,
typeCode: string,
numberOfShares: number,
pricePerShare: number,
totalAmount: number,
currencySymbol: string,
executedAt: string,
};
export type PortfolioTransactionsResponseData = {
transactions: Array<any>,
};
export type RegisterUserPayloadData = {
name: string,
email: string,
password: string,
passwordConfirmation: string,
};
export type Trading212TransactionType = "Market buy" | "Market sell";
export type TransactionImportPayloadData = {
importType: TransactionImportType,
file: undefined,
};
export type TransactionImportType = "Trading 212" | "Interactive Brokers";
export type TransactionImportTypesResponseData = {
importTypes: string[],
};
export type TransactionTypeCode = "buy" | "sell";
export type WholeShareBucketResponseData = {
wholeShareBucketIndex: number,
segments: Array<any>,
};
export type WholeShareGroupResponseData = {
groupIndex: number,
buyBucket: WholeShareBucketResponseData | null,
sellBucket: WholeShareBucketResponseData | null,
buyDate: string | null,
sellDate: string | null,
holdPeriodDays: number | null,
weightedBuyPricePerShare: number | null,
weightedSellPricePerShare: number | null,
returnPercent: number | null,
gainLossAmount: number | null,
isSellTaxable: boolean | null,
};
export type WholeShareGroupsResponseData = {
groups: Array<any>,
ticker: string | null,
currencySymbol: string | null,
realizedGainLossAmount: number | null,
realizedReturnPercent: number | null,
};
export type WholeShareSegmentResponseData = {
sourceTransactionId: number,
externalTransactionId: string,
executedAt: string,
ticker: string,
name: string,
numberOfShares: number,
pricePerShare: number,
totalAmount: number,
currencySymbol: string,
};
