<script lang="ts">
import { defineComponent, ref, watch } from 'vue'

export default defineComponent({
  name: 'ErrorSnackbar',
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
    const showSnackbar = ref(props.visible)
    const errorMessage = ref(props.message)

    // Watch for changes to the visible prop
    watch(
      () => props.visible,
      newVal => {
        showSnackbar.value = newVal
      },
    )
    watch(
      () => props.message,
      newVal => {
        errorMessage.value = newVal
      },
    )
    watch(showSnackbar, newVal => {
      emit('update:visible', newVal)
    })

    return {
      showSnackbar,
      errorMessage,
    }
  },
})
</script>

<template>
  <VSnackbar
    v-model="showSnackbar"
    :timeout="3000"
    location="bottom start"
    variant="flat"
    color="error"
  >
    {{ errorMessage }}
  </VSnackbar>
</template>
