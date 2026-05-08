import { createRouter, createWebHistory } from 'vue-router'
import PortfoliosIndexPage from '@/pages/Portfolios/Index.vue'
import PortfolioShowPage from '@/pages/Portfolios/Show.vue'
import PositionTransactionsPage from '@/pages/Portfolios/PositionTransactions.vue'
import WholeShareBuySegmentsPage from '@/pages/Portfolios/WholeShareBuySegments.vue'
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

const waitUntilScrollableTop = async (targetTop: number): Promise<void> => {
	const timeoutMs = 1200
	const startTimeMs = Date.now()

	return new Promise((resolve) => {
		const checkScrollableArea = () => {
			const maxScrollableTop = Math.max(document.documentElement.scrollHeight - window.innerHeight, 0)
			const hasEnoughHeight = maxScrollableTop >= targetTop
			const hasTimedOut = Date.now() - startTimeMs >= timeoutMs

			if (hasEnoughHeight || hasTimedOut) {
				resolve()
				return
			}

			window.requestAnimationFrame(checkScrollableArea)
		}

		window.requestAnimationFrame(checkScrollableArea)
	})
}

const router = createRouter({
	history: createWebHistory(),
	async scrollBehavior(_to, _from, savedPosition) {
		if (savedPosition) {
			await waitUntilScrollableTop(savedPosition.top ?? 0)
			return savedPosition
		}

		return { top: 0 }
	},
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
			path: '/portfolios/:portfolioId',
			name: 'portfolio-details',
			component: PortfolioShowPage,
			meta: { access: RouteAccess.Auth },
		},
		{
			path: '/portfolios/:portfolioId/positions/:securityId/transactions',
			name: 'portfolio-position-transactions',
			component: PositionTransactionsPage,
			meta: { access: RouteAccess.Auth },
		},
		{
			path: '/portfolios/:portfolioId/positions/:securityId/whole-share-buy-segments',
			name: 'portfolio-position-whole-share-buy-segments',
			component: WholeShareBuySegmentsPage,
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
