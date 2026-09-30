<template>
  <v-container class="fill-height justify-center" fluid>
    <v-card width="100%" max-width="440" rounded="xl" elevation="6" class="pa-4">
      <v-card-item class="text-center pt-4 pb-2">
        <v-avatar color="primary" size="56" class="mb-3">
          <v-icon icon="mdi-format-list-checks" size="32" color="white" />
        </v-avatar>
        <v-card-title class="text-h5 font-weight-bold">
          Team Task Manager
        </v-card-title>
        <v-card-subtitle class="text-body-2 mt-1">
          Sign in to your account to manage tasks
        </v-card-subtitle>
      </v-card-item>

      <v-card-text class="pt-3">
        <!-- Error Alert -->
        <v-alert
          v-if="authStore.errorMessage"
          type="error"
          variant="tonal"
          closable
          density="comfortable"
          class="mb-4"
          @click:close="authStore.errorMessage = null"
        >
          {{ authStore.errorMessage }}
        </v-alert>

        <v-form ref="loginForm" v-model="isValid" @submit.prevent="handleLogin">
          <v-text-field
            v-model="credentials.email"
            label="Email Address"
            placeholder="admin@example.com"
            type="email"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-email-outline"
            :rules="[rules.required, rules.email]"
            class="mb-2"
            autofocus
          />

          <v-text-field
            v-model="credentials.password"
            label="Password"
            placeholder="••••••••"
            :type="showPassword ? 'text' : 'password'"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-lock-outline"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            :rules="[rules.required]"
            class="mb-4"
            @click:append-inner="showPassword = !showPassword"
          />

          <v-btn
            block
            color="primary"
            size="large"
            rounded="lg"
            type="submit"
            :loading="authStore.loading"
            :disabled="!isValid"
            class="text-body-1 font-weight-bold"
          >
            Sign In
          </v-btn>
        </v-form>

        <v-divider class="my-6" />

        <!-- Quick Fill Helper for Demo / Evaluation -->
        <div class="text-center">
          <p class="text-caption text-medium-emphasis mb-2">
            Click to fill test credentials:
          </p>
          <div class="d-flex flex-wrap justify-center ga-2">
            <v-chip
              size="small"
              color="deep-purple"
              variant="tonal"
              prepend-icon="mdi-shield-crown"
              class="cursor-pointer"
              @click="fillCredentials('admin@example.com', 'password')"
            >
              Admin
            </v-chip>
            <v-chip
              size="small"
              color="teal"
              variant="tonal"
              prepend-icon="mdi-account"
              class="cursor-pointer"
              @click="fillCredentials('john@example.com', 'password')"
            >
              User: John
            </v-chip>
            <v-chip
              size="small"
              color="teal"
              variant="tonal"
              prepend-icon="mdi-account"
              class="cursor-pointer"
              @click="fillCredentials('jane@example.com', 'password')"
            >
              User: Jane
            </v-chip>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { LoginCredentials } from '@/types'

const authStore = useAuthStore()
const router = useRouter()

const loginForm = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
const isValid = ref<boolean>(false)
const showPassword = ref<boolean>(false)

const credentials = reactive<LoginCredentials>({
  email: 'admin@example.com',
  password: 'password',
})

const rules = {
  required: (v: string): boolean | string => !!v || 'This field is required.',
  email: (v: string): boolean | string =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Please enter a valid email address.',
}

function fillCredentials(email: string, pass: string): void {
  credentials.email = email
  credentials.password = pass
  authStore.errorMessage = null
}

async function handleLogin(): Promise<void> {
  if (loginForm.value) {
    const { valid } = await loginForm.value.validate()
    if (!valid) return
  }

  const success = await authStore.login(credentials)
  if (success) {
    router.push('/tasks')
  }
}
</script>
