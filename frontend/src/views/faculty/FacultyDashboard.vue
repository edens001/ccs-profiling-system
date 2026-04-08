<template>
  <div class="app-wrapper">
    <FacultySidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Faculty Dashboard'" />
      
      <div class="container-fluid p-4">
        <!-- Welcome Banner -->
        <div class="welcome-banner mb-4">
          <div class="d-flex align-items-center">
            <div class="welcome-avatar me-3">
              <div class="avatar-circle bg-primary text-white">
                {{ facultyName.charAt(0) }}
              </div>
            </div>
            <div>
              <h4 class="mb-1">Welcome back, {{ facultyName }}!</h4>
              <p class="mb-0 text-white-50">
                <i class="bi bi-briefcase me-2"></i>{{ facultyDesignation || 'Faculty' }} • 
                <i class="bi bi-building me-2"></i>{{ facultyDepartment || 'Department' }}
              </p>
            </div>
          </div>
          <div class="welcome-date mt-2 mt-sm-0">
            <div class="date-badge">
              <i class="bi bi-calendar3 me-2"></i>
              {{ currentDate }}
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3 text-muted">Loading dashboard data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-container text-center py-5">
          <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
          <h4 class="text-danger mt-3">Oops! Something went wrong</h4>
          <p class="text-muted">{{ error }}</p>
          <button class="btn btn-primary mt-2" @click="fetchDashboard">
            <i class="bi bi-arrow-clockwise me-2"></i>Retry
          </button>
        </div>

        <!-- Dashboard Content -->
        <template v-else>
          <!-- Statistics Cards -->
          <div class="row g-4 mb-4">
            <div class="col-sm-6 col-lg-3" v-for="stat in statistics" :key="stat.label">
              <div class="stat-card h-100">
                <div class="d-flex align-items-center">
                  <div class="stat-icon-wrapper" :style="{ background: stat.color + '15' }">
                    <i :class="stat.icon" :style="{ color: stat.color }"></i>
                  </div>
                  <div class="ms-3 flex-grow-1">
                    <h3 class="stat-value mb-1">{{ stat.value }}</h3>
                    <p class="stat-label mb-0">{{ stat.label }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <!-- Today's Schedule -->
            <div class="col-lg-6 mb-4">
              <div class="dashboard-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                      <i class="bi bi-calendar-day text-primary"></i>
                    </div>
                    <h5 class="mb-0">Today's Classes</h5>
                    <span class="badge bg-primary ms-2">{{ todayClasses.length }}</span>
                  </div>
                  <router-link to="/faculty/schedule" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-right me-1"></i>View All
                  </router-link>
                </div>
                
                <div class="card-body p-0">
                  <div v-if="todayClasses.length === 0" class="empty-state p-5 text-center">
                    <i class="bi bi-calendar-x" style="font-size: 3rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-3 mb-0">No classes scheduled for today</p>
                    <p class="text-muted small">Enjoy your day off!</p>
                  </div>
                  
                  <div v-else class="class-list">
                    <div v-for="classItem in todayClasses" :key="classItem.id" 
                         class="class-item p-3 border-bottom">
                      <div class="d-flex align-items-center">
                        <div class="class-time me-3 text-center">
                          <div class="time-badge bg-light rounded p-2" style="min-width: 80px;">
                            <div class="small text-muted">Start</div>
                            <strong>{{ formatTime(classItem.time_start) }}</strong>
                            <div class="small text-muted mt-1">End</div>
                            <strong>{{ formatTime(classItem.time_end) }}</strong>
                          </div>
                        </div>
                        <div class="class-info flex-grow-1">
                          <h6 class="mb-2">{{ classItem.course_code }} - {{ classItem.course_name }}</h6>
                          <div class="d-flex flex-wrap gap-3 small">
                            <span class="text-muted">
                              <i class="bi bi-people me-1"></i>{{ classItem.section_code }}
                            </span>
                            <span class="text-muted">
                              <i class="bi bi-door-open me-1"></i>{{ classItem.room_code }}
                            </span>
                            <span class="text-muted">
                              <i class="bi bi-person-check me-1"></i>{{ classItem.student_count || 0 }} students
                            </span>
                          </div>
                        </div>
                        <span class="class-status-badge ms-3" 
                              :class="getClassStatus(classItem.time_start, classItem.time_end)">
                          {{ getClassStatusText(classItem.time_start, classItem.time_end) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="col-lg-6 mb-4">
              <div class="dashboard-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                      <i class="bi bi-calendar-event text-success"></i>
                    </div>
                    <h5 class="mb-0">Upcoming Events</h5>
                    <span class="badge bg-success ms-2">{{ upcomingEvents.length }}</span>
                  </div>
                </div>
                
                <div class="card-body p-0">
                  <div v-if="upcomingEvents.length === 0" class="empty-state p-5 text-center">
                    <i class="bi bi-calendar2-x" style="font-size: 3rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-3 mb-0">No upcoming events</p>
                  </div>
                  
                  <div v-else class="events-list">
                    <div v-for="event in upcomingEvents" :key="event.id" 
                         class="event-item p-3 border-bottom">
                      <div class="d-flex">
                        <div class="event-date me-3 text-center">
                          <div class="date-badge bg-light rounded p-2" style="min-width: 70px;">
                            <div class="small text-muted">{{ formatEventMonth(event.event_date) }}</div>
                            <strong class="fs-5">{{ formatEventDay(event.event_date) }}</strong>
                          </div>
                        </div>
                        <div class="event-info flex-grow-1">
                          <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1">{{ event.title }}</h6>
                            <span class="event-type-badge" :class="getEventTypeClass(event.event_type)">
                              {{ event.event_type }}
                            </span>
                          </div>
                          <p class="text-muted small mb-2">{{ event.description }}</p>
                          <div class="d-flex flex-wrap gap-3 small">
                            <span class="text-muted" v-if="event.event_time">
                              <i class="bi bi-clock me-1"></i>{{ formatTime(event.event_time) }}
                            </span>
                            <span class="text-muted" v-if="event.venue">
                              <i class="bi bi-geo-alt me-1"></i>{{ event.venue }}
                            </span>
                            <span class="text-muted" v-if="event.organizer">
                              <i class="bi bi-person me-1"></i>{{ event.organizer }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import FacultySidebar from '@/components/layout/FacultySidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultyDashboard',
  components: {
    FacultySidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    
    const facultyInfo = ref({})
    const statistics = ref({
      total_classes: 0,
      total_students: 0,
      total_subjects: 0,
      pending_grades: 0
    })
    const todayClasses = ref([])
    const upcomingEvents = ref([])

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const currentDate = computed(() => {
      return new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    })

    const facultyName = computed(() => {
      return facultyInfo.value.name || 'Faculty'
    })

    const facultyDepartment = computed(() => {
      return facultyInfo.value.department || ''
    })

    const facultyDesignation = computed(() => {
      return facultyInfo.value.designation || ''
    })

    const statsData = computed(() => [
      {
        label: 'Total Classes',
        value: statistics.value.total_classes,
        icon: 'bi bi-calendar-check',
        color: '#3498db'
      },
      {
        label: 'Total Students',
        value: statistics.value.total_students,
        icon: 'bi bi-people',
        color: '#27ae60'
      },
      {
        label: 'Subjects',
        value: statistics.value.total_subjects,
        icon: 'bi bi-book',
        color: '#f39c12'
      },
      {
        label: 'Pending Grades',
        value: statistics.value.pending_grades,
        icon: 'bi bi-pencil-square',
        color: '#e74c3c'
      }
    ])

    const fetchDashboard = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          router.push('/faculty/login')
          return
        }

        const response = await axios.get(`${API_URL}/faculty/dashboard.php`, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          facultyInfo.value = response.data.faculty || {}
          statistics.value = response.data.statistics || {
            total_classes: 0,
            total_students: 0,
            total_subjects: 0,
            pending_grades: 0
          }
          todayClasses.value = response.data.today_classes || []
          upcomingEvents.value = response.data.upcoming_events || []
        } else {
          throw new Error(response.data.message || 'Failed to load dashboard')
        }
      } catch (err) {
        console.error('Dashboard error:', err)
        
        if (err.response?.status === 401) {
          error.value = 'Session expired. Please login again.'
          await store.dispatch('auth/logout')
          setTimeout(() => router.push('/faculty/login'), 2000)
        } else if (err.response?.status === 403) {
          error.value = 'Access denied. Faculty privileges required.'
        } else if (err.response?.status === 404) {
          error.value = 'Dashboard endpoint not found. Please check API URL.'
        } else if (err.code === 'ECONNABORTED') {
          error.value = 'Request timeout. Server is taking too long to respond.'
        } else if (err.request) {
          error.value = 'Cannot connect to server. Please check if XAMPP/WAMP is running.'
        } else {
          error.value = err.response?.data?.message || err.message || 'Failed to load dashboard'
        }
      } finally {
        loading.value = false
      }
    }

    const formatTime = (time) => {
      if (!time) return '--:--'
      try {
        return new Date('1970-01-01T' + time).toLocaleTimeString('en-US', {
          hour: 'numeric',
          minute: '2-digit',
          hour12: true
        })
      } catch {
        return time
      }
    }

    const formatEventMonth = (dateString) => {
      if (!dateString) return ''
      try {
        const date = new Date(dateString)
        return date.toLocaleDateString('en-US', { month: 'short' })
      } catch {
        return ''
      }
    }

    const formatEventDay = (dateString) => {
      if (!dateString) return ''
      try {
        const date = new Date(dateString)
        return date.getDate()
      } catch {
        return ''
      }
    }

    const getEventTypeClass = (type) => {
      const types = {
        'Curricular': 'bg-primary',
        'Extracurricular': 'bg-success'
      }
      return types[type] || 'bg-info'
    }

    const getClassStatus = (start, end) => {
      if (!start || !end) return 'bg-secondary'
      
      try {
        const now = new Date()
        const currentTime = now.getHours() * 60 + now.getMinutes()
        
        const startTime = new Date('1970-01-01T' + start)
        const endTime = new Date('1970-01-01T' + end)
        const startMinutes = startTime.getHours() * 60 + startTime.getMinutes()
        const endMinutes = endTime.getHours() * 60 + endTime.getMinutes()
        
        if (currentTime < startMinutes) return 'bg-warning'
        if (currentTime > endMinutes) return 'bg-secondary'
        return 'bg-success'
      } catch {
        return 'bg-secondary'
      }
    }

    const getClassStatusText = (start, end) => {
      if (!start || !end) return 'Unknown'
      
      try {
        const now = new Date()
        const currentTime = now.getHours() * 60 + now.getMinutes()
        
        const startTime = new Date('1970-01-01T' + start)
        const endTime = new Date('1970-01-01T' + end)
        const startMinutes = startTime.getHours() * 60 + startTime.getMinutes()
        const endMinutes = endTime.getHours() * 60 + endTime.getMinutes()
        
        if (currentTime < startMinutes) return 'Upcoming'
        if (currentTime > endMinutes) return 'Completed'
        return 'Ongoing'
      } catch {
        return 'Unknown'
      }
    }

    onMounted(() => {
      fetchDashboard()
    })

    return {
      loading,
      error,
      facultyInfo,
      facultyName,
      facultyDepartment,
      facultyDesignation,
      statistics,
      statsData,
      todayClasses,
      upcomingEvents,
      currentDate,
      fetchDashboard,
      formatTime,
      formatEventMonth,
      formatEventDay,
      getEventTypeClass,
      getClassStatus,
      getClassStatusText
    }
  }
}
</script>

<style scoped>
/* ===== LAYOUT ===== */
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
  width: 100%;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100% - 280px);
  min-height: 100vh;
  padding: 25px;
  transition: margin-left 0.3s ease;
  background: #f8fafc;
}

:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

.container-fluid {
  padding: 0;
  max-width: 1600px;
  margin: 0 auto;
}

/* Welcome Banner */
.welcome-banner {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  padding: 25px;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  animation: slideDown 0.5s ease;
}

.welcome-avatar .avatar-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.2);
  border: 3px solid rgba(255, 255, 255, 0.5);
}

.date-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 8px 16px;
  border-radius: 50px;
  backdrop-filter: blur(10px);
  font-size: 0.9rem;
}

/* Statistics Cards */
.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
  transition: all 0.3s;
  border: 1px solid #f0f0f0;
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.stat-icon-wrapper {
  width: 55px;
  height: 55px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1.2;
  margin: 0;
}

.stat-label {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
  margin: 0;
}

/* Dashboard Cards */
.dashboard-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  border: 1px solid #f0f0f0;
  height: 100%;
}

.card-header {
  background: white;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.header-icon {
  width: 40px;
  height: 40px;
  background: #f8f9fa;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

/* Class List */
.class-item {
  transition: background-color 0.3s;
}

.class-item:hover {
  background-color: #f8f9fa;
}

.time-badge {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
}

.class-status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: white;
}

.class-status-badge.bg-success {
  background: #27ae60 !important;
}

.class-status-badge.bg-warning {
  background: #f39c12 !important;
}

.class-status-badge.bg-secondary {
  background: #95a5a6 !important;
}

/* Event List */
.event-item {
  transition: background-color 0.3s;
}

.event-item:hover {
  background-color: #f8f9fa;
}

.event-type-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 50px;
  font-size: 0.7rem;
  font-weight: 600;
  color: white;
}

.event-type-badge.bg-primary {
  background: #3498db !important;
}

.event-type-badge.bg-success {
  background: #27ae60 !important;
}

/* Empty State */
.empty-state {
  text-align: center;
}

.empty-state i {
  font-size: 3rem;
  color: #dee2e6;
}

/* Error Container */
.error-container {
  background: white;
  border-radius: 16px;
  padding: 3rem;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

/* Animations */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .welcome-banner {
    flex-direction: column;
    align-items: flex-start;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .class-item {
    flex-direction: column;
  }

  .class-time {
    margin-bottom: 10px;
  }

  .class-status-badge {
    margin-top: 10px;
    align-self: flex-start;
  }
}
</style>