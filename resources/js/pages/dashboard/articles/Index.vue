<script setup lang="ts">
import ArticlesTable from '@/components/admin/ArticlesTable.vue';
import BulkActionBar from '@/components/admin/BulkActionBar.vue';
import DeleteConfirmDialog from '@/components/admin/DeleteConfirmDialog.vue';
import FiltersBar from '@/components/admin/FiltersBar.vue';
import StatCard from '@/components/admin/StatCard.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { FileCheck, FilePlus, FileText, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Article {
    id: number;
    title: string;
    published_at: string | null;
    view_count: number;
    author?: string | null;
    news_source?: { id: number; name: string } | null;
    tags?: Array<{ id: number; name: string }>;
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
    [key: string]: unknown;
}

interface Props {
    articles: PaginatedArticles;
    filters: Record<string, string | undefined>;
    stats: { total: number; drafts: number; published: number };
    fields: Array<Record<string, unknown>>;
    filterDefinitions: Array<Record<string, unknown>>;
    columns: Array<{ name: string; label: string; sortable?: boolean }>;
    relationOptions: Record<string, Array<{ id: number; name: string }>>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/articles';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Articles', href: routePrefix },
];

const filterValues = ref<Record<string, string | undefined>>({ ...props.filters });
const selectedIds = ref<number[]>([]);
const deleteDialogOpen = ref(false);
const articleToDelete = ref<Article | null>(null);
const isDeleting = ref(false);
const isBulkLoading = ref(false);

const applyFilters = () => {
    const params: Record<string, string | undefined> = {};
    for (const [key, value] of Object.entries(filterValues.value)) {
        if (value) params[key] = value;
    }
    router.get(routePrefix, params, { preserveState: true, preserveScroll: true });
};

const handleDeleteClick = (article: Article) => {
    articleToDelete.value = article;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (articleToDelete.value) {
        isDeleting.value = true;
        router.delete(`${routePrefix}/${articleToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialogOpen.value = false;
                articleToDelete.value = null;
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
    articleToDelete.value = null;
    deleteDialogOpen.value = true;
};

const handleBulkPublish = () => {
    if (selectedIds.value.length === 0) return;
    isBulkLoading.value = true;
    router.post(`${routePrefix}/bulk-publish`, { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
        onFinish: () => { isBulkLoading.value = false; },
    });
};

const handleBulkUnpublish = () => {
    if (selectedIds.value.length === 0) return;
    isBulkLoading.value = true;
    router.post(`${routePrefix}/bulk-unpublish`, { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = []; },
        onFinish: () => { isBulkLoading.value = false; },
    });
};

const handleTogglePublish = (article: Article) => {
    router.post(`${routePrefix}/${article.id}/toggle-publish`, {}, { preserveScroll: true });
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
    router.get(routePrefix, { ...props.filters, page }, { preserveState: true, preserveScroll: true });
};

const hasSelected = computed(() => selectedIds.value.length > 0);
const showPagination = computed(() => (props.articles?.last_page ?? 0) > 1);

const bulkDeleteDescription = computed(() => {
    const count = selectedIds.value.length;
    return count === 1
        ? 'Are you sure you want to delete this article? This action cannot be undone.'
        : `Are you sure you want to delete ${count} articles? This action cannot be undone.`;
});

watch(
    () => props.articles?.data?.map((a) => a.id).join(','),
    () => { selectedIds.value = []; },
);
</script>

<template>
    <Head title="Articles - Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Articles</h1>
                    <p class="mt-1 text-muted-foreground">Manage your article content</p>
                </div>
                <Button as="a" :href="`${routePrefix}/create`">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Article
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard label="Total Articles" :value="stats?.total ?? 0" :icon="FileText" />
                <StatCard label="Drafts" :value="stats?.drafts ?? 0" :icon="FilePlus" />
                <StatCard label="Published" :value="stats?.published ?? 0" :icon="FileCheck" />
            </div>

            <!-- Filters -->
            <FiltersBar
                :filters="filterDefinitions as any"
                v-model="filterValues"
                :relation-options="relationOptions"
                @apply="applyFilters"
            />

            <!-- Bulk Actions -->
            <BulkActionBar
                v-if="hasSelected"
                :selected-count="selectedIds.length"
                :loading="isBulkLoading"
                mode="index"
                @publish="handleBulkPublish"
                @unpublish="handleBulkUnpublish"
                @delete="handleBulkDelete"
            />

            <!-- Table -->
            <ArticlesTable
                v-if="articles"
                :articles="articles"
                :columns="columns"
                :filters="filters"
                :selected-ids="selectedIds"
                :show-url="(id: number) => `${routePrefix}/${id}`"
                :edit-url="(id: number) => `${routePrefix}/${id}/edit`"
                :index-url="routePrefix"
                @delete="handleDeleteClick"
                @update:selected="(ids: number[]) => selectedIds = ids"
                @toggle-publish="handleTogglePublish"
                @sort="handleSort"
            />

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
            :title="articleToDelete ? 'Delete Article' : 'Delete Articles'"
            :description="articleToDelete
                ? `Are you sure you want to delete &quot;${articleToDelete.title}&quot;? This action cannot be undone.`
                : bulkDeleteDescription"
            :loading="isDeleting || isBulkLoading"
            @confirm="confirmDelete"
            @cancel="() => { deleteDialogOpen = false; articleToDelete = null; }"
        />
    </AppLayout>
</template>
