<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Lesson Details'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading lesson details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchLesson">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Lesson Details -->
        <template v-else>
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
              <div>
                <div class="breadcrumb">
                  <router-link to="/admin/dashboard">Dashboard</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction">Instruction</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction/lessons">Lessons</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>Lesson Details</span>
                </div>
                <h2 class="mb-0">Week {{ lesson.week_number }}: {{ lesson.topic }}</h2>
                <p class="text-muted mb-0">{{ lesson.course_code }} - {{ lesson.course_name }}</p>
              </div>
            </div>
            <div class="header-actions">
              <button class="btn btn-outline-secondary" @click="goBack">
                <i class="bi bi-arrow-left me-2"></i>
                Back
              </button>
              <button class="btn btn-warning" @click="editLesson">
                <i class="bi bi-pencil me-2"></i>
                Edit
              </button>
              <button class="btn btn-danger" @click="deleteLesson">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>
            </div>
          </div>

          <!-- Lesson Content -->
          <div class="row">
            <div class="col-md-8">
              <!-- Objectives Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-bullseye me-2 text-primary"></i>
                  Learning Objectives
                </h5>
                <div v-if="lesson.objectives" class="content-text" v-html="formatText(lesson.objectives)"></div>
                <p v-else class="text-muted">No objectives specified.</p>
              </div>

              <!-- Activities Card -->
              <div class="content-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-activity me-2 text-primary"></i>
                  Activities
                </h5>
                <div v-if="lesson.activities" class="content-text" v-html="formatText(lesson.activities)"></div>
                <p v-else class="text-muted">No activities specified.</p>
              </div>

              <!-- Resources Card -->
              <div class="content-card">
                <h5 class="card-title">
                  <i class="bi bi-link me-2 text-primary"></i>
                  Resources
                </h5>
                <div v-if="lesson.resources" class="content-text" v-html="formatText(lesson.resources)"></div>
                <p v-else class="text-muted">No resources specified.</p>
              </div>
            </div>

            <div class="col-md-4">
              <!-- Lesson Info Card -->
              <div class="info-card mb-4">
                <h5 class="card-title">
                  <i class="bi bi-info-circle me-2 text-primary"></i>
                  Lesson Information
                </h5>
                <div class="info-item">
                  <span class="info-label">Week Number:</span>
                  <span class="info-value">{{ lesson.week_number }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Topic:</span>
                  <span class="info-value">{{ lesson.topic }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Course:</span>
                  <span class="info-value">{{ lesson.course_code }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Syllabus:</span>
                  <span class="info-value">{{ lesson.syllabus_title || 'N/A' }}</span>
                </div>
              </div>

              <!-- Metadata Card -->
              <div class="info-card">
                <h5 class="card-title">
                  <i class="bi bi-calendar me-2 text-primary"></i>
                  Metadata
                </h5>
                <div class="info-item">
                  <span class="info-label">Created By:</span>
                  <span class="info-value">{{ lesson.created_by_name || 'System' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Created At:</span>
                  <span class="info-value">{{ formatDate(lesson.created_at) }}</span>
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
  name: 'LessonView',
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
    const lesson = ref({})

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchLesson = async () => {
      loading.value = true
      error.value = null

      try {
        const token = store.state.auth.token
        const lessonId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/instruction/lessons_view.php?id=${lessonId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          lesson.value = response.data.data
        }
      } catch (err) {
        console.error('Error fetching lesson:', err)
        error.value = err.response?.data?.message || 'Failed to load lesson'
        
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

    const goBack = () => {
      router.push('/admin/instruction/lessons')
    }

    const editLesson = () => {
      router.push(`/admin/instruction/lessons/edit/${route.params.id}`)
    }

    const deleteLesson = async () => {
      const result = await Swal.fire({
        title: 'Delete Lesson?',
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
          const response = await axios.delete(`${API_URL}/admin/instruction/lessons_delete.php?id=${route.params.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Lesson has been deleted',
              timer: 1500,
              showConfirmButton: false
            })
            router.push('/admin/instruction/lessons')
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete lesson'
          })
        }
      }
    }

    onMounted(() => {
      fetchLesson()
    })

    return {
      loading,
      error,
      lesson,
      formatDate,
      formatText,
      goBack,
      editLesson,
      deleteLesson
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

.content-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.info-card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 20px;
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
  padding: 10px 0;
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
  line-height: 1.8;
  color: #2c3e50;
}

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-outline-secondary {
  border: 1px solid #e9ecef;
  color: #6c757d;
}

.btn-outline-secondary:hover {
  background: #f8f9fa;
  border-color: #3498db;
  color: #3498db;
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
}
</style>