import axios from 'axios'
import { globals } from '../globals'
const API_URL = `${globals.api}/api/manage`

export const fetchRegistry = async () => {
  try {
    const response = await axios.get(`${API_URL}/dtr`, {
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
export const fetchUserNot = async () => {
  try {
    const response = await axios.get(`${API_URL}/employee`, {
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
export const fetchEmployee = async (id: string | number) => {
  try {
    const response = await axios.get(`${API_URL}/dtr/user/${id}`, {
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

export const fetchDtr = async (id: string | number, month: string | number, year: string | number) => {
  try {
    const response = await axios.get(`${API_URL}/dtr/view/${id}/${month}/${year}`, {
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
