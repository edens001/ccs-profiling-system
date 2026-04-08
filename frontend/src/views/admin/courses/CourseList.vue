<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Course Management'" />
      
      <div class="container-fluid p-4">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon bg-primary">
                <i class="bi bi-book-fill text-white"></i>
              </div>
              <div>
                <h2 class="mb-1">Course Management</h2>
                <p class="text-muted mb-0">Manage all courses and curriculum offerings</p>
              </div>
            </div>
            <button class="btn btn-primary btn-lg" @click="addCourse">
              <i class="bi bi-plus-circle me-2"></i>
              Add New Course
            </button>
          </div>

          <!-- Stats Cards -->
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon bg-primary-light">
                <i class="bi bi-book text-primary"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ totalCourses }}</div>
                <div class="stat-label">Total Courses</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-success-light">
                <i class="bi bi-building text-success"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ departmentsCount }}</div>
                <div class="stat-label">Departments</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-info-light">
                <i class="bi bi-mortarboard text-info"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ totalUnits }}</div>
                <div class="stat-label">Total Units</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon bg-warning-light">
                <i class="bi bi-clock-history text-warning"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ activeCourses }}</div>
                <div class="stat-label">Active Courses</div>
              </div>
            </div>
          </div>

          <!-- Search and Filter Bar -->
          <div class="filter-bar">
            <div class="row g-3">
              <div class="col-md-5">
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search by course code or name..." 
                    v-model="searchQuery"
                    @keyup.enter="searchCourses"
                  >
                  <button class="btn btn-primary" type="button" @click="searchCourses">
                    Search
                  </button>
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="departmentFilter" @change="filterCourses">
                  <option value="">All Departments</option>
                  <option v-for="dept in departments" :key="dept" :value="dept">
                    {{ dept }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-select" v-model="statusFilter" @change="filterCourses">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
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
            <p class="mt-3 text-muted">Loading courses...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
            <div class="flex-grow-1">{{ error }}</div>
            <button class="btn btn-sm btn-outline-danger" @click="fetchCourses">
              <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
          </div>

          <!-- Courses Table -->
          <div v-else>
            <div class="results-summary mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <p class="mb-0">
                  <strong>{{ filteredCourses.length }}</strong> courses found
                  <span v-if="searchQuery || departmentFilter || statusFilter" class="text-muted ms-2">
                    (filtered)
                  </span>
                </p>
                <div class="d-flex align-items-center">
                  <label class="me-2">Show:</label>
                  <select class="form-select form-select-sm" style="width: auto;" v-model="itemsPerPage">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th @click="sort('course_code')" class="sortable">
                      Course Code
                      <i :class="getSortIcon('course_code')"></i>
                    </th>
                    <th @click="sort('course_name')" class="sortable">
                      Course Name
                      <i :class="getSortIcon('course_name')"></i>
                    </th>
                    <th @click="sort('department')" class="sortable">
                      Department
                      <i :class="getSortIcon('department')"></i>
                    </th>
                    <th @click="sort('units')" class="sortable text-center">
                      Units
                      <i :class="getSortIcon('units')"></i>
                    </th>
                    <th class="text-center">Syllabi</th>
                    <th>Sections</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="paginatedCourses.length === 0">
                    <td colspan="8" class="text-center py-5">
                      <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                      <p class="text-muted mb-0">No courses found</p>
                    </td>
                  </tr>
                  <tr v-for="course in paginatedCourses" :key="course.id">
                    <td>
                      <span class="badge bg-primary bg-opacity-10 text-primary p-2">
                        {{ course.course_code }}
                      </span>
                    </td>
                    <td>
                      <span class="fw-semibold">{{ course.course_name }}</span>
                    </td>
                    <td>{{ course.department || 'N/A' }}</td>
                    <td class="text-center">
                      <span class="badge bg-info bg-opacity-10 text-info p-2">
                        {{ course.units || 0 }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-secondary bg-opacity-10">
                        {{ course.syllabus_count || 0 }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-secondary bg-opacity-10">
                        {{ course.section_count || 0 }}
                      </span>
                    </td>
                    <td>
                      <span :class="'badge bg-' + (course.is_active ? 'success' : 'secondary')">
                        {{ course.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn btn-sm btn-info" @click="viewCourse(course.id)" title="View">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" @click="editCourse(course.id)" title="Edit">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" @click="deleteCourse(course.id)" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

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
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'CourseList',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const router = useRouter()
    const store = useStore()
    const loading = ref(false)
    const error = ref(null)
    const courses = ref([])
    const searchQuery = ref('')
    const departmentFilter = ref('')
    const statusFilter = ref('')
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const sortField = ref('course_code')
    const sortDirection = ref('asc')

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Computed properties
    const totalCourses = computed(() => courses.value.length)
    
    const departments = computed(() => {
      const depts = courses.value.map(c => c.department).filter(Boolean)
      return [...new Set(depts)]
    })

    const departmentsCount = computed(() => departments.value.length)

    const totalUnits = computed(() => {
      return courses.value.reduce((sum, c) => sum + (parseInt(c.units) || 0), 0)
    })

    const activeCourses = computed(() => {
      return courses.value.filter(c => c.is_active).length
    })

    const filteredCourses = computed(() => {
      let filtered = courses.value

      // Search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(c => 
          c.course_code?.toLowerCase().includes(query) ||
          c.course_name?.toLowerCase().includes(query)
        )
      }

      // Department filter
      if (departmentFilter.value) {
        filtered = filtered.filter(c => c.department === departmentFilter.value)
      }

      // Status filter
      if (statusFilter.value) {
        const isActive = statusFilter.value === 'active'
        filtered = filtered.filter(c => c.is_active === isActive)
      }

      // Sorting
      filtered.sort((a, b) => {
        let aVal = a[sortField.value]
        let bVal = b[sortField.value]
        
        if (sortField.value === 'units') {
          aVal = parseInt(aVal) || 0
          bVal = parseInt(bVal) || 0
        } else {
          aVal = aVal?.toString().toLowerCase() || ''
          bVal = bVal?.toString().toLowerCase() || ''
        }

        if (sortDirection.value === 'asc') {
          return aVal > bVal ? 1 : -1
        } else {
          return aVal < bVal ? 1 : -1
        }
      })

      return filtered
    })

    const paginatedCourses = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredCourses.value.slice(start, end)
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredCourses.value.length / itemsPerPage.value)
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

    // Methods
    const fetchCourses = async () => {
      loading.value = true
      error.value = null
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/courses/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          courses.value = response.data.data.map(course => ({
            ...course,
            is_active: course.is_active == 1 ? true : false
          }))
        } else {
          throw new Error(response.data.message)
        }
      } catch (err) {
        console.error('Error fetching courses:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to fetch courses'
        
        // Fallback data
        courses.value = [
          { 
            id: 1, 
            course_code: 'BSCS', 
            course_name: 'BS Computer Science', 
            department: 'CCS', 
            units: 0, 
            is_active: true,
            syllabus_count: 8,
            section_count: 4
          },
          { 
            id: 2, 
            course_code: 'BSIT', 
            course_name: 'BS Information Technology', 
            department: 'CCS', 
            units: 0, 
            is_active: true,
            syllabus_count: 6,
            section_count: 3
          },
          { 
            id: 3, 
            course_code: 'BSIS', 
            course_name: 'BS Information Systems', 
            department: 'CCS', 
            units: 0, 
            is_active: true,
            syllabus_count: 5,
            section_count: 2
          }
        ]
      } finally {
        loading.value = false
      }
    }

    const searchCourses = () => {
      currentPage.value = 1
    }

    const filterCourses = () => {
      currentPage.value = 1
    }

    const resetFilters = () => {
      searchQuery.value = ''
      departmentFilter.value = ''
      statusFilter.value = ''
      currentPage.value = 1
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

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const addCourse = () => {
      router.push('/admin/courses/add')
    }

    const viewCourse = (id) => {
      router.push(`/admin/courses/view/${id}`)
    }

    const editCourse = (id) => {
      router.push(`/admin/courses/edit/${id}`)
    }

    const deleteCourse = async (id) => {
      const result = await Swal.fire({
        title: 'Delete Course?',
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
          const response = await axios.delete(`${API_URL}/admin/courses/delete.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            await Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Course has been deleted',
              timer: 1500,
              showConfirmButton: false
            })
            fetchCourses()
          }
        } catch (error) {
          console.error('Error deleting course:', error)
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete course'
          })
        }
      }
    }

    onMounted(() => {
      fetchCourses()
    })

    return {
      loading,
      error,
      courses,
      searchQuery,
      departmentFilter,
      statusFilter,
      currentPage,
      itemsPerPage,
      filteredCourses,
      paginatedCourses,
      totalPages,
      displayedPages,
      totalCourses,
      departments,
      departmentsCount,
      totalUnits,
      activeCourses,
      searchCourses,
      filterCourses,
      resetFilters,
      sort,
      getSortIcon,
      changePage,
      addCourse,
      viewCourse,
      editCourse,
      deleteCourse
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

.bg-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
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
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.03);
  transition: all 0.3s;
  border: 1px solid #f0f0f0;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.stat-icon {
  width: 55px;
  height: 55px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
}

.bg-primary-light {
  background: rgba(52, 152, 219, 0.12);
  color: #3498db;
}

.bg-success-light {
  background: rgba(46, 204, 113, 0.12);
  color: #27ae60;
}

.bg-info-light {
  background: rgba(52, 152, 219, 0.12);
  color: #3498db;
}

.bg-warning-light {
  background: rgba(241, 196, 15, 0.12);
  color: #f39c12;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1.2;
  margin-bottom: 4px;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Filter Bar */
.filter-bar {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 25px;
  border: 1px solid #e9ecef;
}

.input-group-text {
  background: white;
  border-right: none;
}

.input-group .form-control {
  border-left: none;
}

.input-group .form-control:focus {
  box-shadow: none;
  border-color: #dee2e6;
}

.input-group .btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

/* Results Summary */
.results-summary {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 15px 20px;
  border: 1px solid #e9ecef;
}

/* Table */
.table {
  margin-bottom: 0;
}

.table th {
  background: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 15px;
  border-bottom: 2px solid #e9ecef;
}

.table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.3s;
}

.table th.sortable:hover {
  background-color: #e9ecef;
}

.table th i {
  margin-left: 5px;
  font-size: 0.8rem;
}

.table td {
  padding: 15px;
  vertical-align: middle;
  border-bottom: 1px solid #e9ecef;
}

.table tbody tr:hover {
  background-color: #f8f9fa;
}

/* Badges */
.badge {
  padding: 6px 12px;
  font-weight: 500;
  font-size: 0.85rem;
}

.badge.bg-primary {
  background: #3498db ;
  color: white;
}

.badge.bg-success {
  background: #27ae60 !important;
  color: white;
}

.badge.bg-info {
  background: #3498db !important;
  color: white;
}

.badge.bg-secondary {
  background: #95a5a6 !important;
  color: white;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 5px;
  justify-content: center;
}

.action-buttons .btn {
  width: 35px;
  height: 35px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 0.3s;
}

.action-buttons .btn:hover {
  transform: translateY(-2px);
}

.btn-info {
  background: #3498db;
  border: none;
  color: white;
}

.btn-info:hover {
  background: #2980b9;
}

.btn-warning {
  background: #f39c12;
  border: none;
  color: white;
}

.btn-warning:hover {
  background: #e67e22;
}

.btn-danger {
  background: #e74c3c;
  border: none;
  color: white;
}

.btn-danger:hover {
  background: #c0392b;
}

/* Pagination */
.pagination-wrapper {
  margin-top: 25px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
}

.pagination {
  gap: 5px;
}

.page-link {
  border-radius: 10px;
  border: none;
  padding: 10px 15px;
  color: #2c3e50;
  font-weight: 500;
  transition: all 0.3s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.page-link:hover {
  background-color: #e9ecef;
  color: #2c3e50;
  transform: translateY(-2px);
}

.page-item.active .page-link {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

/* Alert */
.alert {
  border-radius: 12px;
  padding: 15px 20px;
}

.alert-danger {
  background: rgba(231, 76, 60, 0.1);
  border: 1px solid #e74c3c;
  color: #e74c3c;
}

/* Loading Spinner */
.spinner-border {
  width: 3rem;
  height: 3rem;
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

  .content-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .action-buttons {
    flex-wrap: wrap;
  }

  .table {
    font-size: 0.9rem;
  }

  .table th, .table td {
    padding: 10px;
  }
}
</style>