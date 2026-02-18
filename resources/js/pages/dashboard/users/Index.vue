<script setup lang="ts">
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import FiltersBar from '@/components/admin/FiltersBar.vue';
import StatCard from '@/components/admin/StatCard.vue';
import UsersTable from '@/components/admin/UsersTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Shield, ShieldCheck, ShieldOff, Trash2, User, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface UserRecord {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

interface PaginatedUsers {
    data: UserRecord[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    [key: string]: unknown;
}

interface Props {
    users: PaginatedUsers;
    filters: Record<string, string | undefined>;
    stats: { total: number; admins: number; non_admins: number };
    fields: Array<Record<string, unknown>>;
    filterDefinitions: Array<Record<string, unknown>>;
    columns: Array<{ name: string; label: string; sortable?: boolean }>;
}

const props = defineProps<Props>();

const page = usePage();
const currentUserId = computed(() => {
    const auth = page.props.auth as { user: { id: number } } | undefined;
    return auth?.user?.id ?? 0;
});

const routePrefix = '/dashboard/users';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Users', href: routePrefix },
];

const filterValues = ref<Record<string, string | undefined>>({ ...props.filters });
const selectedIds = ref<number[]>([]);
const deleteDialogOpen = ref(false);
const userToDelete = ref<UserRecord | null>(null);
const isDeleting = ref(false);
const isBulkLoading = ref(false);

const applyFilters = () => {
    const params: Record<string, string | undefined> = {};
    for (const [key, value] of Object.entries(filterValues.value)) {
        if (value) params[key] = value;
    }
    router.get(routePrefix, params, { preserveState: true, preserveScroll: true });
};

const handleDeleteClick = (user: UserRecord) => {
    userToDelete.value = user;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (userToDelete.value) {
        isDeleting.value = true;
        router.delete(`${routePrefix}/${userToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                userToDelete.value = null;
                selectedIds.value = [];
            },
            onFinish: () => { isDeleting.value = false; },
        });
    } else if (selectedIds.value.length > 0) {
        isBulkLoading.value = true;
        router.post(`${routePrefix}/bulk-delete`, { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                selectedIds.value = [];
            },
            onFinish: () => { isBulkLoading.value = false; },
        });
    }
};

const handleBulkDelete = () => {
    userToDelete.value = null;
    deleteDialogOpen.value = true;
};

const handleBulkToggleAdmin = (grantAdmin: boolean) => {
    if (selectedIds.value.length === 0) return;
    isBulkLoading.value = true;
    router.post(`${routePrefix}/bulk-toggle-admin`, {
        ids: selectedIds.value,
        is_admin: grantAdmin,
    }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
        onFinish: () => { isBulkLoading.value = false; },
    });
};

const handleToggleAdmin = (user: UserRecord) => {
    router.post(`${routePrefix}/${user.id}/toggle-admin`, {}, { preserveScroll: true });
};

const handleSort = (column: string, direction: string) => {
    router.get(routePrefix, { ...props.filters, sort: column, direction }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const goToPage = (url: string | null) => {
    if (url) router.get(url);
};

const getPageNumbers = () => {
    if (!props.users?.last_page) return [];
    const current = props.users.current_page;
    const last = props.users.last_page;
    const pages: (number | string)[] = [];
    if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
    } else if (current <= 3) {
        for (let i = 1; i <= 5; i++) pages.push(i);
        pages.push('...', last);
    } else if (current >= last - 2) {
        pages.push(1, '...');
        for (let i = last - 4; i <= last; i++) pages.push(i);
    } else {
        pages.push(1, '...');
        for (let i = current - 1; i <= current + 1; i++) pages.push(i);
        pages.push('...', last);
    }
    return pages;
};

const goToPageNumber = (page: number | string) => {
    if (typeof page === 'string' || page === props.users?.current_page) return;
    router.get(routePrefix, { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

const hasSelected = computed(() => selectedIds.value.length > 0);
const showPagination = computed(() => (props.users?.last_page ?? 0) > 1);

const bulkDeleteDescription = computed(() => {
    const count = selectedIds.value.length;
    return count === 1
        ? 'Are you sure you want to delete this user? This action cannot be undone.'
        : `Are you sure you want to delete ${count} users? This action cannot be undone.`;
});

watch(
    () => props.users?.data?.map((u) => u.id).join(','),
    () => { selectedIds.value = []; },
);
</script>

<template>
    <Head title="Users - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Users</h1>
                    <p class="mt-1 text-muted-foreground">Manage user accounts</p>
                </div>
                <Button as="a" :href="`${routePrefix}/create`">
                    <Plus class="mr-2 h-4 w-4" />
                    Create User
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="Total Users" :value="stats?.total ?? 0" :icon="Users" />
                <StatCard label="Admins" :value="stats?.admins ?? 0" :icon="ShieldCheck" />
                <StatCard label="Non-Admins" :value="stats?.non_admins ?? 0" :icon="User" />
            </div>

            <!-- Filters -->
            <FiltersBar
                :filters="filterDefinitions as any"
                v-model="filterValues"
                @apply="applyFilters"
            />

            <!-- Bulk Actions (inline) -->
            <Card v-if="hasSelected" class="border-primary/50 bg-primary/5">
                <CardContent class="pt-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium">
                            {{ selectedIds.length }} user{{ selectedIds.length === 1 ? '' : 's' }}
                            selected
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="isBulkLoading"
                                @click="handleBulkToggleAdmin(true)"
                            >
                                <Shield class="mr-2 h-4 w-4" />
                                Grant Admin
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="isBulkLoading"
                                @click="handleBulkToggleAdmin(false)"
                            >
                                <ShieldOff class="mr-2 h-4 w-4" />
                                Revoke Admin
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                :disabled="isBulkLoading"
                                @click="handleBulkDelete"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Table -->
            <UsersTable
                v-if="users"
                :users="users"
                :columns="columns"
                :filters="filters"
                :selected-ids="selectedIds"
                :show-url="(id: number) => `${routePrefix}/${id}`"
                :edit-url="(id: number) => `${routePrefix}/${id}/edit`"
                :index-url="routePrefix"
                :current-user-id="currentUserId"
                @delete="handleDeleteClick"
                @update:selected="(ids: number[]) => selectedIds = ids"
                @toggle-admin="handleToggleAdmin"
                @sort="handleSort"
            />

            <!-- Pagination -->
            <div v-if="showPagination" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="!users?.prev_page_url" @click="goToPage(users.prev_page_url)">
                        Previous
                    </Button>
                    <div class="flex gap-1">
                        <Button
                            v-for="pageNum in getPageNumbers()"
                            :key="pageNum"
                            size="sm"
                            :variant="pageNum === users.current_page ? 'default' : 'outline'"
                            :disabled="typeof pageNum === 'string'"
                            @click="goToPageNumber(pageNum)"
                        >
                            {{ pageNum }}
                        </Button>
                    </div>
                    <Button variant="outline" size="sm" :disabled="!users?.next_page_url" @click="goToPage(users.next_page_url)">
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            :title="userToDelete ? 'Delete User' : 'Delete Users'"
            :description="userToDelete
                ? `Are you sure you want to delete &quot;${userToDelete.name}&quot;? This action cannot be undone.`
                : bulkDeleteDescription"
            :loading="isDeleting || isBulkLoading"
            @confirm="confirmDelete"
            @cancel="() => { deleteDialogOpen = false; userToDelete = null; }"
        />
    </AppLayout>
</template>
