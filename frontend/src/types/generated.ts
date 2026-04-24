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
