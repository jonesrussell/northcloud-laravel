<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Eye, EyeOff, RotateCcw, Trash2 } from 'lucide-vue-next';

interface Props {
    selectedCount: number;
    loading?: boolean;
    mode?: 'index' | 'trashed';
}

withDefaults(defineProps<Props>(), {
    loading: false,
    mode: 'index',
});

const emit = defineEmits<{
    publish: [];
    unpublish: [];
    delete: [];
    restore: [];
    'force-delete': [];
}>();
</script>

<template>
    <Card class="border-primary/50 bg-primary/5">
        <CardContent class="pt-6">
            <div class="flex items-center justify-between">
                <div class="text-sm font-medium">
                    {{ selectedCount }} article{{ selectedCount === 1 ? '' : 's' }}
                    selected
                </div>
                <div class="flex gap-2">
                    <template v-if="mode === 'index'">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="loading"
                            @click="emit('publish')"
                        >
                            <Eye class="mr-2 h-4 w-4" />
                            Publish
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="loading"
                            @click="emit('unpublish')"
                        >
                            <EyeOff class="mr-2 h-4 w-4" />
                            Unpublish
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            :disabled="loading"
                            @click="emit('delete')"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="loading"
                            @click="emit('restore')"
                        >
                            <RotateCcw class="mr-2 h-4 w-4" />
                            Restore
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            :disabled="loading"
                            @click="emit('force-delete')"
                        >
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete Permanently
                        </Button>
                    </template>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
