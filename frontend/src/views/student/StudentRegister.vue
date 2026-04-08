<template>
  <div class="register-wrapper">
    <div class="container py-4">
      <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
          <div class="card shadow border-0">
            <!-- Header with Progress -->
            <div class="card-header bg-info text-white py-3">
              <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                  <i class="bi bi-person-plus-fill me-2"></i>
                  Student Registration
                </h4>
                <span class="badge bg-light text-info p-2">
                  Step {{ currentStep }} of {{ totalSteps }}
                </span>
              </div>
              
              <!-- Progress Bar -->
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-light" 
                     :style="{ width: (currentStep / totalSteps * 100) + '%' }"
                     role="progressbar">
                </div>
              </div>
            </div>
            
            <div class="card-body p-4">
              <!-- Error Alert -->
              <div v-if="apiError" class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Error:</strong> {{ apiError }}
                <button type="button" class="btn-close" @click="apiError = ''"></button>
              </div>

              <!-- Step Indicators -->
              <div class="step-indicators mb-4">
                <div class="row g-2">
                  <div class="col" v-for="(step, index) in steps" :key="index">
                    <div class="step-item text-center" 
                         :class="{ 
                           'active': currentStep === index + 1,
                           'completed': index + 1 < currentStep
                         }"
                         @click="goToStep(index + 1)">
                      <div class="step-circle">
                        <i v-if="index + 1 < currentStep" class="bi bi-check-lg"></i>
                        <span v-else>{{ index + 1 }}</span>
                      </div>
                      <div class="step-label mt-2">{{ step }}</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Form Container -->
              <div class="form-container" style="min-height: 400px;">
                <form @submit.prevent="registerStudent">
                  <!-- Step 1: Personal Information -->
                  <div v-show="currentStep === 1" class="step-content">
                    <h5 class="mb-4 text-info">
                      <i class="bi bi-person me-2"></i>
                      Personal Information
                    </h5>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" v-model="form.first_name" 
                               :class="{ 'is-invalid': errors.first_name }" required>
                        <div v-if="errors.first_name" class="invalid-feedback">
                          {{ errors.first_name }}
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" v-model="form.last_name"
                               :class="{ 'is-invalid': errors.last_name }" required>
                        <div v-if="errors.last_name" class="invalid-feedback">
                          {{ errors.last_name }}
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" v-model="form.middle_name">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Birth Date *</label>
                        <input type="date" class="form-control" v-model="form.birth_date"
                               :class="{ 'is-invalid': errors.birth_date }" required>
                        <div v-if="errors.birth_date" class="invalid-feedback">
                          {{ errors.birth_date }}
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Gender *</label>
                        <select class="form-select" v-model="form.gender"
                                :class="{ 'is-invalid': errors.gender }" required>
                          <option value="">Select Gender</option>
                          <option value="Male">Male</option>
                          <option value="Female">Female</option>
                          <option value="Other">Other</option>
                        </select>
                        <div v-if="errors.gender" class="invalid-feedback">
                          {{ errors.gender }}
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Number *</label>
                        <input type="text" class="form-control" v-model="form.contact_number"
                               :class="{ 'is-invalid': errors.contact_number }" 
                               placeholder="09XXXXXXXXX" required>
                        <div v-if="errors.contact_number" class="invalid-feedback">
                          {{ errors.contact_number }}
                        </div>
                      </div>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Address *</label>
                      <textarea class="form-control" rows="2" v-model="form.address"
                                :class="{ 'is-invalid': errors.address }" required></textarea>
                      <div v-if="errors.address" class="invalid-feedback">
                        {{ errors.address }}
                      </div>
                    </div>
                  </div>
                  
                  <!-- Step 2: Academic Information -->
                  <div v-show="currentStep === 2" class="step-content">
                    <h5 class="mb-4 text-info">
                      <i class="bi bi-book me-2"></i>
                      Academic Information
                    </h5>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Course *</label>
                        <select class="form-select" v-model="form.course"
                                :class="{ 'is-invalid': errors.course }" required @change="onCourseChange">
                          <option value="">Select Course</option>
                          <option v-for="course in courses" :key="course.id" :value="course.id">
                            {{ course.course_name }}
                          </option>
                        </select>
                        <div v-if="errors.course" class="invalid-feedback">
                          {{ errors.course }}
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Year Level *</label>
                        <select class="form-select" v-model="form.year_level"
                                :class="{ 'is-invalid': errors.year_level }" required>
                          <option value="">Select Year</option>
                          <option value="1">1st Year</option>
                          <option value="2">2nd Year</option>
                          <option value="3">3rd Year</option>
                          <option value="4">4th Year</option>
                        </select>
                        <div v-if="errors.year_level" class="invalid-feedback">
                          {{ errors.year_level }}
                        </div>
                      </div>
                    </div>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Section *</label>
                        <select class="form-select" v-model="form.section"
                                :class="{ 'is-invalid': errors.section }" required>
                          <option value="">Select Section</option>
                          <option value="A">Section A</option>
                          <option value="B">Section B</option>
                          <option value="C">Section C</option>
                          <option value="D">Section D</option>
                        </select>
                        <div v-if="errors.section" class="invalid-feedback">
                          {{ errors.section }}
                        </div>
                      </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                      <i class="bi bi-info-circle me-2"></i>
                      Your student number will be generated upon registration.
                    </div>
                  </div>
                  
                  <!-- Step 3: Account Information -->
                  <div v-show="currentStep === 3" class="step-content">
                    <h5 class="mb-4 text-info">
                      <i class="bi bi-shield-lock me-2"></i>
                      Account Information
                    </h5>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" v-model="form.email"
                               :class="{ 'is-invalid': errors.email }" 
                               placeholder="your.email@student.ccs.edu" required>
                        <div v-if="errors.email" class="invalid-feedback">
                          {{ errors.email }}
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" v-model="form.password"
                               :class="{ 'is-invalid': errors.password }" required>
                        <div v-if="errors.password" class="invalid-feedback">
                          {{ errors.password }}
                        </div>
                      </div>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Confirm Password *</label>
                      <input type="password" class="form-control" v-model="form.confirm_password"
                             :class="{ 'is-invalid': errors.confirm_password }" required>
                      <div v-if="errors.confirm_password" class="invalid-feedback">
                        {{ errors.confirm_password }}
                      </div>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="password-strength mb-3" v-if="form.password">
                      <label class="form-label">Password Strength</label>
                      <div class="progress" style="height: 5px;">
                        <div class="progress-bar" 
                             :class="passwordStrengthClass"
                             :style="{ width: passwordStrength + '%' }">
                        </div>
                      </div>
                      <small class="text-muted">{{ passwordStrengthText }}</small>
                    </div>
                  </div>
                  
                  <!-- Step 4: Guardian & Terms -->
                  <div v-show="currentStep === 4" class="step-content">
                    <h5 class="mb-4 text-info">
                      <i class="bi bi-people me-2"></i>
                      Guardian Information & Terms
                    </h5>
                    
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Guardian Name</label>
                        <input type="text" class="form-control" v-model="form.guardian_name">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Guardian Contact</label>
                        <input type="text" class="form-control" v-model="form.guardian_contact">
                      </div>
                    </div>
                    
                    <!-- Summary Card -->
                    <div class="card bg-light mb-3">
                      <div class="card-body">
                        <h6 class="card-title">Registration Summary</h6>
                        <div class="row small">
                          <div class="col-6">
                            <p class="mb-1"><strong>Name:</strong> {{ fullName || '---' }}</p>
                            <p class="mb-1"><strong>Course:</strong> {{ selectedCourse || '---' }}</p>
                            <p class="mb-1"><strong>Year/Section:</strong> {{ form.year_level || '---' }}{{ form.section || '' }}</p>
                          </div>
                          <div class="col-6">
                            <p class="mb-1"><strong>Email:</strong> {{ form.email || '---' }}</p>
                            <p class="mb-1"><strong>Contact:</strong> {{ form.contact_number || '---' }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="mb-3 form-check">
                      <input type="checkbox" class="form-check-input" id="terms" 
                             v-model="form.terms" :class="{ 'is-invalid': errors.terms }" required>
                      <label class="form-check-label" for="terms">
                        I agree to the <a href="#" @click.prevent="showTerms">Terms and Conditions</a>
                      </label>
                      <div v-if="errors.terms" class="invalid-feedback">
                        {{ errors.terms }}
                      </div>
                    </div>
                  </div>
                  
                  <!-- Navigation Buttons -->
                  <div class="form-navigation mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between">
                      <button type="button" class="btn btn-outline-secondary" 
                              @click="prevStep" :disabled="currentStep === 1">
                        <i class="bi bi-chevron-left me-2"></i>
                        Previous
                      </button>
                      
                      <div>
                        <button type="button" class="btn btn-info text-white me-2" 
                                v-if="currentStep < totalSteps" @click="nextStep">
                          Next
                          <i class="bi bi-chevron-right ms-2"></i>
                        </button>
                        
                        <button type="submit" class="btn btn-success" 
                                v-if="currentStep === totalSteps" :disabled="loading">
                          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                          <i class="bi bi-check-circle me-2" v-else></i>
                          {{ loading ? 'Processing...' : 'Complete Registration' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
            
            <!-- Footer Link -->
            <div class="card-footer bg-white text-center py-3">
              Already have an account? 
              <router-link to="/student/login" class="text-info fw-bold">
                Login here
              </router-link>
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
import axios from 'axios'
import Swal from 'sweetalert2'

export default {
  name: 'StudentRegister',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const currentStep = ref(1)
    const totalSteps = 4
    const apiError = ref('')
    
    const steps = ['Personal', 'Academic', 'Account', 'Confirm']
    
    const form = ref({
      first_name: '',
      last_name: '',
      middle_name: '',
      birth_date: '',
      gender: '',
      contact_number: '',
      address: '',
      course: '',
      year_level: '',
      section: '',
      email: '',
      password: '',
      confirm_password: '',
      guardian_name: '',
      guardian_contact: '',
      terms: false
    })
    
    const errors = ref({})
    
    const courses = ref([])
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Computed Properties
    const fullName = computed(() => {
      const names = [form.value.first_name, form.value.middle_name, form.value.last_name]
      return names.filter(n => n).join(' ')
    })
    
    const selectedCourse = computed(() => {
      const course = courses.value.find(c => c.id === parseInt(form.value.course))
      return course ? course.course_name : ''
    })
    
    const passwordStrength = computed(() => {
      const pwd = form.value.password
      if (!pwd) return 0
      
      let strength = 0
      if (pwd.length >= 8) strength += 25
      if (pwd.match(/[a-z]+/)) strength += 25
      if (pwd.match(/[A-Z]+/)) strength += 25
      if (pwd.match(/[0-9]+/) || pwd.match(/[$@#&!]+/)) strength += 25
      
      return strength
    })
    
    const passwordStrengthClass = computed(() => {
      const strength = passwordStrength.value
      if (strength <= 25) return 'bg-danger'
      if (strength <= 50) return 'bg-warning'
      if (strength <= 75) return 'bg-info'
      return 'bg-success'
    })
    
    const passwordStrengthText = computed(() => {
      const strength = passwordStrength.value
      if (strength <= 25) return 'Weak'
      if (strength <= 50) return 'Fair'
      if (strength <= 75) return 'Good'
      return 'Strong'
    })

    // Fetch courses from database
    const fetchCourses = async () => {
      try {
        console.log('Fetching courses from:', `${API_URL}/courses/list.php`)
        const response = await axios.get(`${API_URL}/courses/list.php`)
        console.log('Courses response:', response.data)
        
        if (response.data.success) {
          courses.value = response.data.data
          console.log('Courses loaded:', courses.value)
        } else {
          console.warn('Failed to fetch courses, using fallback')
          // Fallback courses
          courses.value = [
            { id: 1, course_name: 'BS Computer Science' },
            { id: 2, course_name: 'BS Information Technology' },
            { id: 3, course_name: 'BS Information Systems' }
          ]
        }
      } catch (error) {
        console.error('Error fetching courses:', error)
        // Fallback courses
        courses.value = [
          { id: 1, course_name: 'BS Computer Science' },
          { id: 2, course_name: 'BS Information Technology' },
          { id: 3, course_name: 'BS Information Systems' }
        ]
      }
    }

    // Course change handler
    const onCourseChange = () => {
      console.log('Course selected:', form.value.course)
    }
    
    // Validation methods
    const validateStep = (step) => {
      errors.value = {}
      
      if (step === 1) {
        if (!form.value.first_name?.trim()) errors.value.first_name = 'First name is required'
        if (!form.value.last_name?.trim()) errors.value.last_name = 'Last name is required'
        if (!form.value.birth_date) errors.value.birth_date = 'Birth date is required'
        if (!form.value.gender) errors.value.gender = 'Gender is required'
        if (!form.value.contact_number?.trim()) {
          errors.value.contact_number = 'Contact number is required'
        } else if (!form.value.contact_number.match(/^09\d{9}$/)) {
          errors.value.contact_number = 'Please enter a valid 11-digit mobile number starting with 09'
        }
        if (!form.value.address?.trim()) errors.value.address = 'Address is required'
      }
      
      if (step === 2) {
        if (!form.value.course) {
          errors.value.course = 'Course is required'
        } else {
          // Verify that the selected course exists in the courses list
          const courseExists = courses.value.some(c => c.id === parseInt(form.value.course))
          if (!courseExists) {
            errors.value.course = 'Invalid course selected'
          }
        }
        if (!form.value.year_level) errors.value.year_level = 'Year level is required'
        if (!form.value.section) errors.value.section = 'Section is required'
      }
      
      if (step === 3) {
        if (!form.value.email?.trim()) {
          errors.value.email = 'Email is required'
        } else if (!form.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
          errors.value.email = 'Please enter a valid email address'
        }
        
        if (!form.value.password) {
          errors.value.password = 'Password is required'
        } else if (form.value.password.length < 6) {
          errors.value.password = 'Password must be at least 6 characters'
        }
        
        if (!form.value.confirm_password) {
          errors.value.confirm_password = 'Please confirm your password'
        } else if (form.value.password !== form.value.confirm_password) {
          errors.value.confirm_password = 'Passwords do not match'
        }
      }
      
      if (step === 4) {
        if (!form.value.terms) errors.value.terms = 'You must agree to the terms and conditions'
      }
      
      return Object.keys(errors.value).length === 0
    }
    
    const nextStep = () => {
      if (validateStep(currentStep.value)) {
        currentStep.value++
        window.scrollTo({ top: 0, behavior: 'smooth' })
      } else {
        const errorMessages = Object.values(errors.value).join('\n')
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: errorMessages || 'Please fill in all required fields correctly.',
          timer: 3000,
          showConfirmButton: false
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
            text: 'Please complete the current step first.',
            timer: 2000,
            showConfirmButton: false
          })
        }
      }
    }
    
    const registerStudent = async () => {
      if (!validateStep(4)) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please agree to the terms and conditions.'
        })
        return
      }

      // Double-check course validity
      if (!form.value.course) {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          text: 'Please select a course.'
        })
        return
      }

      const courseId = parseInt(form.value.course)
      const courseExists = courses.value.some(c => c.id === courseId)
      
      if (!courseExists) {
        Swal.fire({
          icon: 'error',
          title: 'Invalid Course',
          text: 'The selected course is invalid. Please select another course.'
        })
        return
      }
      
      loading.value = true
      apiError.value = ''
      
      try {
        // Prepare data for API - course is sent as ID
        const registrationData = {
          first_name: form.value.first_name.trim(),
          last_name: form.value.last_name.trim(),
          middle_name: form.value.middle_name?.trim() || '',
          birth_date: form.value.birth_date,
          gender: form.value.gender,
          contact_number: form.value.contact_number.trim(),
          address: form.value.address.trim(),
          course: courseId,
          year_level: form.value.year_level,
          section: form.value.section,
          email: form.value.email.trim().toLowerCase(),
          password: form.value.password,
          guardian_name: form.value.guardian_name?.trim() || '',
          guardian_contact: form.value.guardian_contact?.trim() || ''
        }
        
        console.log('Sending registration data to:', `${API_URL}/student/register.php`)
        console.log('Registration data:', JSON.stringify(registrationData, null, 2))
        
        const response = await axios.post(`${API_URL}/student/register.php`, registrationData, {
          headers: {
            'Content-Type': 'application/json'
          },
          timeout: 10000
        })
        
        console.log('Registration response:', response.data)
        
        if (response.data.success) {
          if (response.data.token) {
            localStorage.setItem('token', response.data.token)
            localStorage.setItem('user', JSON.stringify(response.data.student))
          }
          
          await Swal.fire({
            icon: 'success',
            title: 'Registration Successful!',
            html: `
              <div class="text-center">
                <i class="bi bi-check-circle-fill text-success fs-1 mb-3"></i>
                <p>Your account has been created successfully.</p>
                <p><strong>Student Number:</strong> ${response.data.student?.student_number || 'N/A'}</p>
                <p><strong>Course:</strong> ${response.data.student?.course || 'N/A'}</p>
                <p><strong>Year & Section:</strong> ${response.data.student?.year_level || ''}${response.data.student?.section || ''}</p>
                <p class="mt-3">You can now login to your account.</p>
              </div>
            `,
            confirmButtonText: 'Go to Login',
            confirmButtonColor: '#17a2b8'
          })
          
          router.push('/student/login')
        } else {
          throw new Error(response.data.message || 'Registration failed')
        }
        
      } catch (error) {
        console.error('Registration error:', error)
        
        let errorMessage = 'An unexpected error occurred. Please try again.'
        let errorDetails = ''
        
        if (error.code === 'ECONNABORTED') {
          errorMessage = 'Request timeout. Server is taking too long to respond.'
        } else if (error.response) {
          console.error('Error response data:', error.response.data)
          console.error('Error response status:', error.response.status)
          
          errorDetails = JSON.stringify(error.response.data, null, 2)
          
          if (error.response.data?.message) {
            errorMessage = error.response.data.message
          } else if (error.response.data?.missing_fields) {
            errorMessage = `Missing fields: ${error.response.data.missing_fields.join(', ')}`
          } else if (error.response.status === 400) {
            errorMessage = 'Bad request. Please check your data.'
          } else if (error.response.status === 500) {
            errorMessage = 'Server error. Please try again later.'
          }
        } else if (error.request) {
          console.error('No response received:', error.request)
          errorMessage = 'Cannot connect to server. Please check your internet connection.'
        } else {
          errorMessage = error.message || 'An error occurred'
        }
        
        apiError.value = errorMessage
        
        Swal.fire({
          icon: 'error',
          title: 'Registration Failed',
          html: `
            <p>${errorMessage}</p>
            ${errorDetails ? `<pre class="text-start small bg-light p-2 mt-2" style="max-height: 200px; overflow: auto;">${errorDetails}</pre>` : ''}
          `,
          confirmButtonColor: '#dc3545'
        })
      } finally {
        loading.value = false
      }
    }
    
    const showTerms = () => {
      Swal.fire({
        title: 'Terms and Conditions',
        html: `
          <div style="text-align: left; max-height: 400px; overflow-y: auto;">
            <h6>1. Acceptance of Terms</h6>
            <p class="small">By registering, you agree to comply with the CCS Profiling System policies and guidelines.</p>
            
            <h6>2. Privacy Policy</h6>
            <p class="small">Your personal information will be kept confidential and used only for academic purposes within the institution.</p>
            
            <h6>3. User Responsibility</h6>
            <p class="small">You are responsible for maintaining the confidentiality of your account credentials and all activities under your account.</p>
            
            <h6>4. Academic Integrity</h6>
            <p class="small">All information provided must be accurate, complete, and truthful. Any false information may result in account suspension.</p>
            
            <h6>5. System Usage</h6>
            <p class="small">The system is to be used only for legitimate academic purposes. Any misuse may result in disciplinary action.</p>
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'I Understand',
        width: '600px'
      })
    }
    
    onMounted(() => {
      fetchCourses()
    })

    return {
      form,
      courses,
      loading,
      currentStep,
      totalSteps,
      steps,
      errors,
      apiError,
      fullName,
      selectedCourse,
      passwordStrength,
      passwordStrengthClass,
      passwordStrengthText,
      registerStudent,
      showTerms,
      nextStep,
      prevStep,
      goToStep,
      onCourseChange
    }
  }
}
</script>

<style scoped>
/* Your existing styles remain the same */
.register-wrapper {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px 0;
}

.step-indicators {
  cursor: pointer;
}

.step-item {
  opacity: 0.5;
  transition: all 0.3s;
}

.step-item.active {
  opacity: 1;
}

.step-item.completed {
  opacity: 0.8;
}

.step-item.completed .step-circle {
  background-color: #28a745;
  color: white;
  border-color: #28a745;
}

.step-item.active .step-circle {
  background-color: #17a2b8;
  color: white;
  border-color: #17a2b8;
  transform: scale(1.1);
}

.step-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: white;
  border: 2px solid #dee2e6;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  font-weight: bold;
  transition: all 0.3s;
}

.step-label {
  font-size: 0.8rem;
  color: #6c757d;
  font-weight: 500;
}

.step-item.active .step-label {
  color: #17a2b8;
  font-weight: bold;
}

.form-container {
  position: relative;
}

.step-content {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.form-navigation {
  background-color: white;
}

/* Custom scrollbar for terms modal */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .step-label {
    font-size: 0.7rem;
  }
  
  .step-circle {
    width: 30px;
    height: 30px;
    font-size: 0.9rem;
  }
}
</style>