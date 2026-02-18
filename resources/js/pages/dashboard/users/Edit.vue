<script setup lang="ts">
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import UserForm from '@/components/admin/UserForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface FieldDefinition {
    name: string;
    type: string;
    label: string;
    required?: boolean;
    [key: string]: unknown;
}

interface UserRecord {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

interface Props {
    user: UserRecord;
    fields: FieldDefinition[];
    isSelf: boolean;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/users';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: routePrefix },
    { title: 'Edit', href: `${routePrefix}/${props.user.id}/edit` },
];

const initFormData = (): Record<string, unknown> => {
    const data: Record<string, unknown> = {};
    for (const field of props.fields) {
        if (field.type === 'checkbox') {
            data[field.name] = props.user[field.name] ?? false;
        } else if (field.type === 'password') {
            data[field.name] = '';
        } else {
            data[field.name] = props.user[field.name] ?? '';
        }
    }
    data.password_confirmation = '';
    return data;
};

const form = ref<Record<string, unknown>>(initFormData());
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const deleteDialogOpen = ref(false);
const isDeleting = ref(false);

const handleSubmit = () => {
    processing.value = true;
    errors.value = {};

    router.patch(`${routePrefix}/${props.user.id}`, form.value, {
        preserveScroll: true,
        onError: (err) => { errors.value = err; },
        onFinish: () => { processing.value = false; },
    });
};

const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`${routePrefix}/${props.user.id}`, {
        onSuccess: () => { router.get(routePrefix); },
        onFinish: () => { isDeleting.value = false; },
    });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`Edit: ${user.name} - Dashboard`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button variant="ghost" size="sm" as="a" :href="routePrefix" class="mb-2">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Users
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">Edit User</h1>
                    <p class="mt-1 text-muted-foreground">Update user details</p>
                </div>
                <Button v-if="!isSelf" variant="destructive" @click="deleteDialogOpen = true">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete User
                </Button>
            </div>

            <!-- Self-edit warning -->
            <Card v-if="isSelf" class="border-yellow-500/50 bg-yellow-500/5">
                <CardContent class="pt-6">
                    <div class="flex items-center gap-3">
                        <AlertTriangle class="h-5 w-5 text-yellow-600" />
                        <p class="text-sm font-medium text-yellow-600">
                            You are editing your own account
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle>User Metadata</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-3">
                        <div>
                            <p class="text-muted-foreground">Created</p>
                            <p class="font-medium">{{ formatDate(user.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Last Updated</p>
                            <p class="font-medium">{{ formatDate(user.updated_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Role</p>
                            <Badge :variant="user.is_admin ? 'default' : 'secondary'">
                                {{ user.is_admin ? 'Admin' : 'User' }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Form -->
            <form @submit.prevent="handleSubmit">
                <UserForm
                    :fields="fields"
                    v-model="form"
                    :errors="errors"
                    :is-edit="true"
                    :is-self="isSelf"
                />

                <div class="mt-6 flex gap-3 border-t pt-4">
                    <Button type="button" variant="outline" as="a" :href="routePrefix" :disabled="processing">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>

        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Delete User"
            :description="`Are you sure you want to delete &quot;${user.name}&quot;? This action cannot be undone.`"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="() => (deleteDialogOpen = false)"
        />
    </AppLayout>
</template>
