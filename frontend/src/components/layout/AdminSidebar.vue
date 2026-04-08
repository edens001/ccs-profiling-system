<template>
  <div class="sidebar" :class="{ 'sidebar-collapsed': isCollapsed, 'active': isMobileActive }">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
      <div class="d-flex align-items-center justify-content-between p-3">
        <div class="d-flex align-items-center" v-if="!isCollapsed">
          <div class="logo-placeholder me-2">
            <i class="bi bi-shield-lock" style="font-size: 1.8rem; color: white;"></i>
          </div>
          <div class="sidebar-brand">
            <h5 class="mb-0 text-white">Admin Portal</h5>
            <small class="text-white-50">{{ adminName }}</small>
          </div>
        </div>
        <div v-else class="w-100 text-center">
          <div class="logo-placeholder mx-auto">
            <i class="bi bi-shield-lock" style="font-size: 1.8rem; color: white;"></i>
          </div>
        </div>
        <button class="btn btn-link text-white p-0" @click="toggleSidebar">
          <i :class="isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left'"></i>
        </button>
      </div>
    </div>

    <!-- Admin Info Card (when expanded) -->
    <div class="admin-info-card mx-3 mt-2 p-2" v-if="!isCollapsed && adminDetails">
      <div class="d-flex align-items-center">
        <div class="admin-avatar me-2">
          <i class="bi bi-person-circle"></i>
        </div>
        <div class="admin-info">
          <small class="text-white-50 d-block">{{ adminDetails.admin_level || 'Administrator' }}</small>
          <strong class="text-white">{{ adminDetails.name || adminName }}</strong>
        </div>
      </div>
      <div class="mt-2">
        <div class="d-flex justify-content-between">
          <span class="text-white-50 small">Department:</span>
          <span class="text-white small">{{ adminDetails.department || 'Computer Studies' }}</span>
        </div>
      </div>
    </div>

    <!-- Navigation Menu - Scrollable Area -->
    <div class="nav-container">
      <nav class="nav flex-column">
        <!-- Dashboard -->
        <router-link 
          to="/admin/dashboard" 
          class="nav-link" 
          :class="{ 
            active: $route.path === '/admin/dashboard',
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-speedometer2"></i>
          <span v-if="!isCollapsed" class="ms-3">Dashboard</span>
          <span v-else class="tooltip-text">Dashboard</span>
        </router-link>
        
        <!-- Students -->
        <router-link 
          to="/admin/students" 
          class="nav-link" 
          :class="{ 
            active: $route.path.startsWith('/admin/students'),
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-people"></i>
          <span v-if="!isCollapsed" class="ms-3">Students</span>
          <span v-else class="tooltip-text">Students</span>
        </router-link>
        
        <!-- Faculty -->
        <router-link 
          to="/admin/faculty" 
          class="nav-link" 
          :class="{ 
            active: $route.path.startsWith('/admin/faculty'),
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-person-badge"></i>
          <span v-if="!isCollapsed" class="ms-3">Faculty</span>
          <span v-else class="tooltip-text">Faculty</span>
        </router-link>
        
        <!-- Courses -->
        <router-link 
          to="/admin/courses" 
          class="nav-link" 
          :class="{ 
            active: $route.path.startsWith('/admin/courses'),
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-book"></i>
          <span v-if="!isCollapsed" class="ms-3">Courses</span>
          <span v-else class="tooltip-text">Courses</span>
        </router-link>
        
        <!-- Scheduling -->
        <router-link 
          to="/admin/scheduling" 
          class="nav-link" 
          :class="{ 
            active: $route.path.startsWith('/admin/scheduling'),
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-calendar-week"></i>
          <span v-if="!isCollapsed" class="ms-3">Scheduling</span>
          <span v-else class="tooltip-text">Scheduling</span>
        </router-link>
        
        <!-- Instruction -->
        <router-link 
          to="/admin/instruction" 
          class="nav-link" 
          :class="{ 
            active: $route.path.startsWith('/admin/instruction'),
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-journal-bookmark-fill"></i>
          <span v-if="!isCollapsed" class="ms-3">Instruction</span>
          <span v-else class="tooltip-text">Instruction</span>
        </router-link>
        
        <!-- Events -->
        <router-link 
          to="/admin/events" 
          class="nav-link" 
          :class="{ 
            active: $route.path.startsWith('/admin/events'),
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-calendar-event"></i>
          <span v-if="!isCollapsed" class="ms-3">Events</span>
          <span v-else class="tooltip-text">Events</span>
        </router-link>
        
        <!-- Search -->
        <router-link 
          to="/admin/search" 
          class="nav-link" 
          :class="{ 
            active: $route.path === '/admin/search',
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-search"></i>
          <span v-if="!isCollapsed" class="ms-3">Search</span>
          <span v-else class="tooltip-text">Search</span>
        </router-link>
      </nav>
    </div>

    <!-- Separator Line -->
    <hr class="my-2 mx-3 bg-light">

    <!-- Bottom Section - Always Clickable -->
    <div class="bottom-section">
      <nav class="nav flex-column">
        <!-- Settings -->
        <router-link 
          to="/admin/settings" 
          class="nav-link" 
          :class="{ 
            active: $route.path === '/admin/settings',
            'justify-content-center': isCollapsed 
          }"
          @click="closeSidebarOnMobile"
        >
          <i class="bi bi-gear"></i>
          <span v-if="!isCollapsed" class="ms-3">Settings</span>
          <span v-else class="tooltip-text">Settings</span>
        </router-link>
        
        <!-- Logout -->
        <a 
          href="#" 
          class="nav-link logout-btn" 
          :class="{ 'justify-content-center': isCollapsed }"
          @click.prevent="logout"
        >
          <i class="bi bi-box-arrow-right"></i>
          <span v-if="!isCollapsed" class="ms-3">Logout</span>
          <span v-else class="tooltip-text">Logout</span>
        </a>
      </nav>

      <!-- Sidebar Footer (when expanded) -->
      <div class="sidebar-footer p-3" v-if="!isCollapsed">
        <div class="text-white-50 small">
          <i class="bi bi-shield-lock me-1"></i>
          CCS Profiling System
          <br>
          <span class="smaller">v1.0.0 • Admin</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import Swal from 'sweetalert2'

export default {
  name: 'AdminSidebar',
  setup() {
    const store = useStore()
    const router = useRouter()
    const route = useRoute()
    const isCollapsed = ref(false)
    const isMobile = ref(false)
    const isMobileActive = ref(false)
    const adminDetails = ref(null)
    
    const adminName = computed(() => store.getters['auth/userName'] || 'Administrator')
    const userRole = computed(() => store.getters['auth/userRole'] || 'admin')
    
    // Check if mobile view
    const checkMobile = () => {
      isMobile.value = window.innerWidth <= 768
      if (isMobile.value) {
        isCollapsed.value = true
        isMobileActive.value = false
      }
    }

    // Fetch admin details
    const fetchAdminDetails = async () => {
      // You can fetch from API if needed
      adminDetails.value = {
        name: adminName.value,
        admin_level: userRole.value === 'admin' ? 'Administrator' : 
                    userRole.value === 'dean' ? 'Dean' :
                    userRole.value === 'dept_chair' ? 'Department Chair' : 'Secretary',
        department: 'Computer Studies'
      }
    }

    // Toggle sidebar collapse
    const toggleSidebar = () => {
      if (isMobile.value) {
        isMobileActive.value = !isMobileActive.value
      } else {
        isCollapsed.value = !isCollapsed.value
        localStorage.setItem('adminSidebarCollapsed', isCollapsed.value)
      }
    }

    // Close sidebar on mobile when route changes
    const closeSidebarOnMobile = () => {
      if (isMobile.value) {
        isMobileActive.value = false
      }
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
        
        router.push('/admin/login')
      }
    }

    // Load collapsed state from localStorage
    const loadCollapsedState = () => {
      const saved = localStorage.getItem('adminSidebarCollapsed')
      if (saved !== null && !isMobile.value) {
        isCollapsed.value = saved === 'true'
      }
    }

    onMounted(() => {
      checkMobile()
      loadCollapsedState()
      fetchAdminDetails()
      window.addEventListener('resize', checkMobile)
    })

    onUnmounted(() => {
      window.removeEventListener('resize', checkMobile)
    })

    return {
      isCollapsed,
      isMobile,
      isMobileActive,
      adminName,
      userRole,
      adminDetails,
      toggleSidebar,
      closeSidebarOnMobile,
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
  display: flex;
  flex-direction: column;
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
  flex-shrink: 0;
}

.logo-placeholder {
  width: 45px;
  height: 45px;
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
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

.admin-info-card {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.admin-avatar {
  width: 35px;
  height: 35px;
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

/* Scrollable navigation container */
.nav-container {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0 10px;
  margin-bottom: 10px;
}

/* Custom scrollbar for nav container */
.nav-container::-webkit-scrollbar {
  width: 5px;
}

.nav-container::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.05);
}

.nav-container::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.2);
  border-radius: 5px;
}

.nav-container::-webkit-scrollbar-thumb:hover {
  background: rgba(255,255,255,0.3);
}

/* Bottom section - fixed at bottom */
.bottom-section {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
  flex-shrink: 0;
  padding: 10px;
}

.nav {
  padding: 0;
  margin: 0;
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
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  color: white;
  box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
}

.nav-link.active i {
  color: white;
}

.nav-link.justify-content-center {
  padding: 12px 0;
  justify-content: center !important;
}

/* Logout button special styling */
.logout-btn {
  color: rgba(255, 99, 99, 0.9);
}

.logout-btn:hover {
  background: rgba(231, 76, 60, 0.2);
  color: #ff6b6b;
}

.logout-btn:hover i {
  color: #ff6b6b;
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
  margin-top: 10px;
  padding-top: 10px;
}

.sidebar-footer .smaller {
  font-size: 0.7rem;
  opacity: 0.6;
}

hr {
  margin: 10px 15px !important;
  opacity: 0.2;
  flex-shrink: 0;
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
  
  .admin-info-card {
    font-size: 0.85rem;
  }
}
</style>