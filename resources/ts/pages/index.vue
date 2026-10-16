<script setup lang="ts">
import { fetchUsers } from '@/src/services/userService'

interface UserRow {
  id: number
  fname: string
  lname: string
  mname?: string
  username: string
  role: string
  status: string
  facility?: string
  doc_category?: string
  [key: string]: any
}

const loading = ref(true)
const users = ref<UserRow[]>([])
const facilitiesCount = ref(0)

const loadStats = async () => {
  loading.value = true
  try {
    const res = await fetchUsers()
    users.value = res.users ?? []
    facilitiesCount.value = (res.facilities ?? []).length
  }
  catch (error) {
    console.error('Failed to load dashboard analytics', error)
  }
  finally {
    loading.value = false
  }
}

onMounted(loadStats)

const totalUsers = computed(() => users.value.length)
const activeUsers = computed(() => users.value.filter(u => u.status === 'active').length)
const inactiveUsers = computed(() => users.value.filter(u => u.status === 'inactive').length)

const countByRole = (role: string) => users.value.filter(u => u.role === role).length

const stats = computed(() => [
  { title: 'Total Users', value: totalUsers.value, icon: 'tabler-users', color: 'primary' },
  { title: 'Active', value: activeUsers.value, icon: 'tabler-user-check', color: 'success' },
  { title: 'Inactive', value: inactiveUsers.value, icon: 'tabler-user-off', color: 'error' },
  { title: 'Facilities', value: facilitiesCount.value, icon: 'tabler-building-hospital', color: 'info' },
])

// ─── Users by Role (compact breakdown) ───────────────────
const roleBreakdown = computed(() => [
  { label: 'Doctors', value: countByRole('doctor'), color: 'secondary' },
  { label: 'Midwives', value: countByRole('midwife'), color: 'secondary' },
  { label: 'Admins', value: countByRole('admin'), color: 'info' },
  { label: 'Superadmins', value: countByRole('superadmin'), color: 'warning' },
])
</script>

<template>
  <div>
    <!-- Summary stat cards -->
    <VRow>
      <VCol
        v-for="stat in stats"
        :key="stat.title"
        cols="12"
        sm="6"
        md="3"
      >
        <VCard class="h-100">
          <VCardText class="d-flex align-center gap-4">
            <VAvatar
              :color="stat.color"
              variant="tonal"
              size="48"
              rounded
            >
              <VIcon
                :icon="stat.icon"
                size="28"
              />
            </VAvatar>
            <div>
              <h4 class="text-h4">
                {{ stat.value }}
              </h4>
              <span class="text-body-2 text-medium-emphasis">{{ stat.title }}</span>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Users by Role -->
    <VRow class="mt-1">
      <VCol
        cols="12"
        sm="6"
        md="4"
      >
        <VCard title="Users by Role">
          <VCardText class="d-flex flex-wrap gap-2">
            <VChip
              v-for="role in roleBreakdown"
              :key="role.label"
              :color="role.color"
              variant="tonal"
              size="small"
            >
              {{ role.label }}: {{ role.value }}
            </VChip>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
