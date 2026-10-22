import { ref } from 'vue'

export interface ToastMessage {
  id: number
  type: 'success' | 'error' | 'info' | 'warning'
  title?: string
  message: string
}

const toasts = ref<ToastMessage[]>([])
let nextId = 1

export function useToast() {
  const show = (message: string, type: ToastMessage['type'] = 'info', title?: string, duration = 3500) => {
    const id = nextId++
    const toast: ToastMessage = { id, type, title, message }
    toasts.value.push(toast)

    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }
  }

  const remove = (id: number) => {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  const success = (message: string, title = 'Berhasil') => show(message, 'success', title)
  const error = (message: string, title = 'Terjadi Kesalahan') => show(message, 'error', title, 4500)
  const info = (message: string, title = 'Informasi') => show(message, 'info', title)
  const warning = (message: string, title = 'Perhatian') => show(message, 'warning', title)

  return {
    toasts,
    show,
    remove,
    success,
    error,
    info,
    warning,
  }
}
