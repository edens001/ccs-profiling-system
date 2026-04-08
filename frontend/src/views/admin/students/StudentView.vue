<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Student Details'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading student details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchStudent">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Student Details Content -->
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
                  <router-link to="/admin/students">Students</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>Student Details</span>
                </div>
                <h4 class="mb-0">{{ student.full_name || 'Student' }}</h4>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary" @click="goBack">
                <i class="bi bi-arrow-left me-2"></i>
                Back
              </button>
              <button v-if="student.status === 'Pending'" class="btn btn-success" @click="approveStudent">
                <i class="bi bi-check-circle me-2"></i>
                Approve Student
              </button>
              <button class="btn btn-warning" @click="editStudent">
                <i class="bi bi-pencil me-2"></i>
                Edit
              </button>
              <button class="btn btn-danger" @click="deleteStudent">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>
            </div>
          </div>

          <div class="row">
            <!-- Left Column - Profile Card -->
            <div class="col-lg-4 mb-4">
              <div class="profile-card">
                <div class="profile-cover" :class="'bg-gradient-' + getStatusColor(student.status)">
                  <div class="profile-avatar-wrapper">
                    <div class="profile-avatar">
                      {{ getUserInitials(student.first_name, student.last_name) }}
                    </div>
                  </div>
                </div>
                
                <div class="profile-body">
                  <div class="text-center mb-4">
                    <h4 class="mb-1">{{ student.full_name }}</h4>
                    <p class="text-muted mb-2">
                      <i class="bi bi-person-badge me-1"></i>
                      {{ student.student_number }}
                    </p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-3">
                      <span class="badge-custom" :class="'badge-' + getStatusColor(student.status)">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                        {{ student.status }}
                      </span>
                      <span class="badge-custom badge-info">
                        {{ student.year_level }}{{ student.section }}
                      </span>
                    </div>
                  </div>

                  <!-- Quick Stats -->
                  <div class="stats-grid">
                    <div class="stat-item">
                      <div class="stat-icon bg-primary-light">
                        <i class="bi bi-calendar text-primary"></i>
                      </div>
                      <div class="stat-content">
                        <div class="stat-value">{{ calculateAge(student.birth_date) }}</div>
                        <div class="stat-label">Age</div>
                      </div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-icon bg-success-light">
                        <i class="bi bi-gender-{{ student.gender === 'Male' ? 'male' : 'female' }} text-success"></i>
                      </div>
                      <div class="stat-content">
                        <div class="stat-value">{{ student.gender }}</div>
                        <div class="stat-label">Gender</div>
                      </div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-icon bg-info-light">
                        <i class="bi bi-book text-info"></i>
                      </div>
                      <div class="stat-content">
                        <div class="stat-value">{{ gradeSummary.total_subjects || 0 }}</div>
                        <div class="stat-label">Subjects</div>
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
                            <a :href="'mailto:' + student.email" class="text-decoration-none">
                              {{ student.email }}
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
                            <a :href="'tel:' + student.contact_number" class="text-decoration-none">
                              {{ student.contact_number }}
                              <i class="bi bi-telephone-forward ms-1 small"></i>
                            </a>
                          </div>
                        </div>
                      </div>
                      <div class="info-row">
                        <div class="info-icon">
                          <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="info-content">
                          <div class="info-label">Address</div>
                          <div class="info-value">{{ student.address || 'No address provided' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Right Column - Detailed Information -->
            <div class="col-lg-8 mb-4">
              <!-- Personal Information Card -->
              <div class="detail-card mb-4">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-primary-light me-3">
                      <i class="bi bi-person text-primary"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Personal Information</h5>
                    </div>
                  </div>
                </div>
                <div class="detail-card-body">
                  <div class="info-grid">
                    <div class="info-grid-item">
                      <span class="info-label">Full Name</span>
                      <span class="info-value">{{ student.full_name }}</span>
                    </div>
                    <div class="info-grid-item">
                      <span class="info-label">Birth Date</span>
                      <span class="info-value">{{ formatDate(student.birth_date) }}</span>
                    </div>
                    <div class="info-grid-item">
                      <span class="info-label">Gender</span>
                      <span class="info-value">{{ student.gender }}</span>
                    </div>
                    <div class="info-grid-item">
                      <span class="info-label">Age</span>
                      <span class="info-value">{{ calculateAge(student.birth_date) }} years old</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Academic Information Card -->
              <div class="detail-card mb-4">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-success-light me-3">
                      <i class="bi bi-book text-success"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Academic Information</h5>
                    </div>
                  </div>
                </div>
                <div class="detail-card-body">
                  <div class="info-grid">
                    <div class="info-grid-item">
                      <span class="info-label">Course</span>
                      <span class="info-value fw-bold">{{ student.course_name }}</span>
                    </div>
                    <div class="info-grid-item">
                      <span class="info-label">Course Code</span>
                      <span class="info-value">{{ student.course_code || 'N/A' }}</span>
                    </div>
                    <div class="info-grid-item">
                      <span class="info-label">Year & Section</span>
                      <span class="info-value">{{ student.year_level }}{{ student.section }}</span>
                    </div>
                    <div class="info-grid-item">
                      <span class="info-label">Username</span>
                      <span class="info-value">{{ student.username }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Guardian Information Card -->
              <div class="detail-card mb-4">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-warning-light me-3">
                      <i class="bi bi-people text-warning"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Guardian Information</h5>
                    </div>
                  </div>
                </div>
                <div class="detail-card-body">
                  <div class="info-grid">
                    <div class="info-grid-item">
                      <span class="info-label">Guardian Name</span>
                      <span class="info-value">{{ student.guardian_name || 'Not specified' }}</span>
                    </div>
                    <div class="info-grid-item">
                      <span class="info-label">Guardian Contact</span>
                      <span class="info-value">{{ student.guardian_contact || 'Not specified' }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Grade Summary Card -->
              <div class="detail-card mb-4">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-info-light me-3">
                      <i class="bi bi-clipboard-data text-info"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Grade Summary</h5>
                    </div>
                  </div>
                  <span class="badge-custom badge-primary">{{ gradeSummary.total_subjects }} Subjects</span>
                </div>
                <div class="detail-card-body">
                  <div class="grade-stats">
                    <div class="grade-stat-item">
                      <span class="grade-stat-label">Average Grade</span>
                      <span class="grade-stat-value">{{ gradeSummary.average_grade }}</span>
                    </div>
                    <div class="grade-stat-item">
                      <span class="grade-stat-label">Passed</span>
                      <span class="grade-stat-value text-success">{{ gradeSummary.passed }}</span>
                    </div>
                    <div class="grade-stat-item">
                      <span class="grade-stat-label">Failed</span>
                      <span class="grade-stat-value text-danger">{{ gradeSummary.failed }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Current Enrollment Card (FIXED) -->
              <div class="detail-card">
                <div class="detail-card-header">
                  <div class="d-flex align-items-center">
                    <div class="header-icon bg-primary-light me-3">
                      <i class="bi bi-calendar-check text-primary"></i>
                    </div>
                    <div>
                      <h5 class="mb-0">Current Enrollment</h5>
                    </div>
                  </div>
                  <span class="badge-custom badge-primary">{{ enrollments.length }} Subjects</span>
                </div>
                
                <div class="detail-card-body p-0">
                  <div v-if="enrollments.length === 0" class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="text-muted">No enrolled subjects</p>
                  </div>
                  
                  <div v-else class="table-responsive">
                    <table class="table table-hover mb-0">
                      <thead>
                        <tr>
                          <th>Course Code</th>
                          <th>Description</th>
                          <th>Schedule</th>
                          <th>Room</th>
                          <th>Instructor</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="subject in enrollments" :key="subject.id">
                          <td><span class="fw-bold">{{ subject.course_code }}</span></td>
                          <td>{{ subject.course_name }}</td>
                          <td>{{ subject.schedule }}</td>
                          <td>{{ subject.room }}</td>
                          <td>{{ subject.instructor }}</td>
                        </tr>
                      </tbody>
                    </table>
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
  name: 'StudentView',
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
    const student = ref({})
    const enrollments = ref([])
    const gradeSummary = ref({
      total_subjects: 0,
      average_grade: 'N/A',
      passed: 0,
      failed: 0
    })
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchStudent = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        const studentId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/students/view.php?id=${studentId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          student.value = response.data.data
          enrollments.value = response.data.data.enrollments || []
          gradeSummary.value = response.data.data.grade_summary || {
            total_subjects: 0,
            average_grade: 'N/A',
            passed: 0,
            failed: 0
          }
        }
      } catch (err) {
        console.error('Error fetching student:', err)
        if (err.response) {
          error.value = err.response.data?.message || 'Failed to fetch student data'
          if (err.response.status === 401) {
            await store.dispatch('auth/logout')
            router.push('/admin/login')
          }
        } else if (err.request) {
          error.value = 'Cannot connect to server'
        } else {
          error.value = err.message
        }
      } finally {
        loading.value = false
      }
    }
    
    const approveStudent = async () => {
      const result = await Swal.fire({
        title: 'Approve Student?',
        text: `Approve ${student.value.full_name} for enrollment?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#e74c3c',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
      })

      if (result.isConfirmed) {
        try {
          const token = store.state.auth.token
          const response = await axios.post(`${API_URL}/admin/students/approve.php`, {
            student_id: route.params.id
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
          
          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Approved!',
              text: 'Student has been approved for enrollment',
              timer: 1500,
              showConfirmButton: false
            })
            fetchStudent()
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to approve student'
          })
        }
      }
    }
    
    const getUserInitials = (first, last) => {
      if (!first || !last) return 'S'
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
    
    const calculateAge = (birthDate) => {
      if (!birthDate) return 'N/A'
      const today = new Date()
      const birth = new Date(birthDate)
      let age = today.getFullYear() - birth.getFullYear()
      const monthDiff = today.getMonth() - birth.getMonth()
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--
      }
      
      return age
    }
    
    const getStatusColor = (status) => {
      const colors = {
        'Active': 'success',
        'Inactive': 'danger',
        'Graduated': 'info',
        'Dropped': 'warning',
        'Pending': 'warning'
      }
      return colors[status] || 'secondary'
    }
    
    const goBack = () => {
      router.push('/admin/students')
    }
    
    const editStudent = () => {
      router.push(`/admin/students/edit/${route.params.id}`)
    }
    
    const deleteStudent = async () => {
      const result = await Swal.fire({
        title: 'Delete Student?',
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
          const response = await axios.delete(`${API_URL}/admin/students/delete.php?id=${route.params.id}`, {
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
            router.push('/admin/students')
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete student'
          })
        }
      }
    }
    
    onMounted(() => {
      fetchStudent()
    })
    
    return {
      loading,
      error,
      student,
      enrollments,
      gradeSummary,
      getUserInitials,
      formatDate,
      calculateAge,
      getStatusColor,
      goBack,
      editStudent,
      deleteStudent,
      approveStudent
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

.bg-gradient-info {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.bg-gradient-warning {
  background: linear-gradient(135deg, #f39c12, #e67e22);
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
  margin-bottom: 20px;
}

.detail-card:last-child {
  margin-bottom: 0;
}

.detail-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
}

.detail-card-body {
  padding: 20px;
}

/* ===== INFO GRID ===== */
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.info-grid-item {
  display: flex;
  flex-direction: column;
}

/* ===== GRADE STATS ===== */
.grade-stats {
  display: flex;
  gap: 30px;
  justify-content: space-around;
}

.grade-stat-item {
  text-align: center;
}

.grade-stat-label {
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.grade-stat-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
}

/* ===== TABLE STYLES ===== */
.table {
  margin-bottom: 0;
}

.table th {
  background: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.85rem;
  border-bottom: 2px solid #e9ecef;
  padding: 12px 15px;
  white-space: nowrap;
}

.table td {
  padding: 12px 15px;
  vertical-align: middle;
  border-bottom: 1px solid #e9ecef;
  font-size: 0.95rem;
}

.table-hover tbody tr:hover {
  background-color: #f8f9fa;
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

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .info-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .grade-stats {
    flex-direction: column;
    gap: 15px;
  }

  .table {
    font-size: 0.85rem;
  }

  .table th, .table td {
    padding: 10px;
    white-space: nowrap;
  }
}
</style>