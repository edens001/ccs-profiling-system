<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="editMode ? 'Edit Syllabus' : 'Add New Syllabus'" />
      
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
                  <router-link to="/admin/instruction/syllabus">Syllabus</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>{{ editMode ? 'Edit Syllabus' : 'Add Syllabus' }}</span>
                </div>
                <h2 class="mb-0">{{ editMode ? 'Edit Syllabus' : 'Create New Syllabus' }}</h2>
                <p class="text-muted mb-0">{{ editMode ? 'Update syllabus information' : 'Fill in the syllabus details below' }}</p>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading syllabus data...</p>
          </div>

          <!-- Form -->
          <form v-else @submit.prevent="saveSyllabus">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Course <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.course_id" required :class="{ 'is-invalid': errors.course_id }">
                  <option value="">Select Course</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">
                    {{ course.course_code }} - {{ course.course_name }}
                  </option>
                </select>
                <div v-if="errors.course_id" class="invalid-feedback">{{ errors.course_id }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Instructor</label>
                <select class="form-select" v-model="form.faculty_id" :class="{ 'is-invalid': errors.faculty_id }">
                  <option value="">Select Instructor</option>
                  <option v-for="faculty in facultyList" :key="faculty.id" :value="faculty.id">
                    {{ faculty.full_name }}
                  </option>
                </select>
                <div v-if="errors.faculty_id" class="invalid-feedback">{{ errors.faculty_id }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Syllabus Title <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.title" 
                  required
                  placeholder="e.g., CS 101 Syllabus - Introduction to Computing"
                  :class="{ 'is-invalid': errors.title }"
                >
                <div v-if="errors.title" class="invalid-feedback">{{ errors.title }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Description</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.description"
                  placeholder="Enter course description"
                  :class="{ 'is-invalid': errors.description }"
                ></textarea>
                <div v-if="errors.description" class="invalid-feedback">{{ errors.description }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Course Objectives</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.objectives"
                  placeholder="Enter course objectives (one per line)"
                  :class="{ 'is-invalid': errors.objectives }"
                ></textarea>
                <small class="text-muted">Separate each objective with a new line</small>
                <div v-if="errors.objectives" class="invalid-feedback">{{ errors.objectives }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Learning Outcomes</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.learning_outcomes"
                  placeholder="Enter learning outcomes (one per line)"
                  :class="{ 'is-invalid': errors.learning_outcomes }"
                ></textarea>
                <small class="text-muted">Separate each outcome with a new line</small>
                <div v-if="errors.learning_outcomes" class="invalid-feedback">{{ errors.learning_outcomes }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Grading System</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.grading_system"
                  placeholder="Enter grading system (e.g., Quizzes: 20%, Activities: 30%, Exams: 50%)"
                  :class="{ 'is-invalid': errors.grading_system }"
                ></textarea>
                <div v-if="errors.grading_system" class="invalid-feedback">{{ errors.grading_system }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Course Policies</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.policies"
                  placeholder="Enter course policies (attendance, late submissions, academic integrity, etc.)"
                  :class="{ 'is-invalid': errors.policies }"
                ></textarea>
                <div v-if="errors.policies" class="invalid-feedback">{{ errors.policies }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Reference Materials</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.reference_materials"
                  placeholder="Enter reference materials (textbooks, online resources, etc.)"
                  :class="{ 'is-invalid': errors.reference_materials }"
                ></textarea>
                <div v-if="errors.reference_materials" class="invalid-feedback">{{ errors.reference_materials }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">File Path (Optional)</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.file_path"
                  placeholder="e.g., /uploads/syllabus/cs101.pdf"
                  :class="{ 'is-invalid': errors.file_path }"
                >
                <div v-if="errors.file_path" class="invalid-feedback">{{ errors.file_path }}</div>
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
                {{ saving ? 'Saving...' : (editMode ? 'Update Syllabus' : 'Save Syllabus') }}
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
  name: 'SyllabusEdit',
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
    const courses = ref([])
    const facultyList = ref([])
    const errors = ref({})

    const editMode = computed(() => route.path.includes('/edit'))

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
      reference_materials: '',
      file_path: ''
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

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

    const fetchSyllabus = async () => {
      if (!editMode.value) return
      
      loading.value = true
      try {
        const token = store.state.auth.token
        const syllabusId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/instruction/syllabus_view.php?id=${syllabusId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          const data = response.data.data
          form.value = {
            id: data.id,
            course_id: data.course_id,
            faculty_id: data.faculty_id || '',
            title: data.title,
            description: data.description || '',
            objectives: data.objectives || '',
            learning_outcomes: data.learning_outcomes || '',
            grading_system: data.grading_system || '',
            policies: data.policies || '',
            reference_materials: data.reference_materials || '',
            file_path: data.file_path || ''
          }
        }
      } catch (error) {
        console.error('Error fetching syllabus:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to load syllabus'
        }).then(() => {
          router.push('/admin/instruction/syllabus')
        })
      } finally {
        loading.value = false
      }
    }

    const validateForm = () => {
      errors.value = {}
      let isValid = true

      if (!form.value.course_id) {
        errors.value.course_id = 'Course is required'
        isValid = false
      }
      if (!form.value.title) {
        errors.value.title = 'Syllabus title is required'
        isValid = false
      }

      return isValid
    }

    const saveSyllabus = async () => {
      if (!validateForm()) {
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
        let response

        if (editMode.value) {
          response = await axios.put(`${API_URL}/admin/instruction/syllabus_update.php?id=${route.params.id}`, form.value, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
        } else {
          response = await axios.post(`${API_URL}/admin/instruction/syllabus.php`, form.value, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
        }

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Syllabus ${editMode.value ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          router.push('/admin/instruction/syllabus')
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
          router.push('/admin/instruction/syllabus')
        }
      })
    }

    onMounted(() => {
      fetchCourses()
      fetchFaculty()
      if (editMode.value) {
        fetchSyllabus()
      }
    })

    return {
      loading,
      saving,
      editMode,
      courses,
      facultyList,
      form,
      errors,
      saveSyllabus,
      cancel
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
  min-height: 120px;
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