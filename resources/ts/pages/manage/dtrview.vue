<script lang="ts">
import LoadingProcess from '@/components/loading.vue'
import ErrorSnackbar from '@/components/snackbars/errors.vue'
import SuccessSnackbar from '@/components/snackbars/success.vue'
import { fetchEmployee } from '@/src/services/dtrService'
import { axiosIns, axiosPdf } from '@/store/axios'
import CryptoJS from 'crypto-js'
import { computed, defineComponent, onMounted, ref } from 'vue'
import type { RouteParams } from 'vue-router'
import { useRoute } from 'vue-router'
import type { VForm } from 'vuetify/components/VForm'
export default defineComponent({
  components: {
    ErrorSnackbar,
    SuccessSnackbar,
    LoadingProcess,
  },
  setup() {
    const refForm = ref<VForm>()
    const route = useRoute()
    const secretKey = 'SecretKey'
    const user = ref([])
    const dtrs = ref([])
    const isLoading = ref(false)
    const params = route.params as RouteParams
    const encryptedId = params.id
    const bytes = CryptoJS.AES.decrypt(encryptedId, secretKey)
    const userId = bytes.toString(CryptoJS.enc.Utf8)
    const apiUrl = 'http://192.168.1.56/hrmis/storage/app/public/'
    const isDtrGen = ref(false)
    const name = ref('')
    const pos = ref('')
    const errorMessage = ref('')
    const isError = ref(false)
    const dtrUrl = ref<string | null>(null);
    const formData = ref({
      id: userId,
      month: '',
      year: '',
      name: '',
    })

    const months = [
      { value: 1, title: 'January' },
      { value: 2, title: 'February' },
      { value: 3, title: 'March' },
      { value: 4, title: 'April' },
      { value: 5, title: 'May' },
      { value: 6, title: 'June' },
      { value: 7, title: 'July' },
      { value: 8, title: 'August' },
      { value: 9, title: 'September' },
      { value: 10, title: 'October' },
      { value: 11, title: 'November' },
      { value: 12, title: 'December' },
    ]

    const startYear = 2024
    const currentYear = new Date().getFullYear()

    const years = Array.from({ length: currentYear - startYear + 1 }, (_, index) => {
      const year = startYear + index

      return { value: year, title: year.toString() }
    })

    const loadUser = async () => {
      isLoading.value = true
      try {
        user.value = await fetchEmployee(userId)

        const mname = computed(() => {
          return user.value.middle_name.charAt(0).toUpperCase()
        })

        name.value = `${user.value.sur_name}, ${user.value.first_name} ${mname.value ? `${mname.value}. ` : ''}`
        pos.value = `${user.value.pos}`
      }
      catch (error) {
        console.error('Failed to fetch users', error)
      }
      finally {
        isLoading.value = false
      }
    }

    async function loadDtr(): Promise<void> {
      isLoading.value = true
      try {
        const fd = new FormData()

        fd.append('id', formData.value.id)
        fd.append('month', formData.value.month.value)
        fd.append('year', formData.value.year.value)
        fd.append('name', name.value)

        const response = await axiosIns.post('api/manage/view/dtr', fd)

        dtrs.value = response.data
      }
      catch (error) {
        errorMessage.value = error.message
        isError.value = true
      }
      finally {
        isLoading.value = false
      }
    }

    const generateDtr = async () => {
      isLoading.value = true
      try {
        const response = await axiosPdf.get('api/manage/generate-dtr', {
          params: {
            id: formData.value.id,
            month: formData.value.month.value,
            year: formData.value.year.value,
            name: name.value,
          },
          responseType: 'blob',
        });
        const pdfBlob = new Blob([response.data], { type: 'application/pdf' });
        dtrUrl.value = URL.createObjectURL(pdfBlob);
        isDtrGen.value = true
      } catch (error) {
        errorMessage.value = error.message
        isError.value = true
      } finally {
        isLoading.value = false
      }
    };

    onMounted(() => {
      loadUser()
      const currentDate = new Date()
      formData.value.month = months.find((month) => month.value === currentDate.getMonth() + 1) || null
      formData.value.year = years.find((year) => year.value === currentDate.getFullYear()) || null
    })

    return {
      user,
      isLoading,
      apiUrl,
      name,
      pos,
      months,
      years,
      loadDtr,
      errorMessage,
      isError,
      formData,
      refForm,
      dtrs,
      generateDtr,
      isDtrGen,
      dtrUrl,
    }
  },
})
</script>

<template>
  <VRow>
    <VCol
      sm="4"
      cols="12"
    >
      <VCard>
        <div class="d-flex flex-wrap flex-md-nowrap flex-column flex-md-row">
          <div class="ma-3 pa-3">
            <VAvatar
              size="75"
              class="avatar-center"
              :image="`${apiUrl}${user.pic}`"
            />
          </div>
          <div>
            <VCardItem>
              <VCardTitle>{{ name }}</VCardTitle>
            </VCardItem>

            <VCardText>
              {{ pos }}
            </VCardText>
          </div>
        </div>
        <VForm
          ref="refForm"
          @submit.prevent="loadDtr"
        >
          <VRow class="pa-3">
            <VCol
              sm="5"
              cols="12"
            >
              <VCombobox
                is
                v-model="formData.month"
                label="Months"
                :items="months"
                item-text="title"
                item-value="value"
                variant="outlined"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol
              sm="5"
              cols="12"
            >
              <VCombobox
                is
                v-model="formData.year"
                label="Year"
                :items="years"
                item-text="title"
                item-value="value"
                variant="outlined"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol
              sm="2"
              cols="12"
            >
              <VBtn
                type="submit"
                icon="tabler-search"
                variant="outlined"
                color="success"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </VCol>
    <VCol
      sm="8"
      cols="12"
    >
      <VCard>
        <VCardItem>
          <VRow>
            <VCol sm="6" cols="12">
              <VCardTitle>Daily Time Record - {{ formData.month.title }} {{ formData.year.title }}</VCardTitle>
            </VCol>
            <VCol sm="6" cols="12" class="text-end mb-3">
                  <VBtn color="info" @click="generateDtr" v-if="dtrs.total_rendered_hours">
                    Print <VIcon icon="tabler-file-export" />
                  </VBtn>
            </VCol>
          </VRow>
          <VRow>
            <VCol sm="4" cols="12">
              <VAlert
                v-if="dtrs.total_rendered_hours"
                border="start"
                border-color="primary"
              >
                Total Rendered Hours: {{ dtrs.total_rendered_hours }}
              </VAlert>
            </VCol>
            <VCol sm="4" cols="12">
              <VAlert
                v-if="dtrs.regdays"
                border="start"
                border-color="info"
              >
                Regular Days: {{ dtrs.regdays }}
              </VAlert>
            </VCol>
            <VCol sm="4" cols="12">
              <VAlert
                v-if="dtrs.sat"
                border="start"
                border-color="secondary"
              >
                Saturdays: {{ dtrs.sat }}
              </VAlert>
            </VCol>
          </VRow>
            <VTable
              v-if="dtrs.attendance"
              density="compact"
              class="text-no-wrap text-center"
              fixed-header
            >
              <thead>
                <tr>
                  <td>
                    DAY
                  </td>
                  <td>
                    AM IN
                  </td>
                  <td>
                    AM OUT
                  </td>
                  <td>
                    PM IN
                  </td>
                  <td>
                    PM OUT
                  </td>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in dtrs.attendance"
                  :key="item.date"
                >
                  <td>
                    {{ item.date }}
                  </td>
                  <td>
                    {{ item.in_am }}
                  </td>
                  <td>
                    {{ item.out_am }}
                  </td>
                  <td>
                    {{ item.in_pm }}
                  </td>
                  <td>
                    {{ item.out_pm }}
                  </td>
                </tr>
              </tbody>
            </VTable>
            <VAlert
              v-if="dtrs.length == 0"
              type="error"
              variant="tonal"
              text="No Data Found(Click search to view Daily Time Record)"
            />
        </VCardItem>
      </VCard>
    </VCol>
  </VRow>
  <VDialog
    v-model="isDtrGen"
    fullscreen
    :scrim="false"
    transition="dialog-bottom-transition"
  >
  <VCard>
    <div>
      <VToolbar color="primary">
        <VBtn
          icon
          variant="plain"
          @click="isDtrGen = false, dtrUrl = null"
        >
          <VIcon
            color="white"
            icon="tabler-x"
          />
        </VBtn>

        <VToolbarTitle>Preview</VToolbarTitle>

        <VSpacer />

        <VToolbarItems>
          <VBtn
            variant="text"
            @click="isDtrGen = false, dtrUrl = null"
          >
            CLOSE
          </VBtn>
        </VToolbarItems>
      </VToolbar>
    </div>
      <object
          v-if="dtrUrl"
          :data="dtrUrl"
          type="application/pdf"
          width="100%"
          height="800px"
        >
          <p>Your browser does not support PDF viewing. 
             <a :href="dtrUrl" download>Click here to download the PDF</a>.
          </p>
        </object>
      </VCard>
  </VDialog>
  <LoadingProcess
    :visible="isLoading"
    @update:visible="isLoading = $event"
  />
  <ErrorSnackbar
    :message="errorMessage"
    :visible="isError"
    @update:visible="isError = $event"
  />
</template>

<style lang="scss">
.dialog-bottom-transition-enter-active,
.dialog-bottom-transition-leave-active {
  transition: transform 0.2s ease-in-out;
}
</style>
