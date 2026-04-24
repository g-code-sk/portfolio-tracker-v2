import { createRouter, createWebHistory } from 'vue-router'
import DashboardPage from '@/pages/DashboardPage.vue'
import LoginPage from '@/pages/Auth/LoginPage.vue'
import RegisterPage from '@/pages/Auth/RegisterPage.vue'
import WelcomePage from '@/pages/WelcomePage.vue'

const router = createRouter({
	history: createWebHistory(),
	routes: [
		{
			path: '/',
			name: 'home',
			component: WelcomePage,
		},
		{
			path: '/dashboard',
			name: 'dashboard',
			component: DashboardPage,
		},
		{
			path: '/login',
			name: 'login',
			component: LoginPage,
		},
		{
			path: '/register',
			name: 'register',
			component: RegisterPage,
		},
	],
})

export default router
