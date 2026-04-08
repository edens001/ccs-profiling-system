<template>
  <div class="sidebar" :class="{ 'sidebar-collapsed': isCollapsed, 'active': isMobileActive }">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
      <div class="d-flex align-items-center justify-content-between p-3">
        <div class="d-flex align-items-center" v-if="!isCollapsed">
          <div class="logo-placeholder me-2">
            <i class="bi bi-person" style="font-size: 1.8rem; color: white;"></i>
          </div>
          <div class="sidebar-brand">
            <h5 class="mb-0 text-white">Student Portal</h5>
            <small class="text-white-50">{{ studentName }}</small>
          </div>
        </div>
        <div v-else class="w-100 text-center">
          <div class="logo-placeholder mx-auto">
            <i class="bi bi-person" style="font-size: 1.8rem; color: white;"></i>
          </div>
        </div>
        <button class="btn btn-link text-white p-0" @click="toggleSidebar">
          <i :class="isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left'"></i>
        </button>
      </div>
    </div>

    <!-- Student Info Card (when expanded) -->
    <div class="student-info-card mx-3 mt-2 p-2" v-if="!isCollapsed && studentDetails">
      <div class="d-flex align-items-center">
        <div class="student-avatar me-2">
          <i class="bi bi-person-circle"></i>
        </div>
        <div class="student-info">
          <small class="text-white-50 d-block">Student #</small>
          <strong class="text-white">{{ studentDetails.student_number || 'N/A' }}</strong>
        </div>
      </div>
      <div class="mt-2">
        <div class="d-flex justify-content-between">
          <span class="text-white-50 small">Course:</span>
          <span class="text-white small">{{ studentDetails.course || 'N/A' }}</span>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-white-50 small">Year/Section:</span>
          <span class="text-white small">{{ studentDetails.year_level || 'N/A' }}{{ studentDetails.section || '' }}</span>
        </div>
      </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="nav flex-column mt-3">
      <router-link 
        to="/student/dashboard" 
        class="nav-link" 
        :class="{ 
          active: $route.path === '/student/dashboard',
          'justify-content-center': isCollapsed 
        }"
        @click="closeSidebarOnMobile"
      >
        <i class="bi bi-speedometer2"></i>
        <span v-if="!isCollapsed" class="ms-3">Dashboard</span>
        <span v-else class="tooltip-text">Dashboard</span>
      </router-link>
      
      <router-link 
        to="/student/schedule" 
        class="nav-link" 
        :class="{ 
          active: $route.path === '/student/schedule',
          'justify-content-center': isCollapsed 
        }"
        @click="closeSidebarOnMobile"
      >
        <i class="bi bi-calendar-week"></i>
        <span v-if="!isCollapsed" class="ms-3">Class Schedule</span>
        <span v-else class="tooltip-text">Schedule</span>
      </router-link>
      
      <router-link 
        to="/student/grades" 
        class="nav-link" 
        :class="{ 
          active: $route.path === '/student/grades',
          'justify-content-center': isCollapsed 
        }"
        @click="closeSidebarOnMobile"
      >
        <i class="bi bi-clipboard-data"></i>
        <span v-if="!isCollapsed" class="ms-3">Grades</span>
        <span v-else class="tooltip-text">Grades</span>
      </router-link>
      
      <router-link 
        to="/student/profile" 
        class="nav-link" 
        :class="{ 
          active: $route.path === '/student/profile',
          'justify-content-center': isCollapsed 
        }"
        @click="closeSidebarOnMobile"
      >
        <i class="bi bi-person"></i>
        <span v-if="!isCollapsed" class="ms-3">Profile</span>
        <span v-else class="tooltip-text">Profile</span>
      </router-link>
      
      <!-- Logout -->
      <a 
        href="#" 
        class="nav-link" 
        :class="{ 'justify-content-center': isCollapsed }"
        @click.prevent="logout"
      >
        <i class="bi bi-box-arrow-right"></i>
        <span v-if="!isCollapsed" class="ms-3">Logout</span>
        <span v-else class="tooltip-text">Logout</span>
      </a>
    </nav>

    <!-- Sidebar Footer (when expanded) -->
    <div class="sidebar-footer mt-auto p-3" v-if="!isCollapsed">
      <div class="text-white-50 small">
        <i class="bi bi-laptop me-1"></i>
        CCS Profiling System
        <br>
        <span class="smaller">v1.0.0</span>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import Swal from 'sweetalert2'
import axios from 'axios'

export default {
  name: 'StudentSidebar',
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()
    const isCollapsed = ref(false)
    const isMobile = ref(false)
    const isMobileActive = ref(false)
    const notificationCount = ref(5) // Example notification count
    const studentDetails = ref(null)
    
    const studentName = computed(() => store.getters['auth/userName'] || 'Student')
    const token = computed(() => store.state.auth.token)
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Check if mobile view
    const checkMobile = () => {
      isMobile.value = window.innerWidth <= 768
      if (isMobile.value) {
        isCollapsed.value = true
        isMobileActive.value = false
      }
    }

    // Fetch student details
    const fetchStudentDetails = async () => {
      if (!token.value) return
      
      try {
        const response = await axios.get(`${API_URL}/student/profile.php`, {
          headers: {
            'Authorization': `Bearer ${token.value}`
          }
        })
        
        if (response.data.success) {
          studentDetails.value = response.data.profile
        }
      } catch (error) {
        console.error('Failed to fetch student details:', error)
      }
    }

    // Toggle sidebar collapse
    const toggleSidebar = () => {
      if (isMobile.value) {
        isMobileActive.value = !isMobileActive.value
      } else {
        isCollapsed.value = !isCollapsed.value
        localStorage.setItem('sidebarCollapsed', isCollapsed.value)
      }
    }

    // Close sidebar on mobile when route changes
    const closeSidebarOnMobile = () => {
      if (isMobile.value) {
        isMobileActive.value = false
      }
    }

    // Show notifications modal (alternative to navigating)
    const showNotificationsModal = () => {
      Swal.fire({
        title: 'Notifications',
        html: `
          <div class="text-start" style="max-height: 300px; overflow-y: auto;">
            <div class="border-bottom pb-2 mb-2">
              <div class="d-flex justify-content-between">
                <strong>New Grade Posted</strong>
                <small class="text-primary">5m ago</small>
              </div>
              <p class="small text-muted mb-0">Your CS 101 grade has been posted</p>
            </div>
            <div class="border-bottom pb-2 mb-2">
              <div class="d-flex justify-content-between">
                <strong>Schedule Change</strong>
                <small class="text-primary">1h ago</small>
              </div>
              <p class="small text-muted mb-0">CS 102 moved to Lab 203</p>
            </div>
            <div class="border-bottom pb-2 mb-2">
              <div class="d-flex justify-content-between">
                <strong>Event Reminder</strong>
                <small class="text-primary">2h ago</small>
              </div>
              <p class="small text-muted mb-0">CCS Week starts tomorrow</p>
            </div>
            <div class="border-bottom pb-2 mb-2">
              <div class="d-flex justify-content-between">
                <strong>Payment Reminder</strong>
                <small class="text-primary">1d ago</small>
              </div>
              <p class="small text-muted mb-0">Tuition payment deadline is approaching</p>
            </div>
            <div class="pb-2">
              <div class="d-flex justify-content-between">
                <strong>Registration Open</strong>
                <small class="text-primary">2d ago</small>
              </div>
              <p class="small text-muted mb-0">Enrollment for next semester is now open</p>
            </div>
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'View All',
        showCancelButton: true,
        cancelButtonText: 'Close',
        width: '400px'
      }).then((result) => {
        if (result.isConfirmed) {
          router.push('/student/notifications')
        }
      })
    }

    // Logout function
    const logout = async () => {
      const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'You will be logged out of the system',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel',
        background: '#fff',
        backdrop: true
      })
      
      if (result.isConfirmed) {
        // Show loading
        Swal.fire({
          title: 'Logging out...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading()
          }
        })
        
        await store.dispatch('auth/logout')
        
        Swal.fire({
          icon: 'success',
          title: 'Logged out successfully',
          text: 'See you again!',
          showConfirmButton: false,
          timer: 1500
        })
        
        router.push('/student/login')
      }
    }

    // Load collapsed state from localStorage
    const loadCollapsedState = () => {
      const saved = localStorage.getItem('sidebarCollapsed')
      if (saved !== null && !isMobile.value) {
        isCollapsed.value = saved === 'true'
      }
    }

    onMounted(() => {
      checkMobile()
      loadCollapsedState()
      fetchStudentDetails()
      window.addEventListener('resize', checkMobile)
    })

    onUnmounted(() => {
      window.removeEventListener('resize', checkMobile)
    })

    return {
      isCollapsed,
      isMobile,
      isMobileActive,
      studentName,
      studentDetails,
      notificationCount,
      toggleSidebar,
      closeSidebarOnMobile,
      showNotificationsModal,
      logout
    }
  }
}
</script>

<style scoped>
.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
  color: white;
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  transition: all 0.3s ease;
  z-index: 1000;
  box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar.sidebar-collapsed {
  width: 80px;
}

.sidebar-header {
  border-bottom: 1px solid rgba(255,255,255,0.1);
  position: sticky;
  top: 0;
  background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
  z-index: 1;
}

.logo-placeholder {
  width: 45px;
  height: 45px;
  background: linear-gradient(135deg, #3498db, #2980b9);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
}

.logo-placeholder:hover {
  transform: rotate(5deg) scale(1.05);
}

.sidebar-brand h5 {
  font-weight: 600;
  font-size: 1rem;
  line-height: 1.2;
}

.sidebar-brand small {
  font-size: 0.75rem;
  opacity: 0.8;
}

.student-info-card {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.student-avatar {
  width: 35px;
  height: 35px;
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.nav {
  padding: 0 10px;
}

.nav-link {
  color: rgba(255,255,255,0.7);
  padding: 12px 15px;
  margin: 4px 0;
  display: flex;
  align-items: center;
  border-radius: 10px;
  transition: all 0.3s;
  position: relative;
  white-space: nowrap;
  text-decoration: none;
  cursor: pointer;
}

.nav-link i {
  font-size: 1.3rem;
  min-width: 24px;
  transition: all 0.3s;
}

.nav-link:hover {
  background: rgba(255,255,255,0.15);
  color: white;
  transform: translateX(5px);
}

.nav-link:hover i {
  transform: scale(1.1);
}

.nav-link.active {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
}

.nav-link.active i {
  color: white;
}

.nav-link.justify-content-center {
  padding: 12px 0;
  justify-content: center !important;
}

/* Notification wrapper for badge positioning */
.notification-wrapper {
  position: relative;
  display: inline-block;
}

/* Notification badge */
.notification-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #e74c3c;
  color: white;
  font-size: 0.7rem;
  font-weight: bold;
  padding: 3px 6px;
  border-radius: 10px;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

/* Tooltip for collapsed mode */
.tooltip-text {
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  background: #2c3e50;
  color: white;
  padding: 5px 10px;
  border-radius: 5px;
  font-size: 0.85rem;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s;
  margin-left: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  z-index: 1001;
}

.nav-link.justify-content-center:hover .tooltip-text {
  opacity: 1;
  visibility: visible;
}

.sidebar-footer {
  border-top: 1px solid rgba(255,255,255,0.1);
  font-size: 0.8rem;
  position: absolute;
  bottom: 0;
  width: 100%;
}

.sidebar-footer .smaller {
  font-size: 0.7rem;
  opacity: 0.6;
}

/* Custom scrollbar */
.sidebar::-webkit-scrollbar {
  width: 5px;
}

.sidebar::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.05);
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.2);
  border-radius: 5px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255,255,255,0.3);
}

/* Mobile styles */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.active {
    transform: translateX(0);
  }
  
  .sidebar.sidebar-collapsed {
    transform: translateX(-100%);
  }
  
  .sidebar.sidebar-collapsed.active {
    transform: translateX(0);
    width: 80px;
  }
  
  .sidebar-footer {
    position: relative;
    bottom: auto;
  }
}

/* Animations */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.nav-link {
  animation: slideIn 0.3s ease;
}

/* Responsive text */
@media (max-width: 480px) {
  .sidebar-brand h5 {
    font-size: 0.9rem;
  }
  
  .student-info-card {
    font-size: 0.85rem;
  }
}
</style>