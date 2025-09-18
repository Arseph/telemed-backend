<script lang="ts">
import LoadingProcess from '@/components/loading.vue'
import ErrorSnackbar from '@/components/snackbars/errors.vue'
import SuccessSnackbar from '@/components/snackbars/success.vue'
import { fetchRegistry, fetchUserNot } from '@/src/services/dtrService'
import { axiosIns } from '@/store/axios'
import CryptoJS from 'crypto-js'
import { defineComponent, ref } from 'vue'
import { useRouter } from 'vue-router'

export default defineComponent({
  components: {
    ErrorSnackbar,
    SuccessSnackbar,
    LoadingProcess,
  },
  setup() {
    const router = useRouter()
    const regForm = ref(false)
    const registryLoading = ref(false)
    const userLoading = ref(false)
    const isError = ref(false)
    const errorMessage = ref('')
    const successMessage = ref('')
    const isSuccess = ref(false)
    const users = ref([])
    const search = ref('')
    const searchReg = ref('')
    const registry = ref([])
    const roles = ['Employee', 'Administrator']
    const apiUrl = 'http://192.168.1.56/hrmis/storage/app/public/'
    const confirmReg = ref(false)
    const isLoading = ref(false)

    const formData = ref({
      id: '',
      role: ref('Employee'),
      name: '',
    })

    const loadUsers = async () => {
      userLoading.value = true
      try {
        users.value = await fetchUserNot()
      }
      catch (error) {
        console.error('Failed to fetch users', error)
      }
      finally {
        userLoading.value = false
      }
    }

    const loadReg = async () => {
      registryLoading.value = true
      try {
        registry.value = await fetchRegistry()
      }
      catch (error) {
        console.error('Failed to fetch registry', error)
      }
      finally {
        registryLoading.value = false
      }
    }

    async function register(): Promise<void> {
      isLoading.value = true
      try {
        const fd = new FormData()

        fd.append('id', formData.value.id)
        fd.append('role', formData.value.role)
        fd.append('name', formData.value.name)

        const response = await axiosIns.post('api/manage/register', fd)

        successMessage.value = response.data.message
      }
      catch (error) {
        errorMessage.value = error.message
        isError.value = true
      }
      finally {
        isSuccess.value = true
        confirmReg.value = false
        searchReg.value = ''
        loadUsers()
        loadReg()
        isLoading.value = false
      }
    }

    const headers = [
      { title: '', key: 'pic' },
      { title: 'Name', key: 'full_name' },
      { title: 'Division', key: 'off' },
      { title: 'Section', key: 'sec' },
      { title: 'Position', key: 'pos' },
      { title: 'Action', key: 'action' },
    ]

    const headersReg = [
      { title: '', key: 'pic' },
      { title: 'Name', key: 'full_name' },
      { title: 'Division', key: 'off' },
      { title: 'Section', key: 'sec' },
      { title: 'Position', key: 'pos' },
      { title: 'Action', key: 'action' },
    ]

    const data = computed(() =>
      users.value.map(user => ({
        ...user,
        full_name: `${user.sur_name}, ${user.first_name} ${user.middle_name ? `${user.middle_name.charAt(0).toUpperCase()}. ` : ''}`,
        picture_link: `${apiUrl}${user.pic}`,
      })),
    )

    const regdata = computed(() =>
      registry.value.map(user => ({
        ...user,
        full_name: `${user.sur_name}, ${user.first_name} ${user.middle_name ? `${user.middle_name.charAt(0).toUpperCase()}. ` : ''}`,
        picture_link: `${apiUrl}${user.pic}`,
      })),
    )

    const editItem = (item: any) => {
      formData.value.name = item.full_name
      formData.value.id = item.id
      formData.value.role = 'Employee'
      confirmReg.value = true
    }

    const viewDtr = (item: any) => {
      const userid = item.id
      const secretKey = 'SecretKey'
      const encryptedId = CryptoJS.AES.encrypt(userid.toString(), secretKey).toString()

      router.push({ name: 'DtrView', params: { id: encryptedId } })
    }

    onMounted(() => {
      loadUsers()
      loadReg()
    })

    return {
      headers,
      headersReg,
      search,
      searchReg,
      regForm,
      roles,
      formData,
      data,
      editItem,
      confirmReg,
      register,
      isError,
      errorMessage,
      successMessage,
      isSuccess,
      registryLoading,
      userLoading,
      regdata,
      viewDtr,
      isLoading,
    }
  },
})
</script>

<template>
  <h2>DTR Management</h2>
  <VDataTable
    :headers="headers"
    :items="regdata"
    :search="search"
    :loading="registryLoading"
    item-key="id"
  >
    <template #item.pic="{ item }">
      <div class="d-flex align-center">
        <VAvatar
          size="32"
          :color="item.pic ? '' : 'primary'"
          :class="item.pic ? '' : 'v-avatar-light-bg primary--text'"
          :variant="!item.pic ? 'tonal' : undefined"
        >
          <VImg
            v-if="item.pic"
            :src="item.picture_link"
          />
          <span v-else>{{ avatarText(item.first_name) }}</span>
        </VAvatar>
      </div>
    </template>
    <template #item.action="{ item }">
      <div class="d-flex gap-1">
        <IconBtn @click="viewDtr(item)">
          <VIcon icon="tabler-share-3" />
        </IconBtn>
      </div>
    </template>
    <template #top>
      <VCardText>
        <VRow>
          <VCol cols="8">
            <VDialog
              v-model="regForm"
              :width="$vuetify.display.smAndDown ? 'auto' : 900"
              persistent
            >
              <template #activator="{ props }">
                <VBtn v-bind="props">
                  Register
                  <VIcon
                    end
                    icon="tabler-plus"
                  />
                </VBtn>
              </template>
              <DialogCloseBtn @click="regForm = !regForm" />
              <VCard title="Register">
                <VCardText>
                  <VDataTable
                    :headers="headersReg"
                    :items="data"
                    :search="searchReg"
                    :loading="userLoading"
                    item-key="id"
                  >
                    <template #top>
                      <VCardText>
                        <VRow>
                          <VCol
                            cols="12"
                            offset-md="8"
                            md="4"
                          >
                            <AppTextField
                              v-model="searchReg"
                              placeholder="Search ..."
                              append-inner-icon="tabler-search"
                              single-line
                              hide-details
                              dense
                              outlined
                              clearable
                            />
                          </VCol>
                        </VRow>
                      </VCardText>
                    </template>
                    <template #item.pic="{ item }">
                      <div class="d-flex align-center">
                        <VAvatar
                          size="32"
                          :color="item.pic ? '' : 'primary'"
                          :class="item.pic ? '' : 'v-avatar-light-bg primary--text'"
                          :variant="!item.pic ? 'tonal' : undefined"
                        >
                          <VImg
                            v-if="item.pic"
                            :src="item.picture_link"
                          />
                          <span v-else>{{ avatarText(item.first_name) }}</span>
                        </VAvatar>
                      </div>
                    </template>
                    <template #item.action="{ item }">
                      <div class="d-flex gap-1">
                        <IconBtn @click="editItem(item)">
                          <VIcon icon="tabler-share-3" />
                        </IconBtn>
                      </div>
                    </template>
                  </VDataTable>
                </VCardText>
              </VCard>
            </VDialog>
          </VCol>
          <VCol cols="4">
            <AppTextField
              v-model="search"
              placeholder="Search ..."
              append-inner-icon="tabler-search"
              single-line
              hide-details
              dense
              clearable
              outlined
            />
          </VCol>
        </VRow>
      </VCardText>
    </template>
  </VDataTable>
  <VDialog
    v-model="confirmReg"
    class="v-dialog-sm"
  >
    <DialogCloseBtn @click="confirmReg = false" />

    <VCard title="Confirm">
      <VForm @submit.prevent="register">
        <VCardText class="d-flex flex-wrap gap-3">
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="formData.name"
                label="Employee"
                placeholder="Placeholder Text"
                disabled
              />
            </VCol>

            <VCol cols="12">
              <AppCombobox
                v-model="formData.role"
                :items="roles"
                label="User Type"
                placeholder="Employee"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="d-flex justify-end">
          <VBtn type="submit">
            Save
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
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
  <LoadingProcess
    :visible="isLoading"
    @update:visible="isLoading = $event"
  />
</template>
