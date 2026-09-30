<template>
  <v-app-bar flat color="white" border="b" density="comfortable" class="px-2">
    <v-container class="d-flex align-center py-0 px-2" fluid>
      <v-avatar color="primary" size="32" class="mr-3" rounded="lg">
        <v-icon color="white" icon="mdi-checkbox-marked-circle-outline" size="20" />
      </v-avatar>

      <v-app-bar-title class="font-weight-bold text-subtitle-1 text-md-h6 tracking-tight">
        Team Task Manager
      </v-app-bar-title>

      <v-spacer />

      <template v-if="authStore.isAuthenticated && authStore.user">
        <div class="d-flex align-center ga-3">
          <v-avatar
            size="32"
            :color="authStore.isAdmin ? 'deep-purple' : 'primary'"
            class="d-none d-sm-flex"
          >
            <span class="text-caption text-white font-weight-bold">
              {{ getUserInitials(authStore.user.name) }}
            </span>
          </v-avatar>

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
            variant="tonal"
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
            color="secondary"
            variant="text"
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
import { getUserInitials } from '@/utils/task'

const authStore = useAuthStore()
const router = useRouter()

async function handleLogout(): Promise<void> {
  await authStore.logout()
  router.push('/login')
}
</script>
