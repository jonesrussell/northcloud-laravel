<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface FieldDefinition {
    name: string;
    type: string;
    label: string;
    required?: boolean;
    rules?: string[];
    placeholder?: string;
}

interface Props {
    fields: FieldDefinition[];
    modelValue: Record<string, unknown>;
    errors?: Record<string, string>;
    isEdit?: boolean;
    isSelf?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
    isEdit: false,
    isSelf: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: Record<string, unknown>];
}>();

const updateField = (name: string, value: unknown) => {
    emit('update:modelValue', { ...props.modelValue, [name]: value });
};

const getPasswordLabel = (field: FieldDefinition): string => {
    return props.isEdit ? `${field.label} (optional)` : field.label;
};

const isAdminCheckboxDisabled = (field: FieldDefinition): boolean => {
    return props.isSelf && field.name === 'is_admin';
};
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <div class="space-y-6">
                <template v-for="field in fields" :key="field.name">
                    <div class="space-y-2">
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

                        <!-- Email input -->
                        <template v-else-if="field.type === 'email'">
                            <Label :for="field.name">
                                {{ field.label }}
                                <span v-if="field.required" class="text-destructive">*</span>
                            </Label>
                            <Input
                                :id="field.name"
                                :model-value="(modelValue[field.name] as string) ?? ''"
                                type="email"
                                :placeholder="`Enter ${field.label.toLowerCase()}`"
                                :class="{ 'border-destructive': errors[field.name] }"
                                @update:model-value="updateField(field.name, $event)"
                            />
                        </template>

                        <!-- Password input -->
                        <template v-else-if="field.type === 'password'">
                            <Label :for="field.name">
                                {{ getPasswordLabel(field) }}
                                <span v-if="field.required && !isEdit" class="text-destructive">*</span>
                            </Label>
                            <Input
                                :id="field.name"
                                :model-value="(modelValue[field.name] as string) ?? ''"
                                type="password"
                                :placeholder="`Enter ${field.label.toLowerCase()}`"
                                :class="{ 'border-destructive': errors[field.name] }"
                                @update:model-value="updateField(field.name, $event)"
                            />
                            <p
                                v-if="errors[field.name]"
                                class="text-sm text-destructive"
                            >
                                {{ errors[field.name] }}
                            </p>

                            <!-- Password confirmation field -->
                            <div class="mt-4 space-y-2">
                                <Label for="password_confirmation">
                                    Confirm Password
                                    <span v-if="field.required && !isEdit" class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    :model-value="(modelValue.password_confirmation as string) ?? ''"
                                    type="password"
                                    placeholder="Confirm password"
                                    :class="{ 'border-destructive': errors.password_confirmation }"
                                    @update:model-value="updateField('password_confirmation', $event)"
                                />
                                <p
                                    v-if="errors.password_confirmation"
                                    class="text-sm text-destructive"
                                >
                                    {{ errors.password_confirmation }}
                                </p>
                            </div>
                        </template>

                        <!-- Checkbox -->
                        <template v-else-if="field.type === 'checkbox'">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :id="field.name"
                                    :checked="(modelValue[field.name] as boolean) ?? false"
                                    :disabled="isAdminCheckboxDisabled(field)"
                                    @update:checked="updateField(field.name, $event)"
                                />
                                <Label
                                    :for="field.name"
                                    class="cursor-pointer"
                                    :class="{ 'opacity-50': isAdminCheckboxDisabled(field) }"
                                >
                                    {{ field.label }}
                                </Label>
                            </div>
                            <p
                                v-if="isAdminCheckboxDisabled(field)"
                                class="text-sm text-muted-foreground"
                            >
                                You cannot change your own admin status
                            </p>
                        </template>

                        <!-- Error message (for non-password, non-checkbox fields) -->
                        <p
                            v-if="errors[field.name] && field.type !== 'checkbox' && field.type !== 'password'"
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
