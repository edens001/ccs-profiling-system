<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Syllabus Management'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-file-text"></i>
              </div>
              <div>
                <h2 class="mb-1">Syllabus Management</h2>
                <p class="text-muted mb-0">Create and manage course syllabi</p>
              </div>
            </div>
            <button class="btn btn-primary" @click="openAddModal">
              <i class="bi bi-plus-circle me-2"></i>
              Add Syllabus
            </button>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search syllabus..." 
                    v-model="filters.search"
                    @keyup.enter="fetchSyllabi"
                  >
                </div>
              </div>
              <div class="col-md-4">
                <select class="form-select" v-model="filters.course_id" @change="fetchSyllabi">
                  <option value="0">All Courses</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.course_code }} - {{ course.course_name }}
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
            <p class="mt-2">Loading syllabi...</p>
          </div>

          <!-- Syllabus Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Course Code</th>
                  <th>Course Title</th>
                  <th>Syllabus Title</th>
                  <th>Instructor</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="syllabi.length === 0">
                  <td colspan="5" class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No syllabi found</p>
                  </td>
                </tr>
                <tr v-for="syllabus in syllabi" :key="syllabus.id">
                  <td><strong>{{ syllabus.course_code }}</strong></td>
                  <td>{{ syllabus.course_name }}</td>
                  <td>{{ syllabus.title }}</td>
                  <td>{{ syllabus.instructor_name || 'Not assigned' }}</td>
                  <td>
                    <button class="btn btn-sm btn-info me-1" @click="viewSyllabus(syllabus.id)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning me-1" @click="editSyllabus(syllabus)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" @click="deleteSyllabus(syllabus.id)">
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

    <!-- Add/Edit Syllabus Modal -->
    <div class="modal fade" id="syllabusModal" tabindex="-1">
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
            <form @submit.prevent="saveSyllabus">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Course <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="form.course_id" required>
                    <option value="">Select Course</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                      {{ course.course_code }} - {{ course.course_name }}
                    </option>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Instructor</label>
                  <select class="form-select" v-model="form.faculty_id">
                    <option value="">Select Instructor</option>
                    <option v-for="faculty in facultyList" :key="faculty.id" :value="faculty.id">
                      {{ faculty.full_name }}
                    </option>
                  </select>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Syllabus Title <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="form.title" required>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" rows="3" v-model="form.description"></textarea>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Objectives</label>
                  <textarea class="form-control" rows="3" v-model="form.objectives"></textarea>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Learning Outcomes</label>
                  <textarea class="form-control" rows="3" v-model="form.learning_outcomes"></textarea>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Grading System</label>
                  <textarea class="form-control" rows="3" v-model="form.grading_system"></textarea>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Policies</label>
                  <textarea class="form-control" rows="3" v-model="form.policies"></textarea>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Reference Materials</label>
                  <textarea class="form-control" rows="3" v-model="form.reference_materials"></textarea>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveSyllabus" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              {{ saving ? 'Saving...' : 'Save Syllabus' }}
            </button>
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
import { Modal } from 'bootstrap'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Syllabus',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const saving = ref(false)
    const syllabi = ref([])
    const courses = ref([])
    const facultyList = ref([])
    
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const totalPages = ref(1)
    
    const filters = ref({
      search: '',
      course_id: 0
    })

    const form = ref({
      id: null,
      course_id: '',
      faculty_id: '',
      title: '',
      description: '',
      objectives: '',
      learning_outcomes: '',
      grading_system: '',
      policies: '',
      reference_materials: ''
    })

    const modalTitle = computed(() => {
      return form.value.id ? 'Edit Syllabus' : 'Add New Syllabus'
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

    const fetchSyllabi = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/instruction/syllabus.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            page: currentPage.value,
            limit: itemsPerPage.value,
            search: filters.value.search,
            course_id: filters.value.course_id
          }
        })

        if (response.data.success) {
          syllabi.value = response.data.data
          totalPages.value = response.data.pagination.pages
        }
      } catch (error) {
        console.error('Error fetching syllabi:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to fetch syllabi'
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
          console.log('Courses loaded:', courses.value) // Debug log
        } else {
          console.error('Failed to fetch courses:', response.data.message)
        }
      } catch (error) {
        console.error('Error fetching courses:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load courses. Please check if courses API is working.'
        })
      }
    }

    const fetchFaculty = async () => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/faculty/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { limit: 100 }
        })
        if (response.data.success) {
          facultyList.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching faculty:', error)
      }
    }

    const resetFilters = () => {
      filters.value = {
        search: '',
        course_id: 0
      }
      currentPage.value = 1
      fetchSyllabi()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        fetchSyllabi()
      }
    }

    const openAddModal = () => {
      form.value = {
        id: null,
        course_id: '',
        faculty_id: '',
        title: '',
        description: '',
        objectives: '',
        learning_outcomes: '',
        grading_system: '',
        policies: '',
        reference_materials: ''
      }
      const modal = new Modal(document.getElementById('syllabusModal'))
      modal.show()
    }

    const editSyllabus = (syllabus) => {
      form.value = { ...syllabus }
      const modal = new Modal(document.getElementById('syllabusModal'))
      modal.show()
    }

    const viewSyllabus = (id) => {
      router.push(`/admin/instruction/syllabus/view/${id}`)
    }

    const saveSyllabus = async () => {
      if (!form.value.course_id || !form.value.title) {
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
          ? `${API_URL}/admin/instruction/syllabus_update.php?id=${form.value.id}`
          : `${API_URL}/admin/instruction/syllabus.php`
        
        const method = form.value.id ? 'put' : 'post'
        
        const response = await axios({
          method,
          url,
          data: form.value,
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          Modal.getInstance(document.getElementById('syllabusModal')).hide()
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Syllabus ${form.value.id ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          fetchSyllabi()
        }
      } catch (error) {
        console.error('Error saving syllabus:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save syllabus'
        })
      } finally {
        saving.value = false
      }
    }

    const deleteSyllabus = async (id) => {
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
          const response = await axios.delete(`${API_URL}/admin/instruction/syllabus_delete.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Syllabus has been deleted',
              timer: 1500,
              showConfirmButton: false
            })
            fetchSyllabi()
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
      fetchSyllabi()
      fetchCourses()
      fetchFaculty()
    })

    return {
      loading,
      saving,
      syllabi,
      courses,
      facultyList,
      filters,
      form,
      currentPage,
      totalPages,
      displayedPages,
      modalTitle,
      modalIcon,
      fetchSyllabi,
      resetFilters,
      changePage,
      openAddModal,
      editSyllabus,
      viewSyllabus,
      saveSyllabus,
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