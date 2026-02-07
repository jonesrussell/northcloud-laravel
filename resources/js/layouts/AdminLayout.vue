<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

interface BreadcrumbItem {
    title: string;
    href: string;
}

interface Props {
    breadcrumbs?: BreadcrumbItem[];
    title?: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs ?? []">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <!-- Header slot or default -->
            <slot name="header">
                <div v-if="title" class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
                        <p v-if="description" class="mt-1 text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot name="header-actions" />
                </div>
            </slot>

            <!-- Main content -->
            <slot />
        </div>
    </AppLayout>
</template>
