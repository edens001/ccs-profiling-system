<template>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-placeholder mb-3">
          <i class="bi bi-shield-lock" style="font-size: 3rem; color: #3498db;"></i>
        </div>
        <h2>CCS Profiling System</h2>
        <p class="text-muted">Admin Portal</p>
      </div>
      
      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-person me-1"></i> Username / Email
          </label>
          <input 
            type="text" 
            class="form-control" 
            v-model="form.username" 
            placeholder="Enter your username or email"
            :class="{ 'is-invalid': errors.username }"
            required
          >
          <div v-if="errors.username" class="invalid-feedback">
            {{ errors.username }}
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
        
        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
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
import Swal from 'sweetalert2'

export default {
  name: 'AdminLogin',
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const showPassword = ref(false)
    const rememberMe = ref(false)
    
    const form = reactive({
      username: '',
      password: ''
    })
    
    const errors = ref({})

    const validateForm = () => {
      errors.value = {}
      let isValid = true
      
      if (!form.username) {
        errors.value.username = 'Username is required'
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
        const response = await store.dispatch('auth/login', {
          username: form.username,
          password: form.password,
          role: 'admin'
        })
        
        if (response.success) {
          // Save to localStorage if remember me is checked
          if (rememberMe.value) {
            localStorage.setItem('rememberedUser', form.username)
          } else {
            localStorage.removeItem('rememberedUser')
          }
          
          await Swal.fire({
            icon: 'success',
            title: 'Login Successful!',
            html: `
              <div class="text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <p class="mt-2">Welcome back, ${response.user.name}!</p>
                <p class="small text-muted">Redirecting to dashboard...</p>
              </div>
            `,
            timer: 2000,
            showConfirmButton: false,
            backdrop: true
          })
          
          router.push('/admin/dashboard')
        }
      } catch (error) {
        console.error('Login error:', error)
        
        let errorMessage = 'Invalid username or password'
        
        if (error.response) {
          errorMessage = error.response.data?.message || errorMessage
        } else if (error.request) {
          errorMessage = 'Cannot connect to server. Please check your connection.'
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Login Failed',
          text: errorMessage,
          confirmButtonColor: '#3498db',
          confirmButtonText: 'Try Again'
        })
      } finally {
        loading.value = false
      }
    }
    
    // Check for remembered user
    const rememberedUser = localStorage.getItem('rememberedUser')
    if (rememberedUser) {
      form.username = rememberedUser
      rememberMe.value = true
    }

    return {
      form,
      loading,
      showPassword,
      rememberMe,
      errors,
      handleLogin
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  border-color: #3498db;
  box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
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

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 12px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.3s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.form-check-input:checked {
  background-color: #3498db;
  border-color: #3498db;
}

.demo-credentials {
  border: 1px dashed #3498db;
  background: #f0f7ff !important;
}

.demo-credentials p {
  color: #3498db !important;
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