<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Syllabus Details'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading syllabus details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchSyllabus">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Syllabus Details -->
        <template v-else>
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-file-text"></i>
              </div>
              <div>
                <div class="breadcrumb">
                  <router-link to="/admin/dashboard">Dashboard</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction">Instruction</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction/syllabus">Syllabus</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>Syllabus Details</span>
                </div>
                <h2 class="mb-0">{{ syllabus.title }}</h2>
              </div>
            </div>
            <div class="header-actions">
              <button class="btn btn-outline-secondary" @click="goBack">
                <i class="bi bi-arrow-left me-2"></i>
                Back
              </button>
              <button class="btn btn-warning" @click="editSyllabus">
                <i class="bi bi-pencil me-2"></i>
                Edit
              </button>
              <button class="btn btn-danger" @click="deleteSyllabus">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>
            </div>
          </div>

          <!-- Syllabus Content -->
          <div class="row">
            <div class="col-md-4 mb-4">
              <!-- Course Info Card -->
              <div class="info-card">
                <h5 class="card-title">
                  <i class="bi bi-book me-2 text-primary"></i>
                  Course Information
                </h5>
                <div class="info-item">
                  <span class="info-label">Course Code:</span>
                  <span class="info-value">{{ syllabus.course_code }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Course Name:</span>
                  <span class="info-value">{{ syllabus.course_name }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Units:</span>
                  <span class="info-value">{{ syllabus.units }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Department:</span>
                  <span class="info-value">{{ syllabus.department }}</span>
                </div>
              </div>

              <!-- Instructor Info Card -->
              <div class="info-card">
                <h5 class="card-title">
                  <i class="bi bi-person-badge me-2 text-primary"></i>
                  Instructor Information
                </h5>
                <div class="info-item">
                  <span class="info-label">Instructor:</span>
                  <span class="info-value">{{ syllabus.instructor_name || 'Not assigned' }}</span>
                </div>
                <div class="info-item" v-if="syllabus.instructor_email">
                  <span class="info-label">Email:</span>
                  <span class="info-value">
                    <a :href="'mailto:' + syllabus.instructor_email">{{ syllabus.instructor_email }}</a>
                  </span>
                </div>
              </div>

              <!-- Metadata Card -->
              <div class="info-card">
                <h5 class="card-title">
                  <i class="bi bi-info-circle me-2 text-primary"></i>
                  Metadata
                </h5>
                <div class="info-item">
                  <span class="info-label">Created:</span>
                  <span class="info-value">{{ formatDate(syllabus.created_at) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Last Updated:</span>
                  <span class="info-value">{{ formatDate(syllabus.updated_at) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Total Weeks:</span>
                  <span class="info-value">{{ syllabus.total_weeks || 0 }}</span>
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
                <p class="content-text">{{ syllabus.description || 'No description provided.' }}</p>
              </div>

              <!-- Objectives Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-bullseye me-2 text-primary"></i>
                  Course Objectives
                </h5>
                <div v-if="syllabus.objectives" class="content-text" v-html="formatText(syllabus.objectives)"></div>
                <p v-else class="text-muted">No objectives provided.</p>
              </div>

              <!-- Learning Outcomes Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-star me-2 text-primary"></i>
                  Learning Outcomes
                </h5>
                <div v-if="syllabus.learning_outcomes" class="content-text" v-html="formatText(syllabus.learning_outcomes)"></div>
                <p v-else class="text-muted">No learning outcomes provided.</p>
              </div>

              <!-- Grading System Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-pie-chart me-2 text-primary"></i>
                  Grading System
                </h5>
                <div v-if="syllabus.grading_system" class="content-text" v-html="formatText(syllabus.grading_system)"></div>
                <p v-else class="text-muted">No grading system provided.</p>
              </div>

              <!-- Policies Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-shield-check me-2 text-primary"></i>
                  Course Policies
                </h5>
                <div v-if="syllabus.policies" class="content-text" v-html="formatText(syllabus.policies)"></div>
                <p v-else class="text-muted">No policies provided.</p>
              </div>

              <!-- Reference Materials Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-bookmarks me-2 text-primary"></i>
                  Reference Materials
                </h5>
                <div v-if="syllabus.reference_materials" class="content-text" v-html="formatText(syllabus.reference_materials)"></div>
                <p v-else class="text-muted">No reference materials provided.</p>
              </div>

              <!-- Lessons Card -->
              <div class="content-card">
                <h5 class="card-title">
                  <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>
                  Weekly Lessons
                </h5>
                
                <div v-if="syllabus.lessons && syllabus.lessons.length > 0">
                  <div v-for="lesson in syllabus.lessons" :key="lesson.id" class="lesson-item">
                    <div class="lesson-header">
                      <span class="lesson-week">Week {{ lesson.week_number }}</span>
                      <span class="lesson-topic">{{ lesson.topic }}</span>
                    </div>
                    <div v-if="lesson.objectives" class="lesson-detail">
                      <strong>Objectives:</strong> {{ truncateText(lesson.objectives, 100) }}
                    </div>
                  </div>
                </div>
                <p v-else class="text-muted text-center py-3">No lessons added yet.</p>
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
  name: 'SyllabusView',
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
    const syllabus = ref({})

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchSyllabus = async () => {
      loading.value = true
      error.value = null

      try {
        const token = store.state.auth.token
        const syllabusId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/instruction/syllabus_view.php?id=${syllabusId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          syllabus.value = response.data.data
        }
      } catch (err) {
        console.error('Error fetching syllabus:', err)
        error.value = err.response?.data?.message || 'Failed to load syllabus'
        
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

    const formatText = (text) => {
      if (!text) return ''
      return text.replace(/\n/g, '<br>')
    }

    const truncateText = (text, length) => {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }

    const goBack = () => {
      router.push('/admin/instruction/syllabus')
    }

    const editSyllabus = () => {
      router.push(`/admin/instruction/syllabus/edit/${route.params.id}`)
    }

    const deleteSyllabus = async () => {
      const result = await Swal.fire({
        title: 'Delete Syllabus?',
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
          const response = await axios.delete(`${API_URL}/admin/instruction/syllabus_delete.php?id=${route.params.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.data.message || 'Syllabus has been deleted',
              timer: 1500,
              showConfirmButton: false
            })
            router.push('/admin/instruction/syllabus')
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete syllabus'
          })
        }
      }
    }

    onMounted(() => {
      fetchSyllabus()
    })

    return {
      loading,
      error,
      syllabus,
      formatDate,
      formatText,
      truncateText,
      goBack,
      editSyllabus,
      deleteSyllabus
    }
  }
}
</script>

<style scoped>
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

.card-title {
  font-size: 1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 15px;
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

.content-card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.content-text {
  line-height: 1.6;
  color: #2c3e50;
}

.lesson-item {
  padding: 15px;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  margin-bottom: 10px;
  transition: all 0.3s;
}

.lesson-item:hover {
  background: #f8f9fa;
  transform: translateX(5px);
}

.lesson-header {
  display: flex;
  gap: 15px;
  margin-bottom: 8px;
}

.lesson-week {
  font-weight: 600;
  color: #3498db;
  min-width: 80px;
}

.lesson-topic {
  font-weight: 500;
  color: #2c3e50;
}

.lesson-detail {
  font-size: 0.9rem;
  color: #7f8c8d;
  padding-left: 95px;
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

  .lesson-header {
    flex-direction: column;
    gap: 5px;
  }

  .lesson-detail {
    padding-left: 0;
  }
}
</style>