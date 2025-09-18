<script lang="ts">
import { defineComponent, ref, watch } from 'vue'

export default defineComponent({
  name: 'SuccessSnackbar',
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
    const successMessage = ref(props.message)

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
        successMessage.value = newVal
      },
    )
    watch(showSnackbar, newVal => {
      emit('update:visible', newVal)
    })

    return {
      showSnackbar,
      successMessage,
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
    color="success"
  >
    {{ successMessage }}
  </VSnackbar>
</template>
