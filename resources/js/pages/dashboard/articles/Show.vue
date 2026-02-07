<script setup lang="ts">
import ArticleStatusBadge from '@/components/admin/ArticleStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Edit, ExternalLink, User } from 'lucide-vue-next';

interface Article {
    id: number;
    title: string;
    url: string;
    excerpt?: string | null;
    content?: string | null;
    image_url?: string | null;
    author?: string | null;
    published_at: string | null;
    view_count: number;
    created_at: string;
    updated_at: string;
    news_source?: { id: number; name: string } | null;
    tags?: Array<{ id: number; name: string; type?: string }>;
    [key: string]: unknown;
}

interface Props {
    article: Article;
    fields: Array<Record<string, unknown>>;
}

const props = defineProps<Props>();

const routePrefix = '/dashboard/articles';
const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Articles', href: routePrefix },
    { title: props.article.title, href: '#' },
];

const formattedDate = props.article.published_at
    ? new Date(props.article.published_at).toLocaleDateString('en-US', {
          year: 'numeric', month: 'long', day: 'numeric',
      })
    : 'Not published';

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`${article.title} - Dashboard`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Button variant="ghost" size="sm" as="a" :href="routePrefix" class="mb-2">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Articles
                    </Button>
                    <h1 class="text-3xl font-bold tracking-tight">{{ article.title }}</h1>
                </div>
                <Button as="a" :href="`${routePrefix}/${article.id}/edit`">
                    <Edit class="mr-2 h-4 w-4" />
                    Edit Article
                </Button>
            </div>

            <!-- Metadata -->
            <Card>
                <CardHeader>
                    <CardTitle>Article Metadata</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground">Status</p>
                            <div class="mt-1">
                                <ArticleStatusBadge :published-at="article.published_at" />
                            </div>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Published</p>
                            <p class="font-medium">{{ formattedDate }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Views</p>
                            <p class="font-medium">{{ article.view_count.toLocaleString() }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Created</p>
                            <p class="font-medium">{{ formatDateTime(article.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground">Last Updated</p>
                            <p class="font-medium">{{ formatDateTime(article.updated_at) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Article Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Article Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <div v-if="article.news_source" class="flex items-center gap-2">
                            <Badge variant="outline">{{ article.news_source.name }}</Badge>
                        </div>
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            {{ formattedDate }}
                        </div>
                        <div v-if="article.author" class="flex items-center gap-2 text-muted-foreground">
                            <User class="h-4 w-4" />
                            {{ article.author }}
                        </div>
                    </div>

                    <div v-if="article.tags && article.tags.length > 0">
                        <p class="mb-2 text-sm text-muted-foreground">Tags</p>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="tag in article.tags" :key="tag.id" variant="secondary">
                                {{ tag.name }}
                            </Badge>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-sm text-muted-foreground">Source URL</p>
                        <a
                            :href="article.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                        >
                            {{ article.url }}
                            <ExternalLink class="h-4 w-4" />
                        </a>
                    </div>
                </CardContent>
            </Card>

            <!-- Featured Image -->
            <Card v-if="article.image_url">
                <CardContent class="pt-6">
                    <img
                        :src="article.image_url"
                        :alt="article.title"
                        class="w-full rounded-lg"
                    />
                </CardContent>
            </Card>

            <!-- Content -->
            <Card>
                <CardHeader>
                    <CardTitle>Content</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="article.content"
                        class="prose prose-gray dark:prose-invert max-w-none"
                        v-html="article.content"
                    />
                    <p v-else-if="article.excerpt" class="text-muted-foreground">
                        {{ article.excerpt }}
                    </p>
                    <p v-else class="text-muted-foreground italic">No content available.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
