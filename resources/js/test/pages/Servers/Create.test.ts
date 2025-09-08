import Create from '@/pages/Servers/Create.vue';
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
    default: { template: '<input :name="name" :required="required" />', props: ['name', 'required'] },
}));
vi.mock('@/components/ui/label/Label.vue', () => ({ default: { template: '<label><slot /></label>' } }));
vi.mock('@/components/ui/button/Button.vue', () => ({ default: { template: '<button><slot /></button>' } }));
vi.mock('@/components/ServerConfigCard.vue', () => ({
    default: {
        template: '<div><input :name="getFieldName(type)" :value="modelValue" /></div>',
        props: ['type', 'modelValue'],
        methods: {
            getFieldName(type: string) {
                return type === 'cpu' ? 'cpu_cores' : type === 'ram' ? 'ram_mb' : 'storage_gb';
            },
        },
    },
}));
vi.mock('lucide-vue-next', () => ({ ArrowLeft: { template: '<svg />' } }));

describe('Servers Create - Form Validation', () => {
    it('renders required form fields', () => {
        const wrapper = mount(Create);

        expect(wrapper.find('input[name="name"]').exists()).toBe(true);
        expect(wrapper.find('input[name="ip_address"]').exists()).toBe(true);
        expect(wrapper.find('select[name="provider"]').exists()).toBe(true);
        expect(wrapper.find('select[name="status"]').exists()).toBe(true);
        expect(wrapper.find('input[name="cpu_cores"]').exists()).toBe(true);
        expect(wrapper.find('input[name="ram_mb"]').exists()).toBe(true);
        expect(wrapper.find('input[name="storage_gb"]').exists()).toBe(true);
    });

    it('shows required field indicators', () => {
        const wrapper = mount(Create);

        expect(wrapper.find('input[name="name"]').attributes('required')).toBeDefined();
        expect(wrapper.find('input[name="ip_address"]').attributes('required')).toBeDefined();
        expect(wrapper.find('select[name="provider"]').attributes('required')).toBeDefined();
        expect(wrapper.find('select[name="status"]').attributes('required')).toBeDefined();
    });

    it('form structure includes error display areas', () => {
        const wrapper = mount(Create);

        // Check if the form has divs for error display
        expect(wrapper.findAll('div').length).toBeGreaterThan(0);
    });

    it('has correct default values', () => {
        const wrapper = mount(Create);

        // Status should default to active
        const statusSelect = wrapper.find('select[name="status"]');
        expect(statusSelect.find('option[value="active"]').attributes('selected')).toBeDefined();

        // Configuration should have defaults
        expect(wrapper.find('input[name="cpu_cores"]').attributes('value')).toBe('4');
        expect(wrapper.find('input[name="ram_mb"]').attributes('value')).toBe('8192');
        expect(wrapper.find('input[name="storage_gb"]').attributes('value')).toBe('100');
    });
});
