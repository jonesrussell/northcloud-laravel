<script setup lang="ts">
import type { PrimitiveProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import type { BadgeVariants } from "."
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { Primitive } from "reka-ui"
import { cn, filterUndefinedProps } from "@/lib/utils"
import { badgeVariants } from "."

const props = defineProps<PrimitiveProps & {
  variant?: BadgeVariants["variant"]
  class?: HTMLAttributes["class"]
}>()

const delegatedProps = reactiveOmit(props, "class")
const filteredProps = computed(() => filterUndefinedProps(delegatedProps))
</script>

<template>
  <Primitive
    data-slot="badge"
    :class="cn(badgeVariants({ variant }), props.class)"
    v-bind="filteredProps"
  >
    <slot />
  </Primitive>
</template>
