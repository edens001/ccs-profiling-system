<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Faculty Schedule'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header with Gradient -->
          <div class="header-gradient bg-warning mb-4">
            <div class="header-content">
              <div class="header-left">
                <div class="header-icon-wrapper">
                  <i class="bi bi-calendar-week-fill"></i>
                </div>
                <div class="header-text">
                  <h1 class="header-title">Faculty Schedule Management</h1>
                  <p class="header-subtitle">Manage teaching schedules and class assignments</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="stats-grid" v-if="facultyInfo">
            <div class="stat-card">
              <div class="stat-icon bg-primary-light">
                <i class="bi bi-book text-primary"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ schedules.length }}</div>
                <div class="stat-label">Subjects</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-success-light">
                <i class="bi bi-clock text-success"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ totalHours }}</div>
                <div class="stat-label">Hours/Week</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-info-light">
                <i class="bi bi-building text-info"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ facultyInfo.department || 'N/A' }}</div>
                <div class="stat-label">Department</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-warning-light">
                <i class="bi bi-person-badge text-warning"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ facultyInfo.designation || 'Faculty' }}</div>
                <div class="stat-label">Designation</div>
              </div>
            </div>
          </div>

          <!-- Filter Section -->
          <div class="filters-card">
            <div class="filters-header">
              <div class="filters-title">
                <i class="bi bi-funnel me-2"></i>
                <span>Filter Schedule</span>
              </div>
            </div>
            <div class="filters-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="filter-label">Select Faculty</label>
                  <select class="filter-select" v-model="selectedFaculty" @change="loadSchedule">
                    <option value="">-- Select Faculty Member --</option>
                    <option v-for="faculty in facultyList" :key="faculty.id" :value="faculty.id">
                      {{ faculty.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="filter-label">Semester</label>
                  <select class="filter-select" v-model="selectedSemester" @change="loadSchedule">
                    <option value="1">1st Semester</option>
                    <option value="2">2nd Semester</option>
                    <option value="3">Summer</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="filter-label">Academic Year</label>
                  <select class="filter-select" v-model="selectedYear" @change="loadSchedule">
                    <option value="2025-2026">2025-2026</option>
                    <option value="2024-2025">2024-2025</option>
                    <option value="2023-2024">2023-2024</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="filter-label">&nbsp;</label>
                  <button class="btn-load" @click="loadSchedule">
                    <i class="bi bi-search me-2"></i>
                    Load
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading schedule...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="error-state">
            <i class="bi bi-exclamation-triangle"></i>
            <h4>Oops! Something went wrong</h4>
            <p>{{ error }}</p>
            <button class="btn-retry" @click="loadSchedule">
              <i class="bi bi-arrow-clockwise me-2"></i>
              Try Again
            </button>
          </div>

          <!-- Schedule Table - Always show when faculty is selected -->
          <div v-else-if="selectedFaculty" class="schedule-container">
            <div class="schedule-header">
              <div class="faculty-info">
                <i class="bi bi-person-badge"></i>
                <span class="faculty-name">{{ facultyInfo?.name || 'Faculty' }}</span>
                <span class="faculty-detail">{{ facultyInfo?.department || 'Department' }} • {{ facultyInfo?.designation || 'Faculty' }}</span>
              </div>
              <button class="btn-add-schedule" @click="openAddModal">
                <i class="bi bi-plus-lg me-2"></i>
                Add Schedule
              </button>
            </div>

            <div class="table-responsive">
              <table class="schedule-table">
                <thead>
                  
                    <th class="time-column">Time</th>
                    <th v-for="day in days" :key="day" class="day-column">
                      {{ day }}
                    </th>
                  </thead>
                <tbody>
                  <tr v-for="time in timeSlots" :key="time">
                    <td class="time-slot">{{ time }}</td>
                    <td v-for="day in days" :key="day" class="schedule-cell">
                      <div v-for="schedule in getScheduleForTimeSlot(day, time)" 
                           :key="schedule.id"
                           class="schedule-card"
                           :class="'schedule-' + schedule.color">
                        <div class="schedule-code">{{ schedule.course_code }}</div>
                        <div class="schedule-name">{{ schedule.course_name }}</div>
                        <div class="schedule-meta">
                          <span class="badge-section">{{ schedule.section_code }}</span>
                          <span class="badge-room">{{ schedule.room_code }}</span>
                          <span class="badge-year">Year {{ schedule.year_level }}</span>
                        </div>
                        <button class="btn-schedule-delete" @click="deleteSchedule(schedule.id)">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </div>
                      <div v-if="getScheduleForTimeSlot(day, time).length === 0" class="empty-cell">
                        <span class="empty-dot"></span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="schedules.length === 0" class="no-schedule-message">
              <i class="bi bi-calendar-plus"></i>
              <p>No schedule found for this faculty member.</p>
              <button class="btn-add-schedule-small" @click="openAddModal">
                <i class="bi bi-plus-lg me-1"></i>
                Add First Schedule
              </button>
            </div>
          </div>

          <!-- No Selection Message -->
          <div v-else class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h4>No Faculty Selected</h4>
            <p>Select a faculty member from the dropdown to view their teaching schedule.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Schedule Modal -->
    <Teleport to="body">
      <div class="modal fade" id="addScheduleModal" tabindex="-1" ref="modalElement">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content custom-modal">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-plus-circle-fill me-2"></i>
                Add Schedule for {{ facultyInfo?.name || 'Faculty' }}
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveSchedule">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Course *</label>
                    <select class="form-control" v-model="scheduleForm.course_id" required>
                      <option value="">Select Course</option>
                      <option v-for="course in courses" :key="course.id" :value="course.id">
                        {{ course.course_code }} - {{ course.course_name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Section *</label>
                    <select class="form-control" v-model="scheduleForm.section_id" required>
                      <option value="">Select Section</option>
                      <option v-for="section in sections" :key="section.id" :value="section.id">
                        {{ section.section_code }} ({{ section.course_code }}) - Year {{ section.year_level }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Year Level *</label>
                    <select class="form-control" v-model="scheduleForm.year_level" required>
                      <option value="">Select Year Level</option>
                      <option value="1">1st Year</option>
                      <option value="2">2nd Year</option>
                      <option value="3">3rd Year</option>
                      <option value="4">4th Year</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Room *</label>
                    <select class="form-control" v-model="scheduleForm.room_id" required>
                      <option value="">Select Room</option>
                      <option v-for="room in rooms" :key="room.id" :value="room.id">
                        {{ room.room_code }} ({{ room.building }}) - Capacity: {{ room.capacity }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Day *</label>
                    <select class="form-control" v-model="scheduleForm.day_of_week" required>
                      <option value="">Select Day</option>
                      <option v-for="day in days" :key="day" :value="day">{{ day }}</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Start Time *</label>
                    <input type="time" class="form-control" v-model="scheduleForm.time_start" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">End Time *</label>
                    <input type="time" class="form-control" v-model="scheduleForm.time_end" required>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" @click="saveSchedule" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle me-2"></i>
                {{ saving ? 'Saving...' : 'Save Schedule' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useStore } from 'vuex'
import axios from 'axios'
import { Modal } from 'bootstrap'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultySchedule',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const loading = ref(false)
    const saving = ref(false)
    const error = ref(null)
    const facultyList = ref([])
    const selectedFaculty = ref('')
    const selectedSemester = ref('1')
    const selectedYear = ref('2025-2026')
    const schedules = ref([])
    const facultyInfo = ref(null)
    
    const modalElement = ref(null)
    let modalInstance = null
    
    const courses = ref([])
    const sections = ref([])
    const rooms = ref([])
    const scheduleForm = ref({
      course_id: '',
      section_id: '',
      year_level: '',
      room_id: '',
      day_of_week: '',
      time_start: '',
      time_end: ''
    })

    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
    const timeSlots = [
      '7:00-8:30', '8:30-10:00', '10:00-11:30',
      '11:30-13:00', '13:00-14:30', '14:30-16:00', '16:00-17:30'
    ]

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const totalHours = computed(() => {
      return schedules.value.reduce((sum, s) => {
        const start = s.time_start.split(':')
        const end = s.time_end.split(':')
        const hours = (parseInt(end[0]) - parseInt(start[0])) + (parseInt(end[1]) - parseInt(start[1])) / 60
        return sum + hours
      }, 0).toFixed(1)
    })

    const initModal = () => {
      if (modalElement.value && !modalInstance) {
        try {
          modalInstance = new Modal(modalElement.value)
        } catch (e) {
          console.error('Modal initialization error:', e)
        }
      }
    }

    const openAddModal = () => {
      if (!selectedFaculty.value) {
        Swal.fire({
          icon: 'warning',
          title: 'No Faculty Selected',
          text: 'Please select a faculty member first.'
        })
        return
      }
      fetchDropdownData()
      resetForm()
      nextTick(() => {
        if (modalInstance) {
          modalInstance.show()
        } else {
          initModal()
          setTimeout(() => {
            if (modalInstance) modalInstance.show()
          }, 100)
        }
      })
    }

    const closeModal = () => {
      if (modalInstance) {
        modalInstance.hide()
      }
    }

    const fetchFacultyList = async () => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/scheduling/faculty-schedule.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          facultyList.value = response.data.faculty
        }
      } catch (error) {
        console.error('Error fetching faculty:', error)
      }
    }

    const loadSchedule = async () => {
      if (!selectedFaculty.value) return
      
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/scheduling/faculty-schedule.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            faculty_id: selectedFaculty.value,
            semester: selectedSemester.value,
            academic_year: selectedYear.value
          }
        })
        
        if (response.data.success) {
          facultyInfo.value = response.data.faculty
          schedules.value = response.data.schedules.map(s => ({
            ...s,
            color: getRandomColor()
          }))
        } else {
          throw new Error(response.data.message)
        }
      } catch (error) {
        console.error('Error loading schedule:', error)
        error.value = error.response?.data?.message || error.message || 'Failed to load schedule'
        schedules.value = []
      } finally {
        loading.value = false
      }
    }

    const getScheduleForTimeSlot = (day, time) => {
      const [start, end] = time.split('-')
      return schedules.value.filter(s => 
        s.day_of_week === day &&
        s.time_start <= end &&
        s.time_end >= start
      )
    }

    const getRandomColor = () => {
      const colors = ['primary', 'success', 'info', 'warning', 'danger']
      return colors[Math.floor(Math.random() * colors.length)]
    }

    const fetchDropdownData = async () => {
      try {
        const token = store.state.auth.token
        const headers = { 'Authorization': `Bearer ${token}` }
        
        const [coursesRes, sectionsRes, roomsRes] = await Promise.all([
          axios.get(`${API_URL}/admin/courses/index.php`, { headers, params: { limit: 100 } }),
          axios.get(`${API_URL}/admin/scheduling/sections.php`, { headers, params: { limit: 100 } }),
          axios.get(`${API_URL}/admin/scheduling/rooms.php`, { headers, params: { limit: 100 } })
        ])
        
        if (coursesRes.data.success) courses.value = coursesRes.data.data
        if (sectionsRes.data.success) sections.value = sectionsRes.data.data
        if (roomsRes.data.success) rooms.value = roomsRes.data.data
        
      } catch (error) {
        console.error('Error fetching dropdown data:', error)
      }
    }

    const saveSchedule = async () => {
      if (!scheduleForm.value.course_id || !scheduleForm.value.section_id || 
          !scheduleForm.value.year_level || !scheduleForm.value.room_id || 
          !scheduleForm.value.day_of_week || !scheduleForm.value.time_start || 
          !scheduleForm.value.time_end) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please fill in all fields'
        })
        return
      }

      saving.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.post(`${API_URL}/admin/scheduling/faculty-schedule.php`, 
          {
            ...scheduleForm.value,
            faculty_id: selectedFaculty.value,
            semester: selectedSemester.value,
            academic_year: selectedYear.value
          },
          { headers: { 'Authorization': `Bearer ${token}` } }
        )
        
        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Schedule added successfully',
            timer: 1500,
            showConfirmButton: false
          })
          closeModal()
          loadSchedule()
          resetForm()
        } else {
          throw new Error(response.data.message)
        }
      } catch (error) {
        console.error('Error saving schedule:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || error.message || 'Failed to save schedule'
        })
      } finally {
        saving.value = false
      }
    }

    const deleteSchedule = async (id) => {
      const result = await Swal.fire({
        title: 'Delete Schedule?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, delete'
      })
      
      if (result.isConfirmed) {
        try {
          const token = store.state.auth.token
          const response = await axios.delete(`${API_URL}/admin/scheduling/faculty-schedule.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
          
          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Schedule deleted successfully',
              timer: 1500,
              showConfirmButton: false
            })
            loadSchedule()
          } else {
            throw new Error(response.data.message)
          }
        } catch (error) {
          console.error('Error deleting schedule:', error)
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete schedule'
          })
        }
      }
    }

    const resetForm = () => {
      scheduleForm.value = {
        course_id: '',
        section_id: '',
        year_level: '',
        room_id: '',
        day_of_week: '',
        time_start: '',
        time_end: ''
      }
    }

    onMounted(() => {
      fetchFacultyList()
      nextTick(() => {
        initModal()
      })
    })

    onBeforeUnmount(() => {
      if (modalInstance) {
        modalInstance.dispose()
        modalInstance = null
      }
    })

    return {
      loading,
      saving,
      error,
      facultyList,
      selectedFaculty,
      selectedSemester,
      selectedYear,
      schedules,
      facultyInfo,
      totalHours,
      days,
      timeSlots,
      courses,
      sections,
      rooms,
      scheduleForm,
      modalElement,
      openAddModal,
      loadSchedule,
      getScheduleForTimeSlot,
      saveSchedule,
      deleteSchedule
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
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border-radius: 20px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 30px rgba(243, 156, 18, 0.3);
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
  border-radius: 16px;
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
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 55px;
  height: 55px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
}

.bg-primary-light {
  background: rgba(52, 152, 219, 0.12);
  color: #3498db;
}

.bg-success-light {
  background: rgba(46, 204, 113, 0.12);
  color: #27ae60;
}

.bg-info-light {
  background: rgba(52, 152, 219, 0.12);
  color: #3498db;
}

.bg-warning-light {
  background: rgba(241, 196, 15, 0.12);
  color: #f39c12;
}

.stat-content {
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
}

/* Filters Card */
.filters-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 25px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  border: 1px solid #f0f0f0;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e9ecef;
}

.filters-title {
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
}

.filter-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #4a5568;
}

.filter-select {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s;
  background: white;
}

.filter-select:focus {
  border-color: #f39c12;
  outline: none;
  box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
}

.btn-load {
  width: 100%;
  padding: 10px 12px;
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border: none;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-load:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
}

/* Schedule Container */
.schedule-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #f0f0f0;
  overflow: hidden;
}

.schedule-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: #f8fafc;
  border-bottom: 2px solid #e9ecef;
  flex-wrap: wrap;
  gap: 15px;
}

.faculty-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.faculty-info i {
  font-size: 1.5rem;
  color: #f39c12;
}

.faculty-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2d3748;
}

.faculty-detail {
  font-size: 0.85rem;
  color: #718096;
  padding-left: 10px;
  border-left: 2px solid #e2e8f0;
}

.btn-add-schedule {
  padding: 8px 20px;
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border: none;
  border-radius: 10px;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-add-schedule:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
}

/* Schedule Table */
.schedule-table {
  width: 100%;
  border-collapse: collapse;
}

.schedule-table th {
  background: #f8fafc;
  padding: 12px;
  font-weight: 600;
  color: #2d3748;
  text-align: center;
  border: 1px solid #e2e8f0;
}

.schedule-table td {
  border: 1px solid #e2e8f0;
  vertical-align: top;
  padding: 8px;
}

.time-column {
  width: 100px;
  background: #f8fafc;
}

.time-slot {
  font-weight: 600;
  color: #4a5568;
  text-align: center;
  background: #f8fafc;
  padding: 12px 8px;
}

.schedule-cell {
  height: 100px;
  min-width: 150px;
}

/* Schedule Card */
.schedule-card {
  background: #3498db;
  color: white;
  padding: 8px;
  border-radius: 8px;
  font-size: 0.8rem;
  position: relative;
  transition: all 0.3s;
  margin-bottom: 5px;
}

.schedule-card:hover {
  transform: scale(1.02);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.schedule-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.schedule-success {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.schedule-info {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.schedule-warning {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.schedule-danger {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.schedule-code {
  font-weight: 700;
  font-size: 0.85rem;
}

.schedule-name {
  font-size: 0.7rem;
  opacity: 0.9;
  margin: 2px 0;
}

.schedule-meta {
  display: flex;
  gap: 5px;
  margin-top: 5px;
  flex-wrap: wrap;
}

.badge-section, .badge-room, .badge-year {
  background: rgba(255, 255, 255, 0.2);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 500;
}

.btn-schedule-delete {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.3s;
}

.schedule-card:hover .btn-schedule-delete {
  opacity: 1;
}

.btn-schedule-delete:hover {
  background: rgba(255, 255, 255, 0.3);
}

.empty-cell {
  height: 100%;
  min-height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-dot {
  width: 6px;
  height: 6px;
  background: #e2e8f0;
  border-radius: 50%;
  opacity: 0.5;
}

.no-schedule-message {
  text-align: center;
  padding: 40px 20px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.no-schedule-message i {
  font-size: 2.5rem;
  color: #cbd5e0;
  margin-bottom: 10px;
}

.no-schedule-message p {
  color: #718096;
  margin-bottom: 15px;
}

.btn-add-schedule-small {
  padding: 8px 20px;
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-add-schedule-small:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(243, 156, 18, 0.3);
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 16px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 3px solid #e2e8f0;
  border-top-color: #f39c12;
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
  border-radius: 16px;
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
  background: #f39c12;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-retry:hover {
  background: #e67e22;
  transform: translateY(-2px);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 16px;
}

.empty-state i {
  font-size: 4rem;
  color: #cbd5e0;
  margin-bottom: 20px;
}

.empty-state h4 {
  color: #2d3748;
  margin-bottom: 10px;
  font-size: 1.3rem;
}

.empty-state p {
  color: #718096;
  margin-bottom: 0;
}

/* Modal */
.custom-modal {
  border-radius: 20px;
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.custom-modal .modal-header {
  background: linear-gradient(135deg, #f39c12, #e67e22);
  color: white;
  border: none;
  padding: 20px 25px;
}

.custom-modal .modal-header .btn-close {
  filter: brightness(0) invert(1);
}

.custom-modal .modal-body {
  padding: 25px;
}

.form-label {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s;
}

.form-control:focus {
  border-color: #f39c12;
  outline: none;
  box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.1);
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
@media (max-width: 992px) {
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

  .schedule-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .faculty-info {
    flex-direction: column;
    align-items: flex-start;
  }

  .faculty-detail {
    padding-left: 0;
    border-left: none;
  }

  .schedule-table {
    font-size: 0.8rem;
  }

  .schedule-table th,
  .schedule-table td {
    padding: 4px;
  }

  .time-slot {
    font-size: 0.7rem;
    padding: 4px;
  }

  .schedule-card {
    padding: 4px;
  }

  .schedule-code {
    font-size: 0.7rem;
  }

  .schedule-name {
    display: none;
  }
}
</style>