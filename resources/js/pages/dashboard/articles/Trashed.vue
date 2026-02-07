<script setup lang="ts">
import BulkActionBar from '@/components/admin/BulkActionBar.vue';
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import StatCard from '@/components/admin/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Archive, ArrowUpDown, FileText, RotateCcw, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Article {
    id: number;
    title: string;
    deleted_at?: string | null;
    news_source?: { id: number; name: string } | null;
    [key: string]: unknown;
}

interface PaginatedArticles {
    data: Article[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Props {
    articles: PaginatedArticles;
    filters: Record<string, string | undefined>;
    newsSources: Array<{ id: number; name: string }>;
    stats: { trashed: number; active: number };
    columns: Array<{ name: string; label: string; sortable?: boolean }>;
    filterDefinitions: Array<Record<string, unknown>>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/articles';
const trashedUrl = `${routePrefix}/trashed`;
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Articles', href: routePrefix },
    { title: 'Trashed', href: trashedUrl },
];

const searchQuery = ref(props.filters.search ?? '');
const sourceFilter = ref(props.filters.source ?? 'all');
const selectedIds = ref<number[]>([]);
const isRestoring = ref(false);
const isForceDeleting = ref(false);
const deleteDialogOpen = ref(false);
const articleToDelete = ref<Article | null>(null);
const isBulkAction = ref(false);

const applyFilters = () => {
    router.get(trashedUrl, {
        search: searchQuery.value || undefined,
        source: sourceFilter.value !== 'all' ? sourceFilter.value : undefined,
    }, { preserveState: true, preserveScroll: true });
};

const handleSourceChange = (val: unknown) => {
    sourceFilter.value = val != null && (typeof val === 'string' || typeof val === 'number') ? String(val) : 'all';
    applyFilters();
};

const toggleSort = (column: string) => {
    const newDirection =
        props.filters?.sort === column && props.filters?.direction === 'asc' ? 'desc' : 'asc';
    router.get(trashedUrl, { ...props.filters, sort: column, direction: newDirection }, {
        preserveState: true, preserveScroll: true,
    });
};

const handleSelectAll = (checked: boolean) => {
    selectedIds.value = checked ? props.articles.data.map((a) => a.id) : [];
};

const handleSelectOne = (id: number, checked: boolean) => {
    selectedIds.value = checked
        ? [...selectedIds.value, id]
        : selectedIds.value.filter((i) => i !== id);
};

const handleRestore = (article: Article) => {
    isRestoring.value = true;
    router.post(`${routePrefix}/${article.id}/restore`, {}, {
        preserveScroll: true,
        onFinish: () => { isRestoring.value = false; },
    });
};

const handleBulkRestore = () => {
    if (selectedIds.value.length === 0) return;
    isRestoring.value = true;
    router.post(`${routePrefix}/bulk-restore`, { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
        onFinish: () => { isRestoring.value = false; },
    });
};

const handleForceDeleteClick = (article: Article) => {
    articleToDelete.value = article;
    isBulkAction.value = false;
    deleteDialogOpen.value = true;
};

const handleBulkForceDelete = () => {
    if (selectedIds.value.length === 0) return;
    articleToDelete.value = null;
    isBulkAction.value = true;
    deleteDialogOpen.value = true;
};

const confirmForceDelete = () => {
    if (isBulkAction.value) {
        isForceDeleting.value = true;
        router.post(`${routePrefix}/bulk-force-delete`, { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => { deleteDialogOpen.value = false; selectedIds.value = []; },
            onFinish: () => { isForceDeleting.value = false; },
        });
    } else if (articleToDelete.value) {
        isForceDeleting.value = true;
        router.delete(`${routePrefix}/${articleToDelete.value.id}/force-delete`, {
            preserveScroll: true,
            onSuccess: () => { deleteDialogOpen.value = false; articleToDelete.value = null; },
            onFinish: () => { isForceDeleting.value = false; },
        });
    }
};

const goToPage = (url: string | null) => {
    if (url) router.get(url);
};

const getPageNumbers = () => {
    if (!props.articles?.last_page) return [];
    const current = props.articles.current_page;
    const last = props.articles.last_page;
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
    if (typeof page === 'string' || page === props.articles?.current_page) return;
    router.get(trashedUrl, { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

const hasSelected = computed(() => selectedIds.value.length > 0);
const allSelected = computed(() =>
    props.articles.data.length > 0 && selectedIds.value.length === props.articles.data.length,
);
const showPagination = computed(() => (props.articles?.last_page ?? 0) > 1);

const deleteDescription = computed(() => {
    if (isBulkAction.value) {
        const count = selectedIds.value.length;
        return count === 1
            ? 'Are you sure you want to PERMANENTLY delete this article? This action cannot be undone.'
            : `Are you sure you want to PERMANENTLY delete ${count} articles? This action cannot be undone.`;
    }
    return articleToDelete.value
        ? `Are you sure you want to PERMANENTLY delete "${articleToDelete.value.title}"? This action cannot be undone.`
        : '';
});

const formatDate = (date: string | null | undefined): string => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-CA', {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

watch(
    () => props.articles?.data?.map((a) => a.id).join(','),
    () => { selectedIds.value = []; },
);
</script>

<template>
    <Head title="Trashed Articles - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Trashed Articles</h1>
                    <p class="mt-1 text-muted-foreground">
                        Manage soft-deleted articles - restore or permanently delete
                    </p>
                </div>
                <Button variant="outline" as="a" :href="routePrefix">
                    <FileText class="mr-2 h-4 w-4" />
                    Back to Articles
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-2">
                <StatCard label="Trashed Articles" :value="stats?.trashed ?? 0" :icon="Archive" />
                <StatCard label="Active Articles" :value="stats?.active ?? 0" :icon="FileText" />
            </div>

            <!-- Filters -->
            <Card>
                <div class="p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center">
                        <div class="flex-1">
                            <Input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Search by title..."
                                @keyup.enter="applyFilters"
                                class="max-w-sm"
                            />
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <Select v-model="sourceFilter" @update:model-value="handleSourceChange">
                                <SelectTrigger class="w-[180px]">
                                    <SelectValue placeholder="All Sources" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Sources</SelectItem>
                                    <SelectItem
                                        v-for="source in newsSources"
                                        :key="source.id"
                                        :value="source.id.toString()"
                                    >
                                        {{ source.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button variant="outline" @click="applyFilters">Apply Filters</Button>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Bulk Actions -->
            <BulkActionBar
                v-if="hasSelected"
                :selected-count="selectedIds.length"
                :loading="isRestoring || isForceDeleting"
                mode="trashed"
                @restore="handleBulkRestore"
                @force-delete="handleBulkForceDelete"
            />

            <!-- Table -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="w-[50px] p-4 text-left">
                                    <Checkbox :checked="allSelected" @update:checked="handleSelectAll" />
                                </th>
                                <th class="p-4 text-left font-medium cursor-pointer hover:bg-muted/50" @click="toggleSort('id')">
                                    <div class="flex items-center gap-1">ID <ArrowUpDown class="h-4 w-4" /></div>
                                </th>
                                <th class="p-4 text-left font-medium cursor-pointer hover:bg-muted/50" @click="toggleSort('title')">
                                    <div class="flex items-center gap-1">Title <ArrowUpDown class="h-4 w-4" /></div>
                                </th>
                                <th class="p-4 text-left font-medium">Source</th>
                                <th class="p-4 text-left font-medium cursor-pointer hover:bg-muted/50" @click="toggleSort('deleted_at')">
                                    <div class="flex items-center gap-1">Deleted At <ArrowUpDown class="h-4 w-4" /></div>
                                </th>
                                <th class="p-4 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="article in articles.data" :key="article.id" class="border-b hover:bg-muted/50">
                                <td class="p-4">
                                    <Checkbox
                                        :checked="selectedIds.includes(article.id)"
                                        @update:checked="(checked: boolean) => handleSelectOne(article.id, checked)"
                                    />
                                </td>
                                <td class="p-4 font-mono text-xs">{{ article.id }}</td>
                                <td class="p-4 max-w-[300px]">
                                    <div class="truncate font-medium">{{ article.title }}</div>
                                </td>
                                <td class="p-4 text-muted-foreground">{{ article.news_source?.name || '-' }}</td>
                                <td class="p-4 text-muted-foreground">{{ formatDate(article.deleted_at) }}</td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button variant="ghost" size="sm" :disabled="isRestoring" @click="handleRestore(article)">
                                            <RotateCcw class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="isForceDeleting"
                                            @click="handleForceDeleteClick(article)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="articles.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-muted-foreground">
                                    No trashed articles found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Pagination -->
            <div v-if="showPagination" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ articles.from }} to {{ articles.to }} of {{ articles.total }} results
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" :disabled="!articles?.prev_page_url" @click="goToPage(articles.prev_page_url)">
                        Previous
                    </Button>
                    <div class="flex gap-1">
                        <Button
                            v-for="page in getPageNumbers()"
                            :key="page"
                            size="sm"
                            :variant="page === articles.current_page ? 'default' : 'outline'"
                            :disabled="typeof page === 'string'"
                            @click="goToPageNumber(page)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                    <Button variant="outline" size="sm" :disabled="!articles?.next_page_url" @click="goToPage(articles.next_page_url)">
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <DeleteConfirmDialog
            v-model:open="deleteDialogOpen"
            title="Permanently Delete"
            :description="deleteDescription"
            :loading="isForceDeleting"
            @confirm="confirmForceDelete"
            @cancel="() => { deleteDialogOpen = false; articleToDelete = null; isBulkAction = false; }"
        />
    </AppLayout>
</template>
