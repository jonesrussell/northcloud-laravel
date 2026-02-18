<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ArrowLeft, Edit } from 'lucide-vue-next';

interface UserRecord {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
    two_factor_confirmed_at?: string | null;
    [key: string]: unknown;
}

interface Props {
    user: UserRecord;
    fields: Array<Record<string, unknown>>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/users';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: routePrefix },
    { title: props.user.name, href: '#' },
];

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`${user.name} - Dashboard`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button variant="ghost" size="sm" as="a" :href="routePrefix" class="mb-2">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Users
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">{{ user.name }}</h1>
                </div>
                <Button as="a" :href="`${routePrefix}/${user.id}/edit`">
                    <Edit class="mr-2 h-4 w-4" />
                    Edit User
                </Button>
            </div>

            <!-- User Details -->
            <Card>
                <CardHeader>
                    <CardTitle>User Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-3">
                        <div>
                            <p class="text-muted-foreground">Name</p>
                            <p class="font-medium">{{ user.name }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Email</p>
                            <p class="font-medium">{{ user.email }}</p>
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

            <!-- Account Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Account Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-3">
                        <div>
                            <p class="text-muted-foreground">Created</p>
                            <p class="font-medium">{{ formatDateTime(user.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Last Updated</p>
                            <p class="font-medium">{{ formatDateTime(user.updated_at) }}</p>
                        </div>
                        <div v-if="'two_factor_confirmed_at' in user">
                            <p class="text-muted-foreground">Two-Factor Authentication</p>
                            <Badge :variant="user.two_factor_confirmed_at ? 'default' : 'secondary'">
                                {{ user.two_factor_confirmed_at ? 'Enabled' : 'Disabled' }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
