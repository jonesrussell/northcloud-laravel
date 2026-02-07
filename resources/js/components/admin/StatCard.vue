<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Component } from 'vue';

interface Props {
    label: string;
    value: number | string;
    icon?: Component;
}

defineProps<Props>();
</script>

<template>
    <Card>
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
        >
            <CardTitle class="text-sm font-medium">
                {{ label }}
            </CardTitle>
            <component
                v-if="icon"
                :is="icon"
                class="h-4 w-4 text-muted-foreground"
            />
        </CardHeader>
        <CardContent>
            <div class="text-2xl font-bold">
                {{ typeof value === 'number' ? value.toLocaleString() : value }}
            </div>
        </CardContent>
    </Card>
</template>
