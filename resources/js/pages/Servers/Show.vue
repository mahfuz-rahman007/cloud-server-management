<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Edit, ArrowLeft, Server, Calendar, Cpu, HardDrive, BarChart3 } from 'lucide-vue-next';

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
];


const formatMemory = (mb: number) => {
  if (mb >= 1024) {
    return `${(mb / 1024).toFixed(1)}GB`;
  }
  return `${mb}MB`;
};

const formatStorage = (gb: number) => {
  if (gb >= 1024) {
    return `${(gb / 1024).toFixed(1)}TB`;
  }
  return `${gb}GB`;
};

const formatDate = (dateString: string) => {
  try {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (error) {
    console.error('Date formatting error:', error);
    return 'Invalid Date';
  }
};
</script>

<template>
  <Head :title="props.server.name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg dark:bg-blue-900/20">
              <Server class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ props.server.name }}
              </h1>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ props.server.provider }} • {{ props.server.ip_address }}
              </p>
            </div>
          </div>
          <div class="ml-auto">
            <Badge
              :variant="props.server.status === 'active' ? 'success' : props.server.status === 'inactive' ? 'destructive' : 'warning'"
              class="capitalize"
            >
              {{ props.server.status }}
            </Badge>
          </div>
        </div>
        <div class="flex gap-3">
          <Button variant="default" as-child>
            <Link :href="`/servers/${props.server.id}/edit`">
              <Edit class="w-4 h-4" />
              Edit Server
            </Link>
          </Button>
          <Button variant="outline" as-child>
            <Link href="/servers">
              <ArrowLeft class="w-4 h-4" />
              Back to Servers
            </Link>
          </Button>
        </div>
      </div>

      <!-- Server Details Cards -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Basic Information -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
              Basic Information
            </h3>
          </div>
          <div class="px-6 py-4 space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ props.server.name }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</label>
              <p class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ props.server.ip_address }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Provider</label>
              <p class="mt-1 text-sm capitalize text-gray-900 dark:text-gray-100">{{ props.server.provider }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
              <div class="mt-1">
                <Badge
                  :variant="props.server.status === 'active' ? 'success' : props.server.status === 'inactive' ? 'destructive' : 'warning'"
                  class="capitalize"
                >
                  {{ props.server.status }}
                </Badge>
              </div>
            </div>
          </div>
        </div>

        <!-- Resource Specifications -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
              <Cpu class="w-5 h-5 text-gray-500" />
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Resource Specifications
              </h3>
            </div>
          </div>
          <div class="px-6 py-4 space-y-4">
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded dark:bg-blue-900/20">
                <Cpu class="w-4 h-4 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">CPU Cores</label>
                <p class="text-sm text-gray-900 dark:text-gray-100">{{ props.server.cpu_cores }} cores</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded dark:bg-green-900/20">
                <HardDrive class="w-4 h-4 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Memory (RAM)</label>
                <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatMemory(props.server.ram_mb) }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-8 h-8 bg-purple-100 rounded dark:bg-purple-900/20">
                <HardDrive class="w-4 h-4 text-purple-600 dark:text-purple-400" />
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Storage</label>
                <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatStorage(props.server.storage_gb) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
              <Calendar class="w-5 h-5 text-gray-500" />
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Timeline
              </h3>
            </div>
          </div>
          <div class="px-6 py-4">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(props.server.created_at) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(props.server.updated_at) }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Resource Usage Visualization (Placeholder) -->
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2">
            <BarChart3 class="w-5 h-5 text-gray-500" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
              Resource Usage
            </h3>
          </div>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Real-time resource monitoring (coming soon)
          </p>
        </div>
        <div class="px-6 py-8">
          <div class="text-center text-gray-500 dark:text-gray-400">
            <div class="mx-auto h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
              <BarChart3 class="h-6 w-6" />
            </div>
            <p class="mt-2 text-sm">Resource monitoring dashboard will be implemented here</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>