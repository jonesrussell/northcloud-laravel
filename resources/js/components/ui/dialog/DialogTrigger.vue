<script setup lang="ts">
import type { DialogTriggerProps } from "reka-ui"
import { computed } from "vue"
import { DialogTrigger } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DialogTriggerProps>()

const filteredProps = computed(() => filterUndefinedProps(props))
</script>

<template>
  <DialogTrigger
    data-slot="dialog-trigger"
    v-bind="filteredProps"
  >
    <slot />
  </DialogTrigger>
</template>
