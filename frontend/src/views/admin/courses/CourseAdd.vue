<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Add Course'" />
      
      <div class="container-fluid p-4">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon bg-primary">
                <i class="bi bi-plus-circle-fill text-white"></i>
              </div>
              <div>
                <h2 class="mb-1">Add New Course</h2>
                <p class="text-muted mb-0">Create a new course offering</p>
              </div>
            </div>
            <button class="btn btn-secondary btn-lg" @click="$router.back()">
              <i class="bi bi-arrow-left me-2"></i>
              Back to List
            </button>
          </div>

          <!-- Form -->
          <div class="form-container">
            <form @submit.prevent="saveCourse" class="needs-validation" novalidate>
              <div class="row">
                <div class="col-md-6 mb-4">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-code-square me-2 text-primary"></i>
                    Course Code <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="text" 
                    class="form-control form-control-lg" 
                    v-model="form.course_code"
                    placeholder="e.g., BSCS, BSIT"
                    :class="{ 'is-invalid': errors.course_code }"
                    required
                  >
                  <div v-if="errors.course_code" class="invalid-feedback">
                    {{ errors.course_code }}
                  </div>
                  <small class="text-muted">Unique identifier for the course</small>
                </div>

                <div class="col-md-6 mb-4">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-book me-2 text-primary"></i>
                    Course Name <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="text" 
                    class="form-control form-control-lg" 
                    v-model="form.course_name"
                    placeholder="e.g., Bachelor of Science in Computer Science"
                    :class="{ 'is-invalid': errors.course_name }"
                    required
                  >
                  <div v-if="errors.course_name" class="invalid-feedback">
                    {{ errors.course_name }}
                  </div>
                </div>

                <div class="col-md-6 mb-4">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-building me-2 text-primary"></i>
                    Department <span class="text-danger">*</span>
                  </label>
                  <select 
                    class="form-select form-select-lg" 
                    v-model="form.department"
                    :class="{ 'is-invalid': errors.department }"
                    required
                  >
                    <option value="">Select Department</option>
                    <option value="CCS">College of Computer Studies (CCS)</option>
                  </select>
                  <div v-if="errors.department" class="invalid-feedback">
                    {{ errors.department }}
                  </div>
                </div>

                <div class="col-md-6 mb-4">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-calculator me-2 text-primary"></i>
                    Total Units <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="number" 
                    class="form-control form-control-lg" 
                    v-model="form.units"
                    placeholder="e.g., 0"
                    min="0"
                    :class="{ 'is-invalid': errors.units }"
                    required
                  >
                  <div v-if="errors.units" class="invalid-feedback">
                    {{ errors.units }}
                  </div>
                </div>

                <div class="col-12 mb-4">
                  <label class="form-label fw-semibold">
                    <i class="bi bi-card-text me-2 text-primary"></i>
                    Description
                  </label>
                  <textarea 
                    class="form-control" 
                    rows="4"
                    v-model="form.description"
                    placeholder="Enter course description, objectives, and other details..."
                  ></textarea>
                </div>

                <div class="col-12 mb-4">
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="isActive"
                      v-model="form.is_active"
                      checked
                    >
                    <label class="form-check-label fw-semibold" for="isActive">
                      Active Course
                    </label>
                    <small class="d-block text-muted mt-1">
                      Inactive courses will not appear in dropdowns and selections
                    </small>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="form-actions">
                <button type="button" class="btn btn-secondary btn-lg" @click="$router.back()">
                  <i class="bi bi-x-circle me-2"></i>
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary btn-lg" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-check-circle me-2"></i>
                  {{ saving ? 'Saving...' : 'Save Course' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'CourseAdd',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const router = useRouter()
    const store = useStore()
    const saving = ref(false)
    
    const form = ref({
      course_code: '',
      course_name: '',
      department: '',
      units: '',
      description: '',
      is_active: true
    })

    const errors = ref({})

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const validateForm = () => {
      errors.value = {}
      let isValid = true

      if (!form.value.course_code.trim()) {
        errors.value.course_code = 'Course code is required'
        isValid = false
      }

      if (!form.value.course_name.trim()) {
        errors.value.course_name = 'Course name is required'
        isValid = false
      }

      if (!form.value.department) {
        errors.value.department = 'Department is required'
        isValid = false
      }

      if (!form.value.units) {
        errors.value.units = 'Units is required'
        isValid = false
      } else if (form.value.units < 0) {
        errors.value.units = 'Units cannot be negative'
        isValid = false
      }

      return isValid
    }

    const saveCourse = async () => {
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
        const response = await axios.post(`${API_URL}/admin/courses/index.php`, 
          form.value,
          { headers: { 'Authorization': `Bearer ${token}` } }
        )

        if (response.data.success) {
          await Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Course has been created successfully',
            timer: 1500,
            showConfirmButton: false
          })
          router.push('/admin/courses')
        }
      } catch (error) {
        console.error('Error saving course:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save course'
        })
      } finally {
        saving.value = false
      }
    }

    return {
      form,
      errors,
      saving,
      saveCourse
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

/* Form */
.form-container {
  max-width: 900px;
  margin: 0 auto;
}

.form-label {
  margin-bottom: 8px;
  color: #2c3e50;
}

.form-label i {
  font-size: 1.1rem;
}

.form-control, .form-select {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 12px 16px;
  transition: all 0.3s;
  font-size: 1rem;
}

.form-control:focus, .form-select:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.form-control-lg, .form-select-lg {
  padding: 14px 18px;
  font-size: 1.1rem;
}

.form-check-input {
  width: 3rem;
  height: 1.5rem;
  margin-right: 10px;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #27ae60;
  border-color: #27ae60;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
}

.btn {
  border-radius: 12px;
  padding: 12px 30px;
  font-weight: 500;
  transition: all 0.3s;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.btn-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  border: none;
}

.btn-secondary {
  background: #95a5a6;
  border: none;
}

.btn-lg {
  padding: 14px 35px;
  font-size: 1.1rem;
}

.invalid-feedback {
  font-size: 0.9rem;
  margin-top: 5px;
}

/* Responsive */
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
}
</style>