import { defineStore } from 'pinia'
import { ref } from 'vue'

export type NotificationColor = 'success' | 'error' | 'info' | 'warning'

export interface NotificationState {
  show: boolean
  text: string
  color: NotificationColor
}

export const useNotificationStore = defineStore('notification', () => {
  const snackbar = ref<NotificationState>({
    show: false,
    text: '',
    color: 'success',
  })

  function notify(text: string, color: NotificationColor = 'success'): void {
    snackbar.value = {
      show: true,
      text,
      color,
    }
  }

  function close(): void {
    snackbar.value.show = false
  }

  return {
    snackbar,
    notify,
    close,
  }
})
