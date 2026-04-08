<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="editMode ? 'Edit Curriculum' : 'Add New Curriculum'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i :class="editMode ? 'bi bi-pencil' : 'bi bi-plus-circle'"></i>
              </div>
              <div>
                <div class="breadcrumb">
                  <router-link to="/admin/dashboard">Dashboard</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction">Instruction</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/instruction/curriculum">Curriculum</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>{{ editMode ? 'Edit Curriculum' : 'Add Curriculum' }}</span>
                </div>
                <h2 class="mb-0">{{ editMode ? 'Edit Curriculum' : 'Create New Curriculum' }}</h2>
                <p class="text-muted mb-0">{{ editMode ? 'Update curriculum information' : 'Fill in the curriculum details below' }}</p>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading curriculum data...</p>
          </div>

          <!-- Form -->
          <form v-else @submit.prevent="saveCurriculum">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Program <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.program" required :class="{ 'is-invalid': errors.program }">
                  <option value="">Select Program</option>
                  <option value="BS Computer Science">BS Computer Science</option>
                  <option value="BS Information Technology">BS Information Technology</option>
                  <option value="BS Information Systems">BS Information Systems</option>
                </select>
                <div v-if="errors.program" class="invalid-feedback">{{ errors.program }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Curriculum Code <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.curriculum_code" 
                  required
                  placeholder="e.g., BSCS-2024"
                  :class="{ 'is-invalid': errors.curriculum_code }"
                >
                <div v-if="errors.curriculum_code" class="invalid-feedback">{{ errors.curriculum_code }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Effective Year <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.effective_year" 
                  required
                  placeholder="e.g., 2024-2025"
                  :class="{ 'is-invalid': errors.effective_year }"
                >
                <div v-if="errors.effective_year" class="invalid-feedback">{{ errors.effective_year }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Total Units <span class="text-danger">*</span></label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="form.total_units" 
                  required
                  min="1"
                  :class="{ 'is-invalid': errors.total_units }"
                >
                <div v-if="errors.total_units" class="invalid-feedback">{{ errors.total_units }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Description</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.description"
                  placeholder="Enter curriculum description"
                  :class="{ 'is-invalid': errors.description }"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">{{ errors.description }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" v-model="form.status" :class="{ 'is-invalid': errors.status }">
                  <option value="Draft">Draft</option>
                  <option value="Active">Active</option>
                  <option value="Archived">Archived</option>
                </select>
                <div v-if="errors.status" class="invalid-feedback">{{ errors.status }}</div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
              <button type="button" class="btn btn-outline-secondary" @click="cancel">
                <i class="bi bi-x-circle me-2"></i>
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-save me-2"></i>
                {{ saving ? 'Saving...' : (editMode ? 'Update Curriculum' : 'Save Curriculum') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'CurriculumEdit',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const route = useRoute()
    const router = useRouter()
    const loading = ref(false)
    const saving = ref(false)
    const errors = ref({})

    const editMode = computed(() => route.path.includes('/edit'))

    const form = ref({
      id: null,
      program: '',
      curriculum_code: '',
      effective_year: '',
      total_units: '',
      description: '',
      status: 'Draft'
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchCurriculum = async () => {
      if (!editMode.value) return
      
      loading.value = true
      try {
        const token = store.state.auth.token
        const curriculumId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/instruction/curriculum_view.php?id=${curriculumId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          const data = response.data.data
          form.value = {
            id: data.id,
            program: data.program,
            curriculum_code: data.curriculum_code,
            effective_year: data.effective_year,
            total_units: data.total_units,
            description: data.description || '',
            status: data.status
          }
        }
      } catch (error) {
        console.error('Error fetching curriculum:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to load curriculum'
        }).then(() => {
          router.push('/admin/instruction/curriculum')
        })
      } finally {
        loading.value = false
      }
    }

    const validateForm = () => {
      errors.value = {}
      let isValid = true

      if (!form.value.program) {
        errors.value.program = 'Program is required'
        isValid = false
      }
      if (!form.value.curriculum_code) {
        errors.value.curriculum_code = 'Curriculum code is required'
        isValid = false
      }
      if (!form.value.effective_year) {
        errors.value.effective_year = 'Effective year is required'
        isValid = false
      }
      if (!form.value.total_units) {
        errors.value.total_units = 'Total units is required'
        isValid = false
      } else if (form.value.total_units < 1) {
        errors.value.total_units = 'Total units must be at least 1'
        isValid = false
      }

      return isValid
    }

    const saveCurriculum = async () => {
      if (!validateForm()) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please fill in all required fields correctly'
        })
        return
      }

      saving.value = true
      try {
        const token = store.state.auth.token
        let response

        if (editMode.value) {
          response = await axios.put(`${API_URL}/admin/instruction/curriculum_update.php?id=${route.params.id}`, form.value, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
        } else {
          response = await axios.post(`${API_URL}/admin/instruction/curriculum.php`, form.value, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
        }

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Curriculum ${editMode.value ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          router.push('/admin/instruction/curriculum')
        }
      } catch (error) {
        console.error('Error saving curriculum:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save curriculum'
        })
      } finally {
        saving.value = false
      }
    }

    const cancel = () => {
      Swal.fire({
        title: 'Discard Changes?',
        text: 'Any unsaved changes will be lost.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, discard'
      }).then((result) => {
        if (result.isConfirmed) {
          router.push('/admin/instruction/curriculum')
        }
      })
    }

    onMounted(() => {
      if (editMode.value) {
        fetchCurriculum()
      }
    })

    return {
      loading,
      saving,
      editMode,
      form,
      errors,
      saveCurriculum,
      cancel
    }
  }
}
</script>

<style scoped>
/* Same styles as SyllabusEdit.vue */
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

.form-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.form-control, .form-select {
  border-radius: 10px;
  border: 2px solid #e0e0e0;
  padding: 12px 15px;
  transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 0.3rem rgba(52, 152, 219, 0.25);
}

textarea.form-control {
  min-height: 100px;
}

.is-invalid {
  border-color: #e74c3c;
}

.invalid-feedback {
  color: #e74c3c;
  font-size: 0.85rem;
  margin-top: 5px;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
}

.btn {
  padding: 12px 25px;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.3s;
}

.btn-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  border: none;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.btn-outline-secondary {
  border: 2px solid #e9ecef;
  color: #6c757d;
}

.btn-outline-secondary:hover {
  background: #f8f9fa;
  border-color: #3498db;
  color: #3498db;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>