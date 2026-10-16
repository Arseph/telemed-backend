<script lang="ts">
import { fetchUsers } from '@/src/services/userService'
import { createUser } from '@/src/services/userService'
import { deleteUserService } from '@/src/services/userService'
import { deactivateUserService } from '@/src/services/userService'
import { updateUserService } from '@/src/services/userService'
import { resetPasswordService } from '@/src/services/userService'
import { globals } from '@/src/globals'
import { ref, reactive, computed, onMounted } from 'vue';

export default {
  setup() {
    interface User {
      id: number
      fname: string
      mname: string
      lname: string
      picture?: string
      full_name?: string
      picture_link?: string
      [key: string]: any
    }

    const users = ref<User[]>([])
    const loading = ref(true)
    const search = ref('')

    const showDeleteDialog = ref(false)
    const showUserDialog = ref(false)
    const dialogMode = ref<'add' | 'edit'>('add')

    const showResetDialog = ref(false)
    const resetTarget = ref<User | null>(null)
    const resetForm = reactive({ password: '', confirm: '' })

    const showProfileDialog = ref(false)
    const profileUser = ref<User | null>(null)

    // Profile picture upload state
    const refInputEl = ref<HTMLInputElement>()
    const selectedFile = ref<File | null>(null)
    const previewUrl = ref<string | null>(null)

    const userActionSnackbar = ref({ show: false, text: '', color: '' });
    
    interface SelectOption {
      title: string
      value: number
    }

    const facilities = ref<SelectOption[]>([])
    const doc_categories = ref<SelectOption[]>([])
    const refForm = ref()
    const selectedUser = ref<User | null>(null)

    const currentStep = ref(0)
    const headers = [
      // { title: '', key: 'pic' },
      { title: 'Name', key: 'full_name' },
      { title: 'Username', key: 'username' },
      { title: 'Facility', key: 'facility' },
      { title: 'Role', key: 'role' },
      { title: 'Status', key: 'status' },
      { title: 'Action', key: 'action' },
    ]
    const data = computed(() =>
      users.value.map(user => ({
      ...user,
        full_name: `${user.fname} ${user.mname} ${user.lname}`,
        picture_link: user.picture ? `${globals.api}/${user.picture}` : null,
      })),
    )

    const userForm = reactive({
      id: null,
      prefix: '',
      fname: '',
      mname: '',
      lname: '',
      email: '',
      username: '',
      password: '',
      contact: '',
      license_no: '',
      accrediation_no: '',
      accrediation_validity: '',
      facility_id: null,
      doc_cat_id: null,
      designation: '',
      status: '',
      role: '',
    })
    
    const openAddDialog = () => {
      Object.assign(userForm, {
        id: null,
        prefix: '',
        fname: '',
        mname: '',
        lname: '',
        email: '',
        username: '',
        password: '',
        contact: '',
        license_no: '',
        accrediation_no: '',
        accrediation_validity: '',
        facility_id: null,
        doc_cat_id: null,
        designation: '',
        status: '',
        role: '',
      })

      selectedFile.value = null
      previewUrl.value = null
      dialogMode.value = 'add'
      currentStep.value = 0
      showUserDialog.value = true
    }

    const openEditDialog = (user: User) => {
      Object.assign(userForm, {
        ...user,
        password: '', // reset password input
        facility_id: user.facility_id ? Number(user.facility_id) : null,
        doc_cat_id: user.doc_cat_id ? Number(user.doc_cat_id) : null,
      });

      selectedFile.value = null
      previewUrl.value = user.picture ? `${globals.api}/${user.picture}` : null
      dialogMode.value = 'edit';
      currentStep.value = 0;
      showUserDialog.value = true;
    };

    const triggerFileInput = () => {
      refInputEl.value?.click()
    }

    const onFileChange = (event: Event) => {
      const target = event.target as HTMLInputElement
      const file = target.files?.[0]
      if (!file)
        return

      selectedFile.value = file
      previewUrl.value = URL.createObjectURL(file)
    }

    const clearPicture = () => {
      selectedFile.value = null
      previewUrl.value = null
      if (refInputEl.value)
        refInputEl.value.value = ''
    }


    const steps = [
      { title: 'Personal Information', subtitle: 'Enter personal information',},
      { title: 'Account Setup', subtitle: 'Enter user details',},
      { title: 'Review & Confirm', subtitle: 'Verify user information',},
    ]
    const roleVariant = (role: string) => {
      if (role === 'doctor')
        return { color: 'secondary', text: 'Doctor' }
      else if (role === 'midwife')
        return { color: 'secondary', text: 'Midwife' }
      else if (role === 'admin')
        return { color: 'info', text: 'Admin' }
      else if (role === 'superadmin')
        return { color: 'warning', text: 'Superadmin' }
      else
        return { color: 'info', text: 'Undefined' }
    }
    const statusVariant = (status: string) => {
      if (status === 'active') {
        return { color: 'success', text: 'Active' }
      } else if (status === 'inactive') {
        return { color: 'error', text: 'Inactive' }
      } else {
        return { color: 'error', text: 'Undefined' }
      }
    }
    const prefixes = [
      { title: 'Mr.', value: 'Mr.' },
      { title: 'Ms.', value: 'Ms.' },
      { title: 'Mrs.', value: 'Mrs.' },
      { title: 'Dr.', value: 'Dr.' },
      { title: 'Engr.', value: 'Engr.' },
      { title: 'Prof.', value: 'Prof.' },
      { title: 'Atty.', value: 'Atty.' },
    ]
    const statuses = [
      { title: 'Active', value: 'active' },
      { title: 'Inactive', value: 'inactive' },
    ]
    const roles = [
      { title: 'Doctor', value: 'doctor' },
      { title: 'Midwife', value: 'midwife' },
      { title: 'Admin', value: 'admin' },
      { title: 'Superadmin', value: 'superadmin' },
    ]
    
    const loadUsers = async () => {
      loading.value = true   
      try {
        const res = await fetchUsers()
        users.value = res.users

        // In loadUsers()
        facilities.value = res.facilities.map(f => ({
          title: f.title,
          value: Number(f.value),  // ensure numeric
        }));

        doc_categories.value = res.doc_categories.map(c => ({
          title: c.title,
          value: Number(c.value),  // ensure numeric
        }));
      } catch (error) {
        console.error('Failed to fetch users', error)
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      loadUsers()
      console.log(localStorage.getItem('authToken'))
    })

    const selectedFacility = computed(() => {
      if (!userForm.facility_id) return '—';
      const facility = facilities.value.find(f => f.value === userForm.facility_id);
      return facility ? facility.title : '—';
    });

    const selectedCategory = computed(() => {
      if (!userForm.doc_cat_id) return '—';
      const category = doc_categories.value.find(c => c.value === userForm.doc_cat_id);
      return category ? category.title : '—';
    });

    const submitUser = async () => {
      // Validate form
      const { valid } = await refForm.value.validate()
      
      if (!valid) {
        // Show snackbar if form is incomplete
        userActionSnackbar.value = { 
          show: true, 
          text: 'Please fill out all required fields.', 
          color: 'error' 
        }
        return
      }

      loading.value = true

      try {
        // Only send real form fields — NOT display-only keys (full_name,
        // picture_link, facility, doc_category) or the existing picture path,
        // which would fail the backend's `image` validation.
        const fields: Record<string, any> = {
          prefix: userForm.prefix,
          fname: userForm.fname,
          mname: userForm.mname,
          lname: userForm.lname,
          email: userForm.email,
          username: userForm.username,
          password: userForm.password,
          contact: userForm.contact,
          license_no: userForm.license_no,
          accrediation_no: userForm.accrediation_no,
          accrediation_validity: userForm.accrediation_validity,
          facility_id: Number(userForm.facility_id),
          doc_cat_id: Number(userForm.doc_cat_id),
          designation: userForm.designation,
          status: userForm.status,
          role: userForm.role,
        }

        // Remove password if editing and password is blank
        if (dialogMode.value === 'edit' && !fields.password)
          delete fields.password

        const formData = new FormData()
        Object.entries(fields).forEach(([key, value]) => {
          if (value !== null && value !== undefined && value !== '')
            formData.append(key, value as string | Blob)
        })
        // Only attach a picture when the admin actually selected a new file
        if (selectedFile.value)
          formData.append('picture', selectedFile.value)

        if (dialogMode.value === 'add') {
          await createUser(formData)
          userActionSnackbar.value = { show: true, text: 'User added successfully', color: 'success' }
        } else {
          if (userForm.id === null) throw new Error('User ID is required for update')
          await updateUserService(Number(userForm.id), formData)
          userActionSnackbar.value = { show: true, text: 'User updated successfully', color: 'success' }
        }

        await loadUsers()
        showUserDialog.value = false
      } catch (err: any) {
        console.error(err)
        // Surface the backend validation message when present (422), else generic
        const resData = err?.response?.data
        const firstError = resData?.errors
          ? Object.values(resData.errors)[0]?.[0]
          : resData?.message
        userActionSnackbar.value = { show: true, text: firstError || 'Failed to save user', color: 'error' }
      } finally {
        loading.value = false
      }
    }

    const deleteUserPermanently = async () => {
      if (!selectedUser.value) return;
      loading.value = true;
      try {
        await deleteUserService(selectedUser.value.id);
        userActionSnackbar.value = { show: true, text: 'User deleted permanently.', color: 'success' };
        await loadUsers();
      } catch (error) {
        userActionSnackbar.value = { show: true, text: 'Failed to delete user.', color: 'error' };
      } finally {
        loading.value = false;
        showDeleteDialog.value = false;
        selectedUser.value = null;
      }
    };

    const deactivateUser = async () => {
      if (!selectedUser.value) return; // prevents null error

      loading.value = true;
      try {
        await deactivateUserService(selectedUser.value.id);
        userActionSnackbar.value = { show: true, text: 'User deactivated successfully.', color: 'success' };
        await loadUsers();
      } catch (error) {
        userActionSnackbar.value = { show: true, text: 'Failed to deactivate user.', color: 'error' };
      } finally {
        loading.value = false;
        showDeleteDialog.value = false;
        selectedUser.value = null;
      }
    };

    const openResetDialog = (user: User) => {
      resetTarget.value = user
      resetForm.password = ''
      resetForm.confirm = ''
      showResetDialog.value = true
    }

    const openProfileDialog = (user: User) => {
      profileUser.value = user
      showProfileDialog.value = true
    }

    const submitResetPassword = async () => {
      if (!resetTarget.value) return
      if (!resetForm.password || resetForm.password.length < 6) {
        userActionSnackbar.value = { show: true, text: 'Password must be at least 6 characters.', color: 'error' }
        return
      }
      if (resetForm.password !== resetForm.confirm) {
        userActionSnackbar.value = { show: true, text: 'Passwords do not match.', color: 'error' }
        return
      }

      loading.value = true
      try {
        await resetPasswordService(resetTarget.value.id, resetForm.password)
        userActionSnackbar.value = { show: true, text: 'Password reset successfully.', color: 'success' }
        showResetDialog.value = false
        resetTarget.value = null
      } catch (error) {
        userActionSnackbar.value = { show: true, text: 'Failed to reset password.', color: 'error' }
      } finally {
        loading.value = false
      }
    }

    return {
      users,
      headers,
      search,
      data,
      loadUsers,
      statusVariant,
      loading,
      roles,
      roleVariant,
      steps,
      currentStep,
      prefixes,
      statuses,
      facilities,
      doc_categories,
      refForm,
      userForm,
      dialogMode,
      userActionSnackbar,
      showDeleteDialog,
      selectedUser,
      selectedFacility,
      selectedCategory,
      deactivateUser,
      deleteUserPermanently,
      showUserDialog,
      openAddDialog,
      openEditDialog,
      submitUser,
      refInputEl,
      previewUrl,
      triggerFileInput,
      onFileChange,
      clearPicture,
      showResetDialog,
      resetTarget,
      resetForm,
      openResetDialog,
      submitResetPassword,
      showProfileDialog,
      profileUser,
      openProfileDialog,
    }
  },
}
</script>

<template>
  <VDialog
    v-model="showUserDialog"
    class="v-dialog-lg"
    persistent
  >
    <DialogCloseBtn @click="showUserDialog = false" />
    <VCard :title="dialogMode === 'add' ? 'Add New User' : 'Edit User'">
        <VCardText>
          <AppStepper
            v-model:current-step="currentStep"
            :items="steps"
          />
        </VCardText>
        <VDivider/>
        <VCardText>
          <VForm
            ref="refForm"
            @submit.prevent="() => {}"
          >
            <!-- Step 1: Personal Information -->
            <VWindow v-model="currentStep">
              <VWindowItem>
                <VRow>
                  <VCol cols="12" md="6">
                    <div class="d-flex align-center gap-4">
                      <!-- Avatar -->
                      <VAvatar
                        size="100"
                        variant="tonal"
                        color="primary"
                      >
                        <VImg
                          v-if="previewUrl"
                          :src="previewUrl"
                        />
                        <VIcon
                          v-else
                          icon="tabler-user"
                          size="40"
                        />
                      </VAvatar>

                      <!-- Form / Upload Section -->
                      <div class="d-flex flex-column">
                        <div class="d-flex gap-2 flex-wrap">
                          <VBtn
                            color="primary"
                            size="small"
                            @click="triggerFileInput"
                          >
                            <VIcon
                              icon="tabler-cloud-upload"
                              class="d-sm-none"
                            />
                            <span class="d-none d-sm-block">Upload new photo</span>
                          </VBtn>
                          <input
                            ref="refInputEl"
                            type="file"
                            name="file"
                            accept=".jpeg,.png,.jpg,.gif"
                            hidden
                            @change="onFileChange"
                          />
                          <VBtn
                            size="small"
                            color="secondary"
                            variant="tonal"
                            @click="clearPicture"
                          >
                            <span class="d-none d-sm-block">Reset</span>
                            <VIcon
                              icon="tabler-refresh"
                              class="d-sm-none"
                            />
                          </VBtn>
                        </div>
                        <p class="text-body-2 mb-0 gap-2">
                          Allowed JPG, PNG or GIF. Max size of 2MB
                        </p>
                      </div>
                    </div>
                  </VCol>

                  
                  <VCol cols="12" md="6">
                      <AppSelect
                        v-model="userForm.prefix"
                        label="Prefix (e.g., Mr., Ms., Mrs.)"
                        :items="prefixes"
                        item-title="title"
                        item-value="value"
                        placeholder="Select Prefix"
                        :rules="[requiredValidator]"
                      />

                     <!-- <AppTextField
                      v-model="userForm.title"
                      label="Title Name (e.g., MD, RN,.)" 
                      placeholder="Enter Title Name (if applicable)"
                      required
                    /> -->
                  </VCol>
                </VRow>
                <VRow>
                  <VCol cols="12" md="4">
                    <AppTextField
                      v-model="userForm.fname"
                      label="First Name"
                      placeholder="Enter First Name"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                  <VCol cols="12" md="4">
                    <AppTextField
                      v-model="userForm.mname"
                      label="Middle Name "
                      placeholder="Enter Middle Name"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                  <VCol cols="12" md="4">
                    <AppTextField
                      v-model="userForm.lname"
                      label="Last Name"
                      placeholder="Enter Last Name"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                </VRow>
                <VRow>
                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="userForm.contact"
                      label="Contact Number"
                      placeholder="Enter Contact Number"
                      type="number"
                       :rules="[requiredValidator, integerValidator]"
                    />
                  </VCol>
                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="userForm.license_no"
                      label="license Number (if applicable)"
                      placeholder="Enter License Number"
                      type="number"
                    />
                  </VCol>             
                </VRow>
                <VRow>
                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="userForm.accrediation_no"
                      label="Accreditation Number (if applicable)"
                      placeholder="Enter Accreditation Number"
                    />
                  </VCol>
                  <VCol cols="12" md="6">
                      <AppTextField
                        v-model="userForm.accrediation_validity"
                        label="Accreditation Validity (if applicable)"
                        placeholder="Enter Accreditation Validity"
                      />
                  </VCol>
                </VRow>
                
              </VWindowItem> 
              <VWindowItem>
                <!-- Step 2: Account Setup -->
                <VRow>
                  <VCol cols="12" md="12">
                    <AppTextField
                      v-model="userForm.email"
                      label="Email"
                      placeholder="Enter Email Address"
                      :rules="[requiredValidator, emailValidator]"
                    />
                  </VCol>
                </VRow>
                <VRow>
                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="userForm.username"
                      label="Username"
                      placeholder="Enter Username"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="userForm.password"
                      label="Password"
                      placeholder="Enter Password"
                      type="password"
                      :rules="dialogMode === 'add' ? [requiredValidator] : []"
                    />
                  </VCol>
                </VRow>
                <VRow>
                  <VCol cols="12" md="12">
                    <AppAutocomplete
                      v-model="userForm.facility_id"
                      label="Facility"
                      :items="facilities"
                      item-title="title"
                      item-value="value"
                      placeholder="Search or select facility"
                      clearable
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                </VRow>
                <VRow>
                  <VCol cols="12" md="6">
                    <AppAutocomplete
                      v-model="userForm.doc_cat_id"
                      label="Medical Specialty"
                      :items="doc_categories"
                      item-title="title"
                      item-value="value"
                      placeholder="Select Medical Specialty"
                      clearable
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                  <VCol cols="12" md="6">
                    <AppTextField
                      v-model="userForm.designation"
                      label="Designation"
                      required
                      placeholder="Enter Designation"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                </VRow>
                <VRow>
                  <VCol cols="12" md="6">
                    <AppSelect
                      v-model="userForm.status"
                      label="Status"
                      :items="statuses"
                      item-title="title"
                      item-value="value"
                      placeholder="Select Status"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                  <VCol cols="12" md="6">
                    <AppSelect
                      v-model="userForm.role"
                      label="Role"
                      :items="roles"
                      item-title="title"
                      item-value="value"
                      placeholder="Select Role"
                      :rules="[requiredValidator]"
                    />
                  </VCol>
                </VRow>
              </VWindowItem>

              <VWindowItem>
                <!-- Step 3: Review -->
                 <VRow>
                  <VCol>
                    <VList>
                      <VListItem title="Name" :subtitle="`${userForm.prefix} ${userForm.fname} ${userForm.mname} ${userForm.lname}`" />
                      <VListItem title="Username" :subtitle="userForm.username" />
                      <VListItem title="Email" :subtitle="userForm.email" />
                      <VListItem title="Password":subtitle="userForm.password ? '********' : '(unchanged)'"/>
                      <VListItem title="Facility" :subtitle="selectedFacility" />
                      <VListItem title="Accreditation Number":subtitle="userForm.accrediation_no || '—'"/>
                      <VListItem title="Accreditation Validity" :subtitle="userForm.accrediation_validity || '—'"/>
                    </VList>
                  </VCol>
                  <VCol>
                    <VList>
                      <VListItem title="Contact Number" :subtitle=" `${userForm.contact}`"/>
                      <VListItem title="Status" :subtitle="`${statuses.find(s => s.value === userForm.status)?.title || userForm.status}`"/>
                      <VListItem title="Role" :subtitle="`${roles.find(r => r.value === userForm.role)?.title || userForm.role}`"/>
                      <VListItem title="Designation" :subtitle=" `${userForm.designation}`"/>
                      <VListItem title="License Number" :subtitle="`${userForm.license_no}`" />
                      <VListItem title="Medical Specialty" :subtitle="selectedCategory" />
                    </VList>
                  </VCol>
                 </VRow>
              </VWindowItem>
            </VWindow>
          </VForm>
        </VCardText>
      <VCardActions class="justify-end gap-3">
        <VBtn
          color="secondary"
          :disabled="currentStep === 0"
          @click="currentStep--"
        >
          Previous
        </VBtn>
        <VBtn v-if="currentStep === steps.length - 1" variant="flat" color="primary"  @click="submitUser">
          Submit
        </VBtn>
        <VBtn
          v-else
          @click="async () => {
            const { valid } = await refForm.validate()
            if (valid) currentStep++
          }"
        >
          Next
          <VIcon
            icon="tabler-arrow-right"
            end
            class="flip-in-rtl"
          />
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VSnackbar
    v-model="userActionSnackbar.show"
    :color="userActionSnackbar.color"
    location="top right"
    timeout="3000"
  >
    {{ userActionSnackbar.text }}
  </VSnackbar>

  <VDialog
    v-model="showDeleteDialog"
    class="v-dialog-sm"
    
  >
    <DialogCloseBtn @click="showDeleteDialog = false" />
    <VCard>
      <VCardTitle>Confirm Delete</VCardTitle>
      <VCardText>
        <p>Are you sure you want to delete <b>{{ selectedUser?.full_name }}</b>?</p>
        <p class="text-body-2 text-medium-emphasis">Deleting will permanently remove this user.
        You can also choose to deactivate the account instead, which will keep the data but prevent login.</p>
      </VCardText>
      <VCardActions class="justify-end gap-3">
        <VBtn
          color="error"
          variant="text"
          @click="deleteUserPermanently"
        >
          Delete Permanently
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          @click="deactivateUser"
        >
          Deactivate User
        </VBtn>
      </VCardActions>
    </VCard>

  </VDialog>

  <VDialog
    v-model="showResetDialog"
    class="v-dialog-sm"
    persistent
  >
    <DialogCloseBtn @click="showResetDialog = false" />
    <VCard title="Reset Password">
      <VCardText>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Set a new password for <b>{{ resetTarget?.full_name }}</b>.
        </p>
        <VRow>
          <VCol cols="12">
            <AppTextField
              v-model="resetForm.password"
              label="New Password"
              type="password"
              placeholder="Enter new password"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="resetForm.confirm"
              label="Confirm Password"
              type="password"
              placeholder="Re-enter new password"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VCardActions class="justify-end gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="showResetDialog = false"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          :loading="loading"
          @click="submitResetPassword"
        >
          Reset Password
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog
    v-model="showProfileDialog"
    class="v-dialog-lg"
  >
    <DialogCloseBtn @click="showProfileDialog = false" />
    <VCard v-if="profileUser">
      <VCardText class="d-flex align-center gap-4 flex-wrap">
        <VAvatar
          size="88"
          :color="profileUser.picture_link ? '' : 'primary'"
          :variant="!profileUser.picture_link ? 'tonal' : undefined"
        >
          <VImg
            v-if="profileUser.picture_link"
            :src="profileUser.picture_link"
          />
          <span v-else class="text-h5">{{ avatarText(profileUser.fname) }}</span>
        </VAvatar>
        <div>
          <h5 class="text-h5 mb-1">
            {{ profileUser.prefix }} {{ profileUser.full_name }}
          </h5>
          <p class="text-body-2 text-medium-emphasis mb-2">
            {{ profileUser.designation || '—' }}
          </p>
          <div class="d-flex gap-2">
            <VChip
              :color="roleVariant(profileUser.role).color"
              variant="tonal"
              size="small"
            >
              {{ roleVariant(profileUser.role).text }}
            </VChip>
            <VChip
              :color="statusVariant(profileUser.status).color"
              variant="tonal"
              size="small"
            >
              {{ statusVariant(profileUser.status).text }}
            </VChip>
          </div>
        </div>
      </VCardText>

      <VDivider />

      <VCardText>
        <VRow>
          <VCol cols="12" md="6">
            <VList class="card-list">
              <VListItem title="Username" :subtitle="profileUser.username || '—'" />
              <VListItem title="Email" :subtitle="profileUser.email || '—'" />
              <VListItem title="Contact Number" :subtitle="profileUser.contact || '—'" />
              <VListItem title="Facility" :subtitle="profileUser.facility || '—'" />
              <VListItem title="Medical Specialty" :subtitle="profileUser.doc_category || '—'" />
            </VList>
          </VCol>
          <VCol cols="12" md="6">
            <VList class="card-list">
              <VListItem title="License Number" :subtitle="profileUser.license_no || '—'" />
              <VListItem title="Accreditation Number" :subtitle="profileUser.accrediation_no || '—'" />
              <VListItem title="Accreditation Validity" :subtitle="profileUser.accrediation_validity || '—'" />
            </VList>
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="justify-end gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="showProfileDialog = false"
        >
          Close
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          @click="() => { showProfileDialog = false; openEditDialog(profileUser) }"
        >
          Edit
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>


  <VDataTable
    :headers="headers"
    :items="data"
    :search="search"
    :loading="loading"
    loading-text="Loading users..."
    hover
    class="cursor-pointer"
    @click:row="(_event, row) => openProfileDialog(row.item)"
  >
    <template #top>
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            offset-md="8"
            md="4"
            class="d-flex align-center justify-end gap-2"
            >
              <VBtn
                prepend-icon="tabler-plus"
                @click="openAddDialog"
              >
                Add User
               </VBtn>
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
    <template #item.role="{ item }" >
      <VChip
        :color="roleVariant(item.role).color"
        variant="tonal"
        size="small"
      >
        {{ roleVariant(item.role).text }}
      </VChip>
  </template>
    <template #item.full_name="{ item }">
      <div class="d-flex align-center">
        <VAvatar
          size="32"
          :color="item.picture_link ? '' : 'primary'"
          :class="item.picture_link ? '' : 'v-avatar-light-bg primary--text'"
          :variant="!item.picture_link ? 'tonal' : undefined"
        >
          <VImg
            v-if="item.picture_link"
            :src="item.picture_link"
          />
          <span v-else>{{ avatarText(item.fname) }}</span>
        </VAvatar>
        <div class="d-flex flex-column ms-3">
          <span class="d-block text-high-emphasis">{{ item.full_name }}</span>
          <small>{{item.designation}}</small>
        </div>
      </div>
    </template>
  <template #item.status="{ item }">
      <VChip
        :color="statusVariant(item.status).color"
        :label="false"
        variant="tonal"
        size="small"
      >
        {{ statusVariant(item.status).text }}
      </VChip>
  </template>
  <template #item.action="{ item }">
    <VBtn
      icon="tabler-trash"
      variant="text"
      color="secondary"
     @click.stop="() => {
      selectedUser = item
      showDeleteDialog = true
    }"
    />
     <VMenu location="end">
      <template #activator="{ props }">
        <VBtn
          v-bind="props"
          icon="tabler-dots-vertical"
          variant="text"
          color="secondary"
          @click.stop
        />
      </template>
      <VList>
        <VListItem
          prepend-icon="tabler-edit-circle"
          title="Edit"
          value=""
          @click="openEditDialog(item)"
        >
        </VListItem>
       <VListItem
          prepend-icon="tabler-restore"
          title="Reset Password"
          value=""
          @click="openResetDialog(item)"
        >
        </VListItem>
      </VList>
    </VMenu>
  </template>
  </VDataTable>
</template>
