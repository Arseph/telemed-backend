<script lang="ts">
import { defineComponent, ref, watch } from 'vue'

export default defineComponent({
  name: 'LoadingProcess',
  props: {
    message: {
      type: String,
      required: false,
      default: '',
    },
    visible: {
      type: Boolean,
      required: true,
    },
  },
  emits: ['update:visible'],
  setup(props, { emit }) {
    const isLoading = ref(props.visible)

    // Watch for changes to the visible prop
    watch(
      () => props.visible,
      newVal => {
        isLoading.value = newVal
      },
    )
    watch(isLoading, newVal => {
      emit('update:visible', newVal)
    })

    return {
      isLoading,
    }
  },
})
</script>

<template>
  <VDialog
    v-model="isLoading"
    width="300"
    persistent
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        Loading... Please stand by
        <VProgressLinear
          indeterminate
          bg-color="rgba(var(--v-theme-surface), 0.1)"
          :height="8"
          class="mb-0 mt-4"
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>
