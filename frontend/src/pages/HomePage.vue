<template>
	<v-main class="bg-grey-lighten-5">
		<v-container class="py-12">
			<v-row justify="center">
				<v-col cols="12" md="10" lg="8" class="text-center">
					<h1 class="text-h2 font-weight-bold mb-4">Welcome to Portfolio Tracker</h1>
					<p class="text-h6 text-medium-emphasis font-weight-regular mb-8">
						Sign in to view your portfolios, or create an account to get started.
					</p>
					<div class="d-flex flex-wrap justify-center ga-3">
						<v-btn color="primary" size="large" :to="{ name: 'register' }" variant="flat"> Create account </v-btn>
						<v-btn size="large" :to="{ name: 'login' }" variant="outlined"> Log in </v-btn>
						<v-btn size="large" :to="{ name: 'portfolios' }" variant="text"> Open portfolios </v-btn>
					</div>
				</v-col>
			</v-row>
		</v-container>
	</v-main>
</template>

<script setup lang="ts"></script>
