<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Sections Management'" />
      
      <div class="container-fluid p-4">
        <!-- Header with Gradient -->
        <div class="header-gradient mb-4">
          <div class="header-content">
            <div class="header-left">
              <div class="header-icon-wrapper">
                <i class="bi bi-columns-gap"></i>
              </div>
              <div class="header-text">
                <h1 class="header-title">Sections Management</h1>
                <p class="header-subtitle">Manage class sections and student groupings</p>
              </div>
            </div>
            <button class="btn-add" @click="openAddModal">
              <i class="bi bi-plus-lg"></i>
              <span>Add New Section</span>
            </button>
          </div>
        </div>

        <!-- Stats Cards with Animation -->
        <div class="stats-grid">
          <div class="stat-card" v-for="(stat, index) in statsData" :key="stat.label" 
               :style="{ animationDelay: index * 0.1 + 's' }">
            <div class="stat-icon" :style="{ background: stat.color + '15' }">
              <i :class="stat.icon" :style="{ color: stat.color }"></i>
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ stat.value }}</span>
              <span class="stat-label">{{ stat.label }}</span>
              <span class="stat-sub" v-if="stat.sub">{{ stat.sub }}</span>
            </div>
          </div>
        </div>

        <!-- Filters Card -->
        <div class="filters-card">
          <div class="filters-header">
            <div class="filters-title">
              <i class="bi bi-funnel me-2"></i>
              <span>Filter Sections</span>
            </div>
            <button class="btn-reset" @click="resetFilters">
              <i class="bi bi-arrow-counterclockwise me-1"></i>
              Reset All
            </button>
          </div>
          
          <div class="filters-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="filter-label">Course</label>
                <select class="filter-select" v-model="filters.course_id" @change="applyFilters">
                  <option value="0">All Courses</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.course_code }} - {{ course.course_name }}
                  </option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="filter-label">Year Level</label>
                <select class="filter-select" v-model="filters.year_level" @change="applyFilters">
                  <option value="0">All Years</option>
                  <option value="1">1st Year</option>
                  <option value="2">2nd Year</option>
                  <option value="3">3rd Year</option>
                  <option value="4">4th Year</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="filter-label">Status</label>
                <select class="filter-select" v-model="filters.is_active" @change="applyFilters">
                  <option value="">All Status</option>
                  <option value="1">Active</option>
                  <option value="0">Inactive</option>
                </select>
              </div>
              
              <div class="col-md-2">
                <label class="filter-label">&nbsp;</label>
                <div class="filter-search">
                  <i class="bi bi-search"></i>
                  <input type="text" class="form-control" placeholder="Search sections..." v-model="searchQuery" @input="applyFilters">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Results Header -->
        <div class="results-header">
          <div class="results-info">
            <i class="bi bi-grid-3x3-gap-fill me-2"></i>
            <span>Showing <strong>{{ displayedSections.length }}</strong> of <strong>{{ filteredSections.length }}</strong> sections</span>
          </div>
          <div class="results-per-page">
            <span>Show:</span>
            <select class="per-page-select" v-model="itemsPerPage" @change="changePage(1)">
              <option value="5">5</option>
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading sections...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <i class="bi bi-exclamation-triangle"></i>
          <h4>Oops! Something went wrong</h4>
          <p>{{ error }}</p>
          <button class="btn-retry" @click="fetchSections">
            <i class="bi bi-arrow-clockwise me-2"></i>
            Try Again
          </button>
        </div>

        <!-- Sections Table -->
        <div v-else class="table-container">
          <table class="custom-table">
            <thead>
              
                <th @click="sort('section_code')">
                  <span>Section Code</span>
                  <i :class="getSortIcon('section_code')"></i>
                </th>
                <th @click="sort('course_name')">
                  <span>Course</span>
                  <i :class="getSortIcon('course_name')"></i>
                </th>
                <th @click="sort('year_level')">
                  <span>Year Level</span>
                  <i :class="getSortIcon('year_level')"></i>
                </th>
                <th>Adviser</th>
                <th>Enrollment</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
               </thead>
            <tbody>
              <tr v-if="displayedSections.length === 0" class="empty-row">
                <td colspan="7">
                  <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4>No Sections Found</h4>
                    <p>Click the "Add New Section" button to create your first section.</p>
                  </div>
                 </td>
                </tr>
              <tr v-for="section in displayedSections" :key="section.id" class="table-row">
                <td>
                  <span class="badge-code">{{ section.section_code }}</span>
                </td>
                <td>
                  <div class="course-info">
                    <span class="course-code">{{ section.course_code }}</span>
                    <span class="course-name">{{ section.course_name }}</span>
                  </div>
                </td>
                <td>
                  <span class="year-badge">Year {{ section.year_level }}</span>
                </td>
                <td>
                  <div class="adviser-info">
                    <i class="bi bi-person-circle"></i>
                    <span>{{ section.adviser_name || 'Not Assigned' }}</span>
                  </div>
                </td>
                <td>
                  <div class="enrollment-info">
                    <div class="enrollment-numbers">
                      <span class="enrolled">{{ section.student_count || 0 }}</span>
                      <span class="separator">/</span>
                      <span class="capacity">{{ section.capacity || 40 }}</span>
                    </div>
                    <div class="progress-bar-container">
                      <div class="progress-bar" 
                           :class="getProgressClass(section)"
                           :style="{ width: getPercentage(section) + '%' }">
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="status-badge" :class="section.is_active ? 'status-active' : 'status-inactive'">
                    <span class="status-dot"></span>
                    {{ section.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-icon btn-view" @click="viewSection(section)" title="View Details">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn-icon btn-edit" @click="editSection(section)" title="Edit Section">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon btn-delete" @click="deleteSection(section.id)" title="Delete Section">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination-container">
            <button class="pagination-btn" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
              <i class="bi bi-chevron-left"></i>
            </button>
            
            <div class="pagination-pages">
              <template v-for="page in displayedPages" :key="page">
                <button v-if="page === '...'" class="pagination-dots" disabled>...</button>
                <button v-else class="pagination-page" 
                        :class="{ active: currentPage === page }"
                        @click="changePage(page)">
                  {{ page }}
                </button>
              </template>
            </div>
            
            <button class="pagination-btn" :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="sectionModal" tabindex="-1" ref="modalElement">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="modalIcon" class="me-2"></i>
              {{ modalTitle }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveSection">
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-book me-1"></i>
                  Course <span class="required">*</span>
                </label>
                <select class="form-control" v-model="form.course_id" required>
                  <option value="">Select Course</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.course_code }} - {{ course.course_name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-tag me-1"></i>
                  Section Code <span class="required">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.section_code" 
                       placeholder="e.g., A, B, 1A, 2B" required>
                <small class="form-text">Unique identifier for the section</small>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-layers me-1"></i>
                      Year Level <span class="required">*</span>
                    </label>
                    <select class="form-control" v-model="form.year_level" required>
                      <option value="">Select Year</option>
                      <option value="1">1st Year</option>
                      <option value="2">2nd Year</option>
                      <option value="3">3rd Year</option>
                      <option value="4">4th Year</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label">
                      <i class="bi bi-people me-1"></i>
                      Capacity
                    </label>
                    <input type="number" class="form-control" v-model="form.capacity" min="1" max="100">
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-person-badge me-1"></i>
                  Adviser
                </label>
                <select class="form-control" v-model="form.adviser_id">
                  <option value="">Select Adviser</option>
                  <option v-for="faculty in facultyList" :key="faculty.id" :value="faculty.id">
                    {{ faculty.name }}
                  </option>
                </select>
              </div>

              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive">
                <label class="form-check-label" for="isActive">
                  Active Section
                </label>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveSection" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else :class="modalIcon" class="me-2"></i>
              {{ saving ? 'Saving...' : (form.id ? 'Update Section' : 'Create Section') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">
              <i class="bi bi-eye-fill me-2"></i>
              Section Details
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="viewData" class="details-container">
              <div class="detail-card">
                <div class="detail-row">
                  <span class="detail-label">Section Code:</span>
                  <span class="detail-value badge-code">{{ viewData.section_code }}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Course:</span>
                  <span class="detail-value">{{ viewData.course_code }} - {{ viewData.course_name }}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Year Level:</span>
                  <span class="detail-value">
                    <span class="year-badge">Year {{ viewData.year_level }}</span>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Adviser:</span>
                  <span class="detail-value">
                    <div class="adviser-info">
                      <i class="bi bi-person-circle"></i>
                      <span>{{ viewData.adviser_name || 'Not Assigned' }}</span>
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Enrollment:</span>
                  <span class="detail-value">
                    <div class="enrollment-info">
                      <strong>{{ viewData.student_count || 0 }}</strong> / {{ viewData.capacity || 40 }} students
                    </div>
                    <div class="progress-bar-container mt-2">
                      <div class="progress-bar" 
                           :class="getProgressClass(viewData)"
                           :style="{ width: getPercentage(viewData) + '%' }">
                      </div>
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Status:</span>
                  <span class="detail-value">
                    <span class="status-badge" :class="viewData.is_active ? 'status-active' : 'status-inactive'">
                      <span class="status-dot"></span>
                      {{ viewData.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
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
    const facultyList = ref([])
    const searchQuery = ref('')
    
    // Filters
    const filters = ref({
      course_id: '0',
      year_level: '0',
      is_active: ''
    })
    
    // Pagination
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    
    // Sorting
    const sortField = ref('section_code')
    const sortDirection = ref('asc')

    // Modal reference
    const modalElement = ref(null)
    let modalInstance = null

    // Form
    const form = ref({
      id: null,
      course_id: '',
      section_code: '',
      year_level: '',
      capacity: 40,
      adviser_id: '',
      is_active: true
    })

    const viewData = ref(null)

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Stats Data
    const statsData = computed(() => [
      {
        label: 'Total Sections',
        value: totalSections.value,
        icon: 'bi bi-columns',
        color: '#4361ee',
        sub: 'All sections'
      },
      {
        label: 'Enrolled Students',
        value: totalStudents.value,
        icon: 'bi bi-people',
        color: '#06b6d4',
        sub: 'Total enrollment'
      },
      {
        label: 'Courses',
        value: totalCourses.value,
        icon: 'bi bi-book',
        color: '#10b981',
        sub: 'With sections'
      },
      {
        label: 'Active Sections',
        value: activeSections.value,
        icon: 'bi bi-check-circle',
        color: '#f59e0b',
        sub: 'Currently active'
      }
    ])

    // FILTERED SECTIONS
    const filteredSections = computed(() => {
      let result = [...sections.value]

      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(s => 
          s.section_code.toLowerCase().includes(query) ||
          s.course_code?.toLowerCase().includes(query) ||
          s.course_name?.toLowerCase().includes(query)
        )
      }

      // Apply course filter
      if (filters.value.course_id !== '0') {
        result = result.filter(s => s.course_id == filters.value.course_id)
      }
      
      // Apply year filter
      if (filters.value.year_level !== '0') {
        result = result.filter(s => s.year_level == filters.value.year_level)
      }
      
      // Apply status filter
      if (filters.value.is_active !== '') {
        const isActive = filters.value.is_active === '1'
        result = result.filter(s => s.is_active === isActive)
      }

      // Apply sorting
      result.sort((a, b) => {
        let aVal = a[sortField.value] || ''
        let bVal = b[sortField.value] || ''
        
        if (sortField.value === 'year_level' || sortField.value === 'student_count' || sortField.value === 'capacity') {
          aVal = parseInt(aVal) || 0
          bVal = parseInt(bVal) || 0
        } else {
          aVal = aVal.toString().toLowerCase()
          bVal = bVal.toString().toLowerCase()
        }

        return sortDirection.value === 'asc' ? 
          (aVal > bVal ? 1 : -1) : 
          (aVal < bVal ? 1 : -1)
      })

      return result
    })

    // PAGINATED SECTIONS
    const displayedSections = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredSections.value.slice(start, end)
    })

    // TOTAL PAGES
    const totalPages = computed(() => {
      return Math.ceil(filteredSections.value.length / itemsPerPage.value)
    })

    // DISPLAYED PAGES FOR PAGINATION
    const displayedPages = computed(() => {
      const total = totalPages.value
      const current = currentPage.value
      const delta = 2
      const pages = []

      for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
          pages.push(i)
        }
      }

      // Add dots
      const result = []
      let prev = 0
      for (const page of pages) {
        if (prev && page - prev > 1) {
          result.push('...')
        }
        result.push(page)
        prev = page
      }
      
      return result
    })

    // STATS
    const totalSections = computed(() => sections.value.length)
    const totalStudents = computed(() => {
      return sections.value.reduce((sum, s) => sum + (parseInt(s.student_count) || 0), 0)
    })
    const totalCourses = computed(() => {
      const ids = sections.value.map(s => s.course_id)
      return [...new Set(ids)].length
    })
    const activeSections = computed(() => {
      return sections.value.filter(s => s.is_active).length
    })

    const modalTitle = computed(() => {
      return form.value.id ? 'Edit Section' : 'Add New Section'
    })

    const modalIcon = computed(() => {
      return form.value.id ? 'bi bi-pencil-fill' : 'bi bi-plus-circle-fill'
    })

    // Initialize modal
    const initModal = () => {
      if (modalElement.value && !modalInstance) {
        try {
          modalInstance = new Modal(modalElement.value)
        } catch (e) {
          console.error('Modal initialization error:', e)
        }
      }
    }

    // METHODS
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
      } catch (err) {
        console.error('Error fetching courses:', err)
        courses.value = [
          { id: 1, course_code: 'BSCS', course_name: 'BS Computer Science' },
          { id: 2, course_code: 'BSIT', course_name: 'BS Information Technology' }
        ]
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
          facultyList.value = response.data.data.map(f => ({
            id: f.id,
            name: f.full_name
          }))
        }
      } catch (err) {
        console.error('Error fetching faculty:', err)
        facultyList.value = [
          { id: 1, name: 'Dr. Juan Dela Cruz' },
          { id: 2, name: 'Dr. Maria Santos' }
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
      if (percentage >= 90) return 'progress-danger'
      if (percentage >= 75) return 'progress-warning'
      return 'progress-success'
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
      if (sortField.value !== field) return 'bi bi-arrow-down-up sort-icon'
      return sortDirection.value === 'asc' ? 'bi bi-arrow-up sort-icon active' : 'bi bi-arrow-down sort-icon active'
    }

    const applyFilters = () => {
      currentPage.value = 1
    }

    const resetFilters = () => {
      filters.value = {
        course_id: '0',
        year_level: '0',
        is_active: ''
      }
      searchQuery.value = ''
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const openAddModal = () => {
      form.value = {
        id: null,
        course_id: '',
        section_code: '',
        year_level: '',
        capacity: 40,
        adviser_id: '',
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
        adviser_id: section.adviser_id || '',
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
        } else {
          throw new Error(response.data.message)
        }
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
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.response?.data?.message || 'Failed to delete section'
          })
        }
      }
    }

    onMounted(() => {
      fetchSections()
      fetchCourses()
      fetchFaculty()
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

    // Watch for filter changes
    watch([searchQuery, () => filters.value.course_id, () => filters.value.year_level, () => filters.value.is_active, sortField, sortDirection], () => {
      currentPage.value = 1
    })

    return {
      loading,
      saving,
      error,
      sections,
      courses,
      facultyList,
      filters,
      form,
      viewData,
      searchQuery,
      currentPage,
      itemsPerPage,
      totalPages,
      displayedPages,
      filteredSections,
      displayedSections,
      statsData,
      totalSections,
      totalStudents,
      totalCourses,
      activeSections,
      modalTitle,
      modalIcon,
      modalElement,
      getPercentage,
      getProgressClass,
      sort,
      getSortIcon,
      applyFilters,
      resetFilters,
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
/* Modern UI Styles */
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
  width: 100%;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100% - 280px);
  min-height: 100vh;
  padding: 25px;
  transition: margin-left 0.3s ease;
  background: #f8fafc;
}

:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

.container-fluid {
  padding: 0;
  max-width: 1600px;
  margin: 0 auto;
}

/* Header Gradient */
.header-gradient {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
  animation: slideDown 0.5s ease;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.header-icon-wrapper {
  width: 70px;
  height: 70px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.header-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  line-height: 1.2;
}

.header-subtitle {
  margin: 5px 0 0;
  opacity: 0.9;
  font-size: 0.95rem;
}

.btn-add {
  background: white;
  color: #667eea;
  border: none;
  padding: 12px 25px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-add:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.btn-add i {
  font-size: 1.2rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 25px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  transition: all 0.3s;
  border: 1px solid #f0f0f0;
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  border-color: #667eea;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1.2;
  display: block;
}

.stat-label {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
  display: block;
  margin-bottom: 2px;
}

.stat-sub {
  color: #a0aec0;
  font-size: 0.75rem;
}

/* Filters Card */
.filters-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  border: 1px solid #f0f0f0;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
}

.filters-title {
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
}

.btn-reset {
  background: none;
  border: 1px solid #e2e8f0;
  padding: 6px 12px;
  border-radius: 8px;
  color: #718096;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-reset:hover {
  background: #f7fafc;
  border-color: #667eea;
  color: #667eea;
}

.filter-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #4a5568;
}

.filter-select {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s;
  background: white;
}

.filter-select:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-search {
  position: relative;
}

.filter-search i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 1rem;
}

.filter-search input {
  width: 100%;
  padding: 10px 12px 10px 35px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s;
}

.filter-search input:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Results Header */
.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding: 0 5px;
}

.results-info {
  color: #4a5568;
  font-size: 0.95rem;
}

.results-per-page {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  color: #718096;
}

.per-page-select {
  padding: 6px 10px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  cursor: pointer;
}

.per-page-select:focus {
  border-color: #667eea;
  outline: none;
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  border: 1px solid #f0f0f0;
  overflow: hidden;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
}

.custom-table thead th {
  background: #f8fafc;
  padding: 16px 20px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #2d3748;
  text-align: left;
  border-bottom: 2px solid #e2e8f0;
  cursor: pointer;
  transition: background 0.3s;
}

.custom-table thead th:hover {
  background: #edf2f7;
}

.custom-table thead th span {
  margin-right: 8px;
}

.sort-icon {
  font-size: 0.8rem;
  color: #a0aec0;
  transition: color 0.3s;
}

.sort-icon.active {
  color: #667eea;
}

.custom-table tbody td {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  color: #4a5568;
  font-size: 0.95rem;
}

.table-row:hover {
  background: #f8fafc;
  transition: background 0.3s;
}

/* Badges */
.badge-code {
  background: linear-gradient(135deg, #667eea15, #764ba215);
  color: #667eea;
  padding: 6px 12px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
}

.course-info {
  display: flex;
  flex-direction: column;
}

.course-code {
  font-weight: 600;
  color: #2d3748;
}

.course-name {
  font-size: 0.85rem;
  color: #718096;
}

.year-badge {
  background: #e2e8f0;
  color: #4a5568;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
}

.adviser-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #4a5568;
}

.adviser-info i {
  color: #667eea;
  font-size: 1.2rem;
}

/* Enrollment Progress */
.enrollment-info {
  min-width: 120px;
}

.enrollment-numbers {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 6px;
  font-size: 0.9rem;
}

.enrollment-numbers .enrolled {
  font-weight: 600;
  color: #2d3748;
}

.enrollment-numbers .separator {
  color: #cbd5e0;
}

.enrollment-numbers .capacity {
  color: #718096;
}

.progress-bar-container {
  height: 6px;
  background: #edf2f7;
  border-radius: 3px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-success {
  background: linear-gradient(90deg, #10b981, #34d399);
}

.progress-warning {
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.progress-danger {
  background: linear-gradient(90deg, #ef4444, #f87171);
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-active {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.status-active .status-dot {
  background: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.status-inactive {
  background: rgba(156, 163, 175, 0.1);
  color: #6b7280;
}

.status-inactive .status-dot {
  background: #9ca3af;
  box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.2);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1rem;
}

.btn-view {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.btn-view:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.btn-edit {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.btn-edit:hover {
  background: #f59e0b;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
}

.btn-delete {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.btn-delete:hover {
  background: #ef4444;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  padding: 20px;
  border-top: 1px solid #edf2f7;
}

.pagination-pages {
  display: flex;
  gap: 5px;
}

.pagination-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-btn:hover:not(:disabled) {
  background: #667eea;
  border-color: #667eea;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-page {
  min-width: 40px;
  height: 40px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
}

.pagination-page:hover {
  border-color: #667eea;
  color: #667eea;
}

.pagination-page.active {
  background: #667eea;
  border-color: #667eea;
  color: white;
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.pagination-dots {
  min-width: 40px;
  height: 40px;
  border: none;
  background: none;
  color: #a0aec0;
  cursor: default;
  font-weight: 500;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 16px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 3px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-state i {
  font-size: 4rem;
  color: #cbd5e0;
  margin-bottom: 20px;
}

.empty-state h4 {
  color: #2d3748;
  margin-bottom: 8px;
  font-size: 1.3rem;
}

.empty-state p {
  color: #718096;
  margin: 0;
}

/* Modal Styles */
.custom-modal {
  border-radius: 20px;
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.custom-modal .modal-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  padding: 20px 25px;
}

.custom-modal .modal-header .btn-close {
  filter: brightness(0) invert(1);
}

.custom-modal .modal-body {
  padding: 25px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group .form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #2d3748;
  font-size: 0.95rem;
}

.form-group .form-label i {
  color: #667eea;
}

.form-group .required {
  color: #ef4444;
}

.form-group .form-control {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s;
}

.form-group .form-control:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group .form-text {
  display: block;
  margin-top: 5px;
  color: #a0aec0;
  font-size: 0.8rem;
}

.form-check {
  margin-top: 15px;
}

.form-check-input {
  width: 3rem;
  height: 1.5rem;
  margin-right: 10px;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #667eea;
  border-color: #667eea;
}

.form-check-label {
  font-weight: 500;
  color: #2d3748;
  cursor: pointer;
}

.custom-modal .modal-footer {
  padding: 15px 25px;
  border-top: 2px solid #edf2f7;
}

/* Details Container */
.details-container {
  padding: 10px;
}

.detail-row {
  display: flex;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #edf2f7;
}

.detail-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.detail-label {
  width: 120px;
  font-weight: 600;
  color: #718096;
  font-size: 0.9rem;
}

.detail-value {
  flex: 1;
  color: #2d3748;
}

/* Animations */
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

/* Responsive */
@media (max-width: 992px) {
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

  .header-left {
    flex-direction: column;
    text-align: center;
  }

  .header-title {
    font-size: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .results-header {
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
  }

  .custom-table {
    font-size: 0.85rem;
  }

  .custom-table thead th,
  .custom-table tbody td {
    padding: 12px 10px;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn-icon {
    width: 32px;
    height: 32px;
  }

  .pagination-container {
    flex-wrap: wrap;
  }

  .detail-row {
    flex-direction: column;
    gap: 5px;
  }

  .detail-label {
    width: 100%;
  }
}
</style>