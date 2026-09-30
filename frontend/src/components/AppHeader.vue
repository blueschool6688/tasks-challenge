<template>
  <v-app-bar flat color="white" border="b" density="comfortable">
    <v-container class="d-flex align-center py-0 px-4" fluid>
      <v-icon color="primary" icon="mdi-checkbox-marked-circle-outline" class="mr-2" size="28" />
      <v-app-bar-title class="font-weight-bold text-subtitle-1 text-md-h6">
        Team Task Manager
      </v-app-bar-title>

      <v-spacer />

      <template v-if="authStore.isAuthenticated && authStore.user">
        <div class="d-flex align-center ga-3">
          <div class="d-flex flex-column text-right d-none d-sm-flex">
            <span class="text-body-2 font-weight-medium text-high-emphasis">
              {{ authStore.user.name }}
            </span>
            <span class="text-caption text-medium-emphasis">
              {{ authStore.user.email }}
            </span>
          </div>

          <v-chip
            :color="authStore.isAdmin ? 'deep-purple' : 'teal'"
            variant="flat"
            size="small"
            class="font-weight-bold text-uppercase"
          >
            <v-icon start size="14">
              {{ authStore.isAdmin ? 'mdi-shield-crown' : 'mdi-account' }}
            </v-icon>
            {{ authStore.user.role }}
          </v-chip>

          <v-divider vertical class="mx-1 my-2" />

          <v-btn
            color="error"
            variant="tonal"
            size="small"
            prepend-icon="mdi-logout"
            @click="handleLogout"
          >
            Logout
          </v-btn>
        </div>
      </template>
    </v-container>
  </v-app-bar>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout(): Promise<void> {
  await authStore.logout()
  router.push('/login')
}
</script>
