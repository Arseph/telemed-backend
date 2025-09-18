import { globals } from '@/src/globals'
import type { AxiosInstance } from 'axios'
import axios from 'axios'
const axiosIns: AxiosInstance = axios.create({
  baseURL: `${globals.api}`,
  headers: {
    'Content-Type': 'multipart/form-data',
    'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
  },
  withCredentials: true,
})

const axiosJson: AxiosInstance = axios.create({
  baseURL: `${globals.api}`,
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
  },
  withCredentials: true,
})

const axiosPdf: AxiosInstance = axios.create({
  baseURL: `${globals.api}`,
  headers: {
    'Content-Type': 'application/pdf',
    'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
  },
  withCredentials: true,
})

export { axiosIns, axiosJson, axiosPdf }

