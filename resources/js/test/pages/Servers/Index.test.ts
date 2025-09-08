import Index from '@/pages/Servers/Index.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<head />' },
    Link: { template: '<a><slot /></a>' },
    router: { get: vi.fn(), delete: vi.fn(), patch: vi.fn() },
}));

// Mock layout and components
vi.mock('@/layouts/AppLayout.vue', () => ({ default: { template: '<div><slot /></div>' } }));
vi.mock('@/components/ui/button', () => ({ Button: { template: '<button><slot /></button>' } }));
vi.mock('@/components/ui/badge', () => ({ Badge: { template: '<span><slot /></span>' } }));
vi.mock('@/components/ui/checkbox', () => ({ Checkbox: { template: '<input type="checkbox" />' } }));
vi.mock('@/components/ui/dialog', () => ({
    Dialog: { template: '<div><slot /></div>' },
    DialogContent: { template: '<div><slot /></div>' },
    DialogDescription: { template: '<div><slot /></div>' },
    DialogFooter: { template: '<div><slot /></div>' },
    DialogHeader: { template: '<div><slot /></div>' },
    DialogTitle: { template: '<div><slot /></div>' },
    DialogTrigger: { template: '<div><slot /></div>' },
}));

// Mock icons
vi.mock('lucide-vue-next', () => ({
    Eye: { template: '<svg />' },
    Edit: { template: '<svg />' },
    Search: { template: '<svg />' },
    Filter: { template: '<svg />' },
    X: { template: '<svg />' },
    ChevronUp: { template: '<svg />' },
    ChevronDown: { template: '<svg />' },
    Trash2: { template: '<svg />' },
    MoreHorizontal: { template: '<svg />' },
    ChevronLeft: { template: '<svg />' },
    ChevronRight: { template: '<svg />' },
    ArrowUpDown: { template: '<svg />' },
}));

const mockProps = {
    servers: {
        data: [
            {
                id: 1,
                name: 'Web Server 01',
                ip_address: '192.168.1.100',
                provider: 'aws',
                status: 'active',
                cpu_cores: 4,
                ram_mb: 8192,
                storage_gb: 100,
                created_at: '2024-01-01T00:00:00Z',
                updated_at: '2024-01-01T00:00:00Z',
            },
            {
                id: 2,
                name: 'DB Server 01',
                ip_address: '192.168.1.101',
                provider: 'digitalocean',
                status: 'maintenance',
                cpu_cores: 8,
                ram_mb: 16384,
                storage_gb: 500,
                created_at: '2024-01-02T00:00:00Z',
                updated_at: '2024-01-02T00:00:00Z',
            },
        ],
    },
    filters: {
        status: '',
        provider: '',
        search: '',
        sort: 'created_at',
        direction: 'desc',
    },
    pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 2,
    },
};

describe('Servers Index - Table Data', () => {
    it('displays server data in table', () => {
        const wrapper = mount(Index, { props: mockProps });

        expect(wrapper.text()).toContain('Web Server 01');
        expect(wrapper.text()).toContain('DB Server 01');
        expect(wrapper.text()).toContain('192.168.1.100');
        expect(wrapper.text()).toContain('192.168.1.101');
    });

    it('formats memory correctly', () => {
        const wrapper = mount(Index, { props: mockProps });

        expect(wrapper.text()).toContain('8.0GB RAM');
        expect(wrapper.text()).toContain('16.0GB RAM');
    });

    it('shows server statuses', () => {
        const wrapper = mount(Index, { props: mockProps });

        expect(wrapper.text()).toContain('active');
        expect(wrapper.text()).toContain('maintenance');
    });

    it('shows empty state when no servers', () => {
        const emptyProps = {
            ...mockProps,
            servers: { data: [] },
            pagination: { ...mockProps.pagination, total: 0 },
        };

        const wrapper = mount(Index, { props: emptyProps });
        expect(wrapper.text()).toContain('No servers found');
    });
});
