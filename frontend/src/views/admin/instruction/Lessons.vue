<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Lesson Plans'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-journal-bookmark-fill"></i>
              </div>
              <div>
                <h2 class="mb-1">Lesson Plans</h2>
                <p class="text-muted mb-0">Manage weekly lesson plans and activities</p>
              </div>
            </div>
            <button class="btn btn-primary" @click="openAddModal">
              <i class="bi bi-plus-circle me-2"></i>
              Add Lesson
            </button>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="row g-3">
              <div class="col-md-5">
                <select class="form-select" v-model="filters.course_id" @change="fetchLessons">
                  <option value="0">All Courses</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.course_code }} - {{ course.course_name }}
                  </option>
                </select>
              </div>
              <div class="col-md-5">
                <select class="form-select" v-model="filters.syllabus_id" @change="fetchLessons">
                  <option value="0">All Syllabi</option>
                  <option v-for="syllabus in syllabi" :key="syllabus.id" :value="syllabus.id">
                    {{ syllabus.title }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" @click="resetFilters">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading lessons...</p>
          </div>

          <!-- Lessons Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Course</th>
                  <th>Week</th>
                  <th>Topic</th>
                  <th>Objectives</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="lessons.length === 0">
                  <td colspan="5" class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No lessons found</p>
                  </td>
                </tr>
                <tr v-for="lesson in lessons" :key="lesson.id">
                  <td>{{ lesson.course_code }} - {{ lesson.course_name }}</td>
                  <td>Week {{ lesson.week_number }}</td>
                  <td>{{ lesson.topic }}</td>
                  <td>{{ truncateText(lesson.objectives, 50) }}</td>
                  <td>
                    <button class="btn btn-sm btn-info me-1" @click="viewLesson(lesson.id)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning me-1" @click="editLesson(lesson)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" @click="deleteLesson(lesson.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination-wrapper">
            <nav>
              <ul class="pagination">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                    <i class="bi bi-chevron-left"></i>
                  </a>
                </li>
                <li class="page-item" v-for="page in displayedPages" :key="page" 
                    :class="{ active: currentPage === page }">
                  <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                  <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                    <i class="bi bi-chevron-right"></i>
                  </a>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Lesson Modal -->
    <div class="modal fade" id="lessonModal" tabindex="-1" ref="modalElement">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="modalIcon" class="me-2"></i>
              {{ modalTitle }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveLesson">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Syllabus <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="form.syllabus_id" required>
                    <option value="">Select Syllabus</option>
                    <option v-for="syllabus in syllabi" :key="syllabus.id" :value="syllabus.id">
                      {{ syllabus.title }}
                    </option>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Week Number <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" v-model="form.week_number" required min="1" max="18">
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Topic <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="form.topic" required>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Objectives</label>
                  <textarea class="form-control" rows="3" v-model="form.objectives"></textarea>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Activities</label>
                  <textarea class="form-control" rows="3" v-model="form.activities"></textarea>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Resources</label>
                  <textarea class="form-control" rows="3" v-model="form.resources"></textarea>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveLesson" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              {{ saving ? 'Saving...' : 'Save Lesson' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Modal } from 'bootstrap'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Lessons',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const saving = ref(false)
    const lessons = ref([])
    const courses = ref([])
    const syllabi = ref([])
    
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const totalPages = ref(1)
    
    const filters = ref({
      course_id: 0,
      syllabus_id: 0
    })

    const form = ref({
      id: null,
      syllabus_id: '',
      week_number: '',
      topic: '',
      objectives: '',
      activities: '',
      resources: ''
    })

    // Modal reference
    const modalElement = ref(null)
    let modalInstance = null

    const modalTitle = computed(() => {
      return form.value.id ? 'Edit Lesson' : 'Add New Lesson'
    })

    const modalIcon = computed(() => {
      return form.value.id ? 'bi bi-pencil' : 'bi bi-plus-circle'
    })

    const displayedPages = computed(() => {
      const delta = 2
      const range = []
      const rangeWithDots = []
      let l

      for (let i = 1; i <= totalPages.value; i++) {
        if (i === 1 || i === totalPages.value || (i >= currentPage.value - delta && i <= currentPage.value + delta)) {
          range.push(i)
        }
      }

      range.forEach((i) => {
        if (l) {
          if (i - l === 2) {
            rangeWithDots.push(l + 1)
          } else if (i - l !== 1) {
            rangeWithDots.push('...')
          }
        }
        rangeWithDots.push(i)
        l = i
      })

      return rangeWithDots
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchLessons = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/instruction/lessons.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            page: currentPage.value,
            limit: itemsPerPage.value,
            course_id: filters.value.course_id,
            syllabus_id: filters.value.syllabus_id
          }
        })

        if (response.data.success) {
          lessons.value = response.data.data
          totalPages.value = response.data.pagination.pages
        }
      } catch (error) {
        console.error('Error fetching lessons:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to fetch lessons'
        })
      } finally {
        loading.value = false
      }
    }

    const fetchCourses = async () => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/courses/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { limit: 100 }
        })
        if (response.data.success) {
          courses.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching courses:', error)
      }
    }

    const fetchSyllabi = async () => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/instruction/syllabus.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { limit: 100 }
        })
        if (response.data.success) {
          syllabi.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching syllabi:', error)
      }
    }

    const resetFilters = () => {
      filters.value = {
        course_id: 0,
        syllabus_id: 0
      }
      currentPage.value = 1
      fetchLessons()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        fetchLessons()
      }
    }

    const truncateText = (text, length) => {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }

    // FIXED: Modal methods with null checking
    const openAddModal = () => {
      form.value = {
        id: null,
        syllabus_id: '',
        week_number: '',
        topic: '',
        objectives: '',
        activities: '',
        resources: ''
      }
      if (modalInstance) {
        modalInstance.show()
      }
    }

    const editLesson = (lesson) => {
      form.value = { ...lesson }
      if (modalInstance) {
        modalInstance.show()
      }
    }

    const viewLesson = (id) => {
      router.push(`/admin/instruction/lessons/view/${id}`)
    }

    const saveLesson = async () => {
      if (!form.value.syllabus_id || !form.value.week_number || !form.value.topic) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please fill in all required fields'
        })
        return
      }

      saving.value = true
      try {
        const token = store.state.auth.token
        const url = form.value.id 
          ? `${API_URL}/admin/instruction/lessons_update.php?id=${form.value.id}`
          : `${API_URL}/admin/instruction/lessons.php`
        
        const method = form.value.id ? 'put' : 'post'
        
        const response = await axios({
          method,
          url,
          data: form.value,
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          if (modalInstance) {
            modalInstance.hide()
          }
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Lesson ${form.value.id ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          fetchLessons()
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save lesson'
        })
      } finally {
        saving.value = false
      }
    }

    const deleteLesson = async (id) => {
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
          const response = await axios.delete(`${API_URL}/admin/instruction/lessons_delete.php?id=${id}`, {
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
            fetchLessons()
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

    // Initialize modal when component is mounted
    onMounted(() => {
      fetchLessons()
      fetchCourses()
      fetchSyllabi()
      
      // Initialize modal
      if (modalElement.value) {
        modalInstance = new Modal(modalElement.value)
      }
    })

    // Clean up modal when component is unmounted
    onBeforeUnmount(() => {
      if (modalInstance) {
        modalInstance.dispose()
      }
    })

    return {
      loading,
      saving,
      lessons,
      courses,
      syllabi,
      filters,
      form,
      currentPage,
      totalPages,
      displayedPages,
      modalTitle,
      modalIcon,
      modalElement,
      fetchLessons,
      resetFilters,
      changePage,
      truncateText,
      openAddModal,
      editLesson,
      viewLesson,
      saveLesson,
      deleteLesson
    }
  }
}
</script>

<style scoped>
/* Same styles as before */
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
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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

.table-container {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 15px;
  text-align: left;
  border-bottom: 2px solid #e9ecef;
}

.table td {
  padding: 12px 15px;
  border-bottom: 1px solid #e9ecef;
  vertical-align: middle;
}

.btn-sm {
  padding: 5px 10px;
  font-size: 0.85rem;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }
}
</style>