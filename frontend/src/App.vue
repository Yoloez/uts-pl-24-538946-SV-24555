<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import apiClient from '@/services/api'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import Navbar from '@/components/Navbar.vue'
import ToastContainer from '@/components/ToastContainer.vue'
import LoginModal from '@/components/LoginModal.vue'
import RoomModal from '@/components/RoomModal.vue'
import RoomDetailModal from '@/components/RoomDetailModal.vue'
import MyRoomView from '@/components/MyRoomView.vue'
import type { Room } from '@/types'

const { isAuthenticated, isAdmin, isPenghuni, fetchMe } = useAuth()
const toast = useToast()

// Navigation & Modals
const activeTab = ref<'rooms' | 'my-room'>('rooms')
const showLoginModal = ref(false)
const showRoomModal = ref(false)
const showDetailModal = ref(false)

const editingRoom = ref<Room | null>(null)
const selectedRoom = ref<Room | null>(null)

// Data state
const rooms = ref<Room[]>([])
const loadingRooms = ref(false)
const searchQuery = ref('')
const selectedStatusFilter = ref<string>('all')

// Statistics
const stats = computed(() => {
  const total = rooms.value.length
  const available = rooms.value.filter((r) => r.status === 'available').length
  const full = rooms.value.filter((r) => r.status === 'full').length
  const maintenance = rooms.value.filter((r) => r.status === 'maintenance').length
  return { total, available, full, maintenance }
})

// Filtered rooms
const filteredRooms = computed(() => {
  return rooms.value.filter((room) => {
    const matchSearch =
      room.room_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      room.type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      room.facilities.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchStatus =
      selectedStatusFilter.value === 'all' || room.status === selectedStatusFilter.value

    return matchSearch && matchStatus
  })
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(price)
}

// Fetch all rooms (CRUD - Read All via Axios)
const fetchRooms = async () => {
  loadingRooms.value = true
  try {
    const res = await apiClient.get('/rooms')
    rooms.value = res.data.data || []
  } catch (err: any) {
    if (isAuthenticated.value) {
      toast.error(err.response?.data?.message || 'Gagal memuat daftar kamar.')
    }
  } finally {
    loadingRooms.value = false
  }
}

// Open modal create
const handleOpenCreateModal = () => {
  editingRoom.value = null
  showRoomModal.value = true
}

// Open modal edit (CRUD - Update via Axios)
const handleOpenEditModal = (room: Room) => {
  editingRoom.value = room
  showRoomModal.value = true
}

// Open detail modal (CRUD - Read One)
const handleOpenDetailModal = (room: Room) => {
  selectedRoom.value = room
  showDetailModal.value = true
}

// Delete room (CRUD - Delete via Axios)
const handleDeleteRoom = async (room: Room) => {
  if (!confirm(`Hapus kamar ${room.room_number}? Tindakan ini tidak dapat dibatalkan.`)) {
    return
  }

  try {
    const res = await apiClient.delete(`/rooms/${room.id}`)
    toast.success(res.data.message || 'Kamar berhasil dihapus!')
    await fetchRooms()
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal menghapus kamar.')
  }
}

// Penghuni quick booking
const handleQuickBook = async (room: Room) => {
  if (!isAuthenticated.value) {
    showLoginModal.value = true
    return
  }

  try {
    const res = await apiClient.post(`/rooms/${room.id}/book`)
    toast.success(res.data.message || 'Booking kamar berhasil!')
    await fetchMe()
    await fetchRooms()
    activeTab.value = 'my-room'
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal booking kamar.')
  }
}

onMounted(async () => {
  window.addEventListener('auth:unauthorized', () => {
    toast.warning('Sesi Anda telah berakhir. Silakan login kembali.', 'Autentikasi Diperlukan')
    showLoginModal.value = true
  })

  if (isAuthenticated.value) {
    await fetchMe()
    await fetchRooms()
  }
})
</script>

<template>
  <div class="app-layout">
    <!-- Navbar Component -->
    <Navbar
      :active-tab="activeTab"
      @change-tab="activeTab = $event"
      @open-login="showLoginModal = true"
    />

    <!-- Toast Notifications Container -->
    <ToastContainer />

    <!-- Modals -->
    <LoginModal
      v-if="showLoginModal"
      @close="showLoginModal = false"
      @success="fetchRooms"
    />

    <RoomModal
      v-if="showRoomModal"
      :room="editingRoom"
      @close="showRoomModal = false"
      @saved="fetchRooms"
    />

    <RoomDetailModal
      v-if="showDetailModal && selectedRoom"
      :room="selectedRoom"
      @close="showDetailModal = false; selectedRoom = null"
      @refresh="fetchRooms"
      @booked="fetchRooms; activeTab = 'my-room'"
    />

    <!-- Main Content Area -->
    <main class="container main-content">
      <!-- Unauthenticated Welcome Banner -->
      <div v-if="!isAuthenticated" class="glass-panel welcome-hero">
        <div class="hero-content">
          <span class="badge badge-admin">Sistem Manajemen Kost</span>
          <h1 class="hero-title">
            Kelola & Booking Kamar Kos dengan <span class="gradient-text">Mudah & Cepat</span>
          </h1>
          <p class="hero-description">
            Akses dashboard lengkap untuk melihat ketersediaan kamar, fasilitas, dan kelola penghuni. Silakan masuk untuk memulai.
          </p>
          <div class="hero-actions">
            <button class="btn btn-primary" @click="showLoginModal = true">
              Masuk / Login Sekarang
            </button>
          </div>
        </div>
      </div>

      <!-- Authenticated View: Tab Kamar Saya (Penghuni) -->
      <div v-else-if="activeTab === 'my-room'">
        <MyRoomView
          @explore-rooms="activeTab = 'rooms'"
          @checkout-success="fetchRooms"
        />
      </div>

      <!-- Authenticated View: Tab Semua Kamar (Admin & Penghuni) -->
      <div v-else>
        <!-- Header & Action Row -->
        <div class="dashboard-header">
          <div>
            <h1 class="page-title">Manajemen Kamar Kos</h1>
            <p class="page-subtitle">Daftar seluruh kamar kos, ketersediaan, dan fasilitas</p>
          </div>

          <div v-if="isAdmin" class="admin-actions">
            <button class="btn btn-primary" @click="handleOpenCreateModal">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              Tambah Kamar Baru
            </button>
          </div>
        </div>

        <!-- Metric Statistics Cards -->
        <div class="stats-grid">
          <div class="glass-panel stat-card">
            <span class="stat-label">Total Kamar</span>
            <span class="stat-number">{{ stats.total }}</span>
          </div>
          <div class="glass-panel stat-card">
            <span class="stat-label">Kamar Tersedia</span>
            <span class="stat-number text-success">{{ stats.available }}</span>
          </div>
          <div class="glass-panel stat-card">
            <span class="stat-label">Kamar Penuh</span>
            <span class="stat-number text-danger">{{ stats.full }}</span>
          </div>
          <div class="glass-panel stat-card">
            <span class="stat-label">Dalam Perawatan</span>
            <span class="stat-number text-warning">{{ stats.maintenance }}</span>
          </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="filter-bar">
          <div class="search-box">
            <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              class="search-input"
              placeholder="Cari nomor kamar, tipe, atau fasilitas..."
            />
          </div>

          <div class="filter-pills">
            <button
              class="pill-btn"
              :class="{ active: selectedStatusFilter === 'all' }"
              @click="selectedStatusFilter = 'all'"
            >
              Semua ({{ stats.total }})
            </button>
            <button
              class="pill-btn"
              :class="{ active: selectedStatusFilter === 'available' }"
              @click="selectedStatusFilter = 'available'"
            >
              Tersedia ({{ stats.available }})
            </button>
            <button
              class="pill-btn"
              :class="{ active: selectedStatusFilter === 'full' }"
              @click="selectedStatusFilter = 'full'"
            >
              Penuh ({{ stats.full }})
            </button>
            <button
              class="pill-btn"
              :class="{ active: selectedStatusFilter === 'maintenance' }"
              @click="selectedStatusFilter = 'maintenance'"
            >
              Perawatan ({{ stats.maintenance }})
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loadingRooms" class="loading-state">
          <div class="spinner"></div>
          <p>Memuat daftar kamar dari server...</p>
        </div>

        <!-- Empty Results -->
        <div v-else-if="filteredRooms.length === 0" class="glass-panel empty-state">
          <div class="empty-icon-wrap">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="9" y1="9" x2="15" y2="15"></line>
              <line x1="15" y1="9" x2="9" y2="15"></line>
            </svg>
          </div>
          <h3>Tidak ada kamar yang cocok</h3>
          <p>Coba ubah kata kunci pencarian atau filter status yang dipilih.</p>
        </div>

        <!-- Rooms Cards Grid (CRUD Read Cards) -->
        <div v-else class="rooms-grid">
          <div
            v-for="room in filteredRooms"
            :key="room.id"
            class="glass-panel room-card"
          >
            <div class="card-top">
              <div class="card-badges">
                <span class="badge" :class="`badge-${room.status}`">
                  {{ room.status }}
                </span>
                <span class="card-type-tag">{{ room.type }}</span>
              </div>
              <h3 class="card-room-title">Kamar {{ room.room_number }}</h3>
              <div class="card-price">
                {{ formatPrice(room.price) }} <span class="price-unit">/ bln</span>
              </div>
            </div>

            <!-- Capacity & Occupancy indicator -->
            <div class="card-meta">
              <div class="meta-item">
                <span class="meta-label">Kapasitas</span>
                <span class="meta-val">{{ room.capacity }} Orang</span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Penghuni</span>
                <span class="meta-val">{{ room.occupants_count ?? (room.occupants?.length || 0) }} / {{ room.capacity }}</span>
              </div>
            </div>

            <!-- Facilities snippet -->
            <div class="card-facilities">
              <span
                v-for="(fac, idx) in room.facilities.split(',').slice(0, 3)"
                :key="idx"
                class="fac-pill"
              >
                {{ fac.trim() }}
              </span>
              <span v-if="room.facilities.split(',').length > 3" class="fac-more">
                +{{ room.facilities.split(',').length - 3 }}
              </span>
            </div>

            <!-- Card Actions -->
            <div class="card-actions">
              <button
                class="btn btn-secondary btn-sm flex-1"
                @click="handleOpenDetailModal(room)"
              >
                Detail & Fasilitas
              </button>

              <!-- Penghuni quick book -->
              <button
                v-if="isPenghuni && room.status === 'available'"
                class="btn btn-primary btn-sm"
                @click="handleQuickBook(room)"
              >
                Booking
              </button>

              <!-- Admin actions (CRUD Edit & Delete) -->
              <template v-if="isAdmin">
                <button
                  class="btn btn-secondary btn-sm icon-btn"
                  title="Ubah Kamar"
                  @click="handleOpenEditModal(room)"
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                </button>
                <button
                  class="btn btn-danger btn-sm icon-btn"
                  title="Hapus Kamar"
                  @click="handleDeleteRoom(room)"
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  padding-top: 36px;
  padding-bottom: 60px;
}

/* Welcome Hero */
.welcome-hero {
  text-align: center;
  padding: 60px 24px;
  max-width: 800px;
  margin: 40px auto 0;
}

.hero-title {
  font-size: 2.4rem;
  font-weight: 800;
  line-height: 1.25;
  margin: 16px 0 12px;
}

.hero-description {
  font-size: 1rem;
  color: var(--text-muted);
  max-width: 580px;
  margin: 0 auto 24px;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
}

/* Dashboard Header */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-title {
  font-size: 1.8rem;
  font-weight: 800;
}

.page-subtitle {
  font-size: 0.9rem;
  color: var(--text-muted);
  margin-top: 2px;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}

.stat-card {
  padding: 18px 20px;
}

.stat-label {
  display: block;
  font-size: 0.78rem;
  color: var(--text-dim);
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 4px;
}

.stat-number {
  font-size: 1.8rem;
  font-weight: 800;
}

.text-success { color: #34d399; }
.text-danger { color: #f87171; }
.text-warning { color: #fbbf24; }

/* Filter Bar */
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 440px;
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-dim);
}

.search-input {
  width: 100%;
  padding: 10px 14px 10px 42px;
  background: rgba(17, 24, 39, 0.75);
  border: 1px solid var(--card-border);
  border-radius: var(--radius-md);
  color: var(--text-main);
  outline: none;
  transition: var(--transition);
}

.search-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.filter-pills {
  display: flex;
  gap: 8px;
}

.pill-btn {
  padding: 8px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: var(--radius-full);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-muted);
  border: 1px solid var(--card-border);
  transition: var(--transition);
}

.pill-btn:hover {
  color: var(--text-main);
  background: rgba(255, 255, 255, 0.1);
}

.pill-btn.active {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.35);
}

/* Rooms Grid */
.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.room-card {
  padding: 22px;
  display: flex;
  flex-direction: column;
  transition: var(--transition);
  position: relative;
}

.room-card:hover {
  transform: translateY(-4px);
  border-color: var(--card-border-hover);
  box-shadow: var(--shadow-glow);
}

.card-top {
  margin-bottom: 16px;
}

.card-badges {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.card-type-tag {
  font-size: 0.75rem;
  color: var(--text-dim);
  background: rgba(255, 255, 255, 0.04);
  padding: 2px 8px;
  border-radius: var(--radius-sm);
  font-weight: 600;
}

.card-room-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--text-main);
}

.card-price {
  font-size: 1.15rem;
  font-weight: 700;
  color: #a5b4fc;
  margin-top: 4px;
}

.price-unit {
  font-size: 0.8rem;
  color: var(--text-dim);
  font-weight: 400;
}

.card-meta {
  display: flex;
  gap: 16px;
  padding: 10px 12px;
  background: rgba(0, 0, 0, 0.25);
  border-radius: var(--radius-md);
  margin-bottom: 14px;
}

.meta-item {
  flex: 1;
}

.meta-label {
  display: block;
  font-size: 0.72rem;
  color: var(--text-dim);
  text-transform: uppercase;
  font-weight: 600;
}

.meta-val {
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--text-main);
}

.card-facilities {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 20px;
  flex: 1;
}

.fac-pill {
  font-size: 0.75rem;
  background: rgba(99, 102, 241, 0.1);
  color: #c7d2fe;
  padding: 3px 8px;
  border-radius: var(--radius-sm);
  border: 1px solid rgba(99, 102, 241, 0.2);
}

.fac-more {
  font-size: 0.75rem;
  color: var(--text-dim);
  padding: 3px 6px;
}

.card-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.flex-1 {
  flex: 1;
}

.icon-btn {
  padding: 6px 10px;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.empty-state {
  text-align: center;
  padding: 50px 20px;
}

.empty-icon-wrap {
  margin-bottom: 14px;
  color: var(--text-dim);
}

.empty-state h3 {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 6px;
}

.empty-state p {
  color: var(--text-muted);
  font-size: 0.9rem;
}
</style>
