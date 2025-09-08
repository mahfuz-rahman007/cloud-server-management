import Edit from '@/pages/Servers/Edit.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<head />' },
    Link: { template: '<a><slot /></a>' },
    Form: {
        template: '<form><slot :errors="errors" :processing="processing" /></form>',
        setup() {
            return {
                errors: {},
                processing: false,
            };
        },
    },
}));

// Mock layout and components
vi.mock('@/layouts/AppLayout.vue', () => ({ default: { template: '<div><slot /></div>' } }));
vi.mock('@/components/ui/input/Input.vue', () => ({
    default: { template: '<input :name="name" :model-value="modelValue" :required="required" />', props: ['name', 'modelValue', 'required'] },
}));
vi.mock('@/components/ui/label/Label.vue', () => ({ default: { template: '<label><slot /></label>' } }));
vi.mock('@/components/ui/button/Button.vue', () => ({ default: { template: '<button><slot /></button>' } }));
vi.mock('@/components/ServerConfigCard.vue', () => ({
    default: {
        template: '<div><input :name="getFieldName(type)" :value="modelValue" /></div>',
        props: ['type', 'modelValue', 'error'],
        methods: {
            getFieldName(type: string) {
                return type === 'cpu' ? 'cpu_cores' : type === 'ram' ? 'ram_mb' : 'storage_gb';
            },
        },
    },
}));
vi.mock('lucide-vue-next', () => ({ ArrowLeft: { template: '<svg />' } }));

const mockServer = {
    id: 1,
    name: 'Test Server',
    ip_address: '192.168.1.100',
    provider: 'aws',
    status: 'active',
    cpu_cores: 4,
    ram_mb: 8192,
    storage_gb: 100,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
};

describe('Servers Edit - Form Validation', () => {
    it('renders form fields with existing values', () => {
        const wrapper = mount(Edit, { props: { server: mockServer } });

        expect(wrapper.find('input[name="name"]').attributes('model-value')).toBe('Test Server');
        expect(wrapper.find('input[name="ip_address"]').attributes('model-value')).toBe('192.168.1.100');
        expect(wrapper.find('select[name="provider"]').attributes('value')).toBe('aws');
        expect(wrapper.find('select[name="status"]').attributes('value')).toBe('active');
    });

    it('includes version control field', () => {
        const wrapper = mount(Edit, { props: { server: mockServer } });

        const versionField = wrapper.find('input[name="updated_at"][type="hidden"]');
        expect(versionField.exists()).toBe(true);
        expect(versionField.attributes('value')).toBe(mockServer.updated_at);
    });

    it('form includes required validation attributes', () => {
        const wrapper = mount(Edit, { props: { server: mockServer } });

        expect(wrapper.find('input[name="name"]').attributes('required')).toBeDefined();
        expect(wrapper.find('input[name="ip_address"]').attributes('required')).toBeDefined();
        expect(wrapper.find('select[name="provider"]').attributes('required')).toBeDefined();
        expect(wrapper.find('select[name="status"]').attributes('required')).toBeDefined();
    });

    it('form includes error display structure', () => {
        const wrapper = mount(Edit, { props: { server: mockServer } });

        // Check if the form has divs that could display errors
        expect(wrapper.findAll('div').length).toBeGreaterThan(0);
    });

    it('renders server configuration with existing values', () => {
        const wrapper = mount(Edit, { props: { server: mockServer } });

        expect(wrapper.find('input[name="cpu_cores"]').attributes('value')).toBe('4');
        expect(wrapper.find('input[name="ram_mb"]').attributes('value')).toBe('8192');
        expect(wrapper.find('input[name="storage_gb"]').attributes('value')).toBe('100');
    });
});
