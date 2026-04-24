<template>
  <v-app>
    <AppNavbar />
    <router-view />
  </v-app>
</template>

<script setup lang="ts">
import AppNavbar from './components/AppNavbar.vue'
</script>
