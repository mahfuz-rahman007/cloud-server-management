<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { Eye, Edit, Search, Filter, X, ChevronUp, ChevronDown, Trash2, MoreHorizontal, ChevronLeft, ChevronRight, ArrowUpDown } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { 
  Dialog, 
  DialogContent, 
  DialogDescription, 
  DialogFooter, 
  DialogHeader, 
  DialogTitle, 
  DialogTrigger 
} from '@/components/ui/dialog';

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
  servers: {
    data: Server[];
  };
  filters: {
    status?: string;
    provider?: string;
    search?: string;
    sort?: string;
    direction?: string;
    per_page?: number;
  };
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Servers',
    href: '/servers',
  },
];

// Reactive filters
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const providerFilter = ref(props.filters.provider || '');
const sortField = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

// Bulk operations
const selectedServers = ref<number[]>([]);
const selectAllCheckbox = ref<HTMLInputElement>();
const deleteDialogOpen = ref(false);
const bulkDeleteDialogOpen = ref(false);
const bulkStatusDialogOpen = ref(false);
const serverToDelete = ref<Server | null>(null);
const bulkStatusValue = ref('');
const isDeleting = ref(false);
const isBulkDeleting = ref(false);
const isBulkUpdating = ref(false);

// Computed values
const hasFilters = computed(() => 
  search.value || statusFilter.value || providerFilter.value
);

const hasSelectedServers = computed(() => selectedServers.value.length > 0);

const allServersSelected = computed(() => {
  return props.servers.data.length > 0 && selectedServers.value.length === props.servers.data.length;
});

const someServersSelected = computed(() => {
  const hasSelection = selectedServers.value.length > 0 && selectedServers.value.length < props.servers.data.length;
  
  // Update indeterminate state
  nextTick(() => {
    if (selectAllCheckbox.value) {
      selectAllCheckbox.value.indeterminate = hasSelection;
    }
  });
  
  return hasSelection;
});

// Methods
const applyFilters = () => {
  router.get('/servers', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    provider: providerFilter.value || undefined,
    sort: sortField.value,
    direction: sortDirection.value,
  }, {
    preserveState: true,
    replace: true
  });
};

const clearFilters = () => {
  search.value = '';
  statusFilter.value = '';
  providerFilter.value = '';
  router.get('/servers');
};

const sort = (field: string) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
  applyFilters();
};


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

// Bulk operations methods
const toggleSelectAll = (checked: boolean) => {
  console.log('Toggle select all:', checked, 'Total servers:', props.servers.data.length);
  if (checked) {
    selectedServers.value = props.servers.data.map(server => server.id);
  } else {
    selectedServers.value = [];
  }
  console.log('Selected servers after toggle:', selectedServers.value);
};

const toggleServerSelection = (serverId: number, checked: boolean) => {
  console.log('Toggle server selection:', serverId, 'checked:', checked);
  if (checked) {
    if (!selectedServers.value.includes(serverId)) {
      selectedServers.value.push(serverId);
    }
  } else {
    const index = selectedServers.value.indexOf(serverId);
    if (index > -1) {
      selectedServers.value.splice(index, 1);
    }
  }
  console.log('Selected servers after individual toggle:', selectedServers.value);
};

const isServerSelected = (serverId: number) => {
  return selectedServers.value.includes(serverId);
};

// Delete methods
const openDeleteDialog = (server: Server) => {
  serverToDelete.value = server;
  deleteDialogOpen.value = true;
};

const confirmDelete = async () => {
  if (!serverToDelete.value) return;
  
  isDeleting.value = true;
  try {
    await router.delete(`/servers/${serverToDelete.value.id}`);
    deleteDialogOpen.value = false;
    serverToDelete.value = null;
  } catch (error) {
    console.error('Failed to delete server:', error);
  } finally {
    isDeleting.value = false;
  }
};

// Bulk delete methods
const openBulkDeleteDialog = () => {
  bulkDeleteDialogOpen.value = true;
};

const confirmBulkDelete = async () => {
  isBulkDeleting.value = true;
  try {
    await router.delete('/servers/bulk-destroy', {
      data: { ids: selectedServers.value }
    });
    selectedServers.value = [];
    bulkDeleteDialogOpen.value = false;
  } catch (error) {
    console.error('Failed to bulk delete servers:', error);
  } finally {
    isBulkDeleting.value = false;
  }
};

// Bulk status update methods
const openBulkStatusDialog = () => {
  bulkStatusValue.value = '';
  bulkStatusDialogOpen.value = true;
};

const confirmBulkStatusUpdate = async () => {
  if (!bulkStatusValue.value) return;
  
  isBulkUpdating.value = true;
  try {
    await router.patch('/servers/bulk-update-status', {
      ids: selectedServers.value,
      status: bulkStatusValue.value
    });
    selectedServers.value = [];
    bulkStatusDialogOpen.value = false;
  } catch (error) {
    console.error('Failed to bulk update status:', error);
  } finally {
    isBulkUpdating.value = false;
  }
};

// Pagination methods
const goToPage = (page: number) => {
  router.get('/servers', {
    page,
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    provider: providerFilter.value || undefined,
    sort: sortField.value,
    direction: sortDirection.value,
  }, {
    preserveState: true,
    replace: true
  });
};

const previousPage = () => {
  if (props.pagination.current_page > 1) {
    goToPage(props.pagination.current_page - 1);
  }
};

const nextPage = () => {
  if (props.pagination.current_page < props.pagination.last_page) {
    goToPage(props.pagination.current_page + 1);
  }
};

const getVisiblePages = computed(() => {
  const current = props.pagination.current_page;
  const last = props.pagination.last_page;
  const pages: number[] = [];

  if (last <= 7) {
    // Show all pages if 7 or fewer
    for (let i = 1; i <= last; i++) {
      pages.push(i);
    }
  } else {
    // Show smart pagination
    if (current <= 4) {
      // Show first 5 pages + last page
      for (let i = 1; i <= 5; i++) {
        pages.push(i);
      }
      if (last > 6) pages.push(-1); // Ellipsis
      pages.push(last);
    } else if (current >= last - 3) {
      // Show first page + last 5 pages
      pages.push(1);
      if (last > 6) pages.push(-1); // Ellipsis
      for (let i = last - 4; i <= last; i++) {
        pages.push(i);
      }
    } else {
      // Show first page + current range + last page
      pages.push(1);
      pages.push(-1); // Ellipsis
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i);
      }
      pages.push(-1); // Ellipsis
      pages.push(last);
    }
  }

  return pages;
});
</script>

<template>

  <Head title="Servers" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            Servers
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Manage your cloud servers across different providers
          </p>
        </div>
        <div class="flex items-center gap-3">
          <!-- Bulk Actions -->
          <div v-if="hasSelectedServers" class="flex items-center gap-2">
            <Button variant="destructive" size="sm" class="cursor-pointer" @click="openBulkDeleteDialog">
              <Trash2 class="h-3 w-3" />
              Delete ({{ selectedServers.length }})
            </Button>
            <Button variant="secondary" size="sm" class="cursor-pointer" @click="openBulkStatusDialog">
              <MoreHorizontal class="h-3 w-3" />
              Update Status
            </Button>
          </div>

          <Button as-child>
            <Link href="/servers/create">
            Add Server
            </Link>
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <div
        class="flex flex-wrap gap-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex-1 min-w-72">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search class="h-3 w-3 text-gray-400" />
            </div>
            <input v-model="search" @keyup.enter="applyFilters" type="text"
              placeholder="Search by name or IP address..."
              class="block w-full pl-10 pr-4 py-2.5 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm transition-colors duration-200" />
          </div>
        </div>

        <div class="min-w-40">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
          <select v-model="statusFilter" @change="applyFilters"
            class="block w-full px-3 py-2.5 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm cursor-pointer transition-colors duration-200">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>

        <div class="min-w-44">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Provider</label>
          <select v-model="providerFilter" @change="applyFilters"
            class="block w-full px-3 py-2.5 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm cursor-pointer transition-colors duration-200">
            <option value="">All Providers</option>
            <option value="aws">AWS</option>
            <option value="digitalocean">DigitalOcean</option>
            <option value="vultr">Vultr</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="flex items-end gap-3">
          <Button class="cursor-pointer" @click="applyFilters">
            <Filter class="h-3 w-3" />
            Filter
          </Button>
          <Button v-if="hasFilters" class="cursor-pointer" @click="clearFilters" variant="destructive">
            <X class="h-3 w-3" />
            Clear
          </Button>
        </div>
      </div>

      <!-- Servers Table -->
      <div
        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left">
                  <input ref="selectAllCheckbox" type="checkbox" :checked="allServersSelected"
                    @change="(e) => toggleSelectAll((e.target as HTMLInputElement).checked)"
                    class="peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50" />
                </th>
                <th @click="sort('name')"
                  class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-600 transition-all duration-200 select-none group">
                  <div class="flex items-center gap-2">
                    <span>Name</span>
                    <div class="flex flex-col">
                      <ChevronUp v-if="sortField === 'name' && sortDirection === 'asc'" class="h-3 w-3 text-blue-500" />
                      <ChevronDown v-else-if="sortField === 'name' && sortDirection === 'desc'" class="h-3 w-3 text-blue-500" />
                      <ArrowUpDown v-else class="h-3 w-3 opacity-50 group-hover:opacity-100 transition-opacity" />
                    </div>
                  </div>
                </th>
                <th @click="sort('ip_address')"
                  class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-600 transition-all duration-200 select-none group">
                  <div class="flex items-center gap-2">
                    <span>IP Address</span>
                    <div class="flex flex-col">
                      <ChevronUp v-if="sortField === 'ip_address' && sortDirection === 'asc'" class="h-3 w-3 text-blue-500" />
                      <ChevronDown v-else-if="sortField === 'ip_address' && sortDirection === 'desc'" class="h-3 w-3 text-blue-500" />
                      <ArrowUpDown v-else class="h-3 w-3 opacity-50 group-hover:opacity-100 transition-opacity" />
                    </div>
                  </div>
                </th>
                <th @click="sort('provider')"
                  class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-600 transition-all duration-200 select-none group">
                  <div class="flex items-center gap-2">
                    <span>Provider</span>
                    <div class="flex flex-col">
                      <ChevronUp v-if="sortField === 'provider' && sortDirection === 'asc'" class="h-3 w-3 text-blue-500" />
                      <ChevronDown v-else-if="sortField === 'provider' && sortDirection === 'desc'" class="h-3 w-3 text-blue-500" />
                      <ArrowUpDown v-else class="h-3 w-3 opacity-50 group-hover:opacity-100 transition-opacity" />
                    </div>
                  </div>
                </th>
                <th @click="sort('status')"
                  class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-600 transition-all duration-200 select-none group">
                  <div class="flex items-center gap-2">
                    <span>Status</span>
                    <div class="flex flex-col">
                      <ChevronUp v-if="sortField === 'status' && sortDirection === 'asc'" class="h-3 w-3 text-blue-500" />
                      <ChevronDown v-else-if="sortField === 'status' && sortDirection === 'desc'" class="h-3 w-3 text-blue-500" />
                      <ArrowUpDown v-else class="h-3 w-3 opacity-50 group-hover:opacity-100 transition-opacity" />
                    </div>
                  </div>
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                  <div class="flex flex-col">
                    <span>Resources</span>
                    <span class="text-xs font-normal normal-case text-gray-400">CPU • RAM • Storage</span>
                  </div>
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
              <tr v-for="server in props.servers.data" :key="server.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4">
                  <input type="checkbox" :checked="isServerSelected(server.id)"
                    @change="(e) => toggleServerSelection(server.id, (e.target as HTMLInputElement).checked)"
                    class="peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50" />
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ server.name }}</div>
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-gray-100">{{ server.ip_address }}</div>
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="text-sm capitalize text-gray-900 dark:text-gray-100">{{ server.provider }}</div>
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <Badge
                    :variant="server.status === 'active' ? 'success' : server.status === 'inactive' ? 'destructive' : 'warning'"
                    class="capitalize">
                    {{ server.status }}
                  </Badge>
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <Badge variant="default" size="sm"
                        class="bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                        {{ server.cpu_cores }} CPU cores
                      </Badge>
                    </div>
                    <div class="flex items-center gap-2">
                      <Badge variant="default" size="sm"
                        class="bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {{ formatMemory(server.ram_mb) }} RAM
                      </Badge>
                    </div>
                    <div class="flex items-center gap-2">
                      <Badge variant="default" size="sm"
                        class="bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400">
                        {{ formatStorage(server.storage_gb) }} Storage
                      </Badge>
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="flex gap-2">
                    <Button variant="outline" size="sm" as-child title="View server details">
                      <Link :href="`/servers/${server.id}`">
                      <Eye class="h-3 w-3" />
                      </Link>
                    </Button>
                    <Button variant="secondary" size="sm" as-child title="Edit server">
                      <Link :href="`/servers/${server.id}/edit`">
                      <Edit class="h-3 w-3" />
                      </Link>
                    </Button>
                    <Button variant="destructive" size="sm" class="cursor-pointer" @click="openDeleteDialog(server)"
                      title="Delete server">
                      <Trash2 class="h-3 w-3" />
                    </Button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0 && pagination.last_page > 1"
          class="border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
          <div class="flex items-center justify-between">
            <!-- Pagination Info -->
            <div class="text-sm text-gray-700 dark:text-gray-300">
              Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} to
              {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} of
              {{ pagination.total }} results
            </div>
            
            <!-- Pagination Controls -->
            <div class="flex items-center space-x-2">
              <!-- Previous Button -->
              <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page === 1"
                @click="previousPage"
                class="cursor-pointer"
              >
                <ChevronLeft class="h-3 w-3" />
                Previous
              </Button>
              
              <!-- Page Numbers -->
              <div class="flex items-center space-x-1">
                <template v-for="page in getVisiblePages" :key="page">
                  <span v-if="page === -1" class="px-2 py-1 text-sm text-gray-500">...</span>
                  <Button
                    v-else
                    :variant="page === pagination.current_page ? 'default' : 'outline'"
                    size="sm"
                    @click="goToPage(page)"
                    class="cursor-pointer min-w-[32px]"
                  >
                    {{ page }}
                  </Button>
                </template>
              </div>
              
              <!-- Next Button -->
              <Button
                variant="outline"
                size="sm"
                :disabled="pagination.current_page === pagination.last_page"
                @click="nextPage"
                class="cursor-pointer"
              >
                Next
                <ChevronRight class="h-3 w-3" />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="props.servers.data.length === 0" class="text-center py-12">
        <div class="text-gray-500 dark:text-gray-400">
          <p class="text-lg font-medium">No servers found</p>
          <p class="mt-1">Get started by adding your first server.</p>
          <Button as-child class="mt-4">
            <Link href="/servers/create">
            Add Server
            </Link>
          </Button>
        </div>
      </div>

      <!-- Delete Confirmation Dialog -->
      <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Delete Server</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete "{{ serverToDelete?.name }}"? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter class="gap-3">
            <Button variant="outline" @click="deleteDialogOpen = false" :disabled="isDeleting" class="cursor-pointer">
              Cancel
            </Button>
            <Button variant="destructive" @click="confirmDelete" :disabled="isDeleting" class="cursor-pointer">
              <Trash2 v-if="!isDeleting" class="h-3 w-3" />
              {{ isDeleting ? 'Deleting...' : 'Delete' }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Bulk Delete Confirmation Dialog -->
      <Dialog v-model:open="bulkDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Delete Multiple Servers</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete {{ selectedServers.length }} selected servers? This action cannot be
              undone.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter class="gap-3">
            <Button variant="outline" @click="bulkDeleteDialogOpen = false" :disabled="isBulkDeleting" class="cursor-pointer">
              Cancel
            </Button>
            <Button variant="destructive" @click="confirmBulkDelete" :disabled="isBulkDeleting" class="cursor-pointer">
              <Trash2 v-if="!isBulkDeleting" class="h-3 w-3" />
              {{ isBulkDeleting ? 'Deleting...' : 'Delete All' }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Bulk Status Update Dialog -->
      <Dialog v-model:open="bulkStatusDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Update Server Status</DialogTitle>
            <DialogDescription>
              Update the status for {{ selectedServers.length }} selected servers.
            </DialogDescription>
          </DialogHeader>
          <div class="py-4">
            <select v-model="bulkStatusValue"
              class="block w-full px-3 py-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm cursor-pointer transition-colors duration-200">
              <option value="">Select new status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>
          <DialogFooter class="gap-3">
            <Button variant="outline" @click="bulkStatusDialogOpen = false" :disabled="isBulkUpdating" class="cursor-pointer">
              Cancel
            </Button>
            <Button @click="confirmBulkStatusUpdate" :disabled="isBulkUpdating || !bulkStatusValue" class="cursor-pointer">
              <MoreHorizontal v-if="!isBulkUpdating" class="h-3 w-3" />
              {{ isBulkUpdating ? 'Updating...' : 'Update Status' }}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>