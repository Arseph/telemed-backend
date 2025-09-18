<script setup lang="ts">
import axios from 'axios'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { themeConfig } from '@themeConfig'
import { globals } from '../src/globals'
definePage({
  meta: {
    layout: 'blank',
  },
})

const isPasswordVisible = ref(false)

const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight,
  authV2LoginIllustrationDark,
  authV2LoginIllustrationBorderedLight,
  authV2LoginIllustrationBorderedDark,
  true)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

const username = ref<string>('')
const password = ref<string>('')
const isLoggedIn = ref<boolean>(false)
const showAlert = ref<boolean>(false)
const alertMessage = ref<string>('')
const router = useRouter()
const API_URL = globals.api
onMounted(() => {
  const token = localStorage.getItem('authToken')
  if (token) {
    isLoggedIn.value = true
    router.push('/')
  }
})

const login = async () => {
  try {
    const response = await axios.post(`${API_URL}/api/auth/dbms/login`, {
      username: username.value,
      password: password.value,
    })

    const token = response.data.accessToken
    const name = response.data.name

    localStorage.setItem('authToken', token)
    localStorage.setItem('name', name)
    router.push('/')
  }
  catch (error) {
    alertMessage.value = 'Invalid email or password. Please try again.'
    showAlert.value = true
  }
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <h1 class="auth-title">
        Database Management System
      </h1>
    </div>
  </RouterLink>

  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
        </div>

        <img
          class="auth-footer-mask"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="10"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Welcome to <span class="text-capitalize"> {{ themeConfig.app.title }} </span>
          </h4>
          <p class="mb-0">
            Please login using your HRMIS acoount
          </p>
        </VCardText>
        <VCardText>
          <VAlert
            v-if="showAlert"
            color="primary"
            variant="tonal"
            dismissible
            @input="showAlert = false"
          >
            {{ alertMessage }}
          </VAlert>
        </VCardText>
        <VCardText>
          <VForm @submit.prevent="login">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="username"
                  autofocus
                  label="Username"
                  type="text"
                  placeholder="············"
                />
              </VCol>
              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
                <p />
                <VBtn
                  block
                  type="submit"
                >
                  Login
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
