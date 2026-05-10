/**
 * Filter kinds for collection filters. Extend as new UI/filter logic is added.
 */
export const FilterType = {
	String: 'string',
} as const

/**
 * One string query matched case-insensitively against row values.
 *
 * - Multiple {@link StringFilterDefinition.keys}: a row passes if **any** key's string value contains the query (OR).
 * - Multiple filters in the parent `filterData` array: combined with **AND**.
 * - Empty or whitespace-only query applies no constraint for that filter.
 */
export type StringFilterDefinition<T> = {
	type: typeof FilterType.String
	keys: (keyof T & string)[]
	label?: string
}

export type FilterDefinition<T> = StringFilterDefinition<T>
