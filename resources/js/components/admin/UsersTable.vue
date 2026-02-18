<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowDown, ArrowUp, Edit, Shield, ShieldOff, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface ColumnDefinition {
    name: string;
    label: string;
    sortable?: boolean;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

interface PaginatedUsers {
    data: User[];
    [key: string]: unknown;
}

interface Props {
    users: PaginatedUsers;
    columns: ColumnDefinition[];
    filters?: {
        sort?: string;
        direction?: 'asc' | 'desc';
        [key: string]: unknown;
    };
    selectedIds?: number[];
    showUrl: (userId: number) => string;
    editUrl: (userId: number) => string;
    indexUrl: string;
    currentUserId: number;
}

const props = withDefaults(defineProps<Props>(), {
    selectedIds: () => [],
});

const emit = defineEmits<{
    delete: [user: User];
    'update:selected': [ids: number[]];
    'toggle-admin': [user: User];
    sort: [column: string, direction: string];
}>();

const allUserIds = computed(
    () => props.users?.data?.map((u) => u.id) ?? [],
);

const selectedIdsSet = computed(() => new Set(props.selectedIds));

const userCheckedStates = computed(() => {
    const states: Record<number, boolean> = {};
    props.users?.data?.forEach((user) => {
        states[user.id] = selectedIdsSet.value.has(user.id);
    });
    return states;
});

const isAllSelected = computed(() => {
    if (allUserIds.value.length === 0) return false;
    return allUserIds.value.every((id) => selectedIdsSet.value.has(id));
});

const isSomeSelected = computed(() => {
    return props.selectedIds.length > 0 && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    const shouldSelect = checked === true || checked === 'indeterminate';

    let newSelectedIds: number[];
    if (shouldSelect) {
        const newIds = allUserIds.value.filter(
            (id) => !props.selectedIds.includes(id),
        );
        newSelectedIds = [...props.selectedIds, ...newIds];
    } else {
        newSelectedIds = props.selectedIds.filter(
            (id) => !allUserIds.value.includes(id),
        );
    }
    emit('update:selected', newSelectedIds);
};

const toggleSelect = (userId: number) => {
    let newSelectedIds: number[];
    if (props.selectedIds.includes(userId)) {
        newSelectedIds = props.selectedIds.filter((id) => id !== userId);
    } else {
        newSelectedIds = [...props.selectedIds, userId];
    }
    emit('update:selected', newSelectedIds);
};

const handleSort = (column: string) => {
    const newDirection =
        props.filters?.sort === column && props.filters?.direction === 'asc'
            ? 'desc'
            : 'asc';
    emit('sort', column, newDirection);
};

const getSortIcon = (column: string) => {
    if (props.filters?.sort !== column) return null;
    return props.filters?.direction === 'asc' ? ArrowUp : ArrowDown;
};

const formatDate = (date: string | null) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const isSelf = (user: User) => {
    return user.id === props.currentUserId;
};
</script>

<template>
    <div class="rounded-md border">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="w-12 px-4 py-3 text-left text-sm font-medium">
                            <Checkbox
                                :model-value="isAllSelected"
                                :indeterminate="isSomeSelected"
                                @update:model-value="toggleSelectAll"
                            />
                        </th>
                        <th
                            v-for="col in columns"
                            :key="col.name"
                            class="px-4 py-3 text-left text-sm font-medium"
                            :class="{ 'cursor-pointer hover:bg-muted': col.sortable }"
                            @click="col.sortable && handleSort(col.name)"
                        >
                            <div class="flex items-center gap-1">
                                {{ col.label }}
                                <component
                                    :is="getSortIcon(col.name)"
                                    v-if="col.sortable && getSortIcon(col.name)"
                                    class="h-3 w-3"
                                />
                            </div>
                        </th>
                        <th class="px-4 py-3 text-right text-sm font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users?.data ?? []"
                        :key="user.id"
                        class="border-b transition-colors hover:bg-muted/50"
                    >
                        <td class="px-4 py-3">
                            <Checkbox
                                :model-value="userCheckedStates[user.id]"
                                @update:model-value="() => toggleSelect(user.id)"
                            />
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.name"
                            class="px-4 py-3"
                        >
                            <!-- ID -->
                            <span v-if="col.name === 'id'" class="text-sm">
                                {{ user.id }}
                            </span>

                            <!-- Name -->
                            <div v-else-if="col.name === 'name'" class="max-w-md">
                                <a
                                    :href="showUrl(user.id)"
                                    class="text-sm font-medium transition-colors hover:text-primary"
                                >
                                    {{ user.name }}
                                </a>
                            </div>

                            <!-- Email -->
                            <span v-else-if="col.name === 'email'" class="text-sm text-muted-foreground">
                                {{ user.email }}
                            </span>

                            <!-- Role (is_admin) -->
                            <template v-else-if="col.name === 'is_admin'">
                                <Badge :variant="user.is_admin ? 'default' : 'secondary'">
                                    {{ user.is_admin ? 'Admin' : 'User' }}
                                </Badge>
                            </template>

                            <!-- Created date -->
                            <span v-else-if="col.name === 'created_at'" class="text-sm text-muted-foreground">
                                {{ formatDate(user.created_at) }}
                            </span>

                            <!-- Generic fallback -->
                            <span v-else class="text-sm text-muted-foreground">
                                {{ user[col.name] ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="editUrl(user.id)"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="!isSelf(user)"
                                    variant="ghost"
                                    size="sm"
                                    :title="user.is_admin ? 'Revoke Admin' : 'Grant Admin'"
                                    @click="emit('toggle-admin', user)"
                                >
                                    <component
                                        :is="user.is_admin ? ShieldOff : Shield"
                                        class="h-4 w-4"
                                    />
                                </Button>
                                <Button
                                    v-if="!isSelf(user)"
                                    variant="ghost"
                                    size="sm"
                                    @click="emit('delete', user)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!users?.data || users.data.length === 0">
                        <td
                            :colspan="columns.length + 2"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-sm">No users found.</p>
                                <p class="text-xs">
                                    Try adjusting your filters or create a new user.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
