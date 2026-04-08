<template>
  <div class="app-wrapper">
    <StudentSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Student Dashboard'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading dashboard data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4 animate__animated animate__shakeX">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchDashboardData">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Dashboard Content -->
        <template v-else>
          <!-- Welcome Banner with Profile Quick View -->
          <div class="welcome-banner animate__animated animate__fadeIn">
            <div class="row align-items-center">
              <div class="col-md-8">
                <div class="d-flex align-items-center">
                  <div class="welcome-avatar me-3">
                    <img v-if="studentProfile?.profile_picture" 
                         :src="getProfileImageUrl(studentProfile.profile_picture)" 
                         class="rounded-circle" 
                         width="60" 
                         height="60"
                         style="object-fit: cover;">
                    <div v-else class="avatar-placeholder">
                      {{ getUserInitials(studentProfile?.first_name, studentProfile?.last_name) }}
                    </div>
                  </div>
                  <div>
                    <h4 class="mb-1">Welcome back, {{ studentName }}! 👋</h4>
                    <p class="mb-0 text-white-50">
                      <i class="bi bi-person-badge me-2"></i>{{ studentProfile?.student_number }} |
                      <i class="bi bi-book ms-2 me-1"></i>{{ studentProfile?.course }} |
                      <i class="bi bi-calendar me-1"></i>{{ getYearLevel(studentProfile?.year_level) }} - Section {{ studentProfile?.section }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="welcome-badge">
                  <i class="bi bi-calendar-check me-2"></i>
                  {{ currentDate }}
                </span>
              </div>
            </div>
          </div>
          
          <!-- Statistics Cards -->
          <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6" v-for="(stat, index) in statistics" :key="stat.label">
              <div class="stat-card animate__animated animate__fadeInUp" :style="{ animationDelay: index * 0.1 + 's' }">
                <div class="stat-icon" :style="{ background: stat.color + '15', color: stat.color }">
                  <i :class="stat.icon"></i>
                </div>
                <div class="stat-details">
                  <div class="stat-value">{{ stat.value }}</div>
                  <div class="stat-label">{{ stat.label }}</div>
                  <div v-if="stat.subtext" class="stat-subtext">{{ stat.subtext }}</div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="row g-4">
            <!-- Today's Schedule -->
            <div class="col-lg-6">
              <div class="card h-100 animate__animated animate__fadeInLeft">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                  <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-day me-2 text-primary"></i>
                    Today's Classes
                  </h5>
                  <router-link to="/student/schedule" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar-week me-1"></i> Full Schedule
                  </router-link>
                </div>
                <div class="card-body">
                  <div v-if="todayClasses.length === 0" class="text-center py-5">
                    <div class="empty-state">
                      <i class="bi bi-calendar-x fs-1 text-muted"></i>
                      <p class="text-muted mt-3 mb-0">No classes scheduled for today</p>
                      <p class="text-muted small">Enjoy your day off! 🎉</p>
                    </div>
                  </div>
                  
                  <div v-else class="schedule-list">
                    <div v-for="(class_item, index) in todayClasses" :key="class_item.id" 
                         class="schedule-item" :class="getClassStatus(class_item).class">
                      <div class="schedule-time">
                        <div class="time-badge">
                          <span class="time-start">{{ formatTime(class_item.time_start) }}</span>
                          <span class="time-separator">—</span>
                          <span class="time-end">{{ formatTime(class_item.time_end) }}</span>
                        </div>
                        <span class="status-badge" :class="'bg-' + getClassStatus(class_item).color">
                          {{ getClassStatus(class_item).status }}
                        </span>
                      </div>
                      <div class="schedule-details">
                        <h6 class="mb-1">{{ class_item.course_code }} - {{ class_item.course_name }}</h6>
                        <div class="schedule-meta">
                          <span class="me-3">
                            <i class="bi bi-door-open me-1"></i>{{ class_item.room_code }}
                          </span>
                          <span>
                            <i class="bi bi-person me-1"></i>{{ class_item.instructor_name }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Actions & Recent Grades -->
            <div class="col-lg-6">
              <div class="card h-100 animate__animated animate__fadeInRight">
                <div class="card-header bg-white">
                  <h5 class="card-title mb-0">
                    <i class="bi bi-lightning-charge me-2 text-warning"></i>
                    Quick Actions
                  </h5>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-6">
                      <router-link to="/student/grades" class="quick-action-card">
                        <div class="quick-action-icon" style="background: #3498db15; color: #3498db;">
                          <i class="bi bi-graph-up"></i>
                        </div>
                        <span>View Grades</span>
                      </router-link>
                    </div>
                    <div class="col-6">
                      <router-link to="/student/schedule" class="quick-action-card">
                        <div class="quick-action-icon" style="background: #27ae6015; color: #27ae60;">
                          <i class="bi bi-calendar-week"></i>
                        </div>
                        <span>Class Schedule</span>
                      </router-link>
                    </div>
                    <div class="col-6">
                      <router-link to="/student/profile" class="quick-action-card">
                        <div class="quick-action-icon" style="background: #f39c1215; color: #f39c12;">
                          <i class="bi bi-person"></i>
                        </div>
                        <span>My Profile</span>
                      </router-link>
                    </div>
                    <div class="col-6">
                      <a href="#" @click.prevent="showHelp" class="quick-action-card">
                        <div class="quick-action-icon" style="background: #e74c3c15; color: #e74c3c;">
                          <i class="bi bi-question-circle"></i>
                        </div>
                        <span>Help & Support</span>
                      </a>
                    </div>
                  </div>

                  <!-- Recent Grades Preview -->
                  <div class="recent-grades mt-4" v-if="recentGrades.length > 0">
                    <h6 class="mb-3">Recent Grades</h6>
                    <div v-for="grade in recentGrades.slice(0, 3)" :key="grade.id" class="grade-item">
                      <div class="grade-info">
                        <div>
                          <strong>{{ grade.course_code }}</strong>
                          <small class="text-muted d-block">{{ grade.course_name }}</small>
                        </div>
                        <div class="text-end">
                          <span class="grade-badge" :class="'grade-' + getGradeColor(grade.grade)">
                            {{ grade.grade }}
                          </span>
                          <small class="d-block" :class="'text-' + getRemarksColor(grade.remarks)">
                            {{ grade.remarks }}
                          </small>
                        </div>
                      </div>
                    </div>
                    <div class="text-center mt-3">
                      <router-link to="/student/grades" class="btn btn-sm btn-link">
                        View All Grades <i class="bi bi-arrow-right"></i>
                      </router-link>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Upcoming Events -->
            <div class="col-12">
              <div class="card animate__animated animate__fadeInUp">
                <div class="card-header bg-white">
                  <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-event me-2 text-info"></i>
                    Upcoming Events
                  </h5>
                </div>
                <div class="card-body">
                  <div v-if="upcomingEvents.length === 0" class="text-center py-4">
                    <i class="bi bi-calendar2-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No upcoming events</p>
                  </div>
                  
                  <div v-else class="row g-4">
                    <div v-for="event in upcomingEvents" :key="event.id" class="col-md-6 col-lg-3">
                      <div class="event-card">
                        <div class="event-date">
                          <span class="event-day">{{ formatDay(event.event_date) }}</span>
                          <span class="event-month">{{ formatMonth(event.event_date) }}</span>
                        </div>
                        <div class="event-details">
                          <h6 class="event-title">{{ event.title }}</h6>
                          <p class="event-description">{{ event.description }}</p>
                          <div class="event-meta">
                            <span v-if="event.event_time">
                              <i class="bi bi-clock me-1"></i>{{ formatTime(event.event_time) }}
                            </span>
                            <span v-if="event.venue">
                              <i class="bi bi-geo-alt me-1"></i>{{ event.venue }}
                            </span>
                          </div>
                          <span class="event-type" :class="'type-' + event.event_type.toLowerCase()">
                            {{ event.event_type }}
                          </span>
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
import StudentSidebar from '@/components/layout/StudentSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'StudentDashboard',
  components: {
    StudentSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    
    const dashboardData = ref({
      student: {},
      statistics: {},
      today_classes: [],
      recent_grades: [],
      upcoming_events: []
    })

    const studentProfile = computed(() => dashboardData.value.student || {})
    
    const studentName = computed(() => {
      return studentProfile.value.name || store.getters['auth/userName'] || 'Student'
    })

    const currentDate = computed(() => {
      return new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    })

    const statistics = computed(() => [
      { 
        label: 'Current GPA', 
        value: dashboardData.value.statistics?.current_gpa || '0.00', 
        subtext: 'Overall Average',
        icon: 'bi bi-graph-up', 
        color: '#3498db'
      },
      { 
        label: 'Units Enrolled', 
        value: dashboardData.value.statistics?.total_units?.toString() || '0', 
        subtext: 'This Semester',
        icon: 'bi bi-book', 
        color: '#27ae60'
      },
      { 
        label: 'Classes Today', 
        value: dashboardData.value.today_classes?.length.toString() || '0', 
        subtext: dashboardData.value.today_classes?.length > 0 ? 'Scheduled' : 'No classes',
        icon: 'bi bi-calendar-check', 
        color: '#f39c12'
      },
      { 
        label: 'Attendance Rate', 
        value: (dashboardData.value.statistics?.attendance_rate || '0') + '%', 
        subtext: 'This Semester',
        icon: 'bi bi-person-check', 
        color: '#e74c3c'
      }
    ])

    const todayClasses = computed(() => dashboardData.value.today_classes || [])
    const recentGrades = computed(() => dashboardData.value.recent_grades || [])
    const upcomingEvents = computed(() => dashboardData.value.upcoming_events || [])

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const getProfileImageUrl = (filename) => {
      return `http://localhost/ccs-profiling-system/backend/uploads/profile_pictures/${filename}`
    }

    const getUserInitials = (first, last) => {
      return (first?.charAt(0) || '') + (last?.charAt(0) || '')
    }

    const getYearLevel = (year) => {
      const levels = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' }
      return levels[year] || year
    }

    const fetchDashboardData = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        console.log('Token from store:', token ? 'Present' : 'Missing')
        
        if (!token) {
          console.log('No token found, redirecting to login')
          router.push('/student/login')
          return
        }

        console.log('Fetching dashboard data from:', `${API_URL}/student/dashboard.php`)
        
        const response = await axios.get(`${API_URL}/student/dashboard.php`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          timeout: 10000
        })

        console.log('Dashboard response:', response.data)

        if (response.data.success) {
          dashboardData.value = response.data
        } else {
          throw new Error(response.data.message || 'Failed to load dashboard')
        }
      } catch (err) {
        console.error('Dashboard error details:', err)
        
        if (err.code === 'ECONNABORTED') {
          error.value = 'Request timeout. Please check your connection.'
        } else if (err.response) {
          console.error('Error response data:', err.response.data)
          console.error('Error response status:', err.response.status)
          error.value = err.response.data?.message || `Server error: ${err.response.status}`
          
          if (err.response.status === 401) {
            await store.dispatch('auth/logout')
            router.push('/student/login')
          }
        } else if (err.request) {
          console.error('No response received:', err.request)
          error.value = 'Cannot connect to server. Please check if the backend server is running.'
        } else {
          error.value = err.message || 'Failed to load dashboard data'
        }
      } finally {
        loading.value = false
      }
    }

    const formatTime = (time) => {
      if (!time) return '—'
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

    const formatDate = (date) => {
      if (!date) return '—'
      try {
        return new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      } catch {
        return date
      }
    }

    const formatDay = (date) => {
      if (!date) return ''
      return new Date(date).getDate()
    }

    const formatMonth = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', { month: 'short' })
    }

    const getClassStatus = (classItem) => {
      try {
        const now = new Date()
        const currentTime = now.getHours() * 60 + now.getMinutes()
        
        const start = classItem.time_start ? 
          new Date('1970-01-01T' + classItem.time_start).getHours() * 60 + 
          new Date('1970-01-01T' + classItem.time_start).getMinutes() : 0
        
        const end = classItem.time_end ? 
          new Date('1970-01-01T' + classItem.time_end).getHours() * 60 + 
          new Date('1970-01-01T' + classItem.time_end).getMinutes() : 0

        if (currentTime < start) {
          return { status: 'Upcoming', color: 'warning', class: 'upcoming' }
        } else if (currentTime >= start && currentTime <= end) {
          return { status: 'Ongoing', color: 'success', class: 'ongoing' }
        } else {
          return { status: 'Completed', color: 'secondary', class: 'completed' }
        }
      } catch {
        return { status: 'Scheduled', color: 'info', class: '' }
      }
    }

    const getGradeColor = (grade) => {
      if (!grade) return 'secondary'
      try {
        const numGrade = parseFloat(grade)
        if (numGrade <= 1.5) return 'excellent'
        if (numGrade <= 2.0) return 'good'
        if (numGrade <= 2.5) return 'average'
        return 'poor'
      } catch {
        return 'secondary'
      }
    }

    const getRemarksColor = (remarks) => {
      const colors = {
        'Passed': 'success',
        'Failed': 'danger',
        'Incomplete': 'warning',
        'Dropped': 'secondary'
      }
      return colors[remarks] || 'secondary'
    }

    const showHelp = () => {
      Swal.fire({
        title: 'Help & Support',
        html: `
          <div class="text-start">
            <p><i class="bi bi-envelope me-2 text-primary"></i> Email: support@ccs.edu</p>
            <p><i class="bi bi-telephone me-2 text-primary"></i> Contact: (123) 456-7890</p>
            <p><i class="bi bi-chat me-2 text-primary"></i> Office Hours: Mon-Fri, 8AM-5PM</p>
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'Got it',
        confirmButtonColor: '#3498db'
      })
    }

    onMounted(() => {
      fetchDashboardData()
    })

    return {
      loading,
      error,
      studentName,
      studentProfile,
      currentDate,
      statistics,
      todayClasses,
      recentGrades,
      upcomingEvents,
      fetchDashboardData,
      getProfileImageUrl,
      getUserInitials,
      getYearLevel,
      formatTime,
      formatDate,
      formatDay,
      formatMonth,
      getClassStatus,
      getGradeColor,
      getRemarksColor,
      showHelp
    }
  }
}
</script>

<style scoped>
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background-color: #f8f9fa;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100% - 280px);
  min-height: 100vh;
  padding: 20px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
}

:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }
}

/* Welcome Banner */
.welcome-banner {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 25px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.welcome-avatar {
  width: 60px;
  height: 60px;
}

.avatar-placeholder {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.welcome-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 8px 16px;
  border-radius: 50px;
  font-size: 0.9rem;
  backdrop-filter: blur(10px);
}

/* Statistics Cards */
.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: all 0.3s;
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.stat-details {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 600;
  color: #2c3e50;
  line-height: 1.2;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 500;
}

.stat-subtext {
  font-size: 0.8rem;
  color: #95a5a6;
  margin-top: 2px;
}

/* Schedule Items */
.schedule-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.schedule-item {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 15px;
  transition: all 0.3s;
  border-left: 4px solid transparent;
}

.schedule-item.upcoming {
  border-left-color: #f39c12;
}

.schedule-item.ongoing {
  border-left-color: #27ae60;
  background: #e8f8f0;
}

.schedule-item.completed {
  border-left-color: #95a5a6;
  opacity: 0.8;
}

.schedule-time {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.time-badge {
  background: white;
  padding: 5px 12px;
  border-radius: 50px;
  font-size: 0.9rem;
  font-weight: 500;
  color: #2c3e50;
}

.time-separator {
  margin: 0 5px;
  color: #95a5a6;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 50px;
  color: white;
  font-size: 0.8rem;
  font-weight: 500;
}

.schedule-details h6 {
  margin-bottom: 5px;
  color: #2c3e50;
}

.schedule-meta {
  font-size: 0.85rem;
  color: #7f8c8d;
}

/* Quick Action Cards */
.quick-action-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px 10px;
  background: #f8f9fa;
  border-radius: 12px;
  text-decoration: none;
  color: #2c3e50;
  transition: all 0.3s;
}

.quick-action-card:hover {
  transform: translateY(-5px);
  background: white;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  color: #2c3e50;
}

.quick-action-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 10px;
}

/* Grade Items */
.grade-item {
  margin-bottom: 15px;
}

.grade-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
}

.grade-badge {
  padding: 5px 12px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1.1rem;
}

.grade-excellent {
  background: #27ae6015;
  color: #27ae60;
}

.grade-good {
  background: #3498db15;
  color: #3498db;
}

.grade-average {
  background: #f39c1215;
  color: #f39c12;
}

.grade-poor {
  background: #e74c3c15;
  color: #e74c3c;
}

/* Event Cards */
.event-card {
  display: flex;
  gap: 15px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 12px;
  transition: all 0.3s;
  height: 100%;
}

.event-card:hover {
  transform: translateY(-5px);
  background: white;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.event-date {
  min-width: 60px;
  height: 60px;
  background: white;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.event-day {
  font-size: 1.5rem;
  font-weight: 700;
  color: #3498db;
  line-height: 1;
}

.event-month {
  font-size: 0.8rem;
  color: #7f8c8d;
  text-transform: uppercase;
}

.event-details {
  flex: 1;
  position: relative;
  padding-bottom: 25px;
}

.event-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: #2c3e50;
}

.event-description {
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-meta {
  font-size: 0.8rem;
  color: #95a5a6;
}

.event-meta span {
  display: inline-block;
  margin-right: 10px;
}

.event-type {
  position: absolute;
  bottom: 0;
  left: 0;
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: 50px;
  background: #e9ecef;
  color: #2c3e50;
}

.event-type.type-curricular {
  background: #27ae6015;
  color: #27ae60;
}

.event-type.type-co-curricular {
  background: #3498db15;
  color: #3498db;
}

/* Empty State */
.empty-state {
  padding: 40px 20px;
}

.empty-state i {
  font-size: 3rem;
  color: #dee2e6;
}

/* Animations */
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

@keyframes fadeInLeft {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes fadeInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes shakeX {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate__animated {
  animation-duration: 0.5s;
  animation-fill-mode: both;
}

.animate__fadeInUp {
  animation-name: fadeInUp;
}

.animate__fadeInLeft {
  animation-name: fadeInLeft;
}

.animate__fadeInRight {
  animation-name: fadeInRight;
}

.animate__shakeX {
  animation-name: shakeX;
}

/* Responsive */
@media (max-width: 768px) {
  .stat-card {
    padding: 15px;
  }
  
  .stat-icon {
    width: 50px;
    height: 50px;
    font-size: 1.5rem;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
  
  .schedule-item {
    padding: 12px;
  }
  
  .event-card {
    flex-direction: column;
  }
  
  .event-date {
    align-self: flex-start;
  }
}
</style>