import axios from 'axios'
import { globals } from '../globals'
const API_URL = `${globals.api}/api/auth/users`

export const fetchUsers = async () => {
  try {
    const response = await axios.get(API_URL, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('authToken')}`,
      },
    })
    return response.data
  }
  catch (error) {
    console.error('Error fetching users:', error)
    throw error
  }
}

export const createUser = async (payload: any) => {
  const response = await axios.post(API_URL, payload, {
    headers: {
      Authorization: `Bearer ${localStorage.getItem('authToken')}`,
    },
  })
  return response.data
}

export const deactivateUserService = async (id: number) => {
  try {
    const response = await axios.post(
      `${API_URL}/${id}/deactivate`, // ensure no extra slash
      {}, // empty payload
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          'Content-Type': 'application/json',
        },
      }
    )
    return response.data
  } catch (error: any) {
    console.error('Failed to deactivate user:', error.response?.data || error)
    throw error
  }
}

export const deleteUserService = async (id: number) => {
  try {
    const response = await axios.delete(`${API_URL}/${id}`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('authToken')}`,
        'Content-Type': 'application/json',
      },
    })
    return response.data
  } catch (error: any) {
    console.error('Failed to delete user:', error.response?.data || error)
    throw error
  }
}

export const updateUserService = async (id: number, data: any) => {
  try {
    const response = await axios.post(
      `${API_URL}/${id}`, // POST to the user ID
      data,              // payload (FormData or plain object)
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('authToken')}`,
        },
      }
    );
    return response.data;
  } catch (error: any) {
    console.error('Failed to update user:', error.response?.data || error);
    throw error;
  }
}

export const resetPasswordService = async (id: number, password: string) => {
  try {
    const response = await axios.post(
      `${API_URL}/${id}/reset-password`,
      { password },
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          'Content-Type': 'application/json',
        },
      }
    )
    return response.data
  } catch (error: any) {
    console.error('Failed to reset password:', error.response?.data || error)
    throw error
  }
}


