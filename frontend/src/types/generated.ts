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
ticker: string,
name: string,
currency: string,
sharesBought: number,
sharesSold: number,
investedAmount: number,
soldAmount: number,
totalShares: number,
};
export type PortfolioPositionsResponseData = {
positions: PortfolioPositionResponseData[],
};
export type PortfolioResponseData = {
id: number,
name: string,
};
export type RegisterUserPayloadData = {
name: string,
email: string,
password: string,
passwordConfirmation: string,
};
export type TransactionImportPayloadData = {
importType: string,
file: undefined,
};
export type TransactionImportType = "Trading 212" | "Interactive Brokers";
export type TransactionImportTypesResponseData = {
importTypes: string[],
};
