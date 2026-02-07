<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ArrowDown, ArrowUp, Edit, Eye, EyeOff, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import ArticleStatusBadge from './ArticleStatusBadge.vue';

interface ColumnDefinition {
    name: string;
    label: string;
    sortable?: boolean;
}

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
    [key: string]: unknown;
}

interface Props {
    articles: PaginatedArticles;
    columns: ColumnDefinition[];
    filters?: {
        sort?: string;
        direction?: 'asc' | 'desc';
        [key: string]: unknown;
    };
    selectedIds?: number[];
    showUrl: (articleId: number) => string;
    editUrl: (articleId: number) => string;
    indexUrl: string;
}

const props = withDefaults(defineProps<Props>(), {
    selectedIds: () => [],
});

const emit = defineEmits<{
    delete: [article: Article];
    'update:selected': [ids: number[]];
    'toggle-publish': [article: Article];
    sort: [column: string, direction: string];
}>();

const allArticleIds = computed(
    () => props.articles?.data?.map((a) => a.id) ?? [],
);

const selectedIdsSet = computed(() => new Set(props.selectedIds));

const articleCheckedStates = computed(() => {
    const states: Record<number, boolean> = {};
    props.articles?.data?.forEach((article) => {
        states[article.id] = selectedIdsSet.value.has(article.id);
    });
    return states;
});

const isAllSelected = computed(() => {
    if (allArticleIds.value.length === 0) return false;
    return allArticleIds.value.every((id) => selectedIdsSet.value.has(id));
});

const isSomeSelected = computed(() => {
    return props.selectedIds.length > 0 && !isAllSelected.value;
});

const toggleSelectAll = (checked: boolean | 'indeterminate') => {
    const shouldSelect = checked === true || checked === 'indeterminate';

    let newSelectedIds: number[];
    if (shouldSelect) {
        const newIds = allArticleIds.value.filter(
            (id) => !props.selectedIds.includes(id),
        );
        newSelectedIds = [...props.selectedIds, ...newIds];
    } else {
        newSelectedIds = props.selectedIds.filter(
            (id) => !allArticleIds.value.includes(id),
        );
    }
    emit('update:selected', newSelectedIds);
};

const toggleSelect = (articleId: number) => {
    let newSelectedIds: number[];
    if (props.selectedIds.includes(articleId)) {
        newSelectedIds = props.selectedIds.filter((id) => id !== articleId);
    } else {
        newSelectedIds = [...props.selectedIds, articleId];
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

const isPublished = (article: Article) => {
    return article.published_at !== null;
};

const formatDate = (date: string | null) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getCellValue = (article: Article, column: ColumnDefinition) => {
    switch (column.name) {
        case 'view_count':
            return article.view_count?.toLocaleString() ?? '0';
        case 'published_at':
            return formatDate(article.published_at);
        default:
            return article[column.name];
    }
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
                        v-for="article in articles?.data ?? []"
                        :key="article.id"
                        class="border-b transition-colors hover:bg-muted/50"
                    >
                        <td class="px-4 py-3">
                            <Checkbox
                                :model-value="articleCheckedStates[article.id]"
                                @update:model-value="() => toggleSelect(article.id)"
                            />
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.name"
                            class="px-4 py-3"
                        >
                            <!-- ID -->
                            <span v-if="col.name === 'id'" class="text-sm">
                                {{ article.id }}
                            </span>

                            <!-- Title -->
                            <div v-else-if="col.name === 'title'" class="max-w-md">
                                <a
                                    :href="showUrl(article.id)"
                                    class="line-clamp-2 text-sm font-medium transition-colors hover:text-primary"
                                >
                                    {{ article.title }}
                                </a>
                                <div
                                    v-if="article.author"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    by {{ article.author }}
                                </div>
                            </div>

                            <!-- News Source -->
                            <template v-else-if="col.name === 'news_source'">
                                <Badge
                                    v-if="article.news_source"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ article.news_source.name }}
                                </Badge>
                            </template>

                            <!-- Tags -->
                            <template v-else-if="col.name === 'tags'">
                                <div class="flex max-w-xs flex-wrap gap-1">
                                    <Badge
                                        v-for="tag in article.tags?.slice(0, 3)"
                                        :key="tag.id"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                    <Badge
                                        v-if="article.tags && article.tags.length > 3"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        +{{ article.tags.length - 3 }}
                                    </Badge>
                                </div>
                            </template>

                            <!-- Status -->
                            <template v-else-if="col.name === 'status'">
                                <ArticleStatusBadge :published-at="article.published_at" />
                            </template>

                            <!-- Published date -->
                            <span v-else-if="col.name === 'published_at'" class="text-sm text-muted-foreground">
                                {{ formatDate(article.published_at) }}
                            </span>

                            <!-- View count -->
                            <span v-else-if="col.name === 'view_count'" class="text-sm text-muted-foreground">
                                {{ article.view_count?.toLocaleString() ?? '0' }}
                            </span>

                            <!-- Generic fallback -->
                            <span v-else class="text-sm text-muted-foreground">
                                {{ getCellValue(article, col) ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :title="isPublished(article) ? 'Unpublish' : 'Publish'"
                                    @click="emit('toggle-publish', article)"
                                >
                                    <component
                                        :is="isPublished(article) ? EyeOff : Eye"
                                        class="h-4 w-4"
                                    />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    as="a"
                                    :href="editUrl(article.id)"
                                >
                                    <Edit class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="emit('delete', article)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!articles?.data || articles.data.length === 0">
                        <td
                            :colspan="columns.length + 2"
                            class="px-4 py-12 text-center text-muted-foreground"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-sm">No articles found.</p>
                                <p class="text-xs">
                                    Try adjusting your filters or create a new article.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
