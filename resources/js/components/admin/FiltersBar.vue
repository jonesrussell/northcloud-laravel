<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface FilterDefinition {
    name: string;
    type: string;
    label?: string;
    placeholder?: string;
    options?: Array<{ value: string; label: string }>;
    relationship?: string;
    display_field?: string;
}

interface Props {
    filters: FilterDefinition[];
    modelValue: Record<string, string | undefined>;
    relationOptions?: Record<string, Array<{ id: number; name: string; [key: string]: unknown }>>;
}

const props = withDefaults(defineProps<Props>(), {
    relationOptions: () => ({}),
});

const emit = defineEmits<{
    'update:modelValue': [value: Record<string, string | undefined>];
    apply: [];
}>();

const updateFilter = (name: string, value: string | undefined) => {
    emit('update:modelValue', { ...props.modelValue, [name]: value });
};

const getRelationOptions = (filter: FilterDefinition): Array<{ id: number; name: string; [key: string]: unknown }> => {
    if (filter.name === 'source') return props.relationOptions.news_sources ?? [];
    return props.relationOptions[filter.name] ?? props.relationOptions[filter.relationship ?? ''] ?? [];
};

const handleApply = () => {
    emit('apply');
};
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center">
                <template v-for="filter in filters" :key="filter.name">
                    <!-- Search input -->
                    <div v-if="filter.type === 'search'" class="flex-1">
                        <Input
                            :model-value="modelValue[filter.name] ?? ''"
                            type="search"
                            :placeholder="filter.placeholder ?? 'Search...'"
                            class="max-w-sm"
                            @update:model-value="updateFilter(filter.name, $event as string)"
                            @keyup.enter="handleApply"
                        />
                    </div>

                    <!-- Select filter (static options) -->
                    <Select
                        v-else-if="filter.type === 'select'"
                        :model-value="modelValue[filter.name] ?? ''"
                        @update:model-value="(val: unknown) => {
                            updateFilter(filter.name, val != null && (typeof val === 'string' || typeof val === 'number') ? String(val) : undefined);
                            handleApply();
                        }"
                    >
                        <SelectTrigger class="w-[150px]">
                            <SelectValue :placeholder="filter.label ?? filter.name" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in filter.options"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Belongs-to filter (dynamic options from relations) -->
                    <Select
                        v-else-if="filter.type === 'belongs-to'"
                        :model-value="modelValue[filter.name] ?? 'all'"
                        @update:model-value="(val: unknown) => {
                            updateFilter(filter.name, val !== 'all' && val != null ? String(val) : undefined);
                            handleApply();
                        }"
                    >
                        <SelectTrigger class="w-[180px]">
                            <SelectValue :placeholder="`All ${filter.label ?? ''}`" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All {{ filter.label }}</SelectItem>
                            <SelectItem
                                v-for="opt in getRelationOptions(filter)"
                                :key="opt.id"
                                :value="opt.id.toString()"
                            >
                                {{ opt[filter.display_field ?? 'name'] }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>

                <Button variant="outline" @click="handleApply">
                    Apply Filters
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
