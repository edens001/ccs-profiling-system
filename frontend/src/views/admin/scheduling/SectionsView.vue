<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Sections Management'" />
      
      <div class="container-fluid p-4">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon bg-primary">
                <i class="bi bi-columns-gap text-white"></i>
              </div>
              <div>
                <h2 class="mb-1">Sections Management</h2>
                <p class="text-muted mb-0">Manage class sections and student groupings</p>
              </div>
            </div>
            <button class="btn btn-primary" @click="openAddModal">
              <i class="bi bi-plus-circle me-2"></i>
              Add Section
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon bg-primary-light">
                <i class="bi bi-columns text-primary"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ totalSections }}</div>
                <div class="stat-label">Total Sections</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-success-light">
                <i class="bi bi-people text-success"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ totalStudents }}</div>
                <div class="stat-label">Enrolled Students</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-info-light">
                <i class="bi bi-book text-info"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ totalCourses }}</div>
                <div class="stat-label">Courses</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-warning-light">
                <i class="bi bi-check-circle text-warning"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ activeSections }}</div>
                <div class="stat-label">Active Sections</div>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="filter-bar">
            <div class="row g-3">
              <div class="col-md-4">
                <select class="form-select" v-model="filters.course_id" @change="applyFilters">
                  <option value="0">All Courses</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.course_code }} - {{ course.course_name }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="filters.year_level" @change="applyFilters">
                  <option value="0">All Years</option>
                  <option value="1">1st Year</option>
                  <option value="2">2nd Year</option>
                  <option value="3">3rd Year</option>
                  <option value="4">4th Year</option>
                </select>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="filters.is_active" @change="applyFilters">
                  <option value="">All Status</option>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" @click="resetFilters">
                  <i class="bi bi-arrow-counterclockwise me-2"></i>
                  Reset
                </button>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading sections...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
            <div class="flex-grow-1">{{ error }}</div>
            <button class="btn btn-sm btn-outline-danger" @click="fetchSections">
              <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
          </div>

          <!-- Sections Table -->
          <div v-else class="table-responsive">
            <div class="results-summary mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0">
                  <strong>{{ filteredSections.length }}</strong> sections found
                  <span v-if="hasActiveFilters" class="text-muted ms-2">
                    (filtered)
                  </span>
                </p>
                <div class="d-flex align-items-center">
                  <label class="me-2">Show:</label>
                  <select class="form-select form-select-sm" style="width: auto;" v-model="itemsPerPage" @change="updatePagination">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                  </select>
                </div>
              </div>
            </div>

            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th @click="sort('section_code')" class="sortable" style="cursor: pointer;">
                    Section Code
                    <i :class="getSortIcon('section_code')"></i>
                  </th>
                  <th @click="sort('course_name')" class="sortable" style="cursor: pointer;">
                    Course
                    <i :class="getSortIcon('course_name')"></i>
                  </th>
                  <th @click="sort('year_level')" class="sortable" style="cursor: pointer;">
                    Year Level
                    <i :class="getSortIcon('year_level')"></i>
                  </th>
                  <th>Adviser</th>
                  <th>Students</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="paginatedSections.length === 0">
                  <td colspan="7" class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-0">No sections found</p>
                  </td>
                </tr>
                <tr v-for="section in paginatedSections" :key="section.id">
                  <td>
                    <span class="badge bg-primary bg-opacity-10 text-primary p-2">
                      {{ section.section_code }}
                    </span>
                  </td>
                  <td>
                    <span class="fw-semibold">{{ section.course_code }}</span>
                    <br>
                    <small class="text-muted">{{ section.course_name }}</small>
                  </td>
                  <td>Year {{ section.year_level }}</td>
                  <td>{{ section.adviser_name || 'Not Assigned' }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <span class="me-2">{{ section.student_count || 0 }}/{{ section.capacity || 40 }}</span>
                      <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar" 
                             :class="getProgressClass(section)"
                             :style="{ width: getPercentage(section) + '%' }">
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span :class="'badge bg-' + (section.is_active ? 'success' : 'secondary')">
                      {{ section.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-info" @click="viewSection(section)" title="View">
                        <i class="bi bi-eye"></i>
                      </button>
                      <button class="btn btn-sm btn-warning" @click="editSection(section)" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" @click="deleteSection(section.id)" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination-wrapper">
              <nav>
                <ul class="pagination justify-content-center">
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
    </div>

    <!-- Add/Edit Modal -->
    <Teleport to="body">
      <div class="modal fade" id="sectionModal" tabindex="-1" ref="modalElement">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i :class="modalIcon" class="me-2 text-primary"></i>
                {{ modalTitle }}
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveSection">
                <div class="mb-3">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-book me-2 text-primary"></i>
                    Course <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" v-model="form.course_id" required>
                    <option value="">Select Course</option>
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                      {{ course.course_code }} - {{ course.course_name }}
                    </option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-tag me-2 text-primary"></i>
                    Section Code <span class="text-danger">*</span>
                  </label>
                  <input type="text" class="form-control" v-model="form.section_code" 
                         placeholder="e.g., A, B, 1A, 2B" required>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-layers me-2 text-primary"></i>
                    Year Level <span class="text-danger">*</span>
                  </label>
                  <select class="form-select" v-model="form.year_level" required>
                    <option value="">Select Year</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-people me-2 text-primary"></i>
                    Capacity
                  </label>
                  <input type="number" class="form-control" v-model="form.capacity" min="1" max="100">
                  <small class="text-muted">Default: 40 students</small>
                </div>

                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive">
                    <label class="form-check-label fw-semibold" for="isActive">
                      Active Section
                    </label>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-2"></i>Cancel
              </button>
              <button type="button" class="btn btn-primary" @click="saveSection" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-check-circle me-2"></i>
                {{ saving ? 'Saving...' : 'Save Section' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- View Modal -->
    <Teleport to="body">
      <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-eye-fill me-2 text-info"></i>
                Section Details
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="view-details" v-if="viewData">
                <div class="detail-row">
                  <label>Section Code:</label>
                  <span class="badge bg-primary">{{ viewData.section_code }}</span>
                </div>
                <div class="detail-row">
                  <label>Course:</label>
                  <span>{{ viewData.course_code }} - {{ viewData.course_name }}</span>
                </div>
                <div class="detail-row">
                  <label>Year Level:</label>
                  <span>Year {{ viewData.year_level }}</span>
                </div>
                <div class="detail-row">
                  <label>Adviser:</label>
                  <span>{{ viewData.adviser_name || 'Not Assigned' }}</span>
                </div>
                <div class="detail-row">
                  <label>Enrollment:</label>
                  <span>{{ viewData.student_count || 0 }} / {{ viewData.capacity || 40 }} students</span>
                  <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar" 
                         :class="getProgressClass(viewData)"
                         :style="{ width: getPercentage(viewData) + '%' }">
                    </div>
                  </div>
                </div>
                <div class="detail-row">
                  <label>Status:</label>
                  <span :class="'badge bg-' + (viewData.is_active ? 'success' : 'secondary')">
                    {{ viewData.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
                <div class="detail-row">
                  <label>Created:</label>
                  <span>{{ formatDate(viewData.created_at) }}</span>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
  name: 'Sections',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const loading = ref(false)
    const saving = ref(false)
    const error = ref(null)
    const sections = ref([])
    const courses = ref([])
    
    // Filter refs
    const filters = ref({
      course_id: '0',
      year_level: '0',
      is_active: ''
    })
    
    // Pagination refs
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const totalPages = ref(1)
    
    // Sort refs
    const sortField = ref('section_code')
    const sortDirection = ref('asc')

    // Form refs
    const form = ref({
      id: null,
      course_id: '',
      section_code: '',
      year_level: '',
      capacity: 40,
      is_active: true
    })

    // Modal refs
    const modalElement = ref(null)
    let modalInstance = null
    const viewData = ref(null)

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Computed properties
    const totalSections = computed(() => sections.value.length)
    
    const totalStudents = computed(() => {
      return sections.value.reduce((sum, s) => sum + (parseInt(s.student_count) || 0), 0)
    })

    const totalCourses = computed(() => {
      const courseIds = sections.value.map(s => s.course_id)
      return [...new Set(courseIds)].length
    })

    const activeSections = computed(() => {
      return sections.value.filter(s => s.is_active).length
    })

    const hasActiveFilters = computed(() => {
      return filters.value.course_id !== '0' || 
             filters.value.year_level !== '0' || 
             filters.value.is_active !== ''
    })

    const filteredSections = computed(() => {
      let filtered = [...sections.value]

      // Apply filters
      if (filters.value.course_id !== '0') {
        filtered = filtered.filter(s => s.course_id == filters.value.course_id)
      }

      if (filters.value.year_level !== '0') {
        filtered = filtered.filter(s => s.year_level == filters.value.year_level)
      }

      if (filters.value.is_active !== '') {
        const isActive = filters.value.is_active === '1'
        filtered = filtered.filter(s => s.is_active === isActive)
      }

      // Apply sorting
      filtered.sort((a, b) => {
        let aVal = a[sortField.value]
        let bVal = b[sortField.value]
        
        if (sortField.value === 'year_level' || sortField.value === 'student_count' || sortField.value === 'capacity') {
          aVal = parseInt(aVal) || 0
          bVal = parseInt(bVal) || 0
        } else {
          aVal = (aVal || '').toString().toLowerCase()
          bVal = (bVal || '').toString().toLowerCase()
        }

        if (sortDirection.value === 'asc') {
          return aVal > bVal ? 1 : -1
        } else {
          return aVal < bVal ? 1 : -1
        }
      })

      return filtered
    })

    const paginatedSections = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredSections.value.slice(start, end)
    })

    // Update total pages when filtered sections change
    const updateTotalPages = () => {
      totalPages.value = Math.ceil(filteredSections.value.length / itemsPerPage.value)
    }

    // Watch for changes that affect pagination
    watch([filteredSections, itemsPerPage], () => {
      updateTotalPages()
      if (currentPage.value > totalPages.value) {
        currentPage.value = 1
      }
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

    const modalTitle = computed(() => {
      return form.value.id ? 'Edit Section' : 'Add New Section'
    })

    const modalIcon = computed(() => {
      return form.value.id ? 'bi bi-pencil-fill' : 'bi bi-plus-circle-fill'
    })

    // Methods
    const fetchSections = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/scheduling/sections.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          sections.value = response.data.data.map(section => ({
            ...section,
            is_active: section.is_active == 1
          }))
          updateTotalPages()
        } else {
          throw new Error(response.data.message)
        }
      } catch (err) {
        console.error('Error fetching sections:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to fetch sections'
        
        // Fallback data
        sections.value = [
          {
            id: 1,
            section_code: 'A',
            course_id: 1,
            course_code: 'BSCS',
            course_name: 'BS Computer Science',
            year_level: 1,
            capacity: 40,
            student_count: 35,
            is_active: true,
            adviser_name: 'Dr. Juan Dela Cruz'
          },
          {
            id: 2,
            section_code: 'B',
            course_id: 1,
            course_code: 'BSCS',
            course_name: 'BS Computer Science',
            year_level: 1,
            capacity: 40,
            student_count: 38,
            is_active: true,
            adviser_name: 'Dr. Maria Santos'
          },
          {
            id: 3,
            section_code: 'A',
            course_id: 2,
            course_code: 'BSIT',
            course_name: 'BS Information Technology',
            year_level: 2,
            capacity: 35,
            student_count: 30,
            is_active: true,
            adviser_name: 'Prof. Pedro Reyes'
          }
        ]
        updateTotalPages()
      } finally {
        loading.value = false
      }
    }

    const fetchCourses = async () => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/courses/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        if (response.data.success) {
          courses.value = response.data.data
        }
      } catch (err) {
        console.error('Error fetching courses:', err)
        // Fallback data
        courses.value = [
          { id: 1, course_code: 'BSCS', course_name: 'BS Computer Science' },
          { id: 2, course_code: 'BSIT', course_name: 'BS Information Technology' }
        ]
      }
    }

    const getPercentage = (section) => {
      const students = parseInt(section.student_count) || 0
      const capacity = parseInt(section.capacity) || 40
      return Math.min(Math.round((students / capacity) * 100), 100)
    }

    const getProgressClass = (section) => {
      const percentage = getPercentage(section)
      if (percentage >= 90) return 'bg-danger'
      if (percentage >= 75) return 'bg-warning'
      return 'bg-success'
    }

    const sort = (field) => {
      if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortField.value = field
        sortDirection.value = 'asc'
      }
    }

    const getSortIcon = (field) => {
      if (sortField.value !== field) return 'bi bi-arrow-down-up text-muted'
      return sortDirection.value === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down'
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const applyFilters = () => {
      currentPage.value = 1
      // Filtering is handled by computed property
    }

    const resetFilters = () => {
      filters.value = {
        course_id: '0',
        year_level: '0',
        is_active: ''
      }
      currentPage.value = 1
    }

    const updatePagination = () => {
      currentPage.value = 1
      updateTotalPages()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    // Modal methods
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
      form.value = {
        id: null,
        course_id: '',
        section_code: '',
        year_level: '',
        capacity: 40,
        is_active: true
      }
      if (modalInstance) {
        modalInstance.show()
      } else {
        initModal()
        nextTick(() => {
          if (modalInstance) modalInstance.show()
        })
      }
    }

    const editSection = (section) => {
      form.value = {
        id: section.id,
        course_id: section.course_id,
        section_code: section.section_code,
        year_level: section.year_level,
        capacity: section.capacity || 40,
        is_active: section.is_active
      }
      if (modalInstance) {
        modalInstance.show()
      } else {
        initModal()
        nextTick(() => {
          if (modalInstance) modalInstance.show()
        })
      }
    }

    const viewSection = (section) => {
      viewData.value = section
      const viewModal = new Modal(document.getElementById('viewModal'))
      viewModal.show()
    }

    const saveSection = async () => {
      if (!form.value.course_id || !form.value.section_code || !form.value.year_level) {
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
        
        // Simulate save
        await new Promise(resolve => setTimeout(resolve, 500))
        
        if (form.value.id) {
          // Update existing
          const index = sections.value.findIndex(s => s.id === form.value.id)
          if (index !== -1) {
            sections.value[index] = {
              ...sections.value[index],
              course_id: form.value.course_id,
              section_code: form.value.section_code,
              year_level: form.value.year_level,
              capacity: form.value.capacity,
              is_active: form.value.is_active
            }
          }
        } else {
          // Add new
          const newSection = {
            id: sections.value.length + 1,
            course_id: form.value.course_id,
            section_code: form.value.section_code,
            year_level: form.value.year_level,
            capacity: form.value.capacity,
            is_active: form.value.is_active,
            student_count: 0,
            course_code: courses.value.find(c => c.id == form.value.course_id)?.course_code || '',
            course_name: courses.value.find(c => c.id == form.value.course_id)?.course_name || ''
          }
          sections.value.push(newSection)
        }

        if (modalInstance) {
          modalInstance.hide()
        }
        
        updateTotalPages()
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: `Section ${form.value.id ? 'updated' : 'created'} successfully`,
          timer: 1500,
          showConfirmButton: false
        })
        
        /* Uncomment when API is ready
        const url = form.value.id 
          ? `${API_URL}/admin/scheduling/sections.php?id=${form.value.id}`
          : `${API_URL}/admin/scheduling/sections.php`
        
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
            title: 'Success!',
            text: `Section ${form.value.id ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          fetchSections()
        }
        */
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err.response?.data?.message || err.message || 'Failed to save section'
        })
      } finally {
        saving.value = false
      }
    }

    const deleteSection = async (id) => {
      const result = await Swal.fire({
        title: 'Delete Section?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, delete'
      })

      if (result.isConfirmed) {
        try {
          // Simulate delete
          await new Promise(resolve => setTimeout(resolve, 500))
          sections.value = sections.value.filter(s => s.id !== id)
          updateTotalPages()
          
          Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            text: 'Section deleted successfully',
            timer: 1500,
            showConfirmButton: false
          })
          
          /* Uncomment when API is ready
          const token = store.state.auth.token
          const response = await axios.delete(`${API_URL}/admin/scheduling/sections.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Section deleted successfully',
              timer: 1500,
              showConfirmButton: false
            })
            fetchSections()
          }
          */
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.response?.data?.message || err.message || 'Failed to delete section'
          })
        }
      }
    }

    // Lifecycle hooks
    onMounted(() => {
      fetchSections()
      fetchCourses()
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
      // State
      loading,
      saving,
      error,
      sections,
      courses,
      filters,
      form,
      viewData,
      currentPage,
      itemsPerPage,
      totalPages,
      sortField,
      sortDirection,
      modalElement,
      
      // Computed
      totalSections,
      totalStudents,
      totalCourses,
      activeSections,
      hasActiveFilters,
      filteredSections,
      paginatedSections,
      displayedPages,
      modalTitle,
      modalIcon,
      
      // Methods
      fetchSections,
      getPercentage,
      getProgressClass,
      sort,
      getSortIcon,
      formatDate,
      applyFilters,
      resetFilters,
      updatePagination,
      changePage,
      openAddModal,
      editSection,
      viewSection,
      saveSection,
      deleteSection
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