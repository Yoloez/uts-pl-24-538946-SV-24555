<script setup lang="ts">
import { ref, watch } from 'vue'
import apiClient from '@/services/api'
import { useToast } from '@/composables/useToast'
import type { Room } from '@/types'

const props = defineProps<{
  room?: Room | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
}>()

const toast = useToast()
const loading = ref(false)
const errorMessage = ref('')

const isEdit = ref(false)
const form = ref({
  room_number: '',
  type: '',
  price: 1000000,
  capacity: 1,
  facilities: '',
  description: '',
  status: 'available' as 'available' | 'full' | 'maintenance',
})

watch(
  () => props.room,
  (val) => {
    if (val) {
      isEdit.value = true
      form.value = {
        room_number: val.room_number,
        type: val.type,
        price: val.price,
        capacity: val.capacity,
        facilities: val.facilities,
        description: val.description || '',
        status: val.status,
      }
    } else {
      isEdit.value = false
      form.value = {
        room_number: '',
        type: 'Standard Single',
        price: 1200000,
        capacity: 1,
        facilities: 'Kasur, Lemari, WiFi',
        description: '',
        status: 'available',
      }
    }
  },
  { immediate: true }
)

const handleSubmit = async () => {
  errorMessage.value = ''
  loading.value = true

  try {
    if (isEdit.value && props.room) {
      // Axios PUT request
      const res = await apiClient.put(`/rooms/${props.room.id}`, form.value)
      toast.success(res.data.message || 'Kamar berhasil diperbarui!')
    } else {
      // Axios POST request
      const res = await apiClient.post('/rooms', form.value)
      toast.success(res.data.message || 'Kamar baru berhasil ditambahkan!')
    }
    emit('saved')
    emit('close')
  } catch (err: any) {
    if (err.response?.data?.errors) {
      const keys = Object.keys(err.response.data.errors)
      if (keys.length > 0 && keys[0]) {
        const firstKey = keys[0]
        errorMessage.value = err.response.data.errors[firstKey][0]
      } else {
        errorMessage.value = 'Data kamar tidak valid.'
      }
    } else if (err.response?.data?.message) {
      errorMessage.value = err.response.data.message
    } else {
      errorMessage.value = 'Gagal menyimpan data kamar.'
    }
    toast.error(errorMessage.value)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content glass-panel">
      <div class="modal-header">
        <h3 class="modal-title">
          {{ isEdit ? 'Ubah Data Kamar' : 'Tambah Kamar Baru' }}
        </h3>
        <p class="modal-subtitle">
          Isi detail data kamar kos yang tersimpan di sistem
        </p>
      </div>

      <div v-if="errorMessage" class="error-banner">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="form-row">
          <div class="form-group flex-1">
            <label class="form-label">Nomor Kamar</label>
            <input
              v-model="form.room_number"
              type="text"
              class="form-control"
              placeholder="e.g. A101"
              required
            />
          </div>

          <div class="form-group flex-1">
            <label class="form-label">Tipe Kamar</label>
            <input
              v-model="form.type"
              type="text"
              class="form-control"
              placeholder="e.g. Deluxe, VIP, Standard"
              required
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group flex-1">
            <label class="form-label">Harga per Bulan (Rp)</label>
            <input
              v-model.number="form.price"
              type="number"
              min="0"
              step="50000"
              class="form-control"
              required
            />
          </div>

          <div class="form-group flex-1">
            <label class="form-label">Kapasitas (Orang)</label>
            <input
              v-model.number="form.capacity"
              type="number"
              min="1"
              max="5"
              class="form-control"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Status Kamar</label>
          <select v-model="form.status" class="form-control">
            <option value="available">Tersedia (Available)</option>
            <option value="full">Penuh (Full)</option>
            <option value="maintenance">Perawatan (Maintenance)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Fasilitas Kamar (Pisahkan dengan koma)</label>
          <input
            v-model="form.facilities"
            type="text"
            class="form-control"
            placeholder="e.g. AC, Kamar Mandi Dalam, Kasur Queen, WiFi"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Deskripsi Tambahan (Opsional)</label>
          <textarea
            v-model="form.description"
            class="form-control"
            rows="3"
            placeholder="Keterangan posisi lantai, sirkulasi udara, dll..."
          ></textarea>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="emit('close')">
            Batal
          </button>
          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Tambah Kamar') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.modal-header {
  margin-bottom: 20px;
}

.modal-title {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text-main);
}

.modal-subtitle {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-top: 4px;
}

.form-row {
  display: flex;
  gap: 16px;
}

.flex-1 {
  flex: 1;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--card-border);
}

.error-banner {
  padding: 10px 14px;
  background: var(--danger-bg);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: var(--radius-md);
  color: #fca5a5;
  font-size: 0.84rem;
  margin-bottom: 16px;
}
</style>
