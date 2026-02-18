<script setup lang="ts">
import type { DialogRootEmits, DialogRootProps } from "reka-ui"
import { computed } from "vue"
import { DialogRoot, useForwardPropsEmits } from "reka-ui"
import { filterUndefinedProps } from "@/lib/utils"

const props = defineProps<DialogRootProps>()
const emits = defineEmits<DialogRootEmits>()

const filteredProps = computed(() => filterUndefinedProps(props))
const forwarded = useForwardPropsEmits(filteredProps, emits)
</script>

<template>
  <DialogRoot
    v-slot="slotProps"
    data-slot="dialog"
    v-bind="forwarded"
  >
    <slot v-bind="slotProps" />
  </DialogRoot>
</template>
