<template>
	<v-dialog v-model="model" :max-width="size" :disabled="disabled">
		<v-card rounded="lg">
			<v-card-title>
				<slot name="title" />
			</v-card-title>
			<v-card-text>
				<slot name="body" />
			</v-card-text>
			<v-card-actions class="justify-end ga-2">
				<v-btn variant="text" :disabled="disabled" @click="model = false">{{ closeText }}</v-btn>
				<slot name="actions" />
			</v-card-actions>
		</v-card>
	</v-dialog>
</template>

<script setup lang="ts">
withDefaults(
	defineProps<{
		disabled?: boolean
		size?: number | string
		closeText?: string
	}>(),
	{
		disabled: false,
		size: 480,
		closeText: 'Cancel',
	},
)

const model = defineModel<boolean>({ required: true })
</script>
