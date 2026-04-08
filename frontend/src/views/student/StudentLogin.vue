<template>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-placeholder mb-3">
          <i class="bi bi-person" style="font-size: 3rem; color: white;"></i>
        </div>
        <h2>CCS Profiling System</h2>
        <p class="text-muted">Student Portal</p>
      </div>
      
      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-person-vcard me-1"></i> Student Number / Email
          </label>
          <input 
            type="text" 
            class="form-control" 
            v-model="form.student_number" 
            placeholder="Enter your student number or email"
            :class="{ 'is-invalid': errors.student_number }"
            required
          >
          <div v-if="errors.student_number" class="invalid-feedback">
            {{ errors.student_number }}
          </div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-lock me-1"></i> Password
          </label>
          <div class="input-group">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              class="form-control" 
              v-model="form.password" 
              placeholder="Enter your password"
              :class="{ 'is-invalid': errors.password }"
              required
            >
            <button 
              class="btn btn-outline-secondary" 
              type="button" 
              @click="showPassword = !showPassword"
            >
              <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
            </button>
          </div>
          <div v-if="errors.password" class="invalid-feedback">
            {{ errors.password }}
          </div>
        </div>
        
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="rememberMe" v-model="rememberMe">
          <label class="form-check-label" for="rememberMe">Remember me</label>
        </div>
        
        <div class="mb-3 d-flex justify-content-between align-items-center">
          <router-link to="/student/register" class="text-info text-decoration-none small">
            <i class="bi bi-person-plus"></i> Create new account
          </router-link>
          <a href="#" class="text-info text-decoration-none small" @click.prevent="forgotPassword">
            <i class="bi bi-question-circle"></i> Forgot password?
          </a>
        </div>
        
        <button type="submit" class="btn btn-info w-100" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="bi bi-box-arrow-in-right me-2"></i>
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>
      
      <div class="text-center mt-3">
        <router-link to="/" class="text-muted text-decoration-none">
          <i class="bi bi-arrow-left"></i> Back to Home
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

export default {
  name: 'StudentLogin',
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const showPassword = ref(false)
    const rememberMe = ref(false)
    
    const form = reactive({
      student_number: '',
      password: ''
    })
    
    const errors = ref({})
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const validateForm = () => {
      errors.value = {}
      let isValid = true
      
      if (!form.student_number) {
        errors.value.student_number = 'Student number or email is required'
        isValid = false
      }
      
      if (!form.password) {
        errors.value.password = 'Password is required'
        isValid = false
      } else if (form.password.length < 6) {
        errors.value.password = 'Password must be at least 6 characters'
        isValid = false
      }
      
      return isValid
    }

    const handleLogin = async () => {
      if (!validateForm()) return
      
      loading.value = true
      
      try {
        console.log('Sending login request to:', `${API_URL}/student/auth/login.php`)
        
        const response = await axios.post(`${API_URL}/student/auth/login.php`, {
          username: form.student_number,
          password: form.password
        }, {
          headers: {
            'Content-Type': 'application/json'
          },
          timeout: 10000
        })
        
        console.log('Login response:', response.data)
        
        if (response.data.success) {
          // Store token and user data
          localStorage.setItem('token', response.data.token)
          localStorage.setItem('user', JSON.stringify(response.data.user))
          
          // Update Vuex store
          await store.dispatch('auth/login', {
          username: form.student_number,
          password: form.password,
          role: 'student'
          })
          
          if (rememberMe.value) {
            localStorage.setItem('rememberedStudent', form.student_number)
          } else {
            localStorage.removeItem('rememberedStudent')
          }
          
          await Swal.fire({
            icon: 'success',
            title: 'Welcome Student!',
            html: `
              <div class="text-center">
                <i class="bi bi-person-check-fill text-info" style="font-size: 3rem;"></i>
                <p class="mt-2">Login successful!</p>
                <p class="small text-muted">Welcome back, ${response.data.user?.name || 'Student'}!</p>
              </div>
            `,
            timer: 1500,
            showConfirmButton: false
          })
          
          router.push('/student/dashboard')
        } else {
          throw new Error(response.data.message || 'Login failed')
        }
        
      } catch (error) {
        console.error('Login error:', error)
        
        let errorMessage = 'Invalid student number or password'
        
        if (error.code === 'ECONNABORTED') {
          errorMessage = 'Request timeout. Server is taking too long to respond.'
        } else if (error.response) {
          console.error('Error response:', error.response.data)
          errorMessage = error.response.data?.message || errorMessage
        } else if (error.request) {
          errorMessage = 'Cannot connect to server. Please check your connection.'
        } else {
          errorMessage = error.message
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Login Failed',
          text: errorMessage,
          confirmButtonColor: '#17a2b8',
          confirmButtonText: 'Try Again'
        })
      } finally {
        loading.value = false
      }
    }
    
    const forgotPassword = () => {
      Swal.fire({
        title: 'Reset Password',
        html: `
          <p>Please contact the CCS Office:</p>
          <p class="mb-0"><strong>Email:</strong> ccs.office@ccs.edu</p>
          <p><strong>Phone:</strong> (02) 9876-5432</p>
          <hr>
          <p class="small text-muted">Or visit the CCS Dean's Office</p>
        `,
        icon: 'info',
        confirmButtonColor: '#17a2b8',
        confirmButtonText: 'OK'
      })
    }
    
    // Check for remembered student
    const rememberedStudent = localStorage.getItem('rememberedStudent')
    if (rememberedStudent) {
      form.student_number = rememberedStudent
      rememberMe.value = true
    }

    return {
      form,
      loading,
      showPassword,
      rememberMe,
      errors,
      handleLogin,
      forgotPassword
    }
  }
}
</script>

<style scoped>
.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0DCAF0;;
  padding: 20px;
}

.login-card {
  background: white;
  border-radius: 15px;
  padding: 40px;
  width: 100%;
  max-width: 450px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.2);
  animation: slideUp 0.5s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.logo-placeholder {
  width: 90px;
  height: 90px;
  background: #0DCAF0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 15px;
  border: 3px solid #fff;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.logo-placeholder i {
  color: white !important;
  font-size: 3rem !important;
}

.login-header h2 {
  color: #333;
  font-weight: 600;
  margin-bottom: 5px;
}

.login-header p {
  color: #666;
  font-size: 0.9rem;
}

.form-label {
  font-weight: 500;
  color: #555;
  font-size: 0.9rem;
}

.form-control {
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  padding: 12px 15px;
  font-size: 0.95rem;
  transition: all 0.3s;
}

.form-control:focus {
  border-color: #17a2b8;
  box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
}

.input-group {
  border-radius: 8px;
}

.input-group .btn {
  border: 1px solid #e0e0e0;
  background: white;
}

.input-group .btn:hover {
  background: #f8f9fa;
}

.btn-info {
  background: #0DCAF0;
  border: none;
  padding: 12px;
  font-weight: 500;
  border-radius: 8px;
  color: white;
  transition: all 0.3s;
}

.btn-info:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-info:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.form-check-input:checked {
  background-color: #17a2b8;
  border-color: #17a2b8;
}

.demo-credentials {
  border: 1px dashed #17a2b8;
  background: #f0f9ff !important;
}

.demo-credentials p {
  color: #17a2b8 !important;
  font-weight: 500;
}

.demo-credentials span {
  color: #555;
}

/* Responsive */
@media (max-width: 576px) {
  .login-card {
    padding: 30px 20px;
  }
  
  .logo-placeholder {
    width: 70px;
    height: 70px;
  }
  
  .logo-placeholder i {
    font-size: 2.5rem !important;
  }
  
  .login-header h2 {
    font-size: 1.5rem;
  }
}
</style>