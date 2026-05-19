import axios from 'axios'

// Configure the shared axios singleton (all `import axios from 'axios'` use this)
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.withCredentials = true

const csrf = document.head.querySelector('meta[name="csrf-token"]')
if (csrf) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.content
}

window.axios = axios