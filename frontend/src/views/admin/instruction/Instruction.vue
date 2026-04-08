<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Instruction Management'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
              <div>
                <h2 class="mb-1">Instruction Management</h2>
                <p class="text-muted mb-0">Manage curriculum, syllabi, and lesson plans</p>
              </div>
            </div>
            <div class="header-actions" v-if="!loading">
              <button class="btn btn-outline-primary" @click="refreshStats">
                <i class="bi bi-arrow-clockwise me-2"></i>
                Refresh
              </button>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon bg-primary-light">
                <i class="bi bi-diagram-3 text-primary"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.curriculum_count }}</div>
                <div class="stat-label">Active Curriculum</div>
                <small class="text-muted">Total: {{ stats.total_curriculum }}</small>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-success-light">
                <i class="bi bi-file-text text-success"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.syllabus_count }}</div>
                <div class="stat-label">Active Syllabi</div>
                <small class="text-muted">Total: {{ stats.total_syllabi }}</small>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-info-light">
                <i class="bi bi-journal-bookmark-fill text-info"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.lessons_count }}</div>
                <div class="stat-label">Total Lessons</div>
                <small class="text-muted">{{ stats.weeks_count }} weeks covered</small>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-warning-light">
                <i class="bi bi-mortarboard text-warning"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.courses_count }}</div>
                <div class="stat-label">Active Courses</div>
                <small class="text-muted">With syllabi</small>
              </div>
            </div>
          </div>

          <!-- Modules Grid -->
          <div class="modules-grid">
            <!-- Curriculum Module -->
            <div class="module-card" @click="$router.push('/admin/instruction/curriculum')">
              <div class="module-icon bg-primary">
                <i class="bi bi-diagram-3"></i>
              </div>
              <div class="module-content">
                <h3>Curriculum Management</h3>
                <p>Manage program curriculum, course offerings, and curriculum structure. Create and update academic programs.</p>
                <div class="module-stats">
                  <span class="badge bg-primary">{{ stats.curriculum_count }} Active</span>
                  <span class="badge bg-secondary">{{ stats.total_curriculum }} Total</span>
                </div>
                <div class="module-footer">
                  <span class="btn-link">Manage Curriculum <i class="bi bi-arrow-right"></i></span>
                </div>
              </div>
            </div>

            <!-- Syllabus Module -->
            <div class="module-card" @click="$router.push('/admin/instruction/syllabus')">
              <div class="module-icon bg-success">
                <i class="bi bi-file-text"></i>
              </div>
              <div class="module-content">
                <h3>Syllabus Management</h3>
                <p>Create and manage course syllabi, including objectives, outcomes, grading systems, and policies.</p>
                <div class="module-stats">
                  <span class="badge bg-success">{{ stats.syllabus_count }} Active</span>
                  <span class="badge bg-secondary">{{ stats.total_syllabi }} Total</span>
                </div>
                <div class="module-footer">
                  <span class="btn-link">Manage Syllabi <i class="bi bi-arrow-right"></i></span>
                </div>
              </div>
            </div>

            <!-- Lessons Module -->
            <div class="module-card" @click="$router.push('/admin/instruction/lessons')">
              <div class="module-icon bg-info">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
              <div class="module-content">
                <h3>Lesson Plans</h3>
                <p>Manage weekly lesson plans, activities, and resources. Organize lessons by week and topic.</p>
                <div class="module-stats">
                  <span class="badge bg-info">{{ stats.lessons_count }} Lessons</span>
                  <span class="badge bg-secondary">{{ stats.weeks_count }} Weeks</span>
                </div>
                <div class="module-footer">
                  <span class="btn-link">Manage Lessons <i class="bi bi-arrow-right"></i></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Activities -->
          <div class="recent-section">
            <h4 class="section-title">
              <i class="bi bi-clock-history me-2"></i>
              Recent Activities
            </h4>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2">Loading activities...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>
              {{ error }}
              <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchStats">
                <i class="bi bi-arrow-clockwise"></i> Retry
              </button>
            </div>

            <!-- Activities List -->
            <div v-else class="activities-list">
              <div v-if="recentActivities.length === 0" class="text-center py-4">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-2">No recent activities</p>
              </div>

              <div v-else>
                <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
                  <div class="activity-icon" :class="'bg-' + activity.type">
                    <i :class="activity.icon"></i>
                  </div>
                  <div class="activity-content">
                    <div class="activity-header">
                      <span class="activity-title">{{ activity.title }}</span>
                      <span class="activity-time">{{ activity.time }}</span>
                    </div>
                    <p class="activity-description">{{ activity.description }}</p>
                    <small class="text-muted">{{ activity.date }}</small>
                  </div>
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
import { ref, onMounted } from 'vue'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Instruction',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const loading = ref(false)
    const error = ref(null)
    const stats = ref({
      curriculum_count: 0,
      total_curriculum: 0,
      syllabus_count: 0,
      total_syllabi: 0,
      lessons_count: 0,
      weeks_count: 0,
      courses_count: 0
    })

    const recentActivities = ref([])

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchStats = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          throw new Error('No authentication token found')
        }

        const headers = { 'Authorization': `Bearer ${token}` }

        // Fetch all data in parallel for better performance
        const [curriculumRes, syllabusRes, lessonsRes, coursesRes] = await Promise.all([
          axios.get(`${API_URL}/admin/instruction/curriculum.php`, { headers, params: { limit: 1 } }),
          axios.get(`${API_URL}/admin/instruction/syllabus.php`, { headers, params: { limit: 1 } }),
          axios.get(`${API_URL}/admin/instruction/lessons.php`, { headers, params: { limit: 1 } }),
          axios.get(`${API_URL}/admin/courses/index.php`, { headers, params: { limit: 1 } })
        ])

        // Process curriculum data
        if (curriculumRes.data.success) {
          stats.value.total_curriculum = curriculumRes.data.pagination?.total || 0
          // Count active curriculum (status = 'Active')
          const activeCurriculum = curriculumRes.data.data?.filter(c => c.status === 'Active') || []
          stats.value.curriculum_count = activeCurriculum.length
        }

        // Process syllabus data
        if (syllabusRes.data.success) {
          stats.value.total_syllabi = syllabusRes.data.pagination?.total || 0
          stats.value.syllabus_count = syllabusRes.data.pagination?.total || 0 // All syllabi are considered active
        }

        // Process lessons data
        if (lessonsRes.data.success) {
          stats.value.lessons_count = lessonsRes.data.pagination?.total || 0
          // Calculate unique weeks
          const weeks = new Set(lessonsRes.data.data?.map(l => l.week_number) || [])
          stats.value.weeks_count = weeks.size
        }

        // Process courses data
        if (coursesRes.data.success) {
          stats.value.courses_count = coursesRes.data.pagination?.total || 0
        }

        // Fetch recent activities
        await fetchRecentActivities()

      } catch (err) {
        console.error('Error fetching stats:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to fetch statistics'
        
        // Use mock data if API fails
        stats.value = {
          curriculum_count: 3,
          total_curriculum: 5,
          syllabus_count: 8,
          total_syllabi: 12,
          lessons_count: 24,
          weeks_count: 8,
          courses_count: 15
        }
        
        // Use mock activities
        recentActivities.value = [
          {
            id: 1,
            type: 'primary',
            icon: 'bi bi-diagram-3',
            title: 'New Curriculum Added',
            description: 'BS Computer Science curriculum (BSCS-2024) was created',
            time: '2 hours ago',
            date: new Date().toISOString()
          },
          {
            id: 2,
            type: 'success',
            icon: 'bi bi-file-text',
            title: 'Syllabus Updated',
            description: 'CS 101 Syllabus was updated with new learning outcomes',
            time: '5 hours ago',
            date: new Date().toISOString()
          },
          {
            id: 3,
            type: 'info',
            icon: 'bi bi-journal-bookmark-fill',
            title: 'Lesson Added',
            description: 'Week 3 lesson for CS 102 was added',
            time: '1 day ago',
            date: new Date().toISOString()
          }
        ]
      } finally {
        loading.value = false
      }
    }

    const fetchRecentActivities = async () => {
      try {
        const token = store.state.auth.token
        const headers = { 'Authorization': `Bearer ${token}` }

        // Fetch recent curriculum changes
        const curriculumRecent = await axios.get(`${API_URL}/admin/instruction/curriculum.php`, {
          headers,
          params: { limit: 3, sort: 'recent' }
        })

        // Fetch recent syllabus changes
        const syllabusRecent = await axios.get(`${API_URL}/admin/instruction/syllabus.php`, {
          headers,
          params: { limit: 3, sort: 'recent' }
        })

        // Fetch recent lessons
        const lessonsRecent = await axios.get(`${API_URL}/admin/instruction/lessons.php`, {
          headers,
          params: { limit: 3, sort: 'recent' }
        })

        // Combine and format activities
        const activities = []

        // Add curriculum activities
        if (curriculumRecent.data.success && curriculumRecent.data.data) {
          curriculumRecent.data.data.forEach(item => {
            activities.push({
              id: `cur-${item.id}`,
              type: 'primary',
              icon: 'bi bi-diagram-3',
              title: item.status === 'Active' ? 'Curriculum Activated' : 'Curriculum Updated',
              description: `${item.program} (${item.curriculum_code})`,
              time: timeAgo(item.created_at),
              date: item.created_at
            })
          })
        }

        // Add syllabus activities
        if (syllabusRecent.data.success && syllabusRecent.data.data) {
          syllabusRecent.data.data.forEach(item => {
            activities.push({
              id: `syl-${item.id}`,
              type: 'success',
              icon: 'bi bi-file-text',
              title: 'Syllabus Updated',
              description: `${item.title}`,
              time: timeAgo(item.updated_at),
              date: item.updated_at
            })
          })
        }

        // Add lesson activities
        if (lessonsRecent.data.success && lessonsRecent.data.data) {
          lessonsRecent.data.data.forEach(item => {
            activities.push({
              id: `les-${item.id}`,
              type: 'info',
              icon: 'bi bi-journal-bookmark-fill',
              title: 'Lesson Added',
              description: `Week ${item.week_number}: ${item.topic}`,
              time: timeAgo(item.created_at),
              date: item.created_at
            })
          })
        }

        // Sort by date (most recent first) and take top 5
        recentActivities.value = activities
          .sort((a, b) => new Date(b.date) - new Date(a.date))
          .slice(0, 5)

      } catch (err) {
        console.error('Error fetching recent activities:', err)
        // Don't show error for activities, just log it
      }
    }

    // Helper function to format time ago
    const timeAgo = (dateString) => {
      if (!dateString) return 'recently'
      
      const date = new Date(dateString)
      const now = new Date()
      const seconds = Math.floor((now - date) / 1000)
      
      if (seconds < 60) return 'just now'
      if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago'
      if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago'
      if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago'
      
      return date.toLocaleDateString()
    }

    const refreshStats = () => {
      fetchStats()
      Swal.fire({
        icon: 'success',
        title: 'Refreshed',
        text: 'Statistics updated successfully',
        timer: 1500,
        showConfirmButton: false
      })
    }

    onMounted(() => {
      fetchStats()
    })

    return {
      loading,
      error,
      stats,
      recentActivities,
      refreshStats
    }
  }
}
</script>

<style scoped>
/* Your existing styles remain the same */
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
  border-radius: 30px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
  padding: 30px;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 3px solid #f0f0f0;
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

.header-actions {
  display: flex;
  gap: 10px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  display: flex;
  align-items: flex-start;
  gap: 15px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
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

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.9rem;
  color: #7f8c8d;
  font-weight: 500;
}

.stat-content small {
  font-size: 0.75rem;
  display: block;
  margin-top: 2px;
}

/* Modules Grid */
.modules-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 25px;
  margin-bottom: 30px;
}

.module-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
  transition: all 0.3s;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  border: 2px solid transparent;
}

.module-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0,0,0,0.1);
  border-color: #3498db;
}

.module-icon {
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: white;
}

.module-icon.bg-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.module-icon.bg-success {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.module-icon.bg-info {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.module-content {
  padding: 25px;
  flex: 1;
}

.module-content h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 10px;
}

.module-content p {
  color: #7f8c8d;
  font-size: 0.95rem;
  line-height: 1.5;
  margin-bottom: 15px;
}

.module-stats {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.module-stats .badge {
  padding: 5px 10px;
  font-size: 0.8rem;
}

.module-footer {
  text-align: right;
}

.btn-link {
  color: #3498db;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-link:hover {
  color: #2980b9;
}

.btn-link i {
  transition: transform 0.3s;
}

.btn-link:hover i {
  transform: translateX(5px);
}

/* Recent Section */
.recent-section {
  margin-top: 20px;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f0f0;
}

.activities-list {
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 15px;
  padding: 15px;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.3s;
}

.activity-item:hover {
  background: #f8f9fa;
  transform: translateX(5px);
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.activity-icon.bg-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.activity-icon.bg-success {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.activity-icon.bg-info {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.activity-icon.bg-warning {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.activity-content {
  flex: 1;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 5px;
  flex-wrap: wrap;
  gap: 5px;
}

.activity-title {
  font-weight: 600;
  color: #2c3e50;
}

.activity-time {
  font-size: 0.8rem;
  color: #95a5a6;
}

.activity-description {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin: 0;
}

/* Badges */
.badge {
  padding: 5px 10px;
  font-weight: 500;
  border-radius: 20px;
}

.badge.bg-primary {
  background: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.badge.bg-success {
  background: rgba(46, 204, 113, 0.15);
  color: #27ae60;
}

.badge.bg-info {
  background: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.badge.bg-secondary {
  background: rgba(149, 165, 166, 0.15);
  color: #7f8c8d;
}

/* Alert */
.alert {
  padding: 15px 20px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.alert-danger {
  background: rgba(231, 76, 60, 0.1);
  border: 1px solid #e74c3c;
  color: #e74c3c;
}

/* Buttons */
.btn-outline-primary {
  border: 1px solid #3498db;
  color: #3498db;
  background: transparent;
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.3s;
}

.btn-outline-primary:hover {
  background: #3498db;
  color: white;
}

.btn-outline-danger {
  border: 1px solid #e74c3c;
  color: #e74c3c;
  background: transparent;
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 0.85rem;
  transition: all 0.3s;
}

.btn-outline-danger:hover {
  background: #e74c3c;
  color: white;
}

/* Responsive */
@media (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .modules-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .modules-grid {
    grid-template-columns: 1fr;
  }

  .content-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .activity-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }
}
</style>