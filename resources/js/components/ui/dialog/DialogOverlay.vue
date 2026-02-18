<script setup lang="ts">
import type { DialogOverlayProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { DialogOverlay } from "reka-ui"
import { cn, filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DialogOverlayProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
const filteredProps = computed(() => filterUndefinedProps(delegatedProps))
</script>

<template>
  <DialogOverlay
    data-slot="dialog-overlay"
    v-bind="filteredProps"
    :class="cn('data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/80', props.class)"
  >
    <slot />
  </DialogOverlay>
</template>
