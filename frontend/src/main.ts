import { createApp } from 'vue'
import './style.css'
import App from '@/App.vue'
import vuetify from '@/plugins/vuetify'
import router from '@/router'
import { initializeAuthState } from '@/stores/auth-session'
import Vue3Toastify, { type ToastContainerOptions } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

void initializeAuthState()

createApp(App)
	.use(vuetify)
	.use(router)
	.use(Vue3Toastify, {
		autoClose: 5000,
		position: 'top-right',
	} as ToastContainerOptions)
	.mount('#app')
