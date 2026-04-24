export type RegisterUserPayloadData = {
name: string,
email: string,
password: string,
password_confirmation: string,
};
export type RegisterUserResponseData = {
id: number,
name: string,
email: string,
};
export type TestApiResponseData = {
status: string,
message: string,
timestamp: string,
items: TestItemData[],
};
export type TestItemData = {
id: number,
name: string,
price: number,
};
