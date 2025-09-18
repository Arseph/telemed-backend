<script lang="ts">
import LoadingProcess from '@/components/loading.vue'
import ConfirmationModal from '@/components/snackbars/confirmation.vue'
import ErrorSnackbar from '@/components/snackbars/errors.vue'
import SuccessSnackbar from '@/components/snackbars/success.vue'
import { axiosJson } from '@/store/axios'
import { defineComponent, ref } from 'vue'

export default defineComponent({
  components: {
    ConfirmationModal,
    LoadingProcess,
    ErrorSnackbar,
    SuccessSnackbar,
  },
  setup() {
    const successMessage = ref('')
    const isSuccess = ref(false)
    const errorMessage = ref('')
    const isError = ref(false)
    const isLoading = ref(false)
    const showModal = ref(false)
    const modalAction = ref<'timeIn' | 'timeOut' | null>(null)

    const openModal = (action: 'timeIn' | 'timeOut') => {
      modalAction.value = action
      showModal.value = true
    }

    const handleTimeLog = async (faceDescriptor: Float32Array) => {
      isLoading.value = true
      if(modalAction.value === 'timeIn') {
        try {
          await axiosJson.post('api/attendance', { faceDescriptor }).then(response => {
            isLoading.value = false
            successMessage.value = response.data.message
            isSuccess.value = true
          })
            .catch(error => {
              console.log(error)
              isLoading.value = false
              errorMessage.value = error.response.data.message
              isError.value = true
            })
        }
        catch (error) {
          console.log('haysss')
          isLoading.value = false
          errorMessage.value = error.message
          isError.value = true
        }
        finally {
          showModal.value = false
        }
      } else {
        try {
          await axiosJson.post('api/manage/time-out', { faceDescriptor }).then(response => {
            isLoading.value = false
            successMessage.value = response.data.message
            isSuccess.value = true
          })
            .catch(error => {
              console.log(error)
              isLoading.value = false
              errorMessage.value = error.data.message
              isError.value = true
            })
        }
        catch (error) {
          console.log('haysss')
          isLoading.value = false
          errorMessage.value = error.data.message
          isError.value = true
        }
        finally {
          showModal.value = false
        }
      }
    }

    return {
      showModal,
      modalAction,
      openModal,
      handleTimeLog,
      isLoading,
      errorMessage,
      isError,
      successMessage,
      isSuccess,
    }
  },
})
</script>

<template>
  <div class="webcam-container">
    <VBtn
      variant="outlined"
      class="ma-3"
      color="success"
      block
      @click="openModal('timeIn')"
    >
      Time In
    </VBtn>
    <VBtn
      variant="outlined"
      color="error"
      block
      @click="openModal('timeOut')"
    >
      Time Out
    </VBtn>
    <VBtn
      variant="outlined"
      color="info"
      block
    >
      Daily Time Record
    </VBtn>
    <ConfirmationModal
      :visible="showModal"
      :title="modalAction === 'timeIn' ? 'Time In' : 'Time Out'"
      :message="modalAction === 'timeIn' ? 'Detecting your face for time in...' : 'Detecting your face for time out...'"
      @confirm="handleTimeLog"
    />
    <LoadingProcess
      :visible="isLoading"
      @update:visible="isLoading = $event"
    />
    <ErrorSnackbar
      :message="errorMessage"
      :visible="isError"
      @update:visible="isError = $event"
    />
    <SuccessSnackbar
      :message="successMessage"
      :visible="isSuccess"
      @update:visible="isSuccess = $event"
    />
  </div>
</template>

<style scoped>
.webcam-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

button {
  margin: 20px;
  font-size: 16px;
  padding-block: 10px;
  padding-inline: 20px;
}
</style>
