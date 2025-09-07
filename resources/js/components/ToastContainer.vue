<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Toast from './Toast.vue';

interface ToastMessage {
  id: string;
  message: string;
  type: 'success' | 'error' | 'info';
}

const toasts = ref<ToastMessage[]>([]);

const page = usePage();

const addToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
  // Prevent duplicate messages
  const isDuplicate = toasts.value.some(toast => toast.message === message && toast.type === type);
  if (isDuplicate) return;
  
  const id = Date.now().toString();
  toasts.value.push({ id, message, type });
};

const removeToast = (id: string) => {
  const index = toasts.value.findIndex(toast => toast.id === id);
  if (index > -1) {
    toasts.value.splice(index, 1);
  }
};

// Watch for flash messages from Laravel
watch(() => page.props, (props: any) => {
  // Check for success messages
  if (props.flash?.success) {
    addToast(props.flash.success, 'success');
  }
  
  // Check for error messages
  if (props.flash?.error) {
    addToast(props.flash.error, 'error');
  }
  
  // Check for info messages
  if (props.flash?.info) {
    addToast(props.flash.info, 'info');
  }
}, { deep: true, immediate: true });

</script>

<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <Toast
      v-for="toast in toasts"
      :key="toast.id"
      :message="toast.message"
      :type="toast.type"
      @close="removeToast(toast.id)"
    />
  </div>
</template>