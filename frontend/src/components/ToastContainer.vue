<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()
</script>

<template>
  <div class="toast-wrapper">
    <transition-group name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast-card"
        :class="`toast-${toast.type}`"
        @click="remove(toast.id)"
      >
        <div class="toast-indicator"></div>
        <div class="toast-body">
          <div v-if="toast.title" class="toast-title">{{ toast.title }}</div>
          <div class="toast-text">{{ toast.message }}</div>
        </div>
        <button class="toast-close" @click.stop="remove(toast.id)">&times;</button>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-wrapper {
  position: fixed;
  bottom: 24px;
  right: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  z-index: 9999;
  max-width: 380px;
  pointer-events: none;
}

.toast-card {
  pointer-events: auto;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: #182234;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: var(--radius-md);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
  cursor: pointer;
  overflow: hidden;
  position: relative;
  transition: transform 0.2s ease;
}

.toast-card:hover {
  transform: translateY(-2px);
}

.toast-indicator {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
}

.toast-success .toast-indicator { background: var(--success); }
.toast-error .toast-indicator { background: var(--danger); }
.toast-warning .toast-indicator { background: var(--warning); }
.toast-info .toast-indicator { background: var(--primary); }

.toast-body {
  flex: 1;
}

.toast-title {
  font-weight: 700;
  font-size: 0.85rem;
  color: var(--text-main);
  margin-bottom: 2px;
}

.toast-text {
  font-size: 0.82rem;
  color: var(--text-muted);
  line-height: 1.4;
}

.toast-close {
  color: var(--text-dim);
  font-size: 1.2rem;
  line-height: 1;
  padding: 0 2px;
  transition: color 0.2s;
}

.toast-close:hover {
  color: var(--text-main);
}

/* Animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease-out;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
