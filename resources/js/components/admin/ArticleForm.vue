<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import TagMultiSelect from './TagMultiSelect.vue';

interface FieldDefinition {
    name: string;
    type: string;
    label: string;
    required?: boolean;
    rules?: string[];
    relationship?: string;
    display_field?: string;
    placeholder?: string;
}

interface Props {
    fields: FieldDefinition[];
    modelValue: Record<string, unknown>;
    errors?: Record<string, string>;
    relationOptions?: Record<string, Array<{ id: number; name: string; [key: string]: unknown }>>;
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
    relationOptions: () => ({}),
});

const emit = defineEmits<{
    'update:modelValue': [value: Record<string, unknown>];
}>();

const updateField = (name: string, value: unknown) => {
    emit('update:modelValue', { ...props.modelValue, [name]: value });
};

const getRelationOptions = (field: FieldDefinition): Array<{ id: number; name: string; [key: string]: unknown }> => {
    if (field.name === 'news_source_id') return props.relationOptions.news_sources ?? [];
    if (field.name === 'tags') return props.relationOptions.tags ?? [];
    // For custom belongs-to/belongs-to-many, check by relationship name or field name
    return props.relationOptions[field.name] ?? props.relationOptions[field.relationship ?? ''] ?? [];
};

const isVisible = (field: FieldDefinition): boolean => {
    // published_at and is_featured are handled by the page component, not the form
    return !['published_at'].includes(field.name);
};
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <div class="space-y-6">
                <template v-for="field in fields" :key="field.name">
                    <div v-if="isVisible(field)" class="space-y-2">
                        <!-- Text input -->
                        <template v-if="field.type === 'text'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <Input
                                :id="field.name"
                                :model-value="(modelValue[field.name] as string) ?? ''"
                                type="text"
                                :placeholder="`Enter ${field.label.toLowerCase()}`"
                                :class="{ 'border-destructive': errors[field.name] }"
                                @update:model-value="updateField(field.name, $event)"
                            />
                        </template>

                        <!-- URL input -->
                        <template v-else-if="field.type === 'url'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <Input
                                :id="field.name"
                                :model-value="(modelValue[field.name] as string) ?? ''"
                                type="url"
                                :placeholder="field.placeholder ?? `https://example.com/${field.name}`"
                                :class="{ 'border-destructive': errors[field.name] }"
                                @update:model-value="updateField(field.name, $event)"
                            />
                        </template>

                        <!-- Textarea -->
                        <template v-else-if="field.type === 'textarea'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <textarea
                                :id="field.name"
                                :value="(modelValue[field.name] as string) ?? ''"
                                rows="3"
                                :placeholder="`Enter ${field.label.toLowerCase()}...`"
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive': errors[field.name] }"
                                @input="updateField(field.name, ($event.target as HTMLTextAreaElement).value)"
                            ></textarea>
                        </template>

                        <!-- Richtext (renders as tall textarea) -->
                        <template v-else-if="field.type === 'richtext'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <textarea
                                :id="field.name"
                                :value="(modelValue[field.name] as string) ?? ''"
                                rows="10"
                                :placeholder="`Enter ${field.label.toLowerCase()}...`"
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive': errors[field.name] }"
                                @input="updateField(field.name, ($event.target as HTMLTextAreaElement).value)"
                            ></textarea>
                        </template>

                        <!-- Datetime -->
                        <template v-else-if="field.type === 'datetime'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <Input
                                :id="field.name"
                                :model-value="(modelValue[field.name] as string) ?? ''"
                                type="datetime-local"
                                :class="{ 'border-destructive': errors[field.name] }"
                                @update:model-value="updateField(field.name, $event)"
                            />
                        </template>

                        <!-- Checkbox -->
                        <template v-else-if="field.type === 'checkbox'">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :id="field.name"
                                    :checked="(modelValue[field.name] as boolean) ?? false"
                                    @update:checked="updateField(field.name, $event)"
                                />
                                <Label :for="field.name" class="cursor-pointer">
                                    {{ field.label }}
                                </Label>
                            </div>
                        </template>

                        <!-- Belongs-to (Select) -->
                        <template v-else-if="field.type === 'belongs-to'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <Select
                                :model-value="modelValue[field.name]"
                                @update:model-value="updateField(field.name, $event)"
                            >
                                <SelectTrigger
                                    :id="field.name"
                                    :class="{ 'border-destructive': errors[field.name] }"
                                >
                                    <SelectValue :placeholder="`Select ${field.label.toLowerCase()}`" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in getRelationOptions(field)"
                                        :key="option.id"
                                        :value="option.id"
                                    >
                                        {{ option[field.display_field ?? 'name'] }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </template>

                        <!-- Belongs-to-many (TagMultiSelect) -->
                        <template v-else-if="field.type === 'belongs-to-many'">
                            <Label>
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <TagMultiSelect
                                :model-value="(modelValue[field.name] as number[]) ?? []"
                                :options="getRelationOptions(field)"
                                :display-field="field.display_field ?? 'name'"
                                :placeholder="`Search ${field.label.toLowerCase()}...`"
                                @update:model-value="updateField(field.name, $event)"
                            />
                        </template>

                        <!-- Select (static options) -->
                        <template v-else-if="field.type === 'select'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <Select
                                :model-value="modelValue[field.name]"
                                @update:model-value="updateField(field.name, $event)"
                            >
                                <SelectTrigger
                                    :id="field.name"
                                    :class="{ 'border-destructive': errors[field.name] }"
                                >
                                    <SelectValue :placeholder="`Select ${field.label.toLowerCase()}`" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="opt in (field as any).options"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </template>

                        <!-- Error message -->
                        <p
                            v-if="errors[field.name] && field.type !== 'checkbox'"
                            class="text-sm text-destructive"
                        >
                            {{ errors[field.name] }}
                        </p>
                    </div>
                </template>
            </div>
        </CardContent>
    </Card>
</template>
