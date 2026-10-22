<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'

defineProps<{
  activeTab: 'rooms' | 'my-room'
}>()

const emit = defineEmits<{
  (e: 'change-tab', tab: 'rooms' | 'my-room'): void
  (e: 'open-login'): void
}>()

const { user, isAuthenticated, isAdmin, isPenghuni, logout } = useAuth()

const handleLogout = async () => {
  await logout()
  emit('change-tab', 'rooms')
}
</script>

<template>
  <header class="navbar-wrapper">
    <div class="container navbar-container">
      <div class="navbar-brand" @click="emit('change-tab', 'rooms')">
        <div class="brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
          </svg>
        </div>
        <div class="brand-text">
          <span class="gradient-text font-bold">KostKu</span>
          <span class="brand-badge">PRO</span>
        </div>
      </div>

      <nav class="navbar-menu" v-if="isAuthenticated">
        <button
          class="nav-tab"
          :class="{ active: activeTab === 'rooms' }"
          @click="emit('change-tab', 'rooms')"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          Semua Kamar
        </button>

        <button
          v-if="isPenghuni"
          class="nav-tab"
          :class="{ active: activeTab === 'my-room' }"
          @click="emit('change-tab', 'my-room')"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          Kamar Saya
          <span v-if="user?.room_id" class="occupying-dot"></span>
        </button>
      </nav>

      <div class="navbar-actions">
        <template v-if="isAuthenticated">
          <div class="user-profile">
            <div class="user-avatar">
              {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
            </div>
            <div class="user-info">
              <span class="user-name">{{ user?.name }}</span>
              <span class="badge" :class="isAdmin ? 'badge-admin' : 'badge-penghuni'">
                {{ user?.role }}
              </span>
            </div>
          </div>
          <button class="btn btn-secondary btn-sm" @click="handleLogout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
          </button>
        </template>
        <template v-else>
          <button class="btn btn-primary" @click="emit('open-login')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
              <polyline points="10 17 15 12 10 7"></polyline>
              <line x1="15" y1="12" x2="3" y2="12"></line>
            </svg>
            Masuk / Login
          </button>
        </template>
      </div>
    </div>
  </header>
</template>

<style scoped>
.navbar-wrapper {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--bg-glass);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--card-border);
}

.navbar-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  user-select: none;
}

.brand-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  background: var(--accent-gradient);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
}

.brand-text {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.brand-badge {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.08);
  color: var(--text-muted);
}

.navbar-menu {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(0, 0, 0, 0.25);
  padding: 4px;
  border-radius: var(--radius-md);
  border: 1px solid var(--card-border);
}

.nav-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  font-size: 0.88rem;
  font-weight: 600;
  transition: var(--transition);
  position: relative;
}

.nav-tab:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.05);
}

.nav-tab.active {
  color: #ffffff;
  background: rgba(99, 102, 241, 0.25);
  border: 1px solid rgba(99, 102, 241, 0.4);
}

.occupying-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--success);
  box-shadow: 0 0 8px var(--success);
}

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-main);
}
</style>
