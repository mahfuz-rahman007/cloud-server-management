<script setup lang="ts">
import { AlertCircle, CheckCircle, Info, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    message: string;
    type?: 'success' | 'error' | 'info';
    duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    duration: 5000,
});

const emit = defineEmits<{
    close: [];
}>();

const visible = ref(true);

const icon = computed(() => {
    switch (props.type) {
        case 'success':
            return CheckCircle;
        case 'error':
            return AlertCircle;
        default:
            return Info;
    }
});

const typeClasses = computed(() => {
    switch (props.type) {
        case 'success':
            return 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400';
        case 'error':
            return 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400';
        default:
            return 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400';
    }
});

const close = () => {
    visible.value = false;
    setTimeout(() => emit('close'), 300);
};

onMounted(() => {
    if (props.duration > 0) {
        setTimeout(close, props.duration);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 transform translate-y-2 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="opacity-100 transform translate-y-0 sm:translate-x-0"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="visible" :class="typeClasses" class="w-full max-w-sm rounded-lg border p-4 shadow-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <component :is="icon" class="h-5 w-5" />
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">{{ message }}</p>
                </div>
                <div class="ml-4 flex flex-shrink-0">
                    <button @click="close" class="inline-flex rounded-md focus:ring-2 focus:ring-current focus:ring-offset-2 focus:outline-none">
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
