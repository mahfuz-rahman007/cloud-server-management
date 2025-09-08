<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Label from '@/components/ui/label/Label.vue';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Props {
    type: 'cpu' | 'ram' | 'storage';
    modelValue: number | string;
    error?: string;
}

const props = defineProps<Props>();
const emits = defineEmits<{
    'update:modelValue': [value: number];
}>();

const selectedValue = computed({
    get: () => Number(props.modelValue) || 0,
    set: (value) => emits('update:modelValue', value),
});

const configOptions = computed(() => {
    switch (props.type) {
        case 'cpu':
            return [
                { value: 1, label: '1', description: 'Basic', popular: false },
                { value: 2, label: '2', description: 'Light', popular: true },
                { value: 4, label: '4', description: 'Standard', recommended: true },
                { value: 6, label: '6', description: 'Balanced', popular: false },
                { value: 8, label: '8', description: 'High perf', popular: true },
                { value: 12, label: '12', description: 'Pro', popular: false },
                { value: 20, label: '20', description: 'Heavy', popular: false },
                { value: 32, label: '32', description: 'Enterprise', popular: false },
                { value: 48, label: '48', description: 'Intensive', popular: false },
                { value: 72, label: '72', description: 'Extreme', popular: false },
                { value: 96, label: '96', description: 'Ultra', popular: false },
                { value: 128, label: '128', description: 'Maximum', popular: false },
            ];
        case 'ram':
            return [
                { value: 512, label: '512 MB', description: 'Minimal', popular: false },
                { value: 1024, label: '1 GB', description: 'Basic', popular: false },
                { value: 2048, label: '2 GB', description: 'Light', popular: true },
                { value: 4096, label: '4 GB', description: 'Standard', popular: false },
                { value: 8192, label: '8 GB', description: 'Recommended', recommended: true },
                { value: 16384, label: '16 GB', description: 'High mem', popular: true },
                { value: 32768, label: '32 GB', description: 'Pro', popular: false },
                { value: 65536, label: '64 GB', description: 'Enterprise', popular: false },
                { value: 131072, label: '128 GB', description: 'Heavy', popular: false },
                { value: 262144, label: '256 GB', description: 'Extreme', popular: false },
                { value: 524288, label: '512 GB', description: 'Ultra', popular: false },
                { value: 1048576, label: '1 TB', description: 'Maximum', popular: false },
            ];
        case 'storage':
            return [
                { value: 10, label: '10 GB', description: 'Minimal', popular: false },
                { value: 25, label: '25 GB', description: 'Basic', popular: false },
                { value: 50, label: '50 GB', description: 'Standard', popular: true },
                { value: 100, label: '100 GB', description: 'Recommended', recommended: true },
                { value: 250, label: '250 GB', description: 'Medium', popular: false },
                { value: 500, label: '500 GB', description: 'Large', popular: true },
                { value: 1000, label: '1 TB', description: 'High cap', popular: false },
                { value: 5000, label: '5 TB', description: 'Pro', popular: false },
                { value: 10000, label: '10 TB', description: 'Enterprise', popular: false },
                { value: 25000, label: '25 TB', description: 'Heavy', popular: false },
                { value: 100000, label: '100 TB', description: 'Extreme', popular: false },
                { value: 1048576, label: '1 PB', description: 'Maximum', popular: false },
            ];
        default:
            return [];
    }
});

const title = computed(() => {
    switch (props.type) {
        case 'cpu':
            return 'CPU Configuration';
        case 'ram':
            return 'Memory Configuration';
        case 'storage':
            return 'Storage Configuration';
        default:
            return '';
    }
});

const icon = computed(() => {
    switch (props.type) {
        case 'cpu':
            return '⚡';
        case 'ram':
            return '🧠';
        case 'storage':
            return '💾';
        default:
            return '⚙️';
    }
});
</script>

<template>
    <Card>
        <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-lg">
                <span class="text-xl">{{ icon }}</span>
                {{ title }}
                <span class="text-red-500">*</span>
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <button
                    v-for="option in configOptions"
                    :key="option.value"
                    type="button"
                    :class="
                        cn(
                            'group relative flex flex-col items-start rounded-lg border-2 p-3 text-left transition-all duration-200 hover:shadow-md',
                            selectedValue === option.value
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/20'
                                : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600',
                        )
                    "
                    @click="selectedValue = option.value"
                >
                    <!-- Selection indicator -->
                    <div
                        :class="
                            cn(
                                'absolute top-1.5 right-1.5 h-3 w-3 rounded-full border-2 transition-all duration-200',
                                selectedValue === option.value ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600',
                            )
                        "
                    >
                        <div v-if="selectedValue === option.value" class="h-full w-full scale-50 rounded-full bg-white" />
                    </div>

                    <!-- Badges -->
                    <div class="absolute top-1 left-1 flex gap-1">
                        <Badge
                            v-if="option.recommended"
                            variant="default"
                            size="sm"
                            class="h-auto bg-green-100 px-1 py-0.5 text-[10px] text-green-700 dark:bg-green-900/20 dark:text-green-400"
                        >
                            ★
                        </Badge>
                        <Badge v-else-if="option.popular" variant="secondary" size="sm" class="h-auto px-1 py-0.5 text-[10px]"> ♦ </Badge>
                    </div>

                    <!-- Content -->
                    <div class="mt-4 w-full">
                        <div
                            class="text-sm font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-gray-100 dark:group-hover:text-blue-400"
                        >
                            {{ option.label }}
                        </div>
                        <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ option.description }}
                        </div>
                    </div>
                </button>
            </div>

            <!-- Hidden input for form submission -->
            <input :name="type === 'cpu' ? 'cpu_cores' : type === 'ram' ? 'ram_mb' : 'storage_gb'" :value="selectedValue" type="hidden" />

            <!-- Error message -->
            <div v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Custom input for advanced users -->
            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <Label class="mb-2 text-xs text-gray-500 dark:text-gray-400"> Or enter custom value: </Label>
                <div class="flex items-center gap-2">
                    <input
                        v-model.number="selectedValue"
                        type="number"
                        :min="type === 'cpu' ? 1 : type === 'ram' ? 512 : 10"
                        :max="type === 'cpu' ? 128 : type === 'ram' ? 1048576 : 1048576"
                        class="w-24 rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        :placeholder="type === 'cpu' ? '4' : type === 'ram' ? '8192' : '100'"
                    />
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ type === 'cpu' ? 'cores' : type === 'ram' ? 'MB' : 'GB' }}
                    </span>
                </div>
                <div class="mt-1 text-xs dark:text-gray-500">
                    {{
                        type === 'cpu'
                            ? 'Range: 1-128 cores'
                            : type === 'ram'
                              ? 'Range: 512MB - 1,048,576MB (1TB)'
                              : 'Range: 10GB - 1,048,576GB (1PB)'
                    }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>
