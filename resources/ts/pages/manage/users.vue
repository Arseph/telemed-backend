<script lang="ts">
import { fetchUsers } from '@/src/services/userService';
import { ref } from 'vue';

export default {
  setup() {
    const users = ref([])
    const search = ref('')
    const apiUrl = 'http://192.168.1.56/hrmis/storage/app/public/'

    const headers = [
      { title: 'ID', key: 'id' },
      { title: '', key: 'pic' },
      { title: 'Email', key: 'email' },
      { title: 'Name', key: 'full_name' },
      { title: 'Division', key: 'off' },
      { title: 'Section', key: 'sec' },
      { title: 'Position', key: 'pos' },
    ]

    const data = computed(() =>
      users.value.map(user => ({
        ...user,
        full_name: `${user.first_name} ${user.middle_name ? `${user.middle_name} ` : ''}${user.sur_name}`,
        picture_link: `${apiUrl}${user.pic}`,
      })),
    )

    const loadUsers = async () => {
      try {
        users.value = await fetchUsers()
      }
      catch (error) {
        console.error('Failed to fetch users', error)
      }
    }

    onMounted(() => {
      loadUsers()
      console.log(localStorage.getItem('authToken'))
    })

    return {
      users,
      headers,
      search,
      data,
      apiUrl,
    }
  },
}
</script>

<template>
  <VDataTable
    :headers="headers"
    :items="data"
    :search="search"
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
              v-model="search"
              placeholder="Search ..."
              append-inner-icon="tabler-search"
              single-line
              hide-details
              dense
              outlined
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
        <div class="d-flex flex-column ms-3">
          <span class="d-block font-weight-medium text-high-emphasis text-truncate">{{ item.fullName }}</span>
          <small>{{ item.post }}</small>
        </div>
      </div>
    </template>
  </VDataTable>
</template>
