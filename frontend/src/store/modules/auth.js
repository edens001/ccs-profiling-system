import axios from 'axios'
import router from '@/router'

const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

export default {
  namespaced: true,
  
  state: {
    token: localStorage.getItem('token') || null,
    user: JSON.parse(localStorage.getItem('user')) || null,
    isAuthenticated: !!localStorage.getItem('token')
  },
  
  mutations: {
    SET_TOKEN(state, token) {
      state.token = token
      state.isAuthenticated = !!token
      if (token) {
        localStorage.setItem('token', token)
      } else {
        localStorage.removeItem('token')
      }
    },
    
    SET_USER(state, user) {
      state.user = user
      if (user) {
        localStorage.setItem('user', JSON.stringify(user))
      } else {
        localStorage.removeItem('user')
      }
    },
    
    CLEAR_AUTH(state) {
      state.token = null
      state.user = null
      state.isAuthenticated = false
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  },
  
  actions: {
    async login({ commit }, credentials) {
      try {
        let endpoint = '';
        
        // Determine which login endpoint to use
        switch(credentials.role) {
          case 'admin':
          case 'dean':
          case 'dept_chair':
          case 'secretary':
            endpoint = `${API_URL}/admin/auth/login.php`;
            break;
          case 'faculty':
            endpoint = `${API_URL}/faculty/auth/login.php`;
            break;
          case 'student':
            endpoint = `${API_URL}/student/auth/login.php`;
            break;
          default:
            endpoint = `${API_URL}/auth/login.php`;
        }
        
        console.log('Logging in to:', endpoint)
        
        const response = await axios.post(endpoint, {
          username: credentials.username,
          password: credentials.password,
          role: credentials.role
        }, {
          headers: {
            'Content-Type': 'application/json'
          }
        })
        
        console.log('Login response:', response.data)
        
        if (response.data.success) {
          commit('SET_TOKEN', response.data.token)
          commit('SET_USER', response.data.user)
          
          // Set axios default header
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
          
          return response.data
        }
      } catch (error) {
        console.error('Login error:', error)
        if (error.response) {
          console.error('Error response:', error.response.data)
        }
        throw error
      }
    },
    
    logout({ commit }) {
      console.log('Logging out...')
      commit('CLEAR_AUTH')
      delete axios.defaults.headers.common['Authorization']
    },
    
    checkAuth({ state, commit }) {
      // Validate that user data exists
      if (state.token && state.user && state.user.role) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${state.token}`
        return true
      } else if (state.token && (!state.user || !state.user.role)) {
        // Corrupted auth state - clear it
        console.log('Corrupted auth state detected, clearing...')
        commit('CLEAR_AUTH')
        return false
      }
      return false
    }
  },
  
  getters: {
    isAuthenticated: state => {
      // Only consider authenticated if both token and user role exist
      return !!(state.token && state.user && state.user.role)
    },
    user: state => state.user,
    userRole: state => state.user?.role || null,
    userName: state => state.user?.name || null,
    userId: state => state.user?.id || null,
    studentNumber: state => state.user?.student_number || null
  }
}