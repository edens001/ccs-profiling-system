<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Faculty Details'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading faculty details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchFaculty">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Faculty Details Content -->
        <template v-else>
          <!-- Header with Breadcrumb -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon bg-primary-light">
                <i class="bi bi-person-badge-fill text-primary"></i>
              </div>
              <div>
                <div class="breadcrumb">
                  <router-link to="/admin/dashboard">Dashboard</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/faculty">Faculty</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>Faculty Details</span>
                </div>
                <h4 class="mb-0">{{ faculty.full_name || 'Faculty Member' }}</h4>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary" @click="goBack">
                <i class="bi bi-arrow-left me-2"></i>
                Back
              </button>
              <button class="btn btn-warning" @click="editFaculty">
                <i class="bi bi-pencil me-2"></i>
                Edit
              </button>
              <button class="btn btn-danger" @click="deleteFaculty">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>
            </div>
          </div>

          <div class="row">
            <!-- Left Column - Profile Card -->
            <div class="col-lg-4 mb-4">
              <div class="profile-card">
                <div class="profile-cover" :class="'bg-gradient-' + getStatusColor(faculty.status)">
                  <div class="profile-avatar-wrapper">
                    <div class="profile-avatar">
                      {{ getUserInitials(faculty.first_name, faculty.last_name) }}
                    </div>
                  </div>
                </div>
                
                <div class="profile-body">
                  <div class="text-center mb-4">
                    <h4 class="mb-1">{{ faculty.full_name }}</h4>
                    <p class="text-muted mb-2">
                      <i class="bi bi-person-badge me-1"></i>
                      {{ faculty.faculty_number }}
                    </p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-3">
                      <span class="badge-custom" :class="'badge-' + getStatusColor(faculty.status)">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                        {{ faculty.status }}
                      </span>
                      <span class="badge-custom" :class="'badge-' + getEmploymentBadgeColor(faculty.employment_status)">
                        <i :class="getEmploymentIcon(faculty.employment_status)" class="me-1"></i>
                        {{ faculty.employment_status }}
                      </span>
                    </div>
                  </div>

                  <!-- Quick Stats -->
                  <div class="stats-grid">
                    <div class="stat-item">
                      <div class="stat-icon bg-primary-light">
                        <i class="bi bi-book text-primary"></i>
                      </div>
                      <div class="stat-content">
                        <div class="stat-value">{{ faculty.total_courses || 0 }}</div>
                        <div class="stat-label">Courses</div>
                      </div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-icon bg-success-light">
                        <i class="bi bi-people text-success"></i>
                      </div>
                      <div class="stat-content">
                        <div class="stat-value">{{ faculty.total_students || 0 }}</div>
                        <div class="stat-label">Students</div>
                      </div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-icon bg-info-light">
                        <i class="bi bi-journal-text text-info"></i>
                      </div>
                      <div class="stat-content">
                        <div class="stat-value">{{ faculty.publications_count || 0 }}</div>
                        <div class="stat-label">Publications</div>
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">

                  <!-- Contact Information -->
                  <div class="info-section">
                    <h6 class="section-subtitle">
                      <i class="bi bi-envelope-paper me-2 text-primary"></i>
                      Contact Information
                    </h6>
                    <div class="info-list">
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-envelope"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Email Address</div>
                          <div class="info-value">
                            <a :href="'mailto:' + faculty.email" class="text-decoration-none">
                              {{ faculty.email }}
                              <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                            </a>
                          </div>
                        </div>
                      </div>
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-telephone"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Contact Number</div>
                          <div class="info-value">
                            <a :href="'tel:' + faculty.contact_number" class="text-decoration-none">
                              {{ faculty.contact_number }}
                              <i class="bi bi-telephone-forward ms-1 small"></i>
                            </a>
                          </div>
                        </div>
                      </div>
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-person"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Username</div>
                          <div class="info-value">{{ faculty.username }}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">

                  <!-- Department & Designation -->
                  <div class="info-section">
                    <h6 class="section-subtitle">
                      <i class="bi bi-briefcase me-2 text-primary"></i>
                      Professional Information
                    </h6>
                    <div class="info-list">
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-building"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Department</div>
                          <div class="info-value">{{ faculty.department }}</div>
                        </div>
                      </div>
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-person-workspace"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Designation</div>
                          <div class="info-value">{{ faculty.designation }}</div>
                        </div>
                      </div>
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-stars"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Specialization</div>
                          <div class="info-value">{{ faculty.specialization || 'Not specified' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">

                  <!-- Employment Details -->
                  <div class="info-section">
                    <h6 class="section-subtitle">
                      <i class="bi bi-calendar2-week me-2 text-primary"></i>
                      Employment Details
                    </h6>
                    <div class="info-list">
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-calendar-plus"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Date Hired</div>
                          <div class="info-value">{{ formatDate(faculty.date_hired) }}</div>
                        </div>
                      </div>
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Years of Service</div>
                          <div class="info-value">{{ calculateYearsOfService(faculty.date_hired) }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Right Column - Detailed Information -->
            <div class="col-lg-8 mb-4">
              <!-- Educational Background Card -->
              <div class="detail-card mb-4">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-success-light me-3">
                      <i class="bi bi-mortarboard text-success"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Educational Background</h5>
                      <p class="text-muted small mb-0">Academic qualifications and degrees</p>
                    </div>
                  </div>
                </div>
                
                <div class="detail-card-body">
                  <div v-if="!faculty.education" class="empty-state">
                    <i class="bi bi-journal-x"></i>
                    <h6>No Educational Records</h6>
                    <p class="text-muted">This faculty member has no educational background recorded.</p>
                  </div>
                  
                  <div v-else class="education-timeline">
                    <!-- Tertiary Education -->
                    <div v-if="faculty.education.tertiary?.degree" class="education-item">
                      <div class="education-icon bg-primary-light">
                        <i class="bi bi-book text-primary"></i>
                      </div>
                      <div class="education-content">
                        <div class="education-header">
                          <h6>Tertiary Education</h6>
                          <span class="education-year">{{ faculty.education.tertiary.year || 'Year not specified' }}</span>
                        </div>
                        <h5 class="education-degree">{{ faculty.education.tertiary.degree }}</h5>
                        <p class="education-school">{{ faculty.education.tertiary.school || 'School not specified' }}</p>
                      </div>
                    </div>

                    <!-- Graduate Studies -->
                    <div v-if="faculty.education.graduate?.degree" class="education-item">
                      <div class="education-icon bg-success-light">
                        <i class="bi bi-bookmark-star text-success"></i>
                      </div>
                      <div class="education-content">
                        <div class="education-header">
                          <h6>Graduate Studies</h6>
                          <span class="education-year">{{ faculty.education.graduate.year || 'Year not specified' }}</span>
                        </div>
                        <h5 class="education-degree">{{ faculty.education.graduate.degree }}</h5>
                        <p class="education-school">{{ faculty.education.graduate.school || 'School not specified' }}</p>
                      </div>
                    </div>

                    <!-- Doctorate Degree -->
                    <div v-if="faculty.education.doctorate?.degree" class="education-item">
                      <div class="education-icon bg-warning-light">
                        <i class="bi bi-award text-warning"></i>
                      </div>
                      <div class="education-content">
                        <div class="education-header">
                          <h6>Doctorate Degree</h6>
                          <span class="education-year">{{ faculty.education.doctorate.year || 'Year not specified' }}</span>
                        </div>
                        <h5 class="education-degree">{{ faculty.education.doctorate.degree }}</h5>
                        <p class="education-school">{{ faculty.education.doctorate.school || 'School not specified' }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Schedule Information Card -->
              <div class="detail-card mb-4">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-primary-light me-3">
                      <i class="bi bi-calendar-week text-primary"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Current Class Schedule</h5>
                      <p class="text-muted small mb-0">Faculty's assigned classes for current semester</p>
                    </div>
                  </div>
                  <span class="badge-custom badge-primary">
                    {{ faculty.schedules?.length || 0 }} Classes
                  </span>
                </div>
                
                <div class="detail-card-body p-0">
                  <div v-if="!faculty.schedules || faculty.schedules.length === 0" class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h6>No Scheduled Classes</h6>
                    <p class="text-muted">This faculty member has no class assignments for the current semester.</p>
                  </div>
                  
                  <div v-else class="schedule-timeline">
                    <div v-for="(schedule, index) in faculty.schedules" :key="schedule.id" 
                         class="schedule-item" :style="{ animationDelay: index * 0.1 + 's' }">
                      <div class="schedule-time">
                        <div class="time-badge" :class="'bg-' + getDayColor(schedule.day)">
                          {{ schedule.day.substring(0,3) }}
                        </div>
                        <div class="time-detail">
                          <div class="time-range">{{ schedule.time_start }} - {{ schedule.time_end }}</div>
                          <div class="time-duration">{{ calculateDuration(schedule.time_start, schedule.time_end) }}</div>
                        </div>
                      </div>
                      <div class="schedule-content">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <h6 class="mb-1">
                              {{ schedule.course_code }} - {{ schedule.course_name }}
                            </h6>
                            <div class="schedule-meta">
                              <span class="meta-item">
                                <i class="bi bi-people"></i>
                                Section {{ schedule.section }}
                              </span>
                              <span class="meta-item">
                                <i class="bi bi-door-open"></i>
                                {{ schedule.room }}
                              </span>
                            </div>
                          </div>
                          <span class="badge-custom badge-info">{{ schedule.units }} units</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Research & Publications Card (Placeholder) -->
              <div class="detail-card">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-info-light me-3">
                      <i class="bi bi-journal-richtext text-info"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Research & Publications</h5>
                      <p class="text-muted small mb-0">Scholarly works and publications</p>
                    </div>
                  </div>
                </div>
                
                <div class="detail-card-body">
                  <div class="empty-state small">
                    <i class="bi bi-file-text"></i>
                    <p class="text-muted mb-0">No publications recorded.</p>
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
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultyView',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const route = useRoute()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    const faculty = ref({})
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchFaculty = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        const facultyId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/faculty/view.php?id=${facultyId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          faculty.value = response.data.data
          console.log('Faculty data:', faculty.value) // For debugging
        }
      } catch (err) {
        console.error('Error fetching faculty:', err)
        error.value = err.response?.data?.message || 'Failed to fetch faculty data'
      } finally {
        loading.value = false
      }
    }
    
    const getUserInitials = (first, last) => {
      if (!first || !last) return 'FM'
      return (first.charAt(0) + last.charAt(0)).toUpperCase()
    }
    
    const formatDate = (date) => {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
    
    const calculateYearsOfService = (dateHired) => {
      if (!dateHired) return 'N/A'
      const hired = new Date(dateHired)
      const now = new Date()
      const years = now.getFullYear() - hired.getFullYear()
      const months = now.getMonth() - hired.getMonth()
      
      if (months < 0 || (months === 0 && now.getDate() < hired.getDate())) {
        return years - 1 + ' years'
      }
      return years + ' years'
    }
    
    const calculateDuration = (start, end) => {
      if (!start || !end) return ''
      const startTime = new Date('1970-01-01T' + start)
      const endTime = new Date('1970-01-01T' + end)
      const diff = (endTime - startTime) / (1000 * 60 * 60)
      return diff + ' hours'
    }
    
    const getStatusColor = (status) => {
      return status === 'Active' ? 'success' : 'danger'
    }
    
    const getEmploymentBadgeColor = (status) => {
      const colors = {
        'Full-time': 'success',
        'Part-time': 'info',
        'Contractual': 'warning'
      }
      return colors[status] || 'secondary'
    }
    
    const getEmploymentIcon = (status) => {
      const icons = {
        'Full-time': 'bi bi-briefcase-fill',
        'Part-time': 'bi bi-clock',
        'Contractual': 'bi bi-file-earmark-text'
      }
      return icons[status] || 'bi bi-person'
    }
    
    const getDayColor = (day) => {
      const colors = {
        'Monday': 'primary',
        'Tuesday': 'success',
        'Wednesday': 'info',
        'Thursday': 'warning',
        'Friday': 'danger',
        'Saturday': 'secondary'
      }
      return colors[day] || 'primary'
    }
    
    const goBack = () => {
      router.push('/admin/faculty')
    }
    
    const editFaculty = () => {
      router.push(`/admin/faculty/edit/${route.params.id}`)
    }
    
    const deleteFaculty = async () => {
      const result = await Swal.fire({
        title: 'Delete Faculty Member?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
      })
      
      if (result.isConfirmed) {
        try {
          const token = store.state.auth.token
          const response = await axios.delete(`${API_URL}/admin/faculty/delete.php?id=${route.params.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
          
          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.data.message,
              timer: 1500,
              showConfirmButton: false
            })
            router.push('/admin/faculty')
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete faculty member'
          })
        }
      }
    }
    
    onMounted(() => {
      fetchFaculty()
    })
    
    return {
      loading,
      error,
      faculty,
      getUserInitials,
      formatDate,
      calculateYearsOfService,
      calculateDuration,
      getStatusColor,
      getEmploymentBadgeColor,
      getEmploymentIcon,
      getDayColor,
      goBack,
      editFaculty,
      deleteFaculty
    }
  }
}
</script>

<style scoped>
/* ===== LAYOUT ===== */
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
  padding: 20px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
}

:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

.container-fluid {
  padding: 0;
  max-width: 1800px;
  margin: 0 auto;
}

/* ===== HEADER ===== */
.content-header {
  background: white;
  border-radius: 16px;
  padding: 20px 25px;
  margin-bottom: 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  animation: slideDown 0.3s ease;
}

.header-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  font-size: 1.5rem;
}

.bg-primary-light {
  background: rgba(52, 152, 219, 0.15);
}

.bg-success-light {
  background: rgba(46, 204, 113, 0.15);
}

.bg-info-light {
  background: rgba(52, 152, 219, 0.15);
}

.bg-warning-light {
  background: rgba(241, 196, 15, 0.15);
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 5px;
  font-size: 0.85rem;
}

.breadcrumb a {
  color: #6c757d;
  text-decoration: none;
  transition: color 0.3s;
}

.breadcrumb a:hover {
  color: #3498db;
}

.breadcrumb i {
  font-size: 0.7rem;
  color: #adb5bd;
}

.breadcrumb span {
  color: #2c3e50;
  font-weight: 500;
}

/* ===== PROFILE CARD ===== */
.profile-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  position: sticky;
  top: 20px;
  animation: slideInLeft 0.5s ease;
}

.profile-cover {
  height: 120px;
  position: relative;
}

.bg-gradient-success {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
}

.bg-gradient-danger {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.profile-avatar-wrapper {
  position: absolute;
  bottom: -50px;
  left: 50%;
  transform: translateX(-50%);
}

.profile-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: 600;
  color: #3498db;
  border: 4px solid white;
  box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.profile-body {
  padding: 70px 25px 25px;
}

/* ===== BADGES ===== */
.badge-custom {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.badge-success {
  background: rgba(46, 204, 113, 0.15);
  color: #27ae60;
}

.badge-danger {
  background: rgba(231, 76, 60, 0.15);
  color: #e74c3c;
}

.badge-info {
  background: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.badge-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
}

.badge-warning {
  background: rgba(241, 196, 15, 0.15);
  color: #f39c12;
}

/* ===== STATS GRID ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
  margin: 20px 0;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 12px;
  transition: all 0.3s;
}

.stat-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.75rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* ===== INFO SECTIONS ===== */
.section-subtitle {
  font-size: 0.9rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  gap: 12px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-icon {
  width: 32px;
  height: 32px;
  background: #f8f9fa;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3498db;
}

.info-content {
  flex: 1;
}

.info-label {
  font-size: 0.8rem;
  color: #7f8c8d;
  margin-bottom: 2px;
}

.info-value {
  font-size: 0.95rem;
  color: #2c3e50;
  font-weight: 500;
}

.info-value a {
  color: #3498db;
  transition: color 0.3s;
}

.info-value a:hover {
  color: #2980b9;
}

/* ===== DETAIL CARDS ===== */
.detail-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
  animation: slideInRight 0.5s ease;
}

.detail-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 25px;
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
}

.detail-card-body {
  padding: 25px;
}

/* ===== EDUCATION TIMELINE ===== */
.education-timeline {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.education-item {
  display: flex;
  gap: 15px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 12px;
  transition: all 0.3s;
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
  animation-fill-mode: forwards;
}

.education-item:hover {
  transform: translateX(5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.education-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.education-content {
  flex: 1;
}

.education-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 5px;
}

.education-header h6 {
  font-size: 0.85rem;
  color: #7f8c8d;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.education-year {
  font-size: 0.8rem;
  color: #3498db;
  font-weight: 500;
  padding: 2px 8px;
  background: rgba(52, 152, 219, 0.1);
  border-radius: 12px;
}

.education-degree {
  font-size: 1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 3px;
}

.education-school {
  font-size: 0.9rem;
  color: #7f8c8d;
  margin: 0;
}

/* ===== SCHEDULE TIMELINE ===== */
.schedule-timeline {
  display: flex;
  flex-direction: column;
}

.schedule-item {
  display: flex;
  padding: 20px 25px;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.3s;
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
  animation-fill-mode: forwards;
}

.schedule-item:hover {
  background: #f8f9fa;
  transform: translateX(5px);
}

.schedule-time {
  width: 150px;
  flex-shrink: 0;
}

.time-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-bottom: 5px;
}

.bg-primary { background: #3498db; color: white; }
.bg-success { background: #27ae60; color: white; }
.bg-info { background: #3498db; color: white; }
.bg-warning { background: #f39c12; color: white; }
.bg-danger { background: #e74c3c; color: white; }
.bg-secondary { background: #95a5a6; color: white; }

.time-detail {
  font-size: 0.9rem;
}

.time-range {
  font-weight: 600;
  color: #2c3e50;
}

.time-duration {
  font-size: 0.75rem;
  color: #7f8c8d;
}

.schedule-content {
  flex: 1;
  padding-left: 20px;
  border-left: 2px dashed #e0e0e0;
}

.schedule-meta {
  display: flex;
  gap: 15px;
  margin-top: 8px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.85rem;
  color: #7f8c8d;
}

.meta-item i {
  font-size: 0.9rem;
}

/* ===== EMPTY STATE ===== */
.empty-state {
  text-align: center;
  padding: 40px 20px;
}

.empty-state i {
  font-size: 3rem;
  color: #dee2e6;
  margin-bottom: 15px;
}

.empty-state.small i {
  font-size: 2rem;
}

.empty-state h6 {
  color: #2c3e50;
  margin-bottom: 5px;
}

.empty-state p {
  margin-bottom: 0;
}

/* ===== BUTTONS ===== */
.btn {
  padding: 8px 16px;
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.3s;
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

.btn-warning {
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border: none;
  color: white;
}

.btn-warning:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
}

.btn-danger {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  border: none;
  color: white;
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
}

/* ===== ANIMATIONS ===== */
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

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
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
@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .content-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .profile-card {
    position: static;
  }

  .schedule-item {
    flex-direction: column;
  }

  .schedule-time {
    width: 100%;
    margin-bottom: 10px;
  }

  .schedule-content {
    padding-left: 0;
    border-left: none;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .education-item {
    flex-direction: column;
  }

  .education-icon {
    margin-bottom: 10px;
  }

  .education-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }
}
</style>