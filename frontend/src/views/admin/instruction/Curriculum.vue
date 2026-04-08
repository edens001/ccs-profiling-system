<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Curriculum Management'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-diagram-3"></i>
              </div>
              <div>
                <h2 class="mb-1">Curriculum Management</h2>
                <p class="text-muted mb-0">Manage program curriculum and course offerings</p>
              </div>
            </div>
            <button class="btn btn-primary" @click="openAddModal">
              <i class="bi bi-plus-circle me-2"></i>
              Add Curriculum
            </button>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search curriculum..." 
                    v-model="filters.search"
                    @keyup.enter="fetchCurriculum"
                  >
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="filters.program" @change="fetchCurriculum">
                  <option value="">All Programs</option>
                  <option value="BS Computer Science">BS Computer Science</option>
                  <option value="BS Information Technology">BS Information Technology</option>
                  <option value="BS Information Systems">BS Information Systems</option>
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
            <p class="mt-2">Loading curriculum...</p>
          </div>

          <!-- Curriculum Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Program</th>
                  <th>Curriculum Code</th>
                  <th>Effective Year</th>
                  <th>Total Units</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="curriculum.length === 0">
                  <td colspan="6" class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No curriculum found</p>
                  </td>
                </tr>
                <tr v-for="item in curriculum" :key="item.id">
                  <td><strong>{{ item.program }}</strong></td>
                  <td>{{ item.curriculum_code }}</td>
                  <td>{{ item.effective_year }}</td>
                  <td>{{ item.total_units }} units</td>
                  <td>
                    <span :class="'badge bg-' + getStatusColor(item.status)">
                      {{ item.status }}
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-info me-1" @click="viewCurriculum(item.id)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning me-1" @click="editCurriculum(item)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" @click="deleteCurriculum(item.id)">
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

    <!-- Add/Edit Curriculum Modal -->
    <div class="modal fade" id="curriculumModal" tabindex="-1">
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
            <form @submit.prevent="saveCurriculum">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Program <span class="text-danger">*</span></label>
                  <select class="form-select" v-model="form.program" required>
                    <option value="">Select Program</option>
                    <option value="BS Computer Science">BS Computer Science</option>
                    <option value="BS Information Technology">BS Information Technology</option>
                    <option value="BS Information Systems">BS Information Systems</option>
                  </select>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Curriculum Code <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="form.curriculum_code" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Effective Year <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="form.effective_year" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Total Units <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" v-model="form.total_units" required>
                </div>

                <div class="col-12 mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" rows="3" v-model="form.description"></textarea>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" v-model="form.status">
                    <option value="Draft">Draft</option>
                    <option value="Active">Active</option>
                    <option value="Archived">Archived</option>
                  </select>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveCurriculum" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              {{ saving ? 'Saving...' : 'Save Curriculum' }}
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
  name: 'Curriculum',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const saving = ref(false)
    const curriculum = ref([])
    
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const totalPages = ref(1)
    
    const filters = ref({
      search: '',
      program: ''
    })

    const form = ref({
      id: null,
      program: '',
      curriculum_code: '',
      effective_year: '',
      total_units: '',
      description: '',
      status: 'Draft'
    })

    const modalTitle = computed(() => {
      return form.value.id ? 'Edit Curriculum' : 'Add New Curriculum'
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

    const fetchCurriculum = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/instruction/curriculum.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            page: currentPage.value,
            limit: itemsPerPage.value,
            search: filters.value.search,
            program: filters.value.program
          }
        })

        if (response.data.success) {
          curriculum.value = response.data.data
          totalPages.value = response.data.pagination.pages
        }
      } catch (error) {
        console.error('Error fetching curriculum:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to fetch curriculum'
        })
      } finally {
        loading.value = false
      }
    }

    const resetFilters = () => {
      filters.value = {
        search: '',
        program: ''
      }
      currentPage.value = 1
      fetchCurriculum()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        fetchCurriculum()
      }
    }

    const getStatusColor = (status) => {
      const colors = {
        'Active': 'success',
        'Draft': 'warning',
        'Archived': 'secondary'
      }
      return colors[status] || 'secondary'
    }

    const openAddModal = () => {
      form.value = {
        id: null,
        program: '',
        curriculum_code: '',
        effective_year: '',
        total_units: '',
        description: '',
        status: 'Draft'
      }
      const modal = new Modal(document.getElementById('curriculumModal'))
      modal.show()
    }

    const editCurriculum = (item) => {
      form.value = { ...item }
      const modal = new Modal(document.getElementById('curriculumModal'))
      modal.show()
    }

    const viewCurriculum = (id) => {
      router.push(`/admin/instruction/curriculum/view/${id}`)
    }

    const saveCurriculum = async () => {
      if (!form.value.program || !form.value.curriculum_code || !form.value.effective_year || !form.value.total_units) {
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
          ? `${API_URL}/admin/instruction/curriculum_update.php?id=${form.value.id}`
          : `${API_URL}/admin/instruction/curriculum.php`
        
        const method = form.value.id ? 'put' : 'post'
        
        const response = await axios({
          method,
          url,
          data: form.value,
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          Modal.getInstance(document.getElementById('curriculumModal')).hide()
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Curriculum ${form.value.id ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          fetchCurriculum()
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save curriculum'
        })
      } finally {
        saving.value = false
      }
    }

    const deleteCurriculum = async (id) => {
      const result = await Swal.fire({
        title: 'Delete Curriculum?',
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
          const response = await axios.delete(`${API_URL}/admin/instruction/curriculum_delete.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Curriculum has been deleted',
              timer: 1500,
              showConfirmButton: false
            })
            fetchCurriculum()
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete curriculum'
          })
        }
      }
    }

    onMounted(() => {
      fetchCurriculum()
    })

    return {
      loading,
      saving,
      curriculum,
      filters,
      form,
      currentPage,
      totalPages,
      displayedPages,
      modalTitle,
      modalIcon,
      fetchCurriculum,
      resetFilters,
      changePage,
      getStatusColor,
      openAddModal,
      editCurriculum,
      viewCurriculum,
      saveCurriculum,
      deleteCurriculum
    }
  }
}
</script>

<style scoped>
/* Same styles as Syllabus.vue */
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