<template>
  <div class="app-wrapper">
    <FacultySidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'My Schedule'" />
      
      <div class="container-fluid p-4">
        <!-- Header with Gradient (same as before) -->
        <div class="header-gradient mb-4">
          <div class="header-content">
            <div class="header-left">
              <div class="header-icon-wrapper">
                <i class="bi bi-calendar-week-fill"></i>
              </div>
              <div class="header-text">
                <h1 class="header-title">My Teaching Schedule</h1>
                <p class="header-subtitle">Manage and view your weekly class assignments</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards (same) -->
        <div class="stats-grid">
          <div class="stat-card" v-for="(stat, index) in statsData" :key="stat.label" 
               :style="{ animationDelay: index * 0.1 + 's' }">
            <div class="stat-icon" :style="{ background: stat.color + '15' }">
              <i :class="stat.icon" :style="{ color: stat.color }"></i>
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ stat.value }}</span>
              <span class="stat-label">{{ stat.label }}</span>
              <span class="stat-sub" v-if="stat.sub">{{ stat.sub }}</span>
            </div>
          </div>
        </div>

        <!-- Filter Card (same) -->
        <div class="filters-card">
          <div class="filters-header">
            <div class="filters-title">
              <i class="bi bi-funnel me-2"></i>
              <span>Filter Schedule</span>
            </div>
            <button class="btn-refresh" @click="fetchSchedule" title="Refresh Schedule">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
          <div class="filters-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="filter-label">Semester</label>
                <select class="filter-select" v-model="filters.semester" @change="fetchSchedule">
                  <option value="1">📚 1st Semester</option>
                  <option value="2">📖 2nd Semester</option>
                  <option value="3">☀️ Summer</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="filter-label">Academic Year</label>
                <select class="filter-select" v-model="filters.year" @change="fetchSchedule">
                  <option value="2025-2026">2025-2026</option>
                  <option value="2024-2025">2024-2025</option>
                  <option value="2023-2024">2023-2024</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading your schedule...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <i class="bi bi-exclamation-triangle"></i>
          <h4>Unable to Load Schedule</h4>
          <p>{{ error }}</p>
          <button class="btn-retry" @click="fetchSchedule">
            <i class="bi bi-arrow-clockwise me-2"></i>
            Try Again
          </button>
        </div>

        <!-- Schedule Content -->
        <template v-else>
          <!-- Period Badge -->
          <div class="period-badge">
            <i class="bi bi-calendar-range"></i>
            <span>{{ academicPeriod }}</span>
          </div>

          <!-- Schedule Grid -->
          <div class="schedule-grid">
            <div v-for="day in daysOrder" :key="day" class="schedule-day" :class="{ 'weekend': day === 'Saturday' || day === 'Sunday' }">
              <div class="day-header" :class="getDayColor(day)">
                <span class="day-name">{{ day }}</span>
                <span class="day-short">{{ day.substring(0, 3) }}</span>
              </div>
              <div class="day-classes">
                <div v-if="!schedule[day] || schedule[day].length === 0" class="empty-classes">
                  <i class="bi bi-calendar-x"></i>
                  <span>No classes</span>
                </div>
                <div v-else>
                  <div v-for="class_item in schedule[day]" :key="class_item.id" class="class-card" :class="getClassColor(class_item.course_code)">
                    <div class="class-time-badge">
                      <i class="bi bi-clock"></i>
                      <span>{{ class_item.time_start }} - {{ class_item.time_end }}</span>
                    </div>
                    <div class="class-code">{{ class_item.course_code }}</div>
                    <div class="class-name">{{ class_item.course_name }}</div>
                    <div class="class-details">
                      <div class="detail-chip">
                        <i class="bi bi-people"></i>
                        <span>{{ class_item.section }}</span>
                      </div>
                      <div class="detail-chip">
                        <i class="bi bi-door-open"></i>
                        <span>{{ class_item.room }}</span>
                      </div>
                      <div class="detail-chip">
                        <i class="bi bi-mortarboard"></i>
                        <span>{{ class_item.units }} units</span>
                      </div>
                      <!-- ADD YEAR LEVEL CHIP -->
                      <div class="detail-chip">
                        <i class="bi bi-layers"></i>
                        <span>Year {{ class_item.year_level }}</span>
                      </div>
                      <div class="detail-chip">
                        <i class="bi bi-person-check"></i>
                        <span>{{ class_item.students || 0 }} students</span>
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
  name: 'FacultySchedule',
  components: {
    FacultySidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    const schedule = ref({})
    const totalClasses = ref(0)
    const totalUnits = ref(0)
    
    const filters = ref({
      semester: '1',
      year: '2025-2026'
    })

    const daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const academicPeriod = computed(() => {
      const sem = filters.value.semester === '1' ? '1st Semester' : 
                  filters.value.semester === '2' ? '2nd Semester' : 'Summer'
      return `${sem}, AY ${filters.value.year}`
    })

    const totalHours = computed(() => {
      let hours = 0
      Object.values(schedule.value).forEach(dayClasses => {
        dayClasses.forEach(classItem => {
          if (classItem.time_start && classItem.time_end) {
            const start = parseTimeToMinutes(classItem.time_start)
            const end = parseTimeToMinutes(classItem.time_end)
            hours += (end - start) / 60
          }
        })
      })
      return hours.toFixed(1)
    })

    const statsData = computed(() => [
      {
        label: 'Total Classes',
        value: totalClasses.value,
        icon: 'bi bi-calendar-check',
        color: '#4361ee',
        sub: 'This semester'
      },
      {
        label: 'Teaching Load',
        value: totalUnits.value,
        icon: 'bi bi-book',
        color: '#10b981',
        sub: 'Total units'
      },
      {
        label: 'Hours/Week',
        value: totalHours.value,
        icon: 'bi bi-clock',
        color: '#f59e0b',
        sub: 'Teaching hours'
      },
      {
        label: 'Active Days',
        value: Object.keys(schedule.value).length,
        icon: 'bi bi-calendar-week',
        color: '#06b6d4',
        sub: 'Days with classes'
      }
    ])

    const parseTimeToMinutes = (timeStr) => {
      if (!timeStr) return 0
      const [time, period] = timeStr.split(' ')
      let [hours, minutes] = time.split(':')
      let hour = parseInt(hours)
      if (period === 'PM' && hour !== 12) hour += 12
      if (period === 'AM' && hour === 12) hour = 0
      return hour * 60 + parseInt(minutes)
    }

    const getDayColor = (day) => {
      const colors = {
        'Monday': 'day-monday',
        'Tuesday': 'day-tuesday',
        'Wednesday': 'day-wednesday',
        'Thursday': 'day-thursday',
        'Friday': 'day-friday',
        'Saturday': 'day-saturday'
      }
      return colors[day] || 'day-default'
    }

    const getClassColor = (courseCode) => {
      const colors = ['primary', 'success', 'info', 'warning', 'danger', 'purple', 'teal']
      let hash = 0
      for (let i = 0; i < courseCode.length; i++) {
        hash = ((hash << 5) - hash) + courseCode.charCodeAt(i)
        hash |= 0
      }
      return `class-${colors[Math.abs(hash) % colors.length]}`
    }

    const fetchSchedule = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          router.push('/faculty/login')
          return
        }

        const response = await axios.get(`${API_URL}/faculty/schedule.php`, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          params: {
            semester: filters.value.semester,
            year: filters.value.year
          }
        })

        if (response.data.success) {
          schedule.value = response.data.schedule || {}
          totalClasses.value = response.data.total_classes || 0
          totalUnits.value = response.data.total_units || 0
        } else {
          throw new Error(response.data.message || 'Failed to load schedule')
        }
      } catch (err) {
        console.error('Schedule error:', err)
        
        if (err.response?.status === 401) {
          error.value = 'Session expired. Please login again.'
          Swal.fire({
            icon: 'error',
            title: 'Session Expired',
            text: 'Please login again to continue.',
            confirmButtonText: 'OK'
          }).then(() => {
            store.dispatch('auth/logout')
            router.push('/faculty/login')
          })
        } else if (err.response?.status === 403) {
          error.value = 'Access denied. Faculty privileges required.'
        } else if (err.response?.status === 404) {
          error.value = 'Schedule endpoint not found.'
        } else if (err.code === 'ECONNABORTED') {
          error.value = 'Request timeout. Please check your connection.'
        } else if (err.request) {
          error.value = 'Cannot connect to server. Please check if backend is running.'
        } else {
          error.value = err.response?.data?.message || err.message || 'Failed to load schedule'
        }
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchSchedule()
    })

    return {
      loading,
      error,
      schedule,
      filters,
      daysOrder,
      totalClasses,
      totalUnits,
      totalHours,
      academicPeriod,
      statsData,
      fetchSchedule,
      getDayColor,
      getClassColor
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

/* Header Gradient */
.header-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 24px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  animation: slideDown 0.5s ease;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.header-icon-wrapper {
  width: 70px;
  height: 70px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.header-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  line-height: 1.2;
}

.header-subtitle {
  margin: 5px 0 0;
  opacity: 0.9;
  font-size: 0.95rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 25px;
}

.stat-card {
  background: white;
  border-radius: 20px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  transition: all 0.3s;
  border: 1px solid #f0f0f0;
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border-color: #667eea;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1.2;
  display: block;
}

.stat-label {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
  display: block;
  margin-bottom: 2px;
}

.stat-sub {
  color: #a0aec0;
  font-size: 0.75rem;
}

/* Filters Card */
.filters-card {
  background: white;
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 25px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  border: 1px solid #f0f0f0;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
}

.filters-title {
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
}

.btn-refresh {
  background: none;
  border: none;
  padding: 8px 12px;
  border-radius: 10px;
  color: #718096;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-refresh:hover {
  background: #f7fafc;
  color: #667eea;
  transform: rotate(180deg);
}

.filter-label {
  display: block;
  margin-bottom: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #4a5568;
}

.filter-select {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.95rem;
  transition: all 0.3s;
  background: white;
  cursor: pointer;
}

.filter-select:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Period Badge */
.period-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #667eea15, #764ba215);
  padding: 8px 20px;
  border-radius: 30px;
  margin-bottom: 20px;
  color: #667eea;
  font-weight: 500;
  border: 1px solid rgba(102, 126, 234, 0.2);
}

/* Schedule Grid */
.schedule-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.schedule-day {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #f0f0f0;
  transition: all 0.3s;
}

.schedule-day:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.schedule-day.weekend {
  opacity: 0.8;
}

.day-header {
  padding: 15px;
  text-align: center;
  color: white;
}

.day-name {
  font-size: 1rem;
  font-weight: 600;
  display: block;
}

.day-short {
  font-size: 0.75rem;
  opacity: 0.8;
  display: none;
}

.day-monday { background: linear-gradient(135deg, #3498db, #2980b9); }
.day-tuesday { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.day-wednesday { background: linear-gradient(135deg, #f39c12, #e67e22); }
.day-thursday { background: linear-gradient(135deg, #e74c3c, #c0392b); }
.day-friday { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
.day-saturday { background: linear-gradient(135deg, #1abc9c, #16a085); }
.day-default { background: linear-gradient(135deg, #95a5a6, #7f8c8d); }

.day-classes {
  padding: 15px;
  min-height: 350px;
  max-height: 450px;
  overflow-y: auto;
}

/* Custom Scrollbar */
.day-classes::-webkit-scrollbar {
  width: 5px;
}

.day-classes::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.day-classes::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 10px;
}

/* Class Cards */
.class-card {
  background: #f8fafc;
  border-radius: 16px;
  padding: 15px;
  margin-bottom: 12px;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
  border-left: 4px solid;
}

.class-card.class-primary { border-left-color: #3498db; }
.class-card.class-success { border-left-color: #2ecc71; }
.class-card.class-info { border-left-color: #3498db; }
.class-card.class-warning { border-left-color: #f39c12; }
.class-card.class-danger { border-left-color: #e74c3c; }
.class-card.class-purple { border-left-color: #9b59b6; }
.class-card.class-teal { border-left-color: #1abc9c; }

.class-card:hover {
  transform: translateX(5px);
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.class-time-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: white;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.class-code {
  font-size: 1rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 4px;
}

.class-name {
  font-size: 0.85rem;
  color: #718096;
  margin-bottom: 12px;
  line-height: 1.4;
}

.class-details {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.detail-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: white;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  color: #4a5568;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.detail-chip i {
  font-size: 0.7rem;
  color: #667eea;
}

.empty-classes {
  text-align: center;
  padding: 40px 20px;
  color: #a0aec0;
}

.empty-classes i {
  font-size: 2.5rem;
  margin-bottom: 10px;
  display: block;
  opacity: 0.5;
}

.empty-classes span {
  font-size: 0.85rem;
}

/* Summary Footer */
.summary-footer {
  background: white;
  border-radius: 20px;
  padding: 20px;
  display: flex;
  justify-content: space-around;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  border: 1px solid #f0f0f0;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 15px;
}

.summary-icon {
  width: 45px;
  height: 45px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
}

.bg-primary-light { background: rgba(67, 97, 238, 0.12); color: #4361ee; }
.bg-success-light { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.bg-info-light { background: rgba(6, 182, 212, 0.12); color: #06b6d4; }

.summary-info {
  display: flex;
  flex-direction: column;
}

.summary-label {
  font-size: 0.75rem;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.summary-value {
  font-size: 1.3rem;
  font-weight: 700;
  color: #2d3748;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 20px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 3px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error State */
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 20px;
}

.error-state i {
  font-size: 4rem;
  color: #ef4444;
  margin-bottom: 20px;
}

.error-state h4 {
  color: #2d3748;
  margin-bottom: 10px;
  font-size: 1.3rem;
}

.error-state p {
  color: #718096;
  margin-bottom: 20px;
}

.btn-retry {
  padding: 10px 25px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-retry:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .header-left {
    flex-direction: column;
    text-align: center;
  }

  .header-title {
    font-size: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .schedule-grid {
    grid-template-columns: 1fr;
  }

  .day-name {
    display: none;
  }

  .day-short {
    display: block;
  }

  .summary-footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .summary-item {
    width: 100%;
  }
}
</style>