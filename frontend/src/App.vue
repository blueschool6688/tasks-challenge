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
      v-model="taskStore.snackbar.show"
      :color="taskStore.snackbar.color"
      timeout="3500"
      location="top right"
      rounded="lg"
      elevation="4"
    >
      <div class="d-flex align-center ga-2">
        <v-icon
          :icon="taskStore.snackbar.color === 'error' ? 'mdi-alert-circle' : 'mdi-check-circle'"
          size="20"
        />
        <span class="text-body-2 font-weight-medium">
          {{ taskStore.snackbar.text }}
        </span>
      </div>
      <template #actions>
        <v-btn
          color="white"
          variant="text"
          density="compact"
          icon="mdi-close"
          @click="taskStore.snackbar.show = false"
        />
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import { useAuthStore } from '@/stores/auth'
import { useTaskStore } from '@/stores/tasks'

const authStore = useAuthStore()
const taskStore = useTaskStore()

onMounted(async () => {
  if (authStore.token && !authStore.user) {
    await authStore.fetchCurrentUser()
  }
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
