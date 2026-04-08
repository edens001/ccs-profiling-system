<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Curriculum Details'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading curriculum details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchCurriculum">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Curriculum Details -->
        <template v-else>
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-diagram-3"></i>
              </div>
              <div>
                <div class="breadcrumb">
                  <router-link to="/admin/dashboard">Dashboard</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction">Instruction</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction/curriculum">Curriculum</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>Curriculum Details</span>
                </div>
                <h2 class="mb-0">{{ curriculum.curriculum_code }}</h2>
                <p class="text-muted mb-0">{{ curriculum.program }}</p>
              </div>
            </div>
            <div class="header-actions">
              <button class="btn btn-outline-secondary" @click="goBack">
                <i class="bi bi-arrow-left me-2"></i>
                Back
              </button>
              <button class="btn btn-warning" @click="editCurriculum">
                <i class="bi bi-pencil me-2"></i>
                Edit
              </button>
              <button class="btn btn-danger" @click="deleteCurriculum">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>
            </div>
          </div>

          <!-- Curriculum Info -->
          <div class="row">
            <div class="col-md-4 mb-4">
              <div class="info-card">
                <h5 class="card-title">
                  <i class="bi bi-info-circle me-2 text-primary"></i>
                  Curriculum Information
                </h5>
                <div class="info-item">
                  <span class="info-label">Program:</span>
                  <span class="info-value">{{ curriculum.program }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Curriculum Code:</span>
                  <span class="info-value">{{ curriculum.curriculum_code }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Effective Year:</span>
                  <span class="info-value">{{ curriculum.effective_year }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Total Units:</span>
                  <span class="info-value">{{ curriculum.total_units }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Status:</span>
                  <span class="info-value">
                    <span :class="'badge bg-' + getStatusColor(curriculum.status)">
                      {{ curriculum.status }}
                    </span>
                  </span>
                </div>
              </div>

              <div class="info-card">
                <h5 class="card-title">
                  <i class="bi bi-calendar me-2 text-primary"></i>
                  Metadata
                </h5>
                <div class="info-item">
                  <span class="info-label">Created By:</span>
                  <span class="info-value">{{ curriculum.created_by_name || 'System' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Created At:</span>
                  <span class="info-value">{{ formatDate(curriculum.created_at) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Last Updated:</span>
                  <span class="info-value">{{ formatDate(curriculum.updated_at) }}</span>
                </div>
              </div>

              <div class="info-card">
                <h5 class="card-title">
                  <i class="bi bi-book me-2 text-primary"></i>
                  Summary
                </h5>
                <div class="info-item">
                  <span class="info-label">Total Courses:</span>
                  <span class="info-value">{{ curriculum.total_courses || 0 }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Total Units:</span>
                  <span class="info-value">{{ curriculum.total_units }}</span>
                </div>
              </div>
            </div>

            <div class="col-md-8 mb-4">
              <!-- Description Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-card-text me-2 text-primary"></i>
                  Description
                </h5>
                <p class="content-text">{{ curriculum.description || 'No description provided.' }}</p>
              </div>

              <!-- Curriculum Structure -->
              <div class="content-card">
                <h5 class="card-title">
                  <i class="bi bi-list-columns me-2 text-primary"></i>
                  Curriculum Structure
                </h5>

                <div v-if="Object.keys(curriculum.grouped_courses || {}).length === 0" class="text-center py-4">
                  <i class="bi bi-inbox fs-1 text-muted"></i>
                  <p class="text-muted mt-2">No courses added to this curriculum yet.</p>
                </div>

                <div v-else>
                  <div v-for="(years, yearIndex) in curriculum.grouped_courses" :key="yearIndex" class="year-section">
                    <h6 class="year-title">Year {{ yearIndex }}</h6>
                    
                    <div v-for="(semesters, semIndex) in years" :key="semIndex" class="semester-section">
                      <h6 class="semester-title">Semester {{ semIndex }}</h6>
                      
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Units</th>
                            <th>Type</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="course in semesters" :key="course.id">
                            <td><strong>{{ course.course_code }}</strong></td>
                            <td>{{ course.course_name }}</td>
                            <td>{{ course.units }}</td>
                            <td>
                              <span v-if="course.is_elective" class="badge bg-warning">Elective</span>
                              <span v-else class="badge bg-primary">Core</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
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
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'CurriculumView',
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
    const curriculum = ref({})

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchCurriculum = async () => {
      loading.value = true
      error.value = null

      try {
        const token = store.state.auth.token
        const curriculumId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/instruction/curriculum_view.php?id=${curriculumId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          curriculum.value = response.data.data
        }
      } catch (err) {
        console.error('Error fetching curriculum:', err)
        error.value = err.response?.data?.message || 'Failed to load curriculum'
        
        if (err.response?.status === 401) {
          await store.dispatch('auth/logout')
          router.push('/admin/login')
        }
      } finally {
        loading.value = false
      }
    }

    const formatDate = (date) => {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getStatusColor = (status) => {
      const colors = {
        'Active': 'success',
        'Draft': 'warning',
        'Archived': 'secondary'
      }
      return colors[status] || 'secondary'
    }

    const goBack = () => {
      router.push('/admin/instruction/curriculum')
    }

    const editCurriculum = () => {
      router.push(`/admin/instruction/curriculum/edit/${route.params.id}`)
    }

    const deleteCurriculum = async () => {
      const result = await Swal.fire({
        title: 'Delete Curriculum?',
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
          const response = await axios.delete(`${API_URL}/admin/instruction/curriculum_delete.php?id=${route.params.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.data.message || 'Curriculum has been deleted',
              timer: 1500,
              showConfirmButton: false
            })
            router.push('/admin/instruction/curriculum')
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete curriculum'
          })
        }
      }
    }

    onMounted(() => {
      fetchCurriculum()
    })

    return {
      loading,
      error,
      curriculum,
      formatDate,
      getStatusColor,
      goBack,
      editCurriculum,
      deleteCurriculum
    }
  }
}
</script>

<style scoped>
/* Same styles as SyllabusView.vue */
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
  max-width: 1800px;
  margin: 0 auto;
}

.content-header {
  background: white;
  border-radius: 20px;
  padding: 20px 25px;
  margin-bottom: 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.header-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #3498db, #2980b9);
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20px;
}

.header-icon i {
  font-size: 2rem;
  color: white;
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
}

.breadcrumb i {
  font-size: 0.7rem;
  color: #adb5bd;
}

.breadcrumb span {
  color: #3498db;
  font-weight: 500;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.info-card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.content-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f0f0;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  color: #7f8c8d;
  font-size: 0.9rem;
}

.info-value {
  font-weight: 500;
  color: #2c3e50;
  text-align: right;
}

.content-text {
  line-height: 1.6;
  color: #2c3e50;
}

.year-section {
  margin-bottom: 25px;
}

.year-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #3498db;
  margin-bottom: 15px;
  padding-bottom: 5px;
  border-bottom: 2px solid #e9ecef;
}

.semester-section {
  margin-bottom: 20px;
  padding-left: 20px;
}

.semester-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 10px;
}

.table {
  font-size: 0.9rem;
}

.table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
}

.badge {
  padding: 5px 8px;
  font-size: 0.75rem;
}

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s;
}

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

  .info-item {
    flex-direction: column;
    gap: 5px;
  }

  .info-value {
    text-align: left;
  }

  .semester-section {
    padding-left: 0;
  }
}
</style>