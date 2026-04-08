<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Add New Faculty'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header with Progress -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon bg-primary-light me-3">
                <i class="bi bi-person-plus-fill text-primary"></i>
              </div>
              <div>
                <h4 class="mb-1">Add New Faculty</h4>
                <p class="text-muted mb-0">Fill in the faculty information below</p>
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-info p-2">
                <i class="bi bi-info-circle me-1"></i>
                Step {{ currentStep }} of {{ totalSteps }}
              </span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="progress-container mb-4">
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
          <form @submit.prevent="saveFaculty">
            <!-- Step 1: Personal Information -->
            <div v-show="currentStep === 1" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-person me-2 text-primary"></i>
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
                    >
                    <div v-if="errors.last_name" class="invalid-feedback">{{ errors.last_name }}</div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Middle Name</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.middle_name"
                      placeholder="Enter middle name"
                    >
                  </div>
                </div>
              </div>

              <!-- Account Information (within Step 1) -->
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-shield-lock me-2 text-primary"></i>
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
                    <small class="text-muted">Username will be: firstname.lastname</small>
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
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Contact Information -->
            <div v-show="currentStep === 2" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-envelope me-2 text-primary"></i>
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
                        placeholder="faculty@ccs.edu"
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
              </div>
            </div>

            <!-- Step 3: Professional Information -->
            <div v-show="currentStep === 3" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-briefcase me-2 text-primary"></i>
                  Professional Information
                </div>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">
                      Department <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.department" required :class="{ 'is-invalid': errors.department }">
                      <option value="">Select Department</option>
                      <option value="Computer Studies">Computer Studies</option>
                      <option value="Information Technology">Information Technology</option>
                      <option value="Computer Science">Computer Science</option>
                    </select>
                    <div v-if="errors.department" class="invalid-feedback">{{ errors.department }}</div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">
                      Designation <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.designation" required :class="{ 'is-invalid': errors.designation }">
                      <option value="">Select Designation</option>
                      <option value="Professor">Professor</option>
                      <option value="Associate Professor">Associate Professor</option>
                      <option value="Assistant Professor">Assistant Professor</option>
                      <option value="Instructor">Instructor</option>
                      <option value="Lecturer">Lecturer</option>
                    </select>
                    <div v-if="errors.designation" class="invalid-feedback">{{ errors.designation }}</div>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Specialization</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.specialization"
                      placeholder="e.g., Web Development, Data Science"
                    >
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">
                      Employment Status <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" v-model="form.employment_status" required :class="{ 'is-invalid': errors.employment_status }">
                      <option value="">Select Status</option>
                      <option value="Full-time">Full-time</option>
                      <option value="Part-time">Part-time</option>
                      <option value="Contractual">Contractual</option>
                    </select>
                    <div v-if="errors.employment_status" class="invalid-feedback">{{ errors.employment_status }}</div>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">
                      Date Hired <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="date" 
                      class="form-control" 
                      v-model="form.date_hired" 
                      required
                      :class="{ 'is-invalid': errors.date_hired }"
                    >
                    <div v-if="errors.date_hired" class="invalid-feedback">{{ errors.date_hired }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Educational Background -->
            <div v-show="currentStep === 4" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-mortarboard me-2 text-primary"></i>
                  Educational Background
                </div>
                
                <!-- Tertiary Education -->
                <div class="education-level mb-4">
                  <h6 class="fw-bold mb-3">Tertiary Education</h6>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Degree</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.tertiary.degree"
                        placeholder="e.g., Bachelor of Science in Computer Science"
                      >
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">School/University</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.tertiary.school"
                        placeholder="e.g., University of the Philippines"
                      >
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">Year Graduated</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.tertiary.year"
                        placeholder="e.g., 2014"
                      >
                    </div>
                  </div>
                </div>

                <!-- Graduate Studies -->
                <div class="education-level mb-4">
                  <h6 class="fw-bold mb-3">Graduate Studies</h6>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Degree</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.graduate.degree"
                        placeholder="e.g., Master of Information Technology"
                      >
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">School/University</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.graduate.school"
                        placeholder="e.g., Ateneo de Manila University"
                      >
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">Year Graduated</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.graduate.year"
                        placeholder="e.g., 2016"
                      >
                    </div>
                  </div>
                </div>

                <!-- Doctorate Degree -->
                <div class="education-level">
                  <h6 class="fw-bold mb-3">Doctorate Degree</h6>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Degree</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.doctorate.degree"
                        placeholder="e.g., Doctor of Philosophy in Computer Science"
                      >
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">School/University</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.doctorate.school"
                        placeholder="e.g., University of the Philippines"
                      >
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <label class="form-label">Year Graduated</label>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.education.doctorate.year"
                        placeholder="e.g., 2020"
                      >
                    </div>
                  </div>
                </div>

                <div class="alert alert-info mt-3">
                  <i class="bi bi-info-circle me-2"></i>
                  Educational background is optional but recommended for faculty records.
                </div>
              </div>
            </div>

            <!-- Step 5: Review & Submit -->
            <div v-show="currentStep === 5" class="step-content">
              <div class="form-section">
                <div class="section-title">
                  <i class="bi bi-check-circle me-2 text-primary"></i>
                  Review Information
                </div>
                
                <div class="review-card">
                  <h6 class="fw-bold mb-3">Personal Information</h6>
                  <div class="row mb-4">
                    <div class="col-md-6">
                      <div class="review-item">
                        <span class="review-label">Full Name:</span>
                        <span class="review-value">{{ fullName }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Username:</span>
                        <span class="review-value">{{ generatedUsername }}</span>
                      </div>
                    </div>
                  </div>

                  <h6 class="fw-bold mb-3">Contact Information</h6>
                  <div class="row mb-4">
                    <div class="col-md-6">
                      <div class="review-item">
                        <span class="review-label">Email:</span>
                        <span class="review-value">{{ form.email || '—' }}</span>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="review-item">
                        <span class="review-label">Contact:</span>
                        <span class="review-value">{{ form.contact_number || '—' }}</span>
                      </div>
                    </div>
                  </div>

                  <h6 class="fw-bold mb-3">Professional Information</h6>
                  <div class="row mb-4">
                    <div class="col-md-6">
                      <div class="review-item">
                        <span class="review-label">Department:</span>
                        <span class="review-value">{{ form.department || '—' }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Designation:</span>
                        <span class="review-value">{{ form.designation || '—' }}</span>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="review-item">
                        <span class="review-label">Employment Status:</span>
                        <span class="review-value">{{ form.employment_status || '—' }}</span>
                      </div>
                      <div class="review-item">
                        <span class="review-label">Date Hired:</span>
                        <span class="review-value">{{ form.date_hired || '—' }}</span>
                      </div>
                    </div>
                  </div>

                  <h6 class="fw-bold mb-3">Educational Background</h6>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="review-item">
                        <span class="review-label">Tertiary:</span>
                        <span class="review-value">{{ educationSummary.tertiary || 'Not provided' }}</span>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="review-item">
                        <span class="review-label">Graduate:</span>
                        <span class="review-value">{{ educationSummary.graduate || 'Not provided' }}</span>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="review-item">
                        <span class="review-label">Doctorate:</span>
                        <span class="review-value">{{ educationSummary.doctorate || 'Not provided' }}</span>
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
                  {{ loading ? 'Saving...' : 'Save Faculty' }}
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
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultyAdd',
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
    
    const steps = ['Personal', 'Contact', 'Professional', 'Education', 'Review']
    
    const form = ref({
      first_name: '',
      last_name: '',
      middle_name: '',
      email: '',
      contact_number: '',
      department: '',
      designation: '',
      specialization: '',
      employment_status: '',
      date_hired: '',
      education: {
        tertiary: { degree: '', school: '', year: '' },
        graduate: { degree: '', school: '', year: '' },
        doctorate: { degree: '', school: '', year: '' }
      }
    })
    
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

    const educationSummary = computed(() => {
      return {
        tertiary: form.value.education.tertiary.degree ? 
          `${form.value.education.tertiary.degree} (${form.value.education.tertiary.year || 'n/a'})` : null,
        graduate: form.value.education.graduate.degree ? 
          `${form.value.education.graduate.degree} (${form.value.education.graduate.year || 'n/a'})` : null,
        doctorate: form.value.education.doctorate.degree ? 
          `${form.value.education.doctorate.degree} (${form.value.education.doctorate.year || 'n/a'})` : null
      }
    })

    // Validation per step
    const validateStep = (step) => {
      errors.value = {}
      
      if (step === 1) {
        if (!form.value.first_name) errors.value.first_name = 'First name is required'
        if (!form.value.last_name) errors.value.last_name = 'Last name is required'
      }
      
      if (step === 2) {
        if (!form.value.email) {
          errors.value.email = 'Email is required'
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
          errors.value.email = 'Invalid email format'
        }
        if (!form.value.contact_number) {
          errors.value.contact_number = 'Contact number is required'
        } else if (!/^09\d{9}$/.test(form.value.contact_number)) {
          errors.value.contact_number = 'Must be 11 digits starting with 09'
        }
      }
      
      if (step === 3) {
        if (!form.value.department) errors.value.department = 'Department is required'
        if (!form.value.designation) errors.value.designation = 'Designation is required'
        if (!form.value.employment_status) errors.value.employment_status = 'Employment status is required'
        if (!form.value.date_hired) errors.value.date_hired = 'Date hired is required'
      }

      return Object.keys(errors.value).length === 0
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
    
    const saveFaculty = async () => {
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
        
        // Prepare data for API
        const facultyData = {
          ...form.value,
          username: generatedUsername.value,
          education: JSON.stringify(form.value.education) // Convert to JSON string
        }
        
        const response = await axios.post(`${API_URL}/admin/faculty/index.php`, facultyData, {
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
                <p class="mt-3">Faculty member has been added successfully.</p>
                <p class="mb-0"><strong>Username:</strong> ${generatedUsername.value}</p>
                <p><strong>Default Password:</strong> password123</p>
                <p class="small text-muted">Please inform the faculty member to change their password upon first login.</p>
              </div>
            `,
            confirmButtonText: 'OK'
          })
          
          router.push('/admin/faculty')
        }
      } catch (error) {
        console.error('Error saving faculty:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: error.response?.data?.message || 'Failed to add faculty member. Please try again.'
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
          router.push('/admin/faculty')
        }
      })
    }
    
    return {
      form,
      loading,
      errors,
      currentStep,
      totalSteps,
      steps,
      generatedUsername,
      fullName,
      educationSummary,
      saveFaculty,
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
  animation: fadeIn 0.3s ease;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
}

.header-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.bg-primary-light {
  background: rgba(52, 152, 219, 0.15);
}

/* Progress Steps */
.progress-container {
  margin-bottom: 30px;
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
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
  background: #27ae60;
  color: white;
  border-color: #27ae60;
}

.progress-step.active .step-indicator {
  background: #3498db;
  color: white;
  border-color: #3498db;
  transform: scale(1.1);
}

.step-indicator {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: white;
  border: 2px solid #dee2e6;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 10px;
  font-weight: bold;
  transition: all 0.3s;
}

.step-label {
  font-size: 0.85rem;
  color: #6c757d;
  font-weight: 500;
}

.progress-step.active .step-label {
  color: #3498db;
  font-weight: bold;
}

.progress-bar-container {
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #3498db, #2980b9);
  transition: width 0.3s ease;
}

/* Form Sections */
.form-section {
  background: #f8f9fa;
  border-radius: 16px;
  padding: 25px;
  margin-bottom: 25px;
  animation: slideIn 0.3s ease;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e9ecef;
}

/* Education Level */
.education-level {
  padding: 20px;
  background: white;
  border-radius: 12px;
  margin-bottom: 20px;
  border: 1px solid #e9ecef;
}

.education-level h6 {
  color: #2c3e50;
  padding-bottom: 10px;
  border-bottom: 1px dashed #e9ecef;
}

/* Review Card */
.review-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  border: 1px solid #e9ecef;
}

.review-item {
  display: flex;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.review-item:last-child {
  border-bottom: none;
}

.review-label {
  width: 140px;
  font-weight: 500;
  color: #7f8c8d;
}

.review-value {
  flex: 1;
  color: #2c3e50;
  font-weight: 500;
}

/* Form Navigation */
.form-navigation {
  display: flex;
  justify-content: space-between;
  margin-top: 30px;
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
  border-radius: 10px;
  border: 1px solid #e0e0e0;
  padding: 10px 15px;
  transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.input-group-text {
  background-color: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 10px 0 0 10px;
}

/* Buttons */
.btn {
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  border: none;
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.btn-success {
  background: linear-gradient(135deg, #27ae60, #229954);
  border: none;
  color: white;
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

.btn-outline-secondary {
  border: 1px solid #dee2e6;
  color: #6c757d;
}

.btn-outline-secondary:hover {
  background: #f8f9fa;
  color: #2c3e50;
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

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.step-content {
  animation: slideIn 0.3s ease;
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

  .progress-step .step-label {
    font-size: 0.7rem;
  }

  .step-indicator {
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
  }

  .form-navigation {
    flex-direction: column;
    gap: 10px;
  }

  .form-navigation .btn {
    width: 100%;
  }

  .review-item {
    flex-direction: column;
  }

  .review-label {
    width: 100%;
    margin-bottom: 5px;
  }
}
</style>