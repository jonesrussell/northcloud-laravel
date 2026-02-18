<script setup lang="ts">
import UserForm from '@/components/admin/UserForm.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

interface FieldDefinition {
    name: string;
    type: string;
    label: string;
    required?: boolean;
    [key: string]: unknown;
}

interface Props {
    fields: FieldDefinition[];
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/users';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: routePrefix },
    { title: 'Create', href: `${routePrefix}/create` },
];

const initFormData = (): Record<string, unknown> => {
    const data: Record<string, unknown> = {};
    for (const field of props.fields) {
        if (field.type === 'checkbox') data[field.name] = false;
        else data[field.name] = '';
    }
    data.password_confirmation = '';
    return data;
};

const form = ref<Record<string, unknown>>(initFormData());
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const handleSubmit = () => {
    processing.value = true;
    errors.value = {};

    router.post(routePrefix, form.value, {
        preserveScroll: true,
        onError: (err) => { errors.value = err; },
        onFinish: () => { processing.value = false; },
    });
};
</script>

<template>
    <Head title="Create User - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button variant="ghost" size="sm" as="a" :href="routePrefix" class="mb-2">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Users
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">Create User</h1>
                    <p class="mt-1 text-muted-foreground">Add a new user account</p>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleSubmit">
                <UserForm
                    :fields="fields"
                    v-model="form"
                    :errors="errors"
                    :is-edit="false"
                />

                <!-- Actions -->
                <div class="mt-6 flex gap-3 border-t pt-4">
                    <Button type="button" variant="outline" as="a" :href="routePrefix" :disabled="processing">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create User' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
