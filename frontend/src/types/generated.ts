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
export type PortfolioResponseData = {
id: number,
name: string,
};
export type RegisterUserPayloadData = {
name: string,
email: string,
password: string,
password_confirmation: string,
};
export type TransactionImportPayloadData = {
import_type: string,
file: undefined,
};
export type TransactionImportType = "Trading 212" | "Interactive Brokers";
export type TransactionImportTypesResponseData = {
importTypes: string[],
};
