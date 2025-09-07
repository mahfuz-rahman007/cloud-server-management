<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Form } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Button from '@/components/ui/button/Button.vue';
import ServerConfigCard from '@/components/ServerConfigCard.vue';
import { ArrowLeft } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Servers',
    href: '/servers',
  },
  {
    title: 'Create',
    href: '/servers/create',
  },
];

const cpuCores = ref<number>(4)
const ramMb = ref<number>(8192)
const storageGb = ref<number>(100)
</script>

<template>

  <Head title="Create Server" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-semibold text-gray-900 dark:text-gray-100">
            Create Server
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Add a new server to your infrastructure
          </p>
        </div>
        <Button variant="outline" as-child>
          <Link href="/servers">
            <ArrowLeft class="w-4 h-4" />
            Back to Servers
          </Link>
        </Button>
      </div>

      <!-- Form -->
      <div
        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
        <Form action="/servers" method="post" #default="{
          errors,
          hasErrors,
          processing,
          wasSuccessful,
        }" class="p-8">
          <div class="space-y-8">
            <!-- Basic Information -->
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                  Basic Information
                </h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <!-- Name -->
                  <div class="space-y-2">
                    <Label for="name">
                      Server Name <span class="text-red-500">*</span>
                    </Label>
                    <Input id="name" name="name" type="text" required placeholder="e.g., web-server-01"
                      class="w-full" />
                    <div v-if="errors.name" class="text-sm text-red-600 dark:text-red-400">
                      {{ errors.name }}
                    </div>
                  </div>

                  <!-- IP Address -->
                  <div class="space-y-2">
                    <Label for="ip_address">
                      IP Address <span class="text-red-500">*</span>
                    </Label>
                    <Input id="ip_address" name="ip_address" type="text" required placeholder="192.168.1.100"
                      class="w-full" />
                    <div v-if="errors.ip_address" class="text-sm text-red-600 dark:text-red-400">
                      {{ errors.ip_address }}
                    </div>
                  </div>

                  <!-- Provider -->
                  <div class="space-y-2">
                    <Label for="provider">
                      Provider <span class="text-red-500">*</span>
                    </Label>
                    <select id="provider" name="provider" required
                      class="w-full h-9 px-3 py-1 text-sm rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30">
                      <option value="">Select a provider</option>
                      <option value="aws">AWS</option>
                      <option value="digitalocean">DigitalOcean</option>
                      <option value="vultr">Vultr</option>
                      <option value="other">Other</option>
                    </select>
                    <div v-if="errors.provider" class="text-sm text-red-600 dark:text-red-400">
                      {{ errors.provider }}
                    </div>
                  </div>

                  <!-- Status -->
                  <div class="space-y-2">
                    <Label for="status">
                      Status <span class="text-red-500">*</span>
                    </Label>
                    <select id="status" name="status" required
                      class="w-full h-9 px-3 py-1 text-sm rounded-md border border-input bg-transparent shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] dark:bg-input/30">
                      <option value="active" selected>Active</option>
                      <option value="inactive">Inactive</option>
                      <option value="maintenance">Maintenance</option>
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                  Server Configuration
                </h3>
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
          <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            <Button variant="outline" as-child>
              <Link href="/servers">
              Cancel
              </Link>
            </Button>
            <Button type="submit" :disabled="processing" class="cursor-pointer">
              {{ processing ? 'Creating...' : 'Create Server' }}
            </Button>
          </div>

        </Form>
      </div>
    </div>
  </AppLayout>
</template>