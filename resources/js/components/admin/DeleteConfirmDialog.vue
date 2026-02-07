<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
    loading?: boolean;
    confirmText?: string;
    cancelText?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Are you sure?',
    description: 'This action cannot be undone.',
    loading: false,
    confirmText: 'Delete',
    cancelText: 'Cancel',
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
    'update:open': [value: boolean];
}>();

const handleConfirm = () => {
    emit('confirm');
};

const handleCancel = () => {
    emit('cancel');
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="handleCancel"
                    :disabled="loading"
                >
                    {{ cancelText }}
                </Button>
                <Button
                    variant="destructive"
                    @click="handleConfirm"
                    :disabled="loading"
                >
                    {{ loading ? 'Deleting...' : confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
