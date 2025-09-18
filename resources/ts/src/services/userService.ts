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
