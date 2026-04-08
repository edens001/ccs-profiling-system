<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Add New Student'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header with Progress -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-person-plus-fill"></i>
              </div>
              <div class="header-title">
                <h2 class="mb-1">Add New Student</h2>
                <p class="text-muted mb-0">Fill in the student information below</p>
              </div>
            </div>
            <div class="header-badge">
              <span class="badge bg-info">
                <i class="bi bi-info-circle me-1"></i>
                Step {{ currentStep }} of {{ totalSteps }}
              </span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="progress-container">
            <div class="progress-steps">
              <div 
                v-for="(step, index) in steps" 
                :key="index"
                class="progress-step"
                :class="{
                  'active': currentStep === index + 1,
                  'completed': index + 1 < currentStep
                }"
                @click="goToStep(index + 1)"
              >
                <div class="step-indicator">
                  <i v-if="index + 1 < currentStep" class="bi bi-check-lg"></i>
                  <span v-else>{{ index + 1 }}</span>
                </div>
                <div class="step-label">{{ step }}</div>
              </div>
            </div>
            <div class="progress-bar-container">
              <div class="progress-bar-fill" :style="{ width: (currentStep / totalSteps * 100) + '%' }"></div>
            </div>
          </div>

          <!-- Form -->
          <form @submit.prevent="saveStudent">
            <!-- Step 1: Personal Information -->
            <div v-show="currentStep === 1" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-person me-2"></i>
                  Personal Information
                </div>
                
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">
                      First Name <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.first_name" 
                      required
                      placeholder="Enter first name"
                      :class="{ 'is-invalid': errors.first_name }"
                      @input="updateUsername"
                    >
                    <div v-if="errors.first_name" class="invalid-feedback">{{ errors.first_name }}</div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">
                      Last Name <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.last_name" 
                      required
                      placeholder="Enter last name"
                      :class="{ 'is-invalid': errors.last_name }"
                      @input="updateUsername"
                    >
                    <div v-if="errors.last_name" class="invalid-feedback">{{ errors.last_name }}</div>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Middle Name</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.middle_name"
                      placeholder="Enter middle name"
                    >
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">
                      Birth Date <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="date" 
                      class="form-control" 
                      v-model="form.birth_date" 
                      required
                      :class="{ 'is-invalid': errors.birth_date }"
                    >
                    <div v-if="errors.birth_date" class="invalid-feedback">{{ errors.birth_date }}</div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">
                      Gender <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.gender" required :class="{ 'is-invalid': errors.gender }">
                      <option value="">Select Gender</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                      <option value="Other">Other</option>
                    </select>
                    <div v-if="errors.gender" class="invalid-feedback">{{ errors.gender }}</div>
                  </div>
                </div>
              </div>

              <!-- Account Information -->
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-shield-lock me-2"></i>
                  Account Information
                </div>
                <div class="alert alert-info">
                  <i class="bi bi-info-circle me-2"></i>
                  Username will be auto-generated from your name. Default password is <strong>password123</strong>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Generated Username</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-person"></i></span>
                      <input 
                        type="text" 
                        class="form-control bg-light" 
                        :value="generatedUsername" 
                        readonly
                        placeholder="Auto-generated"
                      >
                    </div>
                    <small class="text-muted">Username format: firstname.lastname</small>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Default Password</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-key"></i></span>
                      <input 
                        type="text" 
                        class="form-control bg-light" 
                        value="password123" 
                        readonly
                      >
                    </div>
                    <small class="text-muted">Student can change password after first login</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Contact Information -->
            <div v-show="currentStep === 2" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-envelope me-2"></i>
                  Contact Information
                </div>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">
                      Email Address <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                      <input 
                        type="email" 
                        class="form-control" 
                        v-model="form.email" 
                        required
                        placeholder="student@ccs.edu"
                        :class="{ 'is-invalid': errors.email }"
                      >
                    </div>
                    <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                    <small class="text-muted">This will be used for login and notifications</small>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">
                      Contact Number <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-phone"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.contact_number" 
                        required
                        placeholder="09XXXXXXXXX"
                        :class="{ 'is-invalid': errors.contact_number }"
                      >
                    </div>
                    <div v-if="errors.contact_number" class="invalid-feedback">{{ errors.contact_number }}</div>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">
                    Address <span class="text-danger">*</span>
                  </label>
                  <textarea 
                    class="form-control" 
                    rows="3" 
                    v-model="form.address" 
                    required
                    placeholder="Enter complete address"
                    :class="{ 'is-invalid': errors.address }"
                  ></textarea>
                  <div v-if="errors.address" class="invalid-feedback">{{ errors.address }}</div>
                </div>
              </div>
            </div>

            <!-- Step 3: Academic Information -->
            <div v-show="currentStep === 3" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-book me-2"></i>
                  Academic Information
                </div>
                
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">
                      Course <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.course" required :class="{ 'is-invalid': errors.course }">
                      <option value="">Select Course</option>
                      <option v-for="course in courses" :key="course.id" :value="course.course_name">
                        {{ course.course_name }}
                      </option>
                    </select>
                    <div v-if="errors.course" class="invalid-feedback">{{ errors.course }}</div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">
                      Year Level <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.year_level" required :class="{ 'is-invalid': errors.year_level }">
                      <option value="">Select Year</option>
                      <option value="1">1st Year</option>
                      <option value="2">2nd Year</option>
                      <option value="3">3rd Year</option>
                      <option value="4">4th Year</option>
                    </select>
                    <div v-if="errors.year_level" class="invalid-feedback">{{ errors.year_level }}</div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">
                      Section <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.section" required :class="{ 'is-invalid': errors.section }">
                      <option value="">Select Section</option>
                      <option value="A">Section A</option>
                      <option value="B">Section B</option>
                      <option value="C">Section C</option>
                    </select>
                    <div v-if="errors.section" class="invalid-feedback">{{ errors.section }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Guardian Information -->
            <div v-show="currentStep === 4" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-people me-2"></i>
                  Guardian Information
                </div>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Guardian Name</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.guardian_name"
                      placeholder="Enter guardian name"
                    >
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Guardian Contact</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.guardian_contact"
                      placeholder="Enter guardian contact"
                    >
                  </div>
                </div>
                
                <div class="alert alert-info mt-3">
                  <i class="bi bi-info-circle me-2"></i>
                  Guardian information is optional but recommended for emergency contact.
                </div>
              </div>
            </div>

            <!-- Step 5: Review & Submit -->
            <div v-show="currentStep === 5" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-check-circle me-2"></i>
                  Review Information
                </div>
                
                <div class="review-card">
                  <div class="review-section">
                    <h6 class="review-title">
                      <i class="bi bi-person me-2"></i>
                      Personal Information
                    </h6>
                    <div class="review-grid">
                      <div class="review-item">
                        <span class="review-label">Full Name:</span>
                        <span class="review-value">{{ fullName }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Birth Date:</span>
                        <span class="review-value">{{ formatDate(form.birth_date) }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Gender:</span>
                        <span class="review-value">{{ form.gender }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="review-section">
                    <h6 class="review-title">
                      <i class="bi bi-envelope me-2"></i>
                      Contact Information
                    </h6>
                    <div class="review-grid">
                      <div class="review-item">
                        <span class="review-label">Email:</span>
                        <span class="review-value">{{ form.email || '—' }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Contact:</span>
                        <span class="review-value">{{ form.contact_number || '—' }}</span>
                      </div>
                      <div class="review-item full-width">
                        <span class="review-label">Address:</span>
                        <span class="review-value">{{ form.address || '—' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="review-section">
                    <h6 class="review-title">
                      <i class="bi bi-book me-2"></i>
                      Academic Information
                    </h6>
                    <div class="review-grid">
                      <div class="review-item">
                        <span class="review-label">Course:</span>
                        <span class="review-value">{{ form.course || '—' }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Year Level:</span>
                        <span class="review-value">{{ form.year_level || '—' }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Section:</span>
                        <span class="review-value">{{ form.section || '—' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="review-section">
                    <h6 class="review-title">
                      <i class="bi bi-people me-2"></i>
                      Guardian Information
                    </h6>
                    <div class="review-grid">
                      <div class="review-item">
                        <span class="review-label">Guardian Name:</span>
                        <span class="review-value">{{ form.guardian_name || 'Not provided' }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Guardian Contact:</span>
                        <span class="review-value">{{ form.guardian_contact || 'Not provided' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="review-section">
                    <h6 class="review-title">
                      <i class="bi bi-shield-lock me-2"></i>
                      Account Information
                    </h6>
                    <div class="review-grid">
                      <div class="review-item">
                        <span class="review-label">Username:</span>
                        <span class="review-value">{{ generatedUsername }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Default Password:</span>
                        <span class="review-value">password123</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="form-navigation">
              <button 
                type="button" 
                class="btn btn-outline-secondary" 
                @click="prevStep" 
                :disabled="currentStep === 1"
              >
                <i class="bi bi-chevron-left me-2"></i>
                Previous
              </button>
              
              <div class="d-flex gap-2">
                <button 
                  v-if="currentStep < totalSteps" 
                  type="button" 
                  class="btn btn-primary" 
                  @click="nextStep"
                >
                  Next
                  <i class="bi bi-chevron-right ms-2"></i>
                </button>
                
                <button 
                  v-if="currentStep === totalSteps" 
                  type="submit" 
                  class="btn btn-success" 
                  :disabled="loading"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-check-circle me-2"></i>
                  {{ loading ? 'Saving...' : 'Save Student' }}
                </button>
                
                <button 
                  type="button" 
                  class="btn btn-outline-danger" 
                  @click="cancel"
                >
                  Cancel
                </button>
              </div>
            </div>
          </form>
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
  name: 'StudentAdd',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const errors = ref({})
    const currentStep = ref(1)
    const totalSteps = 5
    
    const steps = ['Personal', 'Contact', 'Academic', 'Guardian', 'Review']
    
    const form = ref({
      student_number: '',
      first_name: '',
      last_name: '',
      middle_name: '',
      birth_date: '',
      gender: '',
      email: '',
      contact_number: '',
      address: '',
      course: '',
      year_level: '',
      section: '',
      guardian_name: '',
      guardian_contact: ''
    })
    
    const courses = ref([
      { id: 1, course_name: 'BS Computer Science' },
      { id: 2, course_name: 'BS Information Technology' },
      { id: 3, course_name: 'BS Information Systems' }
    ])
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Computed Properties
    const generatedUsername = computed(() => {
      if (!form.value.first_name || !form.value.last_name) return '—'
      const first = form.value.first_name.toLowerCase().replace(/[^a-z]/g, '')
      const last = form.value.last_name.toLowerCase().replace(/[^a-z]/g, '')
      return `${first}.${last}`
    })

    const fullName = computed(() => {
      const names = [form.value.first_name, form.value.middle_name, form.value.last_name]
      return names.filter(n => n).join(' ') || '—'
    })

    const updateUsername = () => {
      // Just trigger the computed property update
      generatedUsername.value
    }

    const formatDate = (date) => {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    // Validation per step
    const validateStep = (step) => {
      errors.value = {}
      let isValid = true

      if (step === 1) {
        if (!form.value.first_name) {
          errors.value.first_name = 'First name is required'
          isValid = false
        }
        if (!form.value.last_name) {
          errors.value.last_name = 'Last name is required'
          isValid = false
        }
        if (!form.value.birth_date) {
          errors.value.birth_date = 'Birth date is required'
          isValid = false
        }
        if (!form.value.gender) {
          errors.value.gender = 'Gender is required'
          isValid = false
        }
      }
      
      if (step === 2) {
        if (!form.value.email) {
          errors.value.email = 'Email is required'
          isValid = false
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
          errors.value.email = 'Invalid email format'
          isValid = false
        }
        if (!form.value.contact_number) {
          errors.value.contact_number = 'Contact number is required'
          isValid = false
        } else if (!/^09\d{9}$/.test(form.value.contact_number)) {
          errors.value.contact_number = 'Must be 11 digits starting with 09'
          isValid = false
        }
        if (!form.value.address) {
          errors.value.address = 'Address is required'
          isValid = false
        }
      }
      
      if (step === 3) {
        if (!form.value.course) {
          errors.value.course = 'Course is required'
          isValid = false
        }
        if (!form.value.year_level) {
          errors.value.year_level = 'Year level is required'
          isValid = false
        }
        if (!form.value.section) {
          errors.value.section = 'Section is required'
          isValid = false
        }
      }

      return isValid
    }

    const nextStep = () => {
      if (validateStep(currentStep.value)) {
        currentStep.value++
        window.scrollTo({ top: 0, behavior: 'smooth' })
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please fill in all required fields correctly.'
        })
      }
    }

    const prevStep = () => {
      if (currentStep.value > 1) {
        currentStep.value--
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const goToStep = (step) => {
      if (step < currentStep.value) {
        currentStep.value = step
        window.scrollTo({ top: 0, behavior: 'smooth' })
      } else {
        let valid = true
        for (let i = currentStep.value; i < step; i++) {
          if (!validateStep(i)) {
            valid = false
            break
          }
        }
        if (valid) {
          currentStep.value = step
          window.scrollTo({ top: 0, behavior: 'smooth' })
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Cannot Proceed',
            text: 'Please complete the current step first.'
          })
        }
      }
    }
    
    const saveStudent = async () => {
      if (!validateStep(currentStep.value)) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please fill in all required fields correctly.'
        })
        return
      }
      
      loading.value = true
      
      try {
        const token = store.state.auth.token
        const response = await axios.post(`${API_URL}/admin/students/index.php`, form.value, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.data.success) {
          await Swal.fire({
            icon: 'success',
            title: 'Success!',
            html: `
              <div class="text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <p class="mt-3">Student has been added successfully.</p>
                <p class="mb-0"><strong>Student Number:</strong> ${response.data.data.student_number}</p>
                <p class="mb-0"><strong>Username:</strong> ${response.data.data.username}</p>
                <p><strong>Default Password:</strong> password123</p>
                <p class="small text-muted">Please inform the student to change their password upon first login.</p>
              </div>
            `,
            confirmButtonText: 'OK'
          })
          
          router.push('/admin/students')
        }
      } catch (error) {
        console.error('Error saving student:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: error.response?.data?.message || 'Failed to add student. Please try again.'
        })
      } finally {
        loading.value = false
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
          router.push('/admin/students')
        }
      })
    }
    
    onMounted(() => {
      const year = new Date().getFullYear()
      const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0')
      form.value.student_number = `${year}-${random}`
    })
    
    return {
      form,
      courses,
      loading,
      errors,
      currentStep,
      totalSteps,
      steps,
      generatedUsername,
      fullName,
      updateUsername,
      formatDate,
      saveStudent,
      cancel,
      nextStep,
      prevStep,
      goToStep
    }
  }
}
</script>

<style scoped>
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background: linear-gradient(135deg, #3498db, #2980b9);
  width: 100%;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100% - 280px);
  min-height: 100vh;
  padding: 25px;
  transition: margin-left 0.3s ease;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
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
  border-radius: 30px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
  padding: 35px;
  animation: fadeIn 0.5s ease;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 25px;
  border-bottom: 3px solid #f0f0f0;
}

.header-icon {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, #3498db, #2980b9);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20px;
}

.header-icon i {
  font-size: 2.5rem;
  color: white;
}

.header-title h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin: 0;
}

.header-badge .badge {
  padding: 10px 20px;
  font-size: 1rem;
  border-radius: 30px;
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  color: white;
}

/* Progress Steps */
.progress-container {
  margin-bottom: 35px;
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
}

.progress-step {
  flex: 1;
  text-align: center;
  cursor: pointer;
  opacity: 0.5;
  transition: all 0.3s;
}

.progress-step.active {
  opacity: 1;
}

.progress-step.completed {
  opacity: 0.8;
}

.progress-step.completed .step-indicator {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  color: white;
  border-color: #27ae60;
}

.progress-step.active .step-indicator {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  border-color: #3498db;
  transform: scale(1.1);
  box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.step-indicator {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: white;
  border: 3px solid #dee2e6;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px;
  font-weight: bold;
  font-size: 1.2rem;
  transition: all 0.3s;
}

.step-label {
  font-size: 0.95rem;
  color: #6c757d;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.progress-step.active .step-label {
  color: #3498db;
}

.progress-bar-container {
  height: 10px;
  background: #e9ecef;
  border-radius: 5px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #27ae60, #2ecc71);
  transition: width 0.4s ease;
}

/* Form Sections */
.form-section {
  background: linear-gradient(135deg, #f8f9fa, #ffffff);
  border-radius: 20px;
  padding: 30px;
  margin-bottom: 30px;
  border: 1px solid rgba(52, 152, 219, 0.1);
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.section-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 2px solid #e9ecef;
  display: flex;
  align-items: center;
}

.section-title i {
  color: #3498db;
  font-size: 1.4rem;
}

/* Form Controls */
.form-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 8px;
  font-size: 0.95rem;
}

.form-control, .form-select {
  border-radius: 12px;
  border: 2px solid #e0e0e0;
  padding: 12px 15px;
  font-size: 0.95rem;
  transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 0.3rem rgba(52, 152, 219, 0.25);
}

.form-control[readonly] {
  background-color: #f8f9fa;
  border-color: #e9ecef;
  color: #6c757d;
  cursor: not-allowed;
}

.input-group-text {
  background: linear-gradient(135deg, #3498db, #2980b9);
  border: none;
  color: white;
  border-radius: 12px 0 0 12px;
  padding: 0 15px;
}

.input-group-text i {
  font-size: 1.2rem;
}

/* Review Card */
.review-card {
  background: white;
  border-radius: 20px;
  padding: 30px;
}

.review-section {
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 1px dashed #e9ecef;
}

.review-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.review-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #3498db;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
}

.review-title i {
  font-size: 1.3rem;
  color: #3498db;
}

.review-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.review-item {
  display: flex;
  flex-direction: column;
}

.review-item.full-width {
  grid-column: 1 / -1;
}

.review-label {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.review-value {
  font-size: 1.1rem;
  color: #2c3e50;
  font-weight: 600;
}

/* Form Navigation */
.form-navigation {
  display: flex;
  justify-content: space-between;
  margin-top: 35px;
  padding-top: 25px;
  border-top: 3px solid #f0f0f0;
}

/* Buttons */
.btn {
  padding: 12px 25px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
}

.btn-success {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  color: white;
  box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.btn-success:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
}

.btn-outline-secondary {
  background: white;
  border: 2px solid #e9ecef;
  color: #6c757d;
}

.btn-outline-secondary:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #3498db;
  color: #3498db;
  transform: translateY(-3px);
}

.btn-outline-danger {
  background: white;
  border: 2px solid #e74c3c;
  color: #e74c3c;
}

.btn-outline-danger:hover:not(:disabled) {
  background: #e74c3c;
  color: white;
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Badge */
.badge {
  padding: 8px 16px;
  font-weight: 600;
  font-size: 0.9rem;
  border-radius: 30px;
}

.badge-info {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.step-content {
  animation: slideIn 0.4s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
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
    gap: 20px;
    align-items: flex-start;
  }

  .progress-step .step-label {
    font-size: 0.8rem;
  }

  .step-indicator {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }

  .review-grid {
    grid-template-columns: 1fr;
  }

  .form-navigation {
    flex-direction: column;
    gap: 15px;
  }

  .form-navigation .btn {
    width: 100%;
  }

  .d-flex.gap-2 {
    flex-direction: column;
  }
}
</style>