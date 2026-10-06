<template>
  <v-app class="bg-background">
    <!-- Header: only visible when authenticated -->
    <AppHeader v-if="authStore.isAuthenticated" />

    <!-- Main Content -->
    <v-main>
      <router-view />
    </v-main>

    <!-- Global Feedback Notification Snackbar -->
    <v-snackbar
      v-model="notificationStore.snackbar.show"
      :color="notificationStore.snackbar.color"
      timeout="3500"
      location="top right"
      rounded="lg"
      elevation="4"
    >
      <div class="d-flex align-center ga-2">
        <v-icon
          :icon="notificationStore.snackbar.color === 'error' ? 'mdi-alert-circle' : 'mdi-check-circle'"
          size="20"
        />
        <span class="text-body-2 font-weight-medium">
          {{ notificationStore.snackbar.text }}
        </span>
      </div>
      <template #actions>
        <v-btn
          color="white"
          variant="text"
          density="compact"
          icon="mdi-close"
          @click="notificationStore.close"
        />
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notification'

const router = useRouter()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

function handleUnauthorizedEvent(): void {
  authStore.handleUnauthorized()
  router.push({ name: 'Login' })
}

onMounted(async () => {
  window.addEventListener('app:unauthorized', handleUnauthorizedEvent)
  if (!authStore.isInitialized) {
    await authStore.init()
  }
})

onUnmounted(() => {
  window.removeEventListener('app:unauthorized', handleUnauthorizedEvent)
})
</script>

<style>
/* Clean typography and base styles */
html, body {
  font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Oxygen, Ubuntu, Cantarell, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
