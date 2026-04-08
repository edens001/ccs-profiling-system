<template>
  <div class="home">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
      <div class="container">
        <router-link to="/" class="navbar-brand">
          <i class="bi bi-laptop me-2"></i>
          CCS Profiling System
        </router-link>
        <button class="navbar-toggler" type="button" @click="toggleMenu" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" :class="{ 'show': isMenuOpen }">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <router-link to="/" class="nav-link" :class="{ active: $route.path === '/' }" @click="closeMenu">
                <i class="bi bi-house-door me-1"></i> Home
              </router-link>
            </li>
            <li class="nav-item dropdown" :class="{ 'show': isDropdownOpen }">
              <a class="nav-link dropdown-toggle" href="#" @click.prevent="toggleDropdown">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
              </a>
              <ul class="dropdown-menu dropdown-menu-end" :class="{ 'show': isDropdownOpen }">
                <li>
                  <router-link to="/admin/login" class="dropdown-item" @click="closeDropdown">
                    <i class="bi bi-shield-lock me-2 text-primary"></i> Admin Portal
                  </router-link>
                </li>
                <li>
                  <router-link to="/faculty/login" class="dropdown-item" @click="closeDropdown">
                    <i class="bi bi-person-badge me-2 text-success"></i> Faculty Portal
                  </router-link>
                </li>
                <li>
                  <router-link to="/student/login" class="dropdown-item" @click="closeDropdown">
                    <i class="bi bi-person me-2 text-info"></i> Student Portal
                  </router-link>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
      <div class="container">
        <div class="row align-items-center min-vh-100">
          <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">CCS Profiling System</h1>
            <p class="lead mb-4">A comprehensive management system for the College of Computer Studies</p>
            <div class="d-flex gap-3 flex-wrap">
              <button @click="navigateTo('/admin/login')" class="btn btn-primary btn-lg">
                <i class="bi bi-shield-lock me-2"></i>Admin Portal
              </button>
              <button @click="navigateTo('/faculty/login')" class="btn btn-success btn-lg">
                <i class="bi bi-person-badge me-2"></i>Faculty Portal
              </button>
              <button @click="navigateTo('/student/login')" class="btn btn-info btn-lg">
                <i class="bi bi-person me-2"></i>Student Portal
              </button>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="building-placeholder">
              <i class="bi bi-building" style="font-size: 25rem; color: rgba(255,255,255,0.2);"></i>
              <h3 class="text-white ">College of Computer Studies</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Features Section -->
    <div class="features-section py-5">
      <div class="container">
        <h2 class="text-center mb-5">System Features</h2>
        <div class="row">
          <div class="col-md-4 mb-4" v-for="(feature, index) in features" :key="feature.title">
            <div class="card h-100 border-0 shadow-sm feature-card">
              <div class="card-body text-center">
                <div class="feature-icon mb-3">
                  <i :class="feature.icon" style="font-size: 3rem; color: #3498db"></i>
                </div>
                <h5 class="card-title">{{ feature.title }}</h5>
                <p class="card-text text-muted">{{ feature.description }}</p>
              </div>
              <div class="card-footer bg-transparent border-0 pb-3">
                <button @click="navigateTo(feature.link)" class="btn btn-outline-primary btn-sm">
                  Learn More <i class="bi bi-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section py-5 bg-primary text-white">
      <div class="container">
        <div class="row text-center">
          <div class="col-md-3 mb-3" v-for="stat in statistics" :key="stat.label">
            <div class="stat-item">
              <h2 class="display-4 fw-bold">{{ stat.value }}</h2>
              <p class="lead">{{ stat.label }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <h5><i class="bi bi-laptop me-2"></i>CCS Profiling System</h5>
            <p class="text-muted">College of Computer Studies</p>
          </div>
          <div class="col-md-6 text-md-end">
            <p class="mb-0">&copy; 2026 CCS Profiling System. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'Home',
  setup() {
    const router = useRouter()
    const isMenuOpen = ref(false)
    const isDropdownOpen = ref(false)
    
    const features = ref([
      {
        icon: 'bi bi-people',
        title: 'Student Management',
        description: 'Manage student records, enrollments, and academic progress',
        link: '/student/login'
      },
      {
        icon: 'bi bi-person-badge',
        title: 'Faculty Management',
        description: 'Handle faculty information, schedules, and assignments',
        link: '/faculty/login'
      },
      {
        icon: 'bi bi-book',
        title: 'Course Management',
        description: 'Create and manage courses, syllabi, and lessons',
        link: '/admin/login'
      },
      {
        icon: 'bi bi-calendar-week',
        title: 'Scheduling',
        description: 'Create class schedules, manage rooms and sections',
        link: '/admin/login'
      },
      {
        icon: 'bi bi-calendar-event',
        title: 'Events Management',
        description: 'Organize curricular and extracurricular activities',
        link: '/admin/login'
      },
      {
        icon: 'bi bi-graph-up',
        title: 'Reports & Analytics',
        description: 'Generate reports and track academic performance',
        link: '/admin/login'
      }
    ])
    
    const statistics = ref([
      { label: 'Students', value: '2,500+' },
      { label: 'Faculty', value: '150+' },
      { label: 'Courses', value: '50+' },
      { label: 'Events', value: '100+' }
    ])
    
    const navigateTo = (path) => {
      console.log('Navigating to:', path)
      closeMenu()
      closeDropdown()
      
      // Try router navigation first
      router.push(path).then(() => {
        console.log('Navigation successful to:', path)
      }).catch((err) => {
        console.error('Router navigation failed:', err)
        // Fallback to window.location
        window.location.href = path
      })
    }
    
    const toggleMenu = () => {
      isMenuOpen.value = !isMenuOpen.value
    }
    
    const closeMenu = () => {
      isMenuOpen.value = false
    }
    
    const toggleDropdown = () => {
      isDropdownOpen.value = !isDropdownOpen.value
    }
    
    const closeDropdown = () => {
      isDropdownOpen.value = false
    }
    
    onMounted(() => {
      console.log('Home component mounted')
      console.log('Available routes:', router.getRoutes().map(r => r.path))
    })
    
    return {
      features,
      statistics,
      isMenuOpen,
      isDropdownOpen,
      navigateTo,
      toggleMenu,
      closeMenu,
      toggleDropdown,
      closeDropdown
    }
  }
}
</script>

<style scoped>
.hero-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  min-height: 100vh;
  padding-top: 76px; /* Account for fixed navbar */
}

.features-section {
  background-color: #f8f9fa;
  padding: 80px 0;
}

.feature-icon {
  width: 80px;
  height: 80px;
  line-height: 80px;
  text-align: center;
  border-radius: 50%;
  background: rgba(52, 152, 219, 0.1);
  margin: 0 auto;
  transition: transform 0.3s;
}

.feature-card {
  transition: all 0.3s;
  overflow: hidden;
}

.feature-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}

.feature-card:hover .feature-icon {
  transform: scale(1.1);
  background: rgba(52, 152, 219, 0.2);
}

.building-placeholder {
  text-align: center;
  padding: 2rem;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
  100% { transform: translateY(0px); }
}

.stats-section {
  background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
}

.navbar {
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  z-index: 1030;
}

.navbar-brand {
  font-weight: bold;
  font-size: 1.3rem;
}

.dropdown-menu {
  border: none;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  display: block;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s;
}

.dropdown-menu.show {
  opacity: 1;
  visibility: visible;
}

.dropdown-item {
  padding: 10px 20px;
  transition: all 0.3s;
  cursor: pointer;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  transform: translateX(5px);
}

.btn-lg {
  padding: 12px 30px;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-lg:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
  .hero-section {
    text-align: center;
    padding-top: 100px;
  }
  
  .display-4 {
    font-size: 2.5rem;
  }
  
  .building-placeholder i {
    font-size: 10rem !important;
  }
}
</style>