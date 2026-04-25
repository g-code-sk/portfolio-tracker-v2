import { createRouter, createWebHistory } from 'vue-router'
import DashboardPage from '@/pages/DashboardPage.vue'
import LoginPage from '@/pages/Auth/LoginPage.vue'
import RegisterPage from '@/pages/Auth/RegisterPage.vue'
import WelcomePage from '@/pages/WelcomePage.vue'
import { initializeAuthState, refreshAuthState } from '@/stores/auth-session'

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
			path: '/dashboard',
			name: 'dashboard',
			component: DashboardPage,
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
	const initializedUser = await initializeAuthState()
	const isUserAuthenticated = initializedUser !== null

	// Middleware-like access control using per-route meta.
	if (routeAccess === RouteAccess.Guest && isUserAuthenticated) {
		return { name: 'dashboard' }
	}

	if (routeAccess === RouteAccess.Auth && !isUserAuthenticated) {
		const refreshedUser = await refreshAuthState()

		if (refreshedUser !== null) {
			return true
		}
		return { name: 'login' }
	}

	return true
})

export default router
