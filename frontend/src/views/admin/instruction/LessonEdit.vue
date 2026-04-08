<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="editMode ? 'Edit Lesson' : 'Add New Lesson'" />
      
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
                  <router-link to="/admin/instruction/lessons">Lessons</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>{{ editMode ? 'Edit Lesson' : 'Add Lesson' }}</span>
                </div>
                <h2 class="mb-0">{{ editMode ? 'Edit Lesson' : 'Create New Lesson' }}</h2>
                <p class="text-muted mb-0">{{ editMode ? 'Update lesson information' : 'Fill in the lesson details below' }}</p>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading lesson data...</p>
          </div>

          <!-- Form -->
          <form v-else @submit.prevent="saveLesson">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Syllabus <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.syllabus_id" required :class="{ 'is-invalid': errors.syllabus_id }">
                  <option value="">Select Syllabus</option>
                  <option v-for="syllabus in syllabi" :key="syllabus.id" :value="syllabus.id">
                    {{ syllabus.title }} ({{ syllabus.course_code }})
                  </option>
                </select>
                <div v-if="errors.syllabus_id" class="invalid-feedback">{{ errors.syllabus_id }}</div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Week Number <span class="text-danger">*</span></label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="form.week_number" 
                  required
                  min="1"
                  max="18"
                  :class="{ 'is-invalid': errors.week_number }"
                >
                <div v-if="errors.week_number" class="invalid-feedback">{{ errors.week_number }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Topic <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="form.topic" 
                  required
                  placeholder="e.g., Introduction to Programming"
                  :class="{ 'is-invalid': errors.topic }"
                >
                <div v-if="errors.topic" class="invalid-feedback">{{ errors.topic }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Learning Objectives</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.objectives"
                  placeholder="Enter learning objectives for this lesson (one per line)"
                  :class="{ 'is-invalid': errors.objectives }"
                ></textarea>
                <small class="text-muted">What students should learn from this lesson</small>
                <div v-if="errors.objectives" class="invalid-feedback">{{ errors.objectives }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Activities</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.activities"
                  placeholder="Enter activities, exercises, or assignments for this lesson"
                  :class="{ 'is-invalid': errors.activities }"
                ></textarea>
                <div v-if="errors.activities" class="invalid-feedback">{{ errors.activities }}</div>
              </div>

              <div class="col-12 mb-3">
                <label class="form-label">Resources</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="form.resources"
                  placeholder="Enter resources, references, or materials for this lesson"
                  :class="{ 'is-invalid': errors.resources }"
                ></textarea>
                <div v-if="errors.resources" class="invalid-feedback">{{ errors.resources }}</div>
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
                {{ saving ? 'Saving...' : (editMode ? 'Update Lesson' : 'Save Lesson') }}
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
  name: 'LessonEdit',
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
    const syllabi = ref([])
    const errors = ref({})

    const editMode = computed(() => route.path.includes('/edit'))

    const form = ref({
      id: null,
      syllabus_id: '',
      week_number: '',
      topic: '',
      objectives: '',
      activities: '',
      resources: ''
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

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

    const fetchLesson = async () => {
      if (!editMode.value) return
      
      loading.value = true
      try {
        const token = store.state.auth.token
        const lessonId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/instruction/lessons_view.php?id=${lessonId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          const data = response.data.data
          form.value = {
            id: data.id,
            syllabus_id: data.syllabus_id,
            week_number: data.week_number,
            topic: data.topic,
            objectives: data.objectives || '',
            activities: data.activities || '',
            resources: data.resources || ''
          }
        }
      } catch (error) {
        console.error('Error fetching lesson:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to load lesson'
        }).then(() => {
          router.push('/admin/instruction/lessons')
        })
      } finally {
        loading.value = false
      }
    }

    const validateForm = () => {
      errors.value = {}
      let isValid = true

      if (!form.value.syllabus_id) {
        errors.value.syllabus_id = 'Syllabus is required'
        isValid = false
      }
      if (!form.value.week_number) {
        errors.value.week_number = 'Week number is required'
        isValid = false
      } else if (form.value.week_number < 1 || form.value.week_number > 18) {
        errors.value.week_number = 'Week number must be between 1 and 18'
        isValid = false
      }
      if (!form.value.topic) {
        errors.value.topic = 'Topic is required'
        isValid = false
      }

      return isValid
    }

    const saveLesson = async () => {
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
          response = await axios.put(`${API_URL}/admin/instruction/lessons_update.php?id=${route.params.id}`, form.value, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
        } else {
          response = await axios.post(`${API_URL}/admin/instruction/lessons.php`, form.value, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
        }

        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Lesson ${editMode.value ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          router.push('/admin/instruction/lessons')
        }
      } catch (error) {
        console.error('Error saving lesson:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save lesson'
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
          router.push('/admin/instruction/lessons')
        }
      })
    }

    onMounted(() => {
      fetchSyllabi()
      if (editMode.value) {
        fetchLesson()
      }
    })

    return {
      loading,
      saving,
      editMode,
      syllabi,
      form,
      errors,
      saveLesson,
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