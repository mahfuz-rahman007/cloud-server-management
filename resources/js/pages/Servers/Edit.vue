<script setup lang="ts">
import ServerConfigCard from '@/components/ServerConfigCard.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

interface Server {
    id: number;
    name: string;
    ip_address: string;
    provider: 'aws' | 'digitalocean' | 'vultr' | 'other';
    status: 'active' | 'inactive' | 'maintenance';
    cpu_cores: number;
    ram_mb: number;
    storage_gb: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    server: Server;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Servers',
        href: '/servers',
    },
    {
        title: props.server.name,
        href: `/servers/${props.server.id}`,
    },
    {
        title: 'Edit',
        href: `/servers/${props.server.id}/edit`,
    },
];

const cpuCores = ref<number>(props.server.cpu_cores);
const ramMb = ref<number>(props.server.ram_mb);
const storageGb = ref<number>(props.server.storage_gb);
</script>

<template>
    <Head :title="`Edit ${props.server.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900 dark:text-gray-100">Edit {{ props.server.name }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Update server configuration and settings</p>
                </div>

                <Button variant="outline" as-child>
                    <Link href="/servers">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Servers
                    </Link>
                </Button>
            </div>

            <!-- Form -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                <Form :action="`/servers/${props.server.id}`" method="put" #default="{ errors, processing }" class="p-8">
                    <!-- Hidden field for version control -->
                    <input type="hidden" name="updated_at" :value="props.server.updated_at" />

                    <!-- Version conflict error -->
                    <div v-if="errors.updated_at" class="mb-3 rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Version Conflict Detected</h3>
                                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    <p>{{ errors.updated_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <!-- Basic Information -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Basic Information</h3>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <!-- Name -->
                                    <div class="space-y-2">
                                        <Label for="name"> Server Name <span class="text-red-500">*</span> </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            type="text"
                                            required
                                            :model-value="props.server.name"
                                            placeholder="e.g., web-server-01"
                                            class="w-full"
                                        />
                                        <div v-if="errors.name" class="text-sm text-red-600 dark:text-red-400">
                                            {{ errors.name }}
                                        </div>
                                    </div>

                                    <!-- IP Address -->
                                    <div class="space-y-2">
                                        <Label for="ip_address"> IP Address <span class="text-red-500">*</span> </Label>
                                        <Input
                                            id="ip_address"
                                            name="ip_address"
                                            type="text"
                                            required
                                            :model-value="props.server.ip_address"
                                            placeholder="192.168.1.100"
                                            class="w-full"
                                        />
                                        <div v-if="errors.ip_address" class="text-sm text-red-600 dark:text-red-400">
                                            {{ errors.ip_address }}
                                        </div>
                                    </div>

                                    <!-- Provider -->
                                    <div class="space-y-2">
                                        <Label for="provider"> Provider <span class="text-red-500">*</span> </Label>
                                        <select
                                            id="provider"
                                            name="provider"
                                            required
                                            :value="props.server.provider"
                                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                        >
                                            <option value="">Select a provider</option>
                                            <option value="aws" :selected="props.server.provider === 'aws'">AWS</option>
                                            <option value="digitalocean" :selected="props.server.provider === 'digitalocean'">DigitalOcean</option>
                                            <option value="vultr" :selected="props.server.provider === 'vultr'">Vultr</option>
                                            <option value="other" :selected="props.server.provider === 'other'">Other</option>
                                        </select>
                                        <div v-if="errors.provider" class="text-sm text-red-600 dark:text-red-400">
                                            {{ errors.provider }}
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="space-y-2">
                                        <Label for="status"> Status <span class="text-red-500">*</span> </Label>
                                        <select
                                            id="status"
                                            name="status"
                                            required
                                            :value="props.server.status"
                                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                        >
                                            <option value="active" :selected="props.server.status === 'active'">Active</option>
                                            <option value="inactive" :selected="props.server.status === 'inactive'">Inactive</option>
                                            <option value="maintenance" :selected="props.server.status === 'maintenance'">Maintenance</option>
                                        </select>
                                        <div v-if="errors.status" class="text-sm text-red-600 dark:text-red-400">
                                            {{ errors.status }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Server Configuration -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="mb-6 text-lg font-medium text-gray-900 dark:text-gray-100">Server Configuration</h3>
                                <div class="space-y-6">
                                    <!-- CPU Configuration -->
                                    <ServerConfigCard type="cpu" v-model="cpuCores" :error="errors.cpu_cores" />

                                    <!-- RAM Configuration -->
                                    <ServerConfigCard type="ram" v-model="ramMb" :error="errors.ram_mb" />

                                    <!-- Storage Configuration -->
                                    <ServerConfigCard type="storage" v-model="storageGb" :error="errors.storage_gb" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <Button variant="outline" as-child>
                            <Link href="/servers"> Cancel </Link>
                        </Button>
                        <Button type="submit" :disabled="processing" class="cursor-pointer">
                            {{ processing ? 'Updating...' : 'Update Server' }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
