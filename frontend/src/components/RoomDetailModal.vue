<script setup lang="ts">
import { ref, onMounted } from 'vue'
import apiClient from '@/services/api'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import type { Room, User } from '@/types'

const props = defineProps<{
  room: Room
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'refresh'): void
  (e: 'booked'): void
}>()

const { user, isAdmin, isPenghuni, fetchMe } = useAuth()
const toast = useToast()

const occupants = ref<User[]>([])
const loadingOccupants = ref(false)
const bookingLoading = ref(false)
const actionLoadingUserId = ref<number | null>(null)

// Format currency
const formatPrice = (price: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(price)
}

// Fetch occupants
const loadOccupants = async () => {
  loadingOccupants.value = true
  try {
    const res = await apiClient.get(`/rooms/${props.room.id}/occupants`)
    occupants.value = res.data.occupants || []
  } catch (err: any) {
    // If not admin or fails, fallback to props occupants
    occupants.value = props.room.occupants || []
  } finally {
    loadingOccupants.value = false
  }
}

// Admin: Menghapus user dari kamar kos tertentu
const handleRemoveOccupant = async (occupantId: number, occupantName: string) => {
  if (!confirm(`Yakin ingin mengeluarkan ${occupantName} dari kamar ${props.room.room_number}?`)) {
    return
  }

  actionLoadingUserId.value = occupantId
  try {
    const res = await apiClient.delete(`/rooms/${props.room.id}/occupants/${occupantId}`)
    toast.success(res.data.message || `User ${occupantName} berhasil dikeluarkan dari kamar.`)
    await loadOccupants()
    emit('refresh')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal mengeluarkan penghuni.')
  } finally {
    actionLoadingUserId.value = null
  }
}

// Penghuni: Booking kamar kos ini
const handleBookRoom = async () => {
  bookingLoading.value = true
  try {
    const res = await apiClient.post(`/rooms/${props.room.id}/book`)
    toast.success(res.data.message || 'Booking kamar berhasil!')
    await fetchMe()
    emit('booked')
    emit('close')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal melakukan booking kamar.')
  } finally {
    bookingLoading.value = false
  }
}

onMounted(() => {
  if (isAdmin.value) {
    loadOccupants()
  } else {
    occupants.value = props.room.occupants || []
  }
})
</script>

<template>
  <div class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content glass-panel room-detail-card">
      <div class="detail-header">
        <div class="detail-badge-row">
          <span class="badge" :class="`badge-${room.status}`">
            {{ room.status }}
          </span>
          <span class="room-type-tag">{{ room.type }}</span>
        </div>
        <h2 class="room-title">Kamar {{ room.room_number }}</h2>
        <div class="room-price-display">
          {{ formatPrice(room.price) }} <span class="price-cycle">/ bulan</span>
        </div>
      </div>

      <!-- Room Spec Summary -->
      <div class="spec-grid">
        <div class="spec-item">
          <span class="spec-label">Kapasitas Maksimal</span>
          <span class="spec-value">{{ room.capacity }} Orang</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Penghuni Aktif</span>
          <span class="spec-value">{{ room.occupants_count ?? occupants.length }} / {{ room.capacity }}</span>
        </div>
      </div>

      <!-- Facilities List -->
      <div class="section-block">
        <h4 class="section-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          Fasilitas Kamar
        </h4>
        <div class="facility-chips">
          <span
            v-for="(fac, idx) in room.facilities.split(',')"
            :key="idx"
            class="facility-chip"
          >
            {{ fac.trim() }}
          </span>
        </div>
      </div>

      <!-- Description -->
      <div v-if="room.description" class="section-block">
        <h4 class="section-title">Deskripsi</h4>
        <p class="description-text">{{ room.description }}</p>
      </div>

      <!-- Occupants (Admin feature: melihat siapa saja yang ada di kamar & menghapus user) -->
      <div class="section-block">
        <div class="occupant-header-row">
          <h4 class="section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
            </svg>
            Daftar Penghuni Saat Ini
          </h4>
          <span v-if="loadingOccupants" class="loading-tag">Memuat data...</span>
        </div>

        <div v-if="occupants.length === 0" class="empty-occupants">
          Belum ada penghuni di kamar ini.
        </div>

        <div v-else class="occupants-list">
          <div
            v-for="occ in occupants"
            :key="occ.id"
            class="occupant-item"
          >
            <div class="occupant-avatar">
              {{ occ.name.charAt(0).toUpperCase() }}
            </div>
            <div class="occupant-info">
              <div class="occupant-name">{{ occ.name }}</div>
              <div class="occupant-email">{{ occ.email }}</div>
            </div>
            <!-- Admin: Action untuk mengeluarkan user dari kamar -->
            <button
              v-if="isAdmin"
              class="btn btn-danger btn-sm"
              :disabled="actionLoadingUserId === occ.id"
              @click="handleRemoveOccupant(occ.id, occ.name)"
            >
              {{ actionLoadingUserId === occ.id ? 'Memproses...' : 'Keluarkan' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="modal-footer">
        <button class="btn btn-secondary" @click="emit('close')">
          Tutup
        </button>

        <!-- Penghuni booking action -->
        <button
          v-if="isPenghuni && !user?.room_id && room.status === 'available'"
          class="btn btn-primary"
          :disabled="bookingLoading"
          @click="handleBookRoom"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <line x1="20" y1="8" x2="20" y2="14"></line>
            <line x1="23" y1="11" x2="17" y2="11"></line>
          </svg>
          {{ bookingLoading ? 'Memproses Booking...' : 'Booking Kamar Ini' }}
        </button>
      </div>

      <button class="btn-modal-close" @click="emit('close')">&times;</button>
    </div>
  </div>
</template>

<style scoped>
.room-detail-card {
  position: relative;
}

.detail-header {
  border-bottom: 1px solid var(--card-border);
  padding-bottom: 16px;
  margin-bottom: 18px;
}

.detail-badge-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.room-type-tag {
  font-size: 0.82rem;
  color: var(--text-muted);
  font-weight: 600;
  background: rgba(255, 255, 255, 0.05);
  padding: 2px 8px;
  border-radius: var(--radius-sm);
}

.room-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--text-main);
}

.room-price-display {
  font-size: 1.25rem;
  font-weight: 700;
  color: #a5b4fc;
  margin-top: 4px;
}

.price-cycle {
  font-size: 0.85rem;
  color: var(--text-dim);
  font-weight: 400;
}

.spec-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 18px;
}

.spec-item {
  background: rgba(0, 0, 0, 0.25);
  padding: 12px 14px;
  border-radius: var(--radius-md);
  border: 1px solid var(--card-border);
}

.spec-label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-dim);
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 4px;
}

.spec-value {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-main);
}

.section-block {
  margin-bottom: 18px;
}

.section-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.facility-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.facility-chip {
  background: rgba(99, 102, 241, 0.12);
  color: #c7d2fe;
  border: 1px solid rgba(99, 102, 241, 0.25);
  padding: 5px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  font-weight: 500;
}

.description-text {
  font-size: 0.88rem;
  color: var(--text-muted);
  line-height: 1.6;
}

.occupant-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.loading-tag {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.empty-occupants {
  font-size: 0.85rem;
  color: var(--text-dim);
  padding: 12px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: var(--radius-md);
  text-align: center;
}

.occupants-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.occupant-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: rgba(0, 0, 0, 0.25);
  border-radius: var(--radius-md);
  border: 1px solid var(--card-border);
}

.occupant-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.82rem;
  color: #fff;
}

.occupant-info {
  flex: 1;
}

.occupant-name {
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--text-main);
}

.occupant-email {
  font-size: 0.75rem;
  color: var(--text-dim);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--card-border);
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
