<script setup lang="ts">
import { ref, onMounted } from 'vue'
import apiClient from '@/services/api'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import type { Room, Booking } from '@/types'

const emit = defineEmits<{
  (e: 'explore-rooms'): void
  (e: 'checkout-success'): void
}>()

const { fetchMe } = useAuth()
const toast = useToast()

const loading = ref(true)
const checkoutLoading = ref(false)
const room = ref<Room | null>(null)
const activeBooking = ref<Booking | null>(null)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(price)
}

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const loadMyRoom = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/my-room')
    if (res.data?.data) {
      room.value = res.data.data.room
      activeBooking.value = res.data.data.active_booking
    } else {
      room.value = null
      activeBooking.value = null
    }
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal memuat informasi kamar.')
  } finally {
    loading.value = false
  }
}

const handleCheckout = async () => {
  if (!confirm('Apakah Anda yakin ingin checkout dari kamar kos ini?')) {
    return
  }

  checkoutLoading.value = true
  try {
    const res = await apiClient.post('/checkout')
    toast.success(res.data.message || 'Berhasil checkout dari kamar kos.')
    await fetchMe()
    room.value = null
    activeBooking.value = null
    emit('checkout-success')
  } catch (err: any) {
    toast.error(err.response?.data?.message || 'Gagal melakukan checkout.')
  } finally {
    checkoutLoading.value = false
  }
}

onMounted(() => {
  loadMyRoom()
})
</script>

<template>
  <div class="my-room-container">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Memuat data kamar Anda...</p>
    </div>

    <!-- Empty State: User doesn't have a room -->
    <div v-else-if="!room" class="glass-panel empty-room-card">
      <div class="empty-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
          <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
      </div>
      <h3 class="empty-title">Belum Menempati Kamar</h3>
      <p class="empty-desc">
        Anda belum terdaftar menempati kamar kos manapun. Silakan pilih kamar yang tersedia dan lakukan booking.
      </p>
      <button class="btn btn-primary" @click="emit('explore-rooms')">
        Lihat Daftar Kamar Tersedia
      </button>
    </div>

    <!-- Occupied Room Overview -->
    <div v-else class="room-active-layout">
      <div class="glass-panel main-room-panel">
        <div class="panel-header">
          <div>
            <div class="badge-row">
              <span class="badge badge-available">Aktif Ditempati</span>
              <span class="room-type-tag">{{ room.type }}</span>
            </div>
            <h1 class="room-heading">Kamar {{ room.room_number }}</h1>
          </div>
          <div class="price-box">
            <span class="price-value">{{ formatPrice(room.price) }}</span>
            <span class="price-sub">/ bulan</span>
          </div>
        </div>

        <div class="info-grid">
          <div class="info-card">
            <span class="info-label">Tanggal Masuk (Check-in)</span>
            <span class="info-val">{{ formatDate(activeBooking?.check_in_date) }}</span>
          </div>
          <div class="info-card">
            <span class="info-label">Kapasitas Kamar</span>
            <span class="info-val">{{ room.capacity }} Orang</span>
          </div>
          <div class="info-card">
            <span class="info-label">Status Hunian</span>
            <span class="info-val status-text">{{ room.occupants?.length || 1 }} / {{ room.capacity }} Penghuni</span>
          </div>
        </div>

        <!-- Facilities Monitoring -->
        <div class="facility-section">
          <h3 class="section-heading">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Fasilitas Kamar yang Anda Dapatkan
          </h3>
          <div class="facilities-grid">
            <div
              v-for="(fac, index) in room.facilities.split(',')"
              :key="index"
              class="facility-item"
            >
              <div class="facility-icon">✓</div>
              <span>{{ fac.trim() }}</span>
            </div>
          </div>
        </div>

        <div v-if="room.description" class="facility-section">
          <h3 class="section-heading">Informasi & Catatan Kamar</h3>
          <p class="room-desc">{{ room.description }}</p>
        </div>

        <!-- Checkout Section -->
        <div class="checkout-footer">
          <div class="checkout-note">
            <span class="note-title">Selesai Masa Sewa?</span>
            <span class="note-desc">Pastikan semua barang bawaan Anda telah dirapikan sebelum melakukan checkout.</span>
          </div>
          <button
            class="btn btn-danger"
            :disabled="checkoutLoading"
            @click="handleCheckout"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            {{ checkoutLoading ? 'Memproses Checkout...' : 'Checkout dari Kamar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.my-room-container {
  max-width: 900px;
  margin: 0 auto;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.empty-room-card {
  text-align: center;
  padding: 60px 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-icon {
  width: 80px;
  height: 80px;
  background: var(--primary-light);
  color: var(--primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-main);
  margin-bottom: 8px;
}

.empty-desc {
  font-size: 0.95rem;
  color: var(--text-muted);
  max-width: 480px;
  margin-bottom: 24px;
}

.main-room-panel {
  padding: 32px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid var(--card-border);
  padding-bottom: 24px;
  margin-bottom: 24px;
}

.badge-row {
  display: flex;
  gap: 10px;
  margin-bottom: 8px;
}

.room-type-tag {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-muted);
  background: rgba(255, 255, 255, 0.06);
  padding: 2px 8px;
  border-radius: var(--radius-sm);
}

.room-heading {
  font-size: 2rem;
  font-weight: 800;
}

.price-box {
  text-align: right;
}

.price-value {
  font-size: 1.6rem;
  font-weight: 800;
  color: #a5b4fc;
}

.price-sub {
  font-size: 0.85rem;
  color: var(--text-dim);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 28px;
}

.info-card {
  background: rgba(0, 0, 0, 0.25);
  padding: 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--card-border);
}

.info-label {
  display: block;
  font-size: 0.78rem;
  color: var(--text-dim);
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 6px;
}

.info-val {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-main);
}

.facility-section {
  margin-bottom: 28px;
}

.section-heading {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-main);
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.facilities-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.facility-item {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(99, 102, 241, 0.08);
  border: 1px solid rgba(99, 102, 241, 0.2);
  padding: 12px 14px;
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  color: #c7d2fe;
}

.facility-icon {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.3);
  color: #818cf8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

.room-desc {
  font-size: 0.95rem;
  color: var(--text-muted);
  line-height: 1.6;
}

.checkout-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--card-border);
}

.checkout-note {
  display: flex;
  flex-direction: column;
}

.note-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-main);
}

.note-desc {
  font-size: 0.82rem;
  color: var(--text-dim);
}
</style>
