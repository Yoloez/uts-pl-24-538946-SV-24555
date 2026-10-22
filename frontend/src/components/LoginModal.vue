<script setup lang="ts">
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success'): void
}>()

const { login, register } = useAuth()
const toast = useToast()

const activeTab = ref<'login' | 'register'>('login')
const loading = ref(false)
const errorMessage = ref('')

// Form fields
const name = ref('')
const email = ref('')
const password = ref('')
const role = ref<'penghuni' | 'admin'>('penghuni')

const setDemoAccount = (roleType: 'admin' | 'penghuni') => {
  activeTab.value = 'login'
  if (roleType === 'admin') {
    email.value = 'admin@kost.com'
    password.value = 'password123'
  } else {
    email.value = 'penghuni@kost.com'
    password.value = 'password123'
  }
}

const handleSubmit = async () => {
  errorMessage.value = ''
  loading.value = true

  try {
    if (activeTab.value === 'login') {
      const res = await login(email.value, password.value)
      toast.success(res.message || 'Login berhasil! Selamat datang kembali.')
    } else {
      const res = await register(name.value, email.value, password.value, role.value)
      toast.success(res.message || 'Registrasi akun berhasil!')
    }
    emit('success')
    emit('close')
  } catch (err: any) {
    if (err.response?.data?.errors) {
      // Laravel 422 validation errors
      const errorKeys = Object.keys(err.response.data.errors)
      if (errorKeys.length > 0 && errorKeys[0]) {
        const key = errorKeys[0]
        errorMessage.value = err.response.data.errors[key][0]
      } else {
        errorMessage.value = 'Data yang dikirimkan tidak valid.'
      }
    } else if (err.response?.data?.message) {
      // Laravel 401 or custom message
      errorMessage.value = err.response.data.message
    } else {
      errorMessage.value = 'Gagal terhubung ke backend server. Pastikan API berjalan.'
    }
    toast.error(errorMessage.value, 'Gagal')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content glass-panel auth-card">
      <div class="auth-header">
        <div class="auth-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
          </svg>
        </div>
        <h2 class="auth-title">
          {{ activeTab === 'login' ? 'Masuk ke KostKu' : 'Buat Akun Baru' }}
        </h2>
        <p class="auth-subtitle">
          Kelola kamar kos dan pantau fasilitas dengan cepat & aman
        </p>
      </div>

      <!-- Quick Demo Login Presets -->
      <div class="demo-buttons">
        <span class="demo-label">Quick Demo:</span>
        <button type="button" class="btn-demo" @click="setDemoAccount('admin')">
          👑 Admin
        </button>
        <button type="button" class="btn-demo" @click="setDemoAccount('penghuni')">
          👤 Penghuni
        </button>
      </div>

      <!-- Tab Switcher -->
      <div class="auth-tabs">
        <button
          class="auth-tab-btn"
          :class="{ active: activeTab === 'login' }"
          @click="activeTab = 'login'; errorMessage = ''"
        >
          Masuk
        </button>
        <button
          class="auth-tab-btn"
          :class="{ active: activeTab === 'register' }"
          @click="activeTab = 'register'; errorMessage = ''"
        >
          Daftar
        </button>
      </div>

      <div v-if="errorMessage" class="error-banner">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>{{ errorMessage }}</span>
      </div>

      <form @submit.prevent="handleSubmit" class="auth-form">
        <div v-if="activeTab === 'register'" class="form-group">
          <label class="form-label">Nama Lengkap</label>
          <input
            v-model="name"
            type="text"
            class="form-control"
            placeholder="e.g. Budi Santoso"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Alamat Email</label>
          <input
            v-model="email"
            type="email"
            class="form-control"
            placeholder="nama@email.com"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Kata Sandi</label>
          <input
            v-model="password"
            type="password"
            class="form-control"
            placeholder="Minimal 6 karakter"
            required
          />
        </div>

        <div v-if="activeTab === 'register'" class="form-group">
          <label class="form-label">Peran Akun</label>
          <select v-model="role" class="form-control">
            <option value="penghuni">Penghuni Kos</option>
            <option value="admin">Administrator</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary btn-submit" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>{{ activeTab === 'login' ? 'Masuk Sekarang' : 'Daftar Akun' }}</span>
        </button>
      </form>

      <button class="btn-modal-close" @click="emit('close')">&times;</button>
    </div>
  </div>
</template>

<style scoped>
.auth-card {
  position: relative;
  text-align: center;
}

.auth-header {
  margin-bottom: 20px;
}

.auth-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 12px;
  background: var(--primary-light);
  color: var(--primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.auth-title {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--text-main);
}

.auth-subtitle {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-top: 4px;
}

.demo-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-bottom: 16px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px dashed var(--card-border);
  border-radius: var(--radius-md);
}

.demo-label {
  font-size: 0.78rem;
  color: var(--text-dim);
  font-weight: 600;
}

.btn-demo {
  padding: 4px 10px;
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.3);
  color: #c7d2fe;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 600;
  transition: var(--transition);
}

.btn-demo:hover {
  background: rgba(99, 102, 241, 0.3);
  transform: translateY(-1px);
}

.auth-tabs {
  display: flex;
  background: rgba(0, 0, 0, 0.25);
  padding: 4px;
  border-radius: var(--radius-md);
  margin-bottom: 20px;
  border: 1px solid var(--card-border);
}

.auth-tab-btn {
  flex: 1;
  padding: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--text-muted);
  border-radius: var(--radius-sm);
  transition: var(--transition);
}

.auth-tab-btn.active {
  background: var(--primary);
  color: #fff;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
}

.error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: var(--danger-bg);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: var(--radius-md);
  color: #fca5a5;
  font-size: 0.84rem;
  margin-bottom: 16px;
  text-align: left;
}

.auth-form {
  margin-top: 10px;
}

.btn-submit {
  width: 100%;
  margin-top: 10px;
  padding: 12px;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-modal-close {
  position: absolute;
  top: 16px;
  right: 18px;
  font-size: 1.5rem;
  color: var(--text-dim);
  line-height: 1;
  transition: color 0.2s;
}

.btn-modal-close:hover {
  color: var(--text-main);
}
</style>
