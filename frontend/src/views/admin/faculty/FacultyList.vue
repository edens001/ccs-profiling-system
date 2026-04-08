<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Faculty Management'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <i class="bi bi-person-badge-fill fs-2 text-primary me-3"></i>
              <div>
                <h4 class="mb-1">Faculty Management</h4>
                <p class="text-muted mb-0">Manage and view all faculty members</p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-success" @click="importFaculty">
                <i class="bi bi-file-earmark-excel me-2"></i>
                Import
              </button>
              <button class="btn btn-primary" @click="addFaculty">
                <i class="bi bi-plus-circle me-2"></i>
                Add Faculty
              </button>
            </div>
          </div>
          
          <!-- Search and Filter Bar -->
          <div class="filter-bar">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search by name, email or faculty number..." 
                    v-model="searchQuery"
                    @keyup.enter="searchFaculty"
                  >
                  <button class="btn btn-primary" type="button" @click="searchFaculty">
                    Search
                  </button>
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="departmentFilter">
                  <option value="">All Departments</option>
                  <option value="Computer Studies">Computer Studies</option>
                  <option value="Information Technology">Information Technology</option>
                  <option value="Computer Science">Computer Science</option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-select" v-model="statusFilter">
                  <option value="">All Status</option>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
              <div class="col-md-3">
                <button class="btn btn-outline-secondary w-100" @click="resetFilters">
                  <i class="bi bi-arrow-counterclockwise me-2"></i>
                  Reset Filters
                </button>
              </div>
            </div>
          </div>
          
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading faculty data...</p>
          </div>

          <!-- Results Summary -->
          <div v-else class="results-summary">
            <div class="d-flex justify-content-between align-items-center">
              <p class="mb-0">
                <strong>{{ totalFaculty }}</strong> faculty members found
              </p>
              <div class="d-flex align-items-center">
                <label class="me-2">Show:</label>
                <select class="form-select form-select-sm" style="width: auto;" v-model="itemsPerPage" @change="fetchFaculty">
                  <option value="5">5</option>
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
              </div>
            </div>
          </div>
          
          <!-- Faculty Table -->
          <div v-if="!loading" class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Faculty #</th>
                  <th>Name</th>
                  <th>Department</th>
                  <th>Designation</th>
                  <th>Contact</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="faculty.length === 0">
                  <td colspan="7" class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-0">No faculty members found</p>
                  </td>
                </tr>
                <tr v-for="member in faculty" :key="member.id">
                  <td>
                    <span class="fw-bold">{{ member.faculty_number }}</span>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="faculty-avatar me-2">
                        {{ getUserInitials(member.first_name, member.last_name) }}
                      </div>
                      <div>
                        <div>{{ member.last_name }}, {{ member.first_name }}</div>
                        <small class="text-muted">{{ member.email }}</small>
                      </div>
                    </div>
                  </td>
                  <td>{{ member.department }}</td>
                  <td>{{ member.designation }}</td>
                  <td>{{ member.contact_number }}</td>
                  <td>
                    <span :class="'badge bg-' + getStatusColor(member.status)">
                      {{ member.status }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-info" @click="viewFaculty(member.id)" title="View">
                        <i class="bi bi-eye"></i>
                      </button>
                      <button class="btn btn-sm btn-warning" @click="editFaculty(member.id)" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" @click="deleteFaculty(member.id)" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="pagination-wrapper" v-if="totalPages > 1">
            <nav>
              <ul class="pagination justify-content-center mb-0">
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
  name: 'FacultyList',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const faculty = ref([])
    const searchQuery = ref('')
    const departmentFilter = ref('')
    const statusFilter = ref('')
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const totalFaculty = ref(0)
    const totalPages = ref(1)
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

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
    
    const fetchFaculty = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/faculty/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            page: currentPage.value,
            limit: itemsPerPage.value,
            search: searchQuery.value,
            department: departmentFilter.value,
            status: statusFilter.value
          }
        })
        
        if (response.data.success) {
          faculty.value = response.data.data
          totalFaculty.value = response.data.pagination.total
          totalPages.value = response.data.pagination.pages
        }
      } catch (error) {
        console.error('Error fetching faculty:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to fetch faculty data'
        })
      } finally {
        loading.value = false
      }
    }
    
    const searchFaculty = () => {
      currentPage.value = 1
      fetchFaculty()
    }
    
    const resetFilters = () => {
      searchQuery.value = ''
      departmentFilter.value = ''
      statusFilter.value = ''
      currentPage.value = 1
      fetchFaculty()
    }
    
    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        fetchFaculty()
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }
    
    const getUserInitials = (first, last) => {
      return (first?.charAt(0) || '') + (last?.charAt(0) || '')
    }
    
    const getStatusColor = (status) => {
      return status === 'Active' ? 'success' : 'danger'
    }
    
    const addFaculty = () => {
      router.push('/admin/faculty/add')
    }
    
    const viewFaculty = (id) => {
      router.push(`/admin/faculty/view/${id}`)
    }
    
    const editFaculty = (id) => {
      router.push(`/admin/faculty/edit/${id}`)
    }
    
    const deleteFaculty = async (id) => {
      const result = await Swal.fire({
        title: 'Delete Faculty Member?',
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
          const response = await axios.delete(`${API_URL}/admin/faculty/delete.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
          
          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.data.message,
              timer: 1500,
              showConfirmButton: false
            })
            fetchFaculty()
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete faculty member'
          })
        }
      }
    }
    
    const importFaculty = () => {
      Swal.fire({
        title: 'Import Faculty',
        html: `
          <div class="mb-3">
            <input type="file" id="fileInput" class="form-control" accept=".xlsx,.xls,.csv">
          </div>
          <p class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Supported formats: Excel (.xlsx, .xls) and CSV
          </p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Import',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          const file = document.getElementById('fileInput').files[0]
          if (!file) {
            Swal.showValidationMessage('Please select a file')
          }
          return file
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            icon: 'success',
            title: 'Import Started',
            text: 'Faculty data is being imported. You will be notified when complete.',
            showConfirmButton: false,
            timer: 2000
          })
        }
      })
    }
    
    onMounted(() => {
      fetchFaculty()
    })
    
    return {
      loading,
      faculty,
      searchQuery,
      departmentFilter,
      statusFilter,
      currentPage,
      itemsPerPage,
      totalFaculty,
      totalPages,
      displayedPages,
      fetchFaculty,
      searchFaculty,
      resetFilters,
      changePage,
      getUserInitials,
      getStatusColor,
      addFaculty,
      viewFaculty,
      editFaculty,
      deleteFaculty,
      importFaculty
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
  padding: 20px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
}

/* Kapag naka-collapse ang sidebar */
:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

/* Container */
.container-fluid {
  padding: 0;
  max-width: 1800px;
  margin: 0 auto;
}

/* Content Card - para sa lahat ng pages */
.content-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
  padding: 25px;
}

/* Content Header */
.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
}

/* Form Sections */
.form-section {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e9ecef;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
}

/* Form Controls */
.form-label {
  font-weight: 500;
  color: #495057;
  margin-bottom: 5px;
  font-size: 0.9rem;
}

.form-control, .form-select {
  border-radius: 8px;
  border: 1px solid #e0e0e0;
  padding: 10px 12px;
  transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.form-control[readonly] {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.input-group-text {
  background-color: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px 0 0 8px;
}

/* Filter Bar */
.filter-bar {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

/* Results Summary */
.results-summary {
  background: white;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
  border: 1px solid #e9ecef;
}

/* Table Styles */
.table {
  margin-bottom: 0;
}

.table th {
  background: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.9rem;
  border-bottom: 2px solid #e9ecef;
  padding: 15px 10px;
}

.table td {
  vertical-align: middle;
  padding: 15px 10px;
  border-bottom: 1px solid #e9ecef;
}

.table-hover tbody tr:hover {
  background-color: #f8f9fa;
}

/* Student Avatar */
.student-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.8rem;
  text-transform: uppercase;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 5px;
  justify-content: center;
}

.action-buttons .btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.action-buttons .btn i {
  font-size: 0.9rem;
}

/* Badges */
.badge {
  padding: 6px 12px;
  font-weight: 500;
  font-size: 0.8rem;
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
  border-radius: 8px;
  border: none;
  padding: 8px 14px;
  color: #2c3e50;
  font-weight: 500;
  transition: all 0.3s;
}

.page-link:hover {
  background-color: #e9ecef;
  color: #2c3e50;
}

.page-item.active .page-link {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
}

.page-item.disabled .page-link {
  background-color: #f8f9fa;
  color: #adb5bd;
  cursor: not-allowed;
}

/* Mobile Responsive */
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

  .form-actions {
    flex-direction: column;
  }

  .form-actions .btn {
    width: 100%;
  }

  .action-buttons {
    flex-wrap: wrap;
  }

  .table {
    font-size: 0.85rem;
  }

  .student-avatar {
    width: 30px;
    height: 30px;
    font-size: 0.7rem;
  }
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.content-card {
  animation: fadeIn 0.3s ease;
}
</style>