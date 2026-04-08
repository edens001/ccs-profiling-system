<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Course Details'" />
      
      <div class="container-fluid p-4">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon bg-info">
                <i class="bi bi-eye-fill text-white"></i>
              </div>
              <div>
                <h2 class="mb-1">Course Details</h2>
                <p class="text-muted mb-0">View course information</p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-warning btn-lg" @click="editCourse">
                <i class="bi bi-pencil me-2"></i>
                Edit Course
              </button>
              <button class="btn btn-secondary btn-lg" @click="$router.back()">
                <i class="bi bi-arrow-left me-2"></i>
                Back
              </button>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading course details...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
            <div class="flex-grow-1">{{ error }}</div>
            <button class="btn btn-sm btn-outline-danger" @click="fetchCourse">
              <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
          </div>

          <!-- Course Details -->
          <div v-else class="course-details">
            <!-- Status Banner -->
            <div class="status-banner" :class="course.is_active ? 'bg-success' : 'bg-secondary'">
              <i class="bi" :class="course.is_active ? 'bi-check-circle-fill' : 'bi-dash-circle-fill'"></i>
              {{ course.is_active ? 'Active Course' : 'Inactive Course' }}
            </div>

            <div class="row">
              <div class="col-lg-8">
                <!-- Main Information -->
                <div class="details-section">
                  <h4 class="section-title">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i>
                    Basic Information
                  </h4>
                  
                  <div class="details-grid">
                    <div class="detail-item">
                      <div class="detail-label">Course Code</div>
                      <div class="detail-value">
                        <span class="badge bg-primary bg-opacity-10 text-primary p-3 fs-6">
                          {{ course.course_code }}
                        </span>
                      </div>
                    </div>

                    <div class="detail-item">
                      <div class="detail-label">Course Name</div>
                      <div class="detail-value fs-5 fw-semibold">{{ course.course_name }}</div>
                    </div>

                    <div class="detail-item">
                      <div class="detail-label">Department</div>
                      <div class="detail-value">{{ course.department }}</div>
                    </div>

                    <div class="detail-item">
                      <div class="detail-label">Total Units</div>
                      <div class="detail-value">
                        <span class="badge bg-info bg-opacity-10 text-info p-2">
                          {{ course.units }} units
                        </span>
                      </div>
                    </div>

                    <div class="detail-item full-width">
                      <div class="detail-label">Description</div>
                      <div class="detail-value">
                        <p class="mb-0">{{ course.description || 'No description provided' }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Statistics -->
                <div class="details-section">
                  <h4 class="section-title">
                    <i class="bi bi-graph-up text-success me-2"></i>
                    Statistics
                  </h4>
                  
                  <div class="stats-grid">
                    <div class="stat-box">
                      <div class="stat-icon bg-primary-light">
                        <i class="bi bi-file-text text-primary"></i>
                      </div>
                      <div class="stat-info">
                        <div class="stat-value">{{ course.syllabus_count || 0 }}</div>
                        <div class="stat-label">Syllabi</div>
                      </div>
                    </div>

                    <div class="stat-box">
                      <div class="stat-icon bg-success-light">
                        <i class="bi bi-people text-success"></i>
                      </div>
                      <div class="stat-info">
                        <div class="stat-value">{{ course.section_count || 0 }}</div>
                        <div class="stat-label">Sections</div>
                      </div>
                    </div>

                    <div class="stat-box">
                      <div class="stat-icon bg-info-light">
                        <i class="bi bi-clock-history text-info"></i>
                      </div>
                      <div class="stat-info">
                        <div class="stat-value">{{ totalHours }}</div>
                        <div class="stat-label">Total Hours</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-4">
                <!-- Metadata Card -->
                <div class="metadata-card">
                  <h5 class="metadata-title">
                    <i class="bi bi-clock-history me-2"></i>
                    Metadata
                  </h5>
                  
                  <div class="metadata-item">
                    <i class="bi bi-calendar3"></i>
                    <div>
                      <small>Created At</small>
                      <p class="mb-0">{{ formatDate(course.created_at) }}</p>
                    </div>
                  </div>

                  <div class="metadata-item">
                    <i class="bi bi-person"></i>
                    <div>
                      <small>Created By</small>
                      <p class="mb-0">{{ course.created_by || 'System' }}</p>
                    </div>
                  </div>

                  <div class="metadata-item">
                    <i class="bi bi-arrow-repeat"></i>
                    <div>
                      <small>Last Updated</small>
                      <p class="mb-0">{{ formatDate(course.updated_at) || 'Never' }}</p>
                    </div>
                  </div>

                  <div class="metadata-item">
                    <i class="bi bi-check-circle"></i>
                    <div>
                      <small>Status</small>
                      <p class="mb-0">
                        <span :class="'badge bg-' + (course.is_active ? 'success' : 'secondary')">
                          {{ course.is_active ? 'Active' : 'Inactive' }}
                        </span>
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="quick-actions-card">
                  <h5 class="quick-actions-title">
                    <i class="bi bi-lightning-charge me-2"></i>
                    Quick Actions
                  </h5>
                  
                  <button class="quick-action-btn" @click="viewSyllabi">
                    <i class="bi bi-file-text"></i>
                    <span>View Syllabi</span>
                  </button>

                  <button class="quick-action-btn" @click="viewSections">
                    <i class="bi bi-people"></i>
                    <span>View Sections</span>
                  </button>

                  <button class="quick-action-btn" @click="editCourse">
                    <i class="bi bi-pencil"></i>
                    <span>Edit Course</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'

export default {
  name: 'CourseView',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const store = useStore()
    const loading = ref(true)
    const error = ref(null)
    const course = ref({})

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const totalHours = computed(() => {
      const units = parseInt(course.value.units) || 0
      return units * 54 // Assuming 54 hours per unit
    })

    const fetchCourse = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        const courseId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/courses/get.php?id=${courseId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          course.value = response.data.data
        } else {
          throw new Error(response.data.message)
        }
      } catch (err) {
        console.error('Error fetching course:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to fetch course'
        
        // Fallback data
        course.value = {
          id: route.params.id,
          course_code: 'BSCS',
          course_name: 'BS Computer Science',
          department: 'CCS',
          units: 0,
          description: 'Sample course description',
          is_active: true,
          syllabus_count: 8,
          section_count: 4,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
          created_by: 'Admin User'
        }
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const editCourse = () => {
      router.push(`/admin/courses/edit/${route.params.id}`)
    }

    const viewSyllabi = () => {
      router.push(`/admin/instruction/syllabus?course_id=${route.params.id}`)
    }

    const viewSections = () => {
      router.push(`/admin/scheduling/sections?course_id=${route.params.id}`)
    }

    onMounted(() => {
      fetchCourse()
    })

    return {
      loading,
      error,
      course,
      totalHours,
      formatDate,
      editCourse,
      viewSyllabi,
      viewSections
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

.content-card {
  background: white;
  border-radius: 24px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.08);
  padding: 30px;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
}

.header-icon {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20px;
  font-size: 1.8rem;
}

.bg-info {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

/* Status Banner */
.status-banner {
  background: linear-gradient(135deg, #27ae60, #229954);
  color: white;
  padding: 15px 25px;
  border-radius: 12px;
  margin-bottom: 30px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
}

.status-banner.bg-success {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.status-banner.bg-secondary {
  background: linear-gradient(135deg, #95a5a6, #7f8c8d);
}

.status-banner i {
  font-size: 1.5rem;
}

/* Details Section */
.details-section {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 25px;
  border: 1px solid #e9ecef;
}

.section-title {
  color: #2c3e50;
  font-size: 1.2rem;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e9ecef;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.detail-item {
  margin-bottom: 15px;
}

.detail-item.full-width {
  grid-column: span 2;
}

.detail-label {
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  color: #2c3e50;
  font-size: 1rem;
  line-height: 1.5;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.stat-box {
  background: white;
  border-radius: 12px;
  padding: 15px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-icon {
  width: 45px;
  height: 45px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.4rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1.2;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.8rem;
}

/* Metadata Card */
.metadata-card {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 16px;
  padding: 25px;
  color: white;
  margin-bottom: 25px;
}

.metadata-title {
  font-size: 1.1rem;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.2);
}

.metadata-item {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
  align-items: center;
}

.metadata-item i {
  font-size: 1.3rem;
  opacity: 0.9;
}

.metadata-item small {
  display: block;
  font-size: 0.75rem;
  opacity: 0.8;
  margin-bottom: 2px;
}

.metadata-item p {
  font-size: 0.95rem;
  font-weight: 500;
}

/* Quick Actions Card */
.quick-actions-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  border: 1px solid #e9ecef;
}

.quick-actions-title {
  color: #2c3e50;
  font-size: 1.1rem;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e9ecef;
}

.quick-action-btn {
  width: 100%;
  text-align: left;
  padding: 12px 15px;
  margin-bottom: 8px;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  background: white;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s;
}

.quick-action-btn:hover {
  background: #f8f9fa;
  transform: translateX(5px);
  border-color: #3498db;
}

.quick-action-btn i {
  font-size: 1.2rem;
  color: #3498db;
}

/* Badges */
.badge {
  padding: 8px 12px;
  font-weight: 500;
}

/* Responsive */
@media (max-width: 992px) {
  .details-grid {
    grid-template-columns: 1fr;
  }

  .detail-item.full-width {
    grid-column: span 1;
  }

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

  .content-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .content-header .d-flex {
    flex-direction: column;
    width: 100%;
  }

  .content-header .btn {
    width: 100%;
  }
}
</style>