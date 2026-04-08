<template>
  <div class="top-nav">
    <div class="d-flex align-items-center">
      <button class="btn btn-link d-md-none me-3" @click="toggleMobileSidebar">
        <i class="bi bi-list fs-4"></i>
      </button>
      <h4 class="page-title">{{ pageTitle }}</h4>
    </div>
    
    <div class="user-info">
      <div class="dropdown">
        <button class="btn btn-link text-dark dropdown-toggle" type="button" 
                data-bs-toggle="dropdown" aria-expanded="false">
          <div class="user-avatar me-2">
            {{ userInitials }}
          </div>
          <span class="d-none d-sm-inline">{{ userName }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="#" @click.prevent="goToProfile">
              <i class="bi bi-person me-2"></i> Profile
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="#" @click.prevent="goToSettings">
              <i class="bi bi-gear me-2"></i> Settings
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="#" @click.prevent="logout">
              <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import Swal from 'sweetalert2'

export default {
  name: 'TopNav',
  props: {
    pageTitle: {
      type: String,
      default: 'Dashboard'
    }
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()
    
    const userName = computed(() => store.getters['auth/userName'] || 'User')
    
    const userInitials = computed(() => {
      const name = userName.value
      if (name === 'User') return 'U'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    })
    
    const toggleMobileSidebar = () => {
      document.querySelector('.sidebar')?.classList.toggle('active')
    }
    
    const goToProfile = () => {
      if (route.path.includes('/admin')) {
        router.push('/admin/profile')
      } else if (route.path.includes('/faculty')) {
        router.push('/faculty/profile')
      } else if (route.path.includes('/student')) {
        router.push('/student/profile')
      }
    }
    
    const goToSettings = () => {
      if (route.path.includes('/admin')) {
        router.push('/admin/settings')
      } else if (route.path.includes('/faculty')) {
        router.push('/faculty/settings')
      } else if (route.path.includes('/student')) {
        router.push('/student/settings')
      }
    }
    
    const logout = async () => {
      const result = await Swal.fire({
        title: 'Logout',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel'
      })
      
      if (result.isConfirmed) {
        await store.dispatch('auth/logout')
        
        if (route.path.includes('/admin')) {
          router.push('/admin/login')
        } else if (route.path.includes('/faculty')) {
          router.push('/faculty/login')
        } else if (route.path.includes('/student')) {
          router.push('/student/login')
        } else {
          router.push('/')
        }
        
        Swal.fire({
          icon: 'success',
          title: 'Logged out successfully',
          showConfirmButton: false,
          timer: 1500
        })
      }
    }
    
    return {
      userName,
      userInitials,
      toggleMobileSidebar,
      goToProfile,
      goToSettings,
      logout
    }
  }
}
</script>

<style scoped>
.top-nav {
  background: white;
  padding: 12px 20px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.user-info {
  display: flex;
  align-items: center;
}

.user-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.9rem;
}

.dropdown-toggle {
  display: flex;
  align-items: center;
  padding: 0;
  border: none;
  background: none;
  color: #2c3e50;
}

.dropdown-toggle:hover {
  color: #3498db;
}

.dropdown-toggle::after {
  display: none;
}

.dropdown-menu {
  border: none;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  border-radius: 8px;
  padding: 8px;
  min-width: 180px;
}

.dropdown-item {
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 0.95rem;
  transition: all 0.3s;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

.dropdown-item i {
  font-size: 1rem;
}

.btn-link {
  text-decoration: none;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .top-nav {
    padding: 10px 15px;
  }
  
  .page-title {
    font-size: 1.1rem;
  }
  
  .user-avatar {
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
  }
  
  .dropdown-menu {
    min-width: 160px;
  }
}
</style>