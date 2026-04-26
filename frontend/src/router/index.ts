import { createRouter, createWebHistory } from 'vue-router'
import PortfoliosIndexPage from '@/pages/Portfolios/Index.vue'
import LoginPage from '@/pages/Auth/Login.vue'
import RegisterPage from '@/pages/Auth/Register.vue'
import WelcomePage from '@/pages/HomePage.vue'
import { initializeAuthState, useAuthSession } from '@/stores/auth-session'

const RouteAccess = {
	Public: 'public',
	Guest: 'guest',
	Auth: 'auth',
} as const

type RouteAccess = (typeof RouteAccess)[keyof typeof RouteAccess]

const router = createRouter({
	history: createWebHistory(),
	routes: [
		{
			path: '/',
			name: 'home',
			component: WelcomePage,
			meta: { access: RouteAccess.Public },
		},
		{
			path: '/portfolios',
			name: 'portfolios',
			component: PortfoliosIndexPage,
			meta: { access: RouteAccess.Auth },
		},
		{
			path: '/login',
			name: 'login',
			component: LoginPage,
			meta: { access: RouteAccess.Guest },
		},
		{
			path: '/register',
			name: 'register',
			component: RegisterPage,
			meta: { access: RouteAccess.Guest },
		},
	],
})

router.beforeEach(async (to) => {
	const routeAccess = (to.meta.access as RouteAccess | undefined) ?? RouteAccess.Public
	const { currentUser, isAuthReady } = useAuthSession()

	if (!isAuthReady.value) {
		await initializeAuthState()
	}

	const isUserAuthenticated = currentUser.value !== null

	// Middleware-like access control using per-route meta.
	if (routeAccess === RouteAccess.Guest && isUserAuthenticated) {
		return { name: 'portfolios' }
	}

	if (routeAccess === RouteAccess.Auth && !isUserAuthenticated) {
		return { name: 'login' }
	}

	return true
})

export default router
