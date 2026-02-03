<template>
  <VContainer fluid class="pa-4 rounded">
    <!-- HEADER -->
    <VRow class="mb-4">
      <VCol>
        <h2 class="text-h4">Online Users</h2>
      </VCol>
    </VRow>

    <!-- SEARCH AND TABS -->
    <VRow class="mb-4" align="center">
      <VCol cols="12" md="6">
        <VTextField
          v-model="search"
          label="Search users"
          prepend-inner-icon="tabler-search"
          clearable
          density="comfortable"
        />
      </VCol>

      <VCol cols="12" md="6">
        <VTabs v-model="activeTab" background-color="primary-lighten-5">
          <VTab value="all">All Users</VTab>
          <VTab value="doctor">Doctors</VTab>
          <VTab value="patient">Patients</VTab>
        </VTabs>
      </VCol>
    </VRow>

    <!-- USER CARDS -->
    <VRow>
      <VCol
        v-for="item in filteredUsers"
        :key="item.user?.id || item.id"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <VCard class="pa-3" elevation="2">
          <div class="d-flex align-center mb-3">
            <!-- Avatar -->
            <VAvatar
              color="primary"
              variant="tonal"
              size="64"
              class="rounded-circle"
            >
              {{ getUserName(item).charAt(0) }}
            </VAvatar>

            <div class="ms-3">
              <!-- Name -->
              <div class="font-weight-medium text-subtitle-2">
                {{ getUserName(item) }}
              </div>
              <!-- Role / Level -->
              <div class="text-caption text-capitalize text-medium-emphasis">
                {{ getUserRole(item) }}
              </div>
            </div>
          </div>

          <!-- Online Chip -->
          <div class="d-flex justify-end">
            <VChip color="success" size="small" variant="flat">
              Online
            </VChip>
          </div>
        </VCard>
      </VCol>

      <!-- NO USERS -->
      <VCol cols="12" v-if="filteredUsers.length === 0">
        <VCard class="pa-6 text-center text-medium-emphasis">
          No online users found
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup lang="ts">
import { useOnlineUsers } from '@/composables/useOnlineUsers'
import { computed, ref } from 'vue'

const search = ref('')
const activeTab = ref('all')

// Get real-time online users from composable
const { onlineUsers } = useOnlineUsers()

// Helper to get full name
const getUserName = (item: any) => {
  const u = item?.user ?? item
  const fname = u?.fname ?? ''
  const lname = u?.lname ?? ''
  return `${fname} ${lname}`.trim() || 'Unknown'
}

// Helper to get role / level
const getUserRole = (item: any) => {
  const u = item?.user ?? item
  return u?.level ?? 'unknown'
}

// Filter online users by search and tab
const filteredUsers = computed(() => {
  if (!Array.isArray(onlineUsers.value)) return []
  return onlineUsers.value.filter((item: any) => {
    const role = getUserRole(item)
    const name = getUserName(item)
    const roleMatch = activeTab.value === 'all' || role.toLowerCase() === activeTab.value.toLowerCase()
    const searchMatch = name.toLowerCase().includes(search.value.toLowerCase())
    return roleMatch && searchMatch
  })
})
</script>
