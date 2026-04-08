<template>
  <div class="app-wrapper">
    <StudentSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'My Schedule'" />
      
      <div class="container-fluid p-3">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading schedule data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchScheduleData">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Schedule Content -->
        <template v-else>
          <!-- Student Info Banner -->
          <div class="student-info-banner mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-primary">{{ studentInfo.student_number }}</span>
                <span class="badge bg-success ms-2">{{ studentInfo.course }}</span>
                <span class="badge bg-info ms-2">Year {{ studentInfo.year_level }} - Section {{ studentInfo.section }}</span>
              </div>
              <div>
                <span class="badge bg-warning">{{ currentPeriod }}</span>
              </div>
            </div>
          </div>

          <!-- Schedule Controls -->
          <div class="row mb-3 align-items-center">
            <div class="col-md-6">
              <div class="btn-group" role="group">
                <button class="btn btn-outline-primary" @click="changeWeek(-1)" :disabled="loading">
                  <i class="bi bi-chevron-left"></i> Previous
                </button>
                <button class="btn btn-primary" @click="goToCurrentWeek" :disabled="loading">
                  Current Week
                </button>
                <button class="btn btn-outline-primary" @click="changeWeek(1)" :disabled="loading">
                  Next <i class="bi bi-chevron-right"></i>
                </button>
              </div>
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
              <div class="btn-group" role="group">
                <button class="btn btn-sm btn-outline-secondary" @click="showFilters = !showFilters">
                  <i class="bi bi-funnel"></i> Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" @click="exportSchedule">
                  <i class="bi bi-download"></i> Export
                </button>
              </div>
            </div>
          </div>

          <!-- Filters Panel -->
          <div v-if="showFilters" class="filter-panel mb-3 p-3 bg-light rounded">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label small mb-1">School Year</label>
                <select class="form-select form-select-sm" v-model="selectedYear" @change="fetchScheduleData">
                  <option v-for="year in availableYears" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">Semester</label>
                <select class="form-select form-select-sm" v-model="selectedSemester" @change="fetchScheduleData">
                  <option value="1">1st Semester</option>
                  <option value="2">2nd Semester</option>
                  <option value="3">Summer</option>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary btn-sm w-100" @click="fetchScheduleData">
                  <i class="bi bi-search"></i> Apply Filters
                </button>
              </div>
            </div>
          </div>

          <!-- Week Navigation Info -->
          <div class="row mb-2">
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-primary mb-0">
                  <i class="bi bi-calendar-week me-2"></i>
                  {{ weekStartDate }} - {{ weekEndDate }}
                </h6>
                <span class="badge bg-info">
                  {{ statistics.total_courses }} Courses • {{ statistics.total_units }} Units
                </span>
              </div>
            </div>
          </div>
          
          <!-- Schedule Table -->
          <div class="schedule-wrapper">
            <table class="table table-bordered schedule-table mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 90px">Time</th>
                  <th v-for="day in daysOfWeek" :key="day" class="text-center">{{ day.substring(0,3) }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="time in timeSlots" :key="time">
                  <td class="time-slot small fw-bold">{{ time }}</td>
                  <td v-for="day in daysOfWeek" :key="day" class="schedule-cell p-1">
                    <div v-for="class_item in getClassesForSlot(day, time)" 
                         :key="class_item.id" 
                         class="schedule-item"
                         :class="class_item.colorClass"
                         @click="viewClassDetails(class_item)">
                      <div class="fw-bold small">{{ class_item.course_code }}</div>
                      <div class="small">{{ class_item.room }}</div>
                      <div class="small text-truncate">{{ class_item.instructor }}</div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Legend -->
          <div class="row mt-2">
            <div class="col-12">
              <div class="d-flex gap-3 justify-content-end small">
                <div class="d-flex align-items-center">
                  <div class="legend-color bg-primary-light me-1"></div>
                  <span>Lecture</span>
                </div>
                <div class="d-flex align-items-center">
                  <div class="legend-color bg-success-light me-1"></div>
                  <span>Lab</span>
                </div>
                <div class="d-flex align-items-center">
                  <div class="legend-color bg-warning-light me-1"></div>
                  <span>PE</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Class Details Modal -->
    <div class="modal fade" id="classModal" tabindex="-1">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white py-2">
            <h6 class="modal-title">
              <i class="bi bi-book me-2"></i>
              Class Details
            </h6>
            <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-3" v-if="selectedClass">
            <div class="text-center mb-2">
              <span class="badge" :class="selectedClass.colorClass">
                {{ selectedClass.course_code }}
              </span>
            </div>
            
            <table class="table table-sm table-borderless small">
              <tr>
                <td width="40%"><i class="bi bi-book me-1 text-primary"></i>Course:</td>
                <td class="fw-bold">{{ selectedClass.course_name || selectedClass.course_code }}</td>
              </tr>
              <tr>
                <td><i class="bi bi-person me-1 text-primary"></i>Instructor:</td>
                <td class="fw-bold">{{ selectedClass.instructor }}</td>
              </tr>
              <tr v-if="selectedClass.instructor_email">
                <td><i class="bi bi-envelope me-1 text-primary"></i>Email:</td>
                <td><small>{{ selectedClass.instructor_email }}</small></td>
              </tr>
              <tr>
                <td><i class="bi bi-door-open me-1 text-primary"></i>Room:</td>
                <td>{{ selectedClass.room }}</td>
              </tr>
              <tr>
                <td><i class="bi bi-calendar me-1 text-primary"></i>Time:</td>
                <td>{{ selectedClass.time }}</td>
              </tr>
              <tr v-if="selectedClass.year_level">
                <td><i class="bi bi-layers me-1 text-primary"></i>Year Level:</td>
                <td>{{ selectedClass.year_level }}</td>
              </tr>
              <tr>
                <td><i class="bi bi-people me-1 text-primary"></i>Section:</td>
                <td>{{ selectedClass.section }}</td>
              </tr>
            </table>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { Modal } from 'bootstrap'
import StudentSidebar from '@/components/layout/StudentSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'StudentSchedule',
  components: {
    StudentSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    const showFilters = ref(false)
    const currentWeek = ref(0)
    const selectedClass = ref(null)
    const selectedYear = ref('')
    const selectedSemester = ref('')
    const availableYears = ref([])
    const studentInfo = ref({
      student_number: '',
      course: '',
      year_level: '',
      section: ''
    })
    
    const scheduleData = ref({})
    const statistics = ref({
      total_courses: 0,
      total_units: 0
    })

    const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    
    const timeSlots = [
      '7:00-8:00', '8:00-9:00', '9:00-10:00', '10:00-11:00',
      '11:00-12:00', '12:00-13:00', '13:00-14:00', '14:00-15:00',
      '15:00-16:00', '16:00-17:00', '17:00-18:00', '18:00-19:00'
    ]

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const weekStartDate = computed(() => {
      const date = new Date()
      date.setDate(date.getDate() + (currentWeek.value * 7))
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    })

    const weekEndDate = computed(() => {
      const date = new Date()
      date.setDate(date.getDate() + (currentWeek.value * 7) + 6)
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    })

    const currentPeriod = computed(() => {
      const sem = selectedSemester.value === '1' ? '1st Semester' : 
                  selectedSemester.value === '2' ? '2nd Semester' : 'Summer'
      return `${sem}, AY ${selectedYear.value || '2025-2026'}`
    })

    const fetchScheduleData = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          router.push('/student/login')
          return
        }

        const params = new URLSearchParams()
        if (selectedYear.value) params.append('year', selectedYear.value)
        if (selectedSemester.value) params.append('semester', selectedSemester.value)

        const response = await axios.get(`${API_URL}/student/schedule.php?${params.toString()}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          studentInfo.value = response.data.student || {}
          scheduleData.value = response.data.schedule || {}
          statistics.value = response.data.statistics || { total_courses: 0, total_units: 0 }
          
          if (response.data.available_periods) {
            availableYears.value = [...new Set(response.data.available_periods.map(p => p.academic_year))]
          }
          
          if (!selectedYear.value && availableYears.value.length > 0) {
            selectedYear.value = availableYears.value[0]
          }
        } else {
          throw new Error(response.data.message || 'Failed to load schedule')
        }
      } catch (err) {
        console.error('Schedule error:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to load schedule data'
        
        if (err.response?.status === 401) {
          await store.dispatch('auth/logout')
          router.push('/student/login')
        }
      } finally {
        loading.value = false
      }
    }

    const changeWeek = (delta) => {
      currentWeek.value += delta
    }

    const goToCurrentWeek = () => {
      currentWeek.value = 0
    }

    const getClassesForSlot = (day, time) => {
      const classes = scheduleData.value[day] || []
      const timeStart = time.split('-')[0].trim()
      
      return classes.filter(item => {
        const itemStart = item.time_start.split(':')[0]
        const filterHour = timeStart.split(':')[0]
        return itemStart === filterHour
      }).map(item => ({
        ...item,
        time: `${item.time_start} - ${item.time_end}`,
        colorClass: getCourseColor(item.course_code)
      }))
    }

    const getCourseColor = (courseCode) => {
      if (!courseCode) return 'bg-info-light'
      if (courseCode.includes('CS')) return 'bg-primary-light'
      if (courseCode.includes('IT')) return 'bg-success-light'
      if (courseCode.includes('PE')) return 'bg-warning-light'
      return 'bg-info-light'
    }

    const viewClassDetails = (classItem) => {
      selectedClass.value = classItem
      const modal = new Modal(document.getElementById('classModal'))
      modal.show()
    }

    const exportSchedule = () => {
      Swal.fire({
        title: 'Export Schedule',
        text: 'Choose export format',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'PDF',
        cancelButtonText: 'Excel'
      })
    }

    onMounted(() => {
      fetchScheduleData()
    })

    return {
      loading,
      error,
      showFilters,
      currentWeek,
      selectedYear,
      selectedSemester,
      availableYears,
      studentInfo,
      daysOfWeek,
      timeSlots,
      weekStartDate,
      weekEndDate,
      currentPeriod,
      scheduleData,
      statistics,
      selectedClass,
      fetchScheduleData,
      changeWeek,
      goToCurrentWeek,
      getClassesForSlot,
      viewClassDetails,
      exportSchedule
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
  padding: 15px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
  overflow: hidden;
}

/* When sidebar is collapsed */
:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 10px;
  }
}

/* Container adjustments */
.container-fluid {
  padding: 0;
  max-width: 100%;
  height: 100%;
}

/* Schedule wrapper - FIXED HEIGHT NO SCROLL */
.schedule-wrapper {
  height: calc(100vh - 200px);
  overflow: hidden;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  background: white;
}

.schedule-table {
  width: 100%;
  table-layout: fixed;
  border-collapse: collapse;
}

.schedule-table th,
.schedule-table td {
  border: 1px solid #dee2e6;
  padding: 4px;
  text-align: left;
  vertical-align: top;
}

.schedule-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  font-size: 0.8rem;
  padding: 6px 4px;
  text-align: center;
  position: sticky;
  top: 0;
  z-index: 10;
}

.time-slot {
  font-size: 0.75rem;
  font-weight: 600;
  background-color: #f8f9fa;
  white-space: nowrap;
  padding: 4px !important;
  width: 70px;
}

.schedule-cell {
  height: 55px;
  padding: 2px !important;
  vertical-align: top;
}

.schedule-item {
  height: 100%;
  padding: 2px 4px;
  border-radius: 4px;
  font-size: 0.7rem;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 1px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.schedule-item:hover {
  transform: scale(1.02);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  z-index: 5;
}

/* Color classes */
.bg-primary-light {
  background-color: rgba(52, 152, 219, 0.15);
  border-left: 3px solid #3498db;
}

.bg-success-light {
  background-color: rgba(46, 204, 113, 0.15);
  border-left: 3px solid #2ecc71;
}

.bg-warning-light {
  background-color: rgba(241, 196, 15, 0.15);
  border-left: 3px solid #f1c40f;
}

.bg-info-light {
  background-color: rgba(52, 152, 219, 0.1);
  border-left: 3px solid #3498db;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

/* Filter panel */
.filter-panel {
  border: 1px solid #dee2e6;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Badge adjustments */
.badge {
  font-size: 0.7rem;
  padding: 4px 6px;
}

/* Modal adjustments */
.modal-sm {
  max-width: 300px;
}

.modal-body {
  padding: 12px;
}

.table-sm td {
  padding: 4px;
  font-size: 0.8rem;
}

/* Remove any margins that cause overflow */
.row {
  margin: 0;
}

[class*="col-"] {
  padding: 0 6px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .schedule-wrapper {
    height: calc(100vh - 180px);
  }
  
  .time-slot {
    font-size: 0.7rem;
    width: 60px;
  }
  
  .schedule-item {
    font-size: 0.65rem;
    padding: 1px 2px;
  }
}
</style>