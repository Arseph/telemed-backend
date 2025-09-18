<script setup lang="ts">
import axios from 'axios'
import Swal from 'sweetalert2'
import { onMounted, ref } from 'vue'

const name = ref<string>('')
const token = ref<string>('')
const router = useRouter()

onMounted(() => {
  token.value = localStorage.getItem('authToken') || ''
  name.value = localStorage.getItem('name') || 'Guest'
})

const firstLetter = computed(() => {
  return name.value.charAt(0).toUpperCase()
})

const logout = async () => {
  try {
    Swal.fire({
      title: 'Are you sure you want to logout?',
      text: 'You won\'t be able to revert this!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#285c4d',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes',
    }).then(result => {
      if (result.isConfirmed) {
        const response = async () => await axios.get('http://127.0.0.1:8000/api/auth/logout', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        })

        console.log(response)
        localStorage.removeItem('authToken')
        localStorage.removeItem('name')
        router.push('/login')
      }
    })
  }
  catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Logout Failed',
      text: 'Unauthorized',
      confirmButtonText: 'OK',
    })
  }
}
</script>

<template>
  <VBadge
    dot
    location="bottom right"
    offset-x="3"
    offset-y="3"
    bordered
    color="success"
  >
    <VAvatar
      class="cursor-pointer"
      color="primary"
      variant="tonal"
    >
      <VAvatar
        color="primary"
        size="56"
      >
        <span class="avatar-letter">{{ firstLetter }}</span>
      </VAvatar>

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="230"
        location="bottom end"
        offset="14px"
      >
        <VList>
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                >
                  <VAvatar
                    color="primary"
                    size="56"
                  >
                    <span class="avatar-letter">{{ firstLetter }}</span>
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-semibold">
              {{ name }}
            </VListItemTitle>
            <VListItemSubtitle>Superadmin</VListItemSubtitle>
          </VListItem>

          <VDivider class="my-2" />

          <!-- 👉 Profile -->
          <VListItem link>
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-user"
                size="22"
              />
            </template>

            <VListItemTitle>Profile</VListItemTitle>
          </VListItem>
          <VDivider class="my-2" />
          <VListItem @click="logout">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-logout"
                size="22"
              />
            </template>

            <VListItemTitle>Logout</VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
