<template>
  <div class="app-wrapper">
    <!-- Sidebar -->
    <AdminSidebar />
    
    <!-- Main Content -->
    <div class="main-content">
      <TopNav :pageTitle="'Dashboard'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading dashboard data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
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
          <!-- Welcome Banner -->
          <div class="welcome-banner">
            <div class="d-flex align-items-center">
              <div class="welcome-icon">
                <i class="bi bi-megaphone"></i>
              </div>
              <div>
                <h5 class="mb-1">Welcome back, {{ userName }}!</h5>
                <p class="mb-0 text-muted">Here's what's happening in CCS today.</p>
              </div>
            </div>
            <div class="welcome-date">
              <i class="bi bi-calendar3 me-2"></i>
              {{ currentDate }}
            </div>
          </div>
          
          <!-- Statistics Cards -->
          <div class="stats-grid">
            <div class="stat-card-wrapper" v-for="(stat, index) in statistics" :key="stat.label">
              <div class="stat-card" :style="{ animationDelay: (index * 0.1) + 's' }">
                <div class="stat-icon" :style="{ background: stat.color + '15', color: stat.color }">
                  <i :class="stat.icon"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value">{{ stat.value }}</div>
                  <div class="stat-label">{{ stat.label }}</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Charts and Tables -->
          <div class="dashboard-grid">
            <!-- Recent Activities -->
            <div class="dashboard-card">
              <div class="card-header">
                <div class="d-flex align-items-center">
                  <div class="header-icon bg-primary-light">
                    <i class="bi bi-activity text-primary"></i>
                  </div>
                  <h5 class="mb-0">Recent Activities</h5>
                </div>
                <button class="btn btn-sm btn-outline-primary" @click="refreshActivities">
                  <i class="bi bi-arrow-clockwise me-1"></i>
                  Refresh
                </button>
              </div>
              
              <div class="card-body p-0">
                <div v-if="recentActivities.length === 0" class="empty-state">
                  <i class="bi bi-inbox"></i>
                  <p class="text-muted">No recent activities</p>
                </div>
                
                <div v-else class="activity-list">
                  <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
                    <div class="activity-avatar">
                      {{ getUserInitials(activity.user) }}
                    </div>
                    <div class="activity-content">
                      <div class="activity-header">
                        <span class="activity-user">{{ activity.user }}</span>
                        <span class="activity-time">{{ formatTimeAgo(activity.date) }}</span>
                      </div>
                      <div class="activity-action">{{ activity.action }}</div>
                      <div class="activity-status">
                        <span :class="'badge bg-' + getStatusColor(activity.status)">
                          {{ activity.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="dashboard-card">
              <div class="card-header">
                <div class="d-flex align-items-center">
                  <div class="header-icon bg-warning-light">
                    <i class="bi bi-calendar-event text-warning"></i>
                  </div>
                  <h5 class="mb-0">Upcoming Events</h5>
                </div>
                <button class="btn btn-sm btn-primary" @click="addEvent">
                  <i class="bi bi-plus-circle me-1"></i>
                  Add Event
                </button>
              </div>
              
              <div class="card-body p-0">
                <div v-if="upcomingEvents.length === 0" class="empty-state">
                  <i class="bi bi-calendar-x"></i>
                  <p class="text-muted">No upcoming events</p>
                </div>
                
                <div v-else class="events-list">
                  <div v-for="event in upcomingEvents" :key="event.id" class="event-item">
                    <div class="event-date-badge">
                      <span class="event-day">{{ formatDay(event.event_date) }}</span>
                      <span class="event-month">{{ formatMonth(event.event_date) }}</span>
                    </div>
                    <div class="event-content">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <h6 class="event-title">{{ event.title }}</h6>
                          <p class="event-description">{{ event.description || 'No description' }}</p>
                          <div class="event-meta">
                            <span v-if="event.event_time">
                              <i class="bi bi-clock"></i>
                              {{ formatTime(event.event_time) }}
                            </span>
                            <span v-if="event.venue">
                              <i class="bi bi-geo-alt"></i>
                              {{ event.venue }}
                            </span>
                          </div>
                        </div>
                        <span :class="'badge bg-' + getEventTypeColor(event.event_type)">
                          {{ event.event_type }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Enrollment Trends -->
            <div class="dashboard-card full-width">
              <div class="card-header">
                <div class="d-flex align-items-center">
                  <div class="header-icon bg-success-light">
                    <i class="bi bi-graph-up text-success"></i>
                  </div>
                  <h5 class="mb-0">Enrollment Trends (Last 6 Months)</h5>
                </div>
              </div>
              
              <div class="card-body">
                <div v-if="enrollmentTrends.length === 0" class="empty-state">
                  <i class="bi bi-bar-chart"></i>
                  <p class="text-muted">No enrollment data available</p>
                </div>
                
                <div v-else class="trend-chart">
                  <div class="chart-bars">
                    <div v-for="trend in enrollmentTrends" :key="trend.month" 
                         class="chart-bar-container">
                      <div class="chart-bar" 
                           :style="{ height: (trend.count / maxEnrollment * 180) + 'px' }">
                        <span class="bar-value">{{ trend.count }}</span>
                      </div>
                      <span class="bar-label">{{ formatShortMonth(trend.month) }}</span>
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
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

export default {
  name: 'AdminDashboard',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    
    const statistics = ref([])
    const recentActivities = ref([])
    const upcomingEvents = ref([])
    const enrollmentTrends = ref([])
    const userName = ref('Admin')

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const currentDate = computed(() => {
      return new Date().toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    })

    const maxEnrollment = computed(() => {
      if (enrollmentTrends.value.length === 0) return 1
      return Math.max(...enrollmentTrends.value.map(t => parseInt(t.count) || 0))
    })

    const fetchDashboardData = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          router.push('/admin/login')
          return
        }

        const response = await axios.get(`${API_URL}/admin/dashboard.php`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          timeout: 10000
        })

        if (response.data.success) {
          statistics.value = response.data.statistics || []
          recentActivities.value = response.data.recent_activities || []
          upcomingEvents.value = response.data.upcoming_events || []
          enrollmentTrends.value = response.data.enrollment_trends || []
          userName.value = response.data.user?.name || 'Admin'
        } else {
          throw new Error(response.data.message || 'Failed to load dashboard')
        }
      } catch (err) {
        console.error('Dashboard error:', err)
        
        if (err.code === 'ECONNABORTED') {
          error.value = 'Request timeout. Please check your connection.'
        } else if (err.response) {
          error.value = err.response.data?.message || `Server error: ${err.response.status}`
          
          if (err.response.status === 401) {
            await store.dispatch('auth/logout')
            router.push('/admin/login')
          }
        } else if (err.request) {
          error.value = 'Cannot connect to server. Please check if the backend is running.'
        } else {
          error.value = err.message || 'Failed to load dashboard data'
        }
      } finally {
        loading.value = false
      }
    }

    const refreshActivities = () => {
      fetchDashboardData()
      Swal.fire({
        icon: 'success',
        title: 'Refreshed',
        text: 'Dashboard data has been updated',
        timer: 1500,
        showConfirmButton: false
      })
    }

    const addEvent = () => {
      Swal.fire({
        title: 'Add New Event',
        html: `
          <div class="text-start">
            <div class="mb-3">
              <label class="form-label">Event Title</label>
              <input type="text" id="eventTitle" class="form-control" placeholder="Enter event title">
            </div>
            <div class="mb-3">
              <label class="form-label">Event Type</label>
              <select id="eventType" class="form-select">
                <option value="Curricular">Curricular</option>
                <option value="Extracurricular">Extracurricular</option>
              </select>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Date</label>
                <input type="date" id="eventDate" class="form-control">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Time</label>
                <input type="time" id="eventTime" class="form-control">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Venue</label>
              <input type="text" id="eventVenue" class="form-control" placeholder="Enter venue">
            </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Create Event',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          const title = document.getElementById('eventTitle').value
          if (!title) {
            Swal.showValidationMessage('Event title is required')
            return false
          }
          return {
            title: title,
            type: document.getElementById('eventType').value,
            date: document.getElementById('eventDate').value,
            time: document.getElementById('eventTime').value,
            venue: document.getElementById('eventVenue').value
          }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            icon: 'success',
            title: 'Event Created',
            text: 'New event has been added successfully.',
            timer: 1500,
            showConfirmButton: false
          })
        }
      })
    }

    const getUserInitials = (name) => {
      if (!name) return 'U'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    }

    const formatTimeAgo = (date) => {
      if (!date) return ''
      
      const now = new Date()
      const past = new Date(date)
      const diffTime = Math.abs(now - past)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      const diffHours = Math.ceil(diffTime / (1000 * 60 * 60))
      const diffMinutes = Math.ceil(diffTime / (1000 * 60))

      if (diffMinutes < 60) {
        return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`
      } else if (diffHours < 24) {
        return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
      } else if (diffDays === 1) {
        return 'Yesterday'
      } else {
        return past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
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

    const formatShortMonth = (monthStr) => {
      if (!monthStr) return ''
      const [year, month] = monthStr.split('-')
      const date = new Date(year, month - 1)
      return date.toLocaleDateString('en-US', { month: 'short' })
    }

    const formatTime = (time) => {
      if (!time) return ''
      return new Date('1970-01-01T' + time).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      })
    }

    const getStatusColor = (status) => {
      const colors = {
        'Completed': 'success',
        'Pending': 'warning',
        'Failed': 'danger',
        'In Progress': 'info'
      }
      return colors[status] || 'secondary'
    }

    const getEventTypeColor = (type) => {
      const colors = {
        'Curricular': 'success',
        'Extracurricular': 'info',
        'Meeting': 'warning',
        'Seminar': 'primary'
      }
      return colors[type] || 'secondary'
    }

    onMounted(() => {
      fetchDashboardData()
    })

    return {
      loading,
      error,
      statistics,
      recentActivities,
      upcomingEvents,
      enrollmentTrends,
      userName,
      currentDate,
      maxEnrollment,
      fetchDashboardData,
      refreshActivities,
      addEvent,
      getUserInitials,
      formatTimeAgo,
      formatDay,
      formatMonth,
      formatShortMonth,
      formatTime,
      getStatusColor,
      getEventTypeColor
    }
  }
}
</script>

<style scoped>
/* ===== LAYOUT FIXES ===== */
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background-color: #f8f9fa;
  width: 100%;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100% - 280px);
  min-height: 100vh;
  padding: 25px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
}

:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

.container-fluid {
  padding: 0;
  max-width: 1900px;
  margin: 0 auto;
}

/* ===== WELCOME BANNER ===== */
.welcome-banner {
  background: rgb(69, 125, 207);
  border-radius: 20px;
  padding: 25px 30px;
  margin-bottom: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.welcome-icon {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  font-size: 1.8rem;
}

.welcome-date {
  background: rgba(255, 255, 255, 0.15);
  padding: 8px 16px;
  border-radius: 30px;
  font-size: 0.95rem;
  backdrop-filter: blur(5px);
}

/* ===== STATISTICS GRID ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card-wrapper {
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
  animation-fill-mode: forwards;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
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

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1.2;
  margin-bottom: 5px;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* ===== DASHBOARD GRID ===== */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 25px;
}

.dashboard-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
  animation: fadeIn 0.5s ease;
}

.dashboard-card.full-width {
  grid-column: 1 / -1;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 25px;
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
}

.header-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  font-size: 1.3rem;
}

.bg-primary-light {
  background: rgba(52, 152, 219, 0.15);
}

.bg-warning-light {
  background: rgba(241, 196, 15, 0.15);
}

.bg-success-light {
  background: rgba(46, 204, 113, 0.15);
}

.card-body {
  padding: 0;
}

/* ===== ACTIVITY LIST ===== */
.activity-list {
  padding: 5px 0;
}

.activity-item {
  display: flex;
  padding: 15px 25px;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.3s;
}

.activity-item:hover {
  background: #f8f9fa;
  transform: translateX(5px);
}

.activity-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-right: 15px;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 5px;
}

.activity-user {
  font-weight: 600;
  color: #2c3e50;
}

.activity-time {
  font-size: 0.8rem;
  color: #95a5a6;
}

.activity-action {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-bottom: 8px;
}

/* ===== EVENTS LIST ===== */
.events-list {
  padding: 5px 0;
}

.event-item {
  display: flex;
  padding: 15px 25px;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.3s;
}

.event-item:hover {
  background: #f8f9fa;
}

.event-date-badge {
  width: 50px;
  height: 60px;
  background: #f8f9fa;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  flex-shrink: 0;
  border: 1px solid #e9ecef;
}

.event-day {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1.2;
}

.event-month {
  font-size: 0.8rem;
  color: #7f8c8d;
  text-transform: uppercase;
}

.event-content {
  flex: 1;
}

.event-title {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 5px;
}

.event-description {
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-bottom: 8px;
}

.event-meta {
  display: flex;
  gap: 15px;
  font-size: 0.8rem;
  color: #95a5a6;
}

.event-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* ===== TREND CHART ===== */
.trend-chart {
  padding: 20px 25px;
}

.chart-bars {
  display: flex;
  justify-content: space-around;
  align-items: flex-end;
  height: 200px;
  gap: 10px;
}

.chart-bar-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.chart-bar {
  width: 100%;
  background: linear-gradient(180deg, #3498db, #2980b9);
  border-radius: 8px 8px 0 0;
  position: relative;
  min-width: 40px;
  transition: all 0.3s;
  cursor: pointer;
}

.chart-bar:hover {
  transform: scale(1.05);
  background: linear-gradient(180deg, #e74c3c, #c0392b);
}

.bar-value {
  position: absolute;
  top: -25px;
  left: 50%;
  transform: translateX(-50%);
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.bar-label {
  font-size: 0.8rem;
  color: #7f8c8d;
  font-weight: 500;
}

/* ===== EMPTY STATE ===== */
.empty-state {
  text-align: center;
  padding: 50px 20px;
}

.empty-state i {
  font-size: 3rem;
  color: #dee2e6;
  margin-bottom: 15px;
}

.empty-state p {
  margin-bottom: 0;
}

/* ===== BADGES ===== */
.badge {
  padding: 5px 10px;
  font-weight: 500;
  border-radius: 20px;
}

/* ===== BUTTONS ===== */
.btn {
  padding: 8px 16px;
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-sm {
  padding: 5px 12px;
  font-size: 0.85rem;
}

.btn-outline-primary {
  border: 1px solid #3498db;
  color: #3498db;
}

.btn-outline-primary:hover {
  background: #3498db;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  border: none;
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

/* ===== ANIMATIONS ===== */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
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

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 992px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .welcome-banner {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .event-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .event-date-badge {
    margin-bottom: 10px;
  }

  .chart-bars {
    height: 150px;
  }

  .chart-bar {
    min-width: 25px;
  }

  .bar-value {
    font-size: 0.75rem;
    top: -20px;
  }
}

@media (max-width: 480px) {
  .stat-card {
    flex-direction: column;
    text-align: center;
  }

  .activity-item {
    flex-direction: column;
  }

  .activity-avatar {
    margin-bottom: 10px;
  }

  .event-meta {
    flex-direction: column;
    gap: 5px;
  }
}
</style>