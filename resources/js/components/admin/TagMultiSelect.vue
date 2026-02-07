<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Option {
    id: number;
    name: string;
    [key: string]: unknown;
}

interface Props {
    options: Option[];
    modelValue: number[];
    displayField?: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    displayField: 'name',
    placeholder: 'Search...',
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const searchQuery = ref('');
const isOpen = ref(false);

const getLabel = (option: Option): string => {
    return String(option[props.displayField] ?? option.name);
};

const selectedOptions = computed(() =>
    props.options.filter((opt) => props.modelValue.includes(opt.id)),
);

const filteredOptions = computed(() =>
    props.options.filter((opt) =>
        getLabel(opt).toLowerCase().includes(searchQuery.value.toLowerCase()),
    ),
);

const toggleOption = (id: number) => {
    const newValue = props.modelValue.includes(id)
        ? props.modelValue.filter((v) => v !== id)
        : [...props.modelValue, id];

    emit('update:modelValue', newValue);
};

const removeOption = (id: number) => {
    emit(
        'update:modelValue',
        props.modelValue.filter((v) => v !== id),
    );
};

const handleInputBlur = () => {
    window.setTimeout(() => {
        isOpen.value = false;
    }, 200);
};
</script>

<template>
    <div class="space-y-2">
        <div class="mb-2 flex flex-wrap gap-2" v-if="selectedOptions.length > 0">
            <Badge
                v-for="opt in selectedOptions"
                :key="opt.id"
                variant="secondary"
                class="flex items-center gap-1"
            >
                {{ getLabel(opt) }}
                <button
                    type="button"
                    @click="removeOption(opt.id)"
                    class="rounded-full p-0.5 hover:bg-muted"
                >
                    <X class="h-3 w-3" />
                </button>
            </Badge>
        </div>

        <div class="relative">
            <Input
                v-model="searchQuery"
                type="text"
                :placeholder="placeholder"
                @focus="isOpen = true"
                @blur="handleInputBlur"
            />

            <div
                v-if="isOpen && filteredOptions.length > 0"
                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-background shadow-lg"
            >
                <div
                    v-for="opt in filteredOptions"
                    :key="opt.id"
                    class="flex cursor-pointer items-center gap-2 px-3 py-2 hover:bg-muted"
                    @mousedown.prevent="toggleOption(opt.id)"
                >
                    <Checkbox
                        :checked="modelValue.includes(opt.id)"
                        @click.stop="toggleOption(opt.id)"
                    />
                    <span class="text-sm">{{ getLabel(opt) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
