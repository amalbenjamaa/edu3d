import axios from 'axios'

const csrf = document.head.querySelector('meta[name="csrf-token"]')

const api = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(csrf ? { 'X-CSRF-TOKEN': csrf.content } : {}),
  },
})

export default api
