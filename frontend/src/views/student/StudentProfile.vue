<template>
  <div class="app-wrapper">
    <StudentSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'My Profile'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading profile data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4 animate__animated animate__shakeX">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchProfileData">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Profile Content -->
        <template v-else>
          <!-- Profile Header Card -->
          <div class="profile-header-card animate__animated animate__fadeIn">
            <div class="profile-cover"></div>
            <div class="profile-header-content">
              <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                  <img v-if="profile.profile_picture" 
                       :src="getProfileImageUrl(profile.profile_picture)" 
                       class="profile-image"
                       alt="Profile">
                  <div v-else class="profile-initials">
                    {{ getUserInitials(profile.first_name, profile.last_name) }}
                  </div>
                </div>
                <button class="upload-photo-btn" @click="triggerFileUpload" title="Change Photo">
                  <i class="bi bi-camera-fill"></i>
                </button>
                <input type="file" ref="fileInput" class="d-none" accept="image/*" @change="uploadProfilePicture">
              </div>
              <div class="profile-header-info">
                <h2 class="profile-name">{{ profile.first_name }} {{ profile.last_name }}</h2>
                <p class="profile-id">{{ profile.student_number }}</p>
                <div class="profile-badges">
                  <span class="badge bg-primary">{{ profile.course }}</span>
                  <span class="badge bg-success">{{ getYearLevel(profile.year_level) }}</span>
                  <span class="badge bg-info">Section {{ profile.section }}</span>
                </div>
              </div>
              <button class="edit-profile-btn" @click="editProfile">
                <i class="bi bi-pencil-square me-2"></i>Edit Profile
              </button>
            </div>
          </div>

          <!-- Profile Details Tabs -->
          <div class="profile-tabs-card animate__animated animate__fadeInUp">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                <a class="nav-link" :class="{ active: activeTab === tab.id }" 
                   href="#" @click.prevent="activeTab = tab.id">
                  <i :class="tab.icon" class="me-2"></i>
                  {{ tab.name }}
                </a>
              </li>
            </ul>

            <div class="tab-content">
              <!-- Personal Information Tab -->
              <div v-show="activeTab === 'personal'" class="tab-pane active">
                <div class="row">
                  <div class="col-md-6">
                    <div class="info-group">
                      <label>First Name</label>
                      <p>{{ profile.first_name || '—' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Middle Name</label>
                      <p>{{ profile.middle_name || '—' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Last Name</label>
                      <p>{{ profile.last_name || '—' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Birth Date</label>
                      <p>{{ formatDate(profile.birth_date) }}</p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-group">
                      <label>Gender</label>
                      <p>{{ profile.gender || '—' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Contact Number</label>
                      <p>{{ profile.contact_number || '—' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Email Address</label>
                      <p>{{ profile.email || '—' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Address</label>
                      <p>{{ profile.address || '—' }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Academic Information Tab -->
              <div v-show="activeTab === 'academic'" class="tab-pane active">
                <div class="row">
                  <div class="col-md-6">
                    <div class="info-group">
                      <label>Student Number</label>
                      <p><strong>{{ profile.student_number || '—' }}</strong></p>
                    </div>
                    <div class="info-group">
                      <label>Course</label>
                      <p>{{ profile.course || '—' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Year Level</label>
                      <p>{{ getYearLevel(profile.year_level) }}</p>
                    </div>
                    <div class="info-group">
                      <label>Section</label>
                      <p>{{ profile.section || '—' }}</p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-group">
                      <label>Status</label>
                      <p><span class="badge bg-success">{{ profile.status || 'Enrolled' }}</span></p>
                    </div>
                    <div class="info-group">
                      <label>Enrollment Date</label>
                      <p>{{ formatDate(profile.enrolled_at) }}</p>
                    </div>
                    <div class="info-group">
                      <label>Total Units</label>
                      <p>{{ academicSummary.total_units_taken || '0' }}</p>
                    </div>
                    <div class="info-group">
                      <label>Current GPA</label>
                      <p><span class="grade-badge" :class="getGradeClass(academicSummary.current_gpa)">{{ academicSummary.current_gpa || '0.00' }}</span></p>
                    </div>
                  </div>
                </div>

                <hr class="my-4">

                <h6 class="mb-3">Academic Summary</h6>
                <div class="row g-3">
                  <div class="col-sm-6 col-md-3">
                    <div class="summary-card">
                      <div class="summary-icon bg-primary-soft">
                        <i class="bi bi-calendar-check text-primary"></i>
                      </div>
                      <div class="summary-details">
                        <span class="summary-value">{{ academicSummary.total_semesters || '0' }}</span>
                        <span class="summary-label">Semesters</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6 col-md-3">
                    <div class="summary-card">
                      <div class="summary-icon bg-success-soft">
                        <i class="bi bi-book text-success"></i>
                      </div>
                      <div class="summary-details">
                        <span class="summary-value">{{ academicSummary.total_courses || '0' }}</span>
                        <span class="summary-label">Courses</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6 col-md-3">
                    <div class="summary-card">
                      <div class="summary-icon bg-info-soft">
                        <i class="bi bi-grid text-info"></i>
                      </div>
                      <div class="summary-details">
                        <span class="summary-value">{{ academicSummary.total_units_taken || '0' }}</span>
                        <span class="summary-label">Units</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-sm-6 col-md-3">
                    <div class="summary-card">
                      <div class="summary-icon bg-warning-soft">
                        <i class="bi bi-graph-up text-warning"></i>
                      </div>
                      <div class="summary-details">
                        <span class="summary-value">{{ academicSummary.overall_passing_rate || '0' }}%</span>
                        <span class="summary-label">Pass Rate</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Guardian Information Tab -->
              <div v-show="activeTab === 'guardian'" class="tab-pane active">
                <div class="row">
                  <div class="col-md-6">
                    <div class="info-group">
                      <label>Guardian Name</label>
                      <p>{{ profile.guardian_name || 'Not provided' }}</p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-group">
                      <label>Guardian Contact</label>
                      <p>{{ profile.guardian_contact || 'Not provided' }}</p>
                    </div>
                  </div>
                </div>
                <div class="alert alert-info mt-3">
                  <i class="bi bi-info-circle me-2"></i>
                  Guardian information is used for emergency contact purposes only.
                </div>
              </div>

              <!-- Enrollment History Tab -->
              <div v-show="activeTab === 'enrollment'" class="tab-pane active">
                <h6 class="mb-3">Enrollment History</h6>
                <div v-if="enrollmentHistory.length === 0" class="text-center py-5">
                  <i class="bi bi-calendar-x fs-1 text-muted"></i>
                  <p class="text-muted mt-3">No enrollment history found</p>
                </div>
                <div v-else class="timeline">
                  <div v-for="enrollment in enrollmentHistory" :key="enrollment.id" class="timeline-item">
                    <div class="timeline-marker" :class="'bg-' + getEnrollmentStatusColor(enrollment.status)"></div>
                    <div class="timeline-content">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <h6 class="mb-1">{{ enrollment.semester_name }} {{ enrollment.academic_year }}</h6>
                          <p class="mb-2">
                            <span class="badge" :class="'bg-' + getEnrollmentStatusColor(enrollment.status)">
                              {{ enrollment.status }}
                            </span>
                          </p>
                          <p class="small text-muted mb-1">
                            <i class="bi bi-book me-1"></i>{{ enrollment.total_subjects }} subjects
                          </p>
                          <p class="small text-muted mb-1">
                            <i class="bi bi-grid me-1"></i>{{ enrollment.total_units }} units
                          </p>
                          <small class="text-muted">
                            <i class="bi bi-calendar me-1"></i>{{ formatDate(enrollment.enrollment_date) }}
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Security Tab -->
              <div v-show="activeTab === 'security'" class="tab-pane active">
                <div class="security-container">
                  <h6 class="mb-3">Change Password</h6>
                  <form @submit.prevent="changePassword" class="password-form">
                    <div class="mb-3">
                      <label class="form-label">Current Password</label>
                      <div class="input-group">
                        <input :type="showCurrentPassword ? 'text' : 'password'" 
                               class="form-control" 
                               v-model="passwordForm.current"
                               placeholder="Enter current password"
                               required>
                        <button class="btn btn-outline-secondary" type="button" @click="showCurrentPassword = !showCurrentPassword">
                          <i :class="showCurrentPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                      </div>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">New Password</label>
                      <div class="input-group">
                        <input :type="showNewPassword ? 'text' : 'password'" 
                               class="form-control" 
                               v-model="passwordForm.new"
                               placeholder="Enter new password"
                               required
                               minlength="6">
                        <button class="btn btn-outline-secondary" type="button" @click="showNewPassword = !showNewPassword">
                          <i :class="showNewPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                      </div>
                      <div class="password-strength mt-2" v-if="passwordForm.new">
                        <div class="progress" style="height: 4px;">
                          <div class="progress-bar" 
                               :class="passwordStrengthClass"
                               :style="{ width: passwordStrength + '%' }"></div>
                        </div>
                        <small class="text-muted">{{ passwordStrengthText }}</small>
                      </div>
                    </div>
                    
                    <div class="mb-4">
                      <label class="form-label">Confirm New Password</label>
                      <div class="input-group">
                        <input :type="showConfirmPassword ? 'text' : 'password'" 
                               class="form-control" 
                               v-model="passwordForm.confirm"
                               placeholder="Confirm new password"
                               required>
                        <button class="btn btn-outline-secondary" type="button" @click="showConfirmPassword = !showConfirmPassword">
                          <i :class="showConfirmPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                      </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" :disabled="passwordLoading">
                      <span v-if="passwordLoading" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-shield-lock me-2"></i>
                      Change Password
                    </button>
                  </form>
                  
                  <hr class="my-4">
                  
                  <div class="danger-zone">
                    <h6 class="mb-3 text-danger">Danger Zone</h6>
                    <button class="btn btn-outline-danger" @click="deactivateAccount">
                      <i class="bi bi-exclamation-triangle me-2"></i>
                      Deactivate Account
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="bi bi-pencil-square me-2"></i>
              Edit Profile
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveProfile">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="editForm.first_name" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="editForm.last_name" required>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Middle Name</label>
                  <input type="text" class="form-control" v-model="editForm.middle_name">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" v-model="editForm.email" required>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Contact Number</label>
                  <input type="text" class="form-control" v-model="editForm.contact_number" 
                         placeholder="09XXXXXXXXX">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Birth Date</label>
                  <input type="date" class="form-control" v-model="editForm.birth_date">
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label fw-semibold">Address</label>
                <textarea class="form-control" rows="2" v-model="editForm.address"></textarea>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Guardian Name</label>
                  <input type="text" class="form-control" v-model="editForm.guardian_name">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Guardian Contact</label>
                  <input type="text" class="form-control" v-model="editForm.guardian_contact">
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-2"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" @click="saveProfile" :disabled="editLoading">
              <span v-if="editLoading" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { Modal } from 'bootstrap'
import StudentSidebar from '@/components/layout/StudentSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'StudentProfile',
  components: {
    StudentSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    const editLoading = ref(false)
    const passwordLoading = ref(false)
    const activeTab = ref('personal')
    const fileInput = ref(null)
    
    const showCurrentPassword = ref(false)
    const showNewPassword = ref(false)
    const showConfirmPassword = ref(false)
    
    const tabs = [
      { id: 'personal', name: 'Personal Info', icon: 'bi bi-person' },
      { id: 'academic', name: 'Academic', icon: 'bi bi-book' },
      { id: 'guardian', name: 'Guardian', icon: 'bi bi-people' },
      { id: 'enrollment', name: 'Enrollment', icon: 'bi bi-calendar-check' },
      { id: 'security', name: 'Security', icon: 'bi bi-shield-lock' }
    ]
    
    const profile = ref({})
    const currentEnrollment = ref(null)
    const enrollmentHistory = ref([])
    const academicSummary = ref({})
    
    const editForm = reactive({
      first_name: '',
      last_name: '',
      middle_name: '',
      email: '',
      contact_number: '',
      address: '',
      birth_date: '',
      guardian_name: '',
      guardian_contact: ''
    })
    
    const passwordForm = reactive({
      current: '',
      new: '',
      confirm: ''
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Password strength computed properties
    const passwordStrength = computed(() => {
      const pwd = passwordForm.new
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

    const fetchProfileData = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          router.push('/student/login')
          return
        }

        const response = await axios.get(`${API_URL}/student/profile.php`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          profile.value = response.data.profile || {}
          currentEnrollment.value = response.data.current_enrollment || null
          enrollmentHistory.value = response.data.enrollment_history || []
          academicSummary.value = response.data.academic_summary || {}
          
          // Populate edit form
          Object.assign(editForm, profile.value)
        } else {
          throw new Error(response.data.message || 'Failed to load profile')
        }
      } catch (err) {
        console.error('Profile error:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to load profile data'
        
        if (err.response?.status === 401) {
          await store.dispatch('auth/logout')
          router.push('/student/login')
        }
      } finally {
        loading.value = false
      }
    }

    const getProfileImageUrl = (filename) => {
      return `http://localhost/ccs-profiling-system/backend/uploads/profile_pictures/${filename}`
    }

    const getUserInitials = (first, last) => {
      return (first?.charAt(0) || '') + (last?.charAt(0) || '')
    }

    const getYearLevel = (year) => {
      const levels = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' }
      return levels[year] || year
    }

    const getGradeClass = (grade) => {
      if (!grade) return 'grade-default'
      const numGrade = parseFloat(grade)
      if (numGrade <= 1.5) return 'grade-excellent'
      if (numGrade <= 2.0) return 'grade-good'
      if (numGrade <= 2.5) return 'grade-average'
      return 'grade-poor'
    }

    const triggerFileUpload = () => {
      fileInput.value.click()
    }

    const uploadProfilePicture = async (event) => {
      const file = event.target.files[0]
      if (!file) return

      // Validate file type and size
      if (!file.type.match('image.*')) {
        Swal.fire({
          icon: 'error',
          title: 'Invalid File',
          text: 'Please select an image file (JPEG, PNG, GIF)'
        })
        return
      }

      if (file.size > 2 * 1024 * 1024) {
        Swal.fire({
          icon: 'error',
          title: 'File Too Large',
          text: 'Image size should not exceed 2MB'
        })
        return
      }

      const formData = new FormData()
      formData.append('profile_picture', file)

      try {
        const token = store.state.auth.token
        const response = await axios.post(`${API_URL}/student/profile.php?action=upload_picture`, formData, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data.success) {
          profile.value.profile_picture = response.data.filename
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Profile picture updated successfully',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          })
        }
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Upload Failed',
          text: err.response?.data?.message || 'Failed to upload picture'
        })
      }
    }

    const editProfile = () => {
      const modal = new Modal(document.getElementById('editProfileModal'))
      modal.show()
    }

    const saveProfile = async () => {
      editLoading.value = true
      
      try {
        const token = store.state.auth.token
        const response = await axios.put(`${API_URL}/student/profile.php`, editForm, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          await fetchProfileData()
          Modal.getInstance(document.getElementById('editProfileModal')).hide()
          
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Profile updated successfully',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          })
        }
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: err.response?.data?.message || 'Failed to update profile'
        })
      } finally {
        editLoading.value = false
      }
    }

    const changePassword = async () => {
      if (passwordForm.new !== passwordForm.confirm) {
        Swal.fire({
          icon: 'error',
          title: 'Password Mismatch',
          text: 'New passwords do not match'
        })
        return
      }

      if (passwordForm.new.length < 6) {
        Swal.fire({
          icon: 'error',
          title: 'Invalid Password',
          text: 'Password must be at least 6 characters'
        })
        return
      }

      passwordLoading.value = true
      
      try {
        const token = store.state.auth.token
        const response = await axios.post(`${API_URL}/student/profile.php?action=change_password`, {
          current_password: passwordForm.current,
          new_password: passwordForm.new
        }, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          passwordForm.current = ''
          passwordForm.new = ''
          passwordForm.confirm = ''
          
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Password changed successfully',
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          })
        }
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Password Change Failed',
          text: err.response?.data?.message || 'Failed to change password'
        })
      } finally {
        passwordLoading.value = false
      }
    }

    const deactivateAccount = async () => {
      const result = await Swal.fire({
        title: 'Deactivate Account?',
        text: 'This action cannot be undone. Your account will be deactivated.',
        icon: 'warning',
        input: 'password',
        inputLabel: 'Enter your password to confirm',
        inputPlaceholder: 'Password',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, deactivate',
        preConfirm: (password) => {
          if (!password) {
            Swal.showValidationMessage('Password is required')
          }
          return password
        }
      })

      if (result.isConfirmed) {
        try {
          const token = store.state.auth.token
          const response = await axios.delete(`${API_URL}/student/profile.php`, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            },
            data: { password: result.value }
          })

          if (response.data.success) {
            await Swal.fire({
              icon: 'success',
              title: 'Account Deactivated',
              text: 'Your account has been deactivated successfully',
              timer: 2000,
              showConfirmButton: false
            })
            
            await store.dispatch('auth/logout')
            router.push('/')
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Deactivation Failed',
            text: err.response?.data?.message || 'Failed to deactivate account'
          })
        }
      }
    }

    const formatDate = (date) => {
      if (!date) return 'Not provided'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const getEnrollmentStatusColor = (status) => {
      const colors = {
        'Enrolled': 'success',
        'Dropped': 'danger',
        'Completed': 'info'
      }
      return colors[status] || 'secondary'
    }

    onMounted(() => {
      fetchProfileData()
    })

    return {
      loading,
      error,
      editLoading,
      passwordLoading,
      activeTab,
      tabs,
      fileInput,
      showCurrentPassword,
      showNewPassword,
      showConfirmPassword,
      profile,
      currentEnrollment,
      enrollmentHistory,
      academicSummary,
      editForm,
      passwordForm,
      passwordStrength,
      passwordStrengthClass,
      passwordStrengthText,
      fetchProfileData,
      getProfileImageUrl,
      getUserInitials,
      getYearLevel,
      getGradeClass,
      triggerFileUpload,
      uploadProfilePicture,
      editProfile,
      saveProfile,
      changePassword,
      deactivateAccount,
      formatDate,
      getEnrollmentStatusColor
    }
  }
}
</script>

<style scoped>
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background-color: #f8f9fa;
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

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }
}

/* Profile Header Card */
.profile-header-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
  margin-bottom: 25px;
}

.profile-cover {
  height: 120px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.profile-header-content {
  padding: 0 30px 30px;
  position: relative;
}

.profile-avatar-wrapper {
  position: relative;
  width: 120px;
  margin: -60px auto 0;
}

.profile-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 4px solid white;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  overflow: hidden;
  background: white;
}

.profile-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-initials {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  font-weight: bold;
  text-transform: uppercase;
}

.upload-photo-btn {
  position: absolute;
  bottom: 10px;
  right: 10px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #3498db;
  border: 2px solid white;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  z-index: 10;
}

.upload-photo-btn:hover {
  background: #2980b9;
  transform: scale(1.1);
}

.profile-header-info {
  text-align: center;
  margin-top: 15px;
}

.profile-name {
  font-size: 1.8rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: #2c3e50;
}

.profile-id {
  color: #7f8c8d;
  margin-bottom: 15px;
  font-size: 1rem;
}

.profile-badges {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

.profile-badges .badge {
  padding: 8px 16px;
  font-size: 0.9rem;
  border-radius: 50px;
}

.edit-profile-btn {
  position: absolute;
  top: 20px;
  right: 30px;
  padding: 8px 20px;
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  color: #2c3e50;
  font-weight: 500;
  transition: all 0.3s;
}

.edit-profile-btn:hover {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

/* Profile Tabs Card */
.profile-tabs-card {
  background: white;
  border-radius: 16px;
  padding: 25px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.nav-tabs {
  border-bottom: 2px solid #e9ecef;
  margin-bottom: 25px;
  gap: 5px;
}

.nav-tabs .nav-link {
  border: none;
  color: #7f8c8d;
  font-weight: 500;
  padding: 10px 20px;
  transition: all 0.3s;
}

.nav-tabs .nav-link:hover {
  color: #3498db;
  background: #f8f9fa;
  border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-link.active {
  color: #3498db;
  background: transparent;
  border-bottom: 2px solid #3498db;
}

/* Info Groups */
.info-group {
  margin-bottom: 20px;
}

.info-group label {
  display: block;
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-bottom: 5px;
  font-weight: 500;
}

.info-group p {
  font-size: 1rem;
  color: #2c3e50;
  margin-bottom: 0;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 8px;
}

/* Summary Cards */
.summary-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 12px;
  transition: all 0.3s;
}

.summary-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.summary-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.bg-primary-soft { background: #3498db15; }
.bg-success-soft { background: #27ae6015; }
.bg-info-soft { background: #3498db15; }
.bg-warning-soft { background: #f39c1215; }

.summary-details {
  display: flex;
  flex-direction: column;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
  line-height: 1.2;
}

.summary-label {
  font-size: 0.8rem;
  color: #7f8c8d;
}

/* Grade Badge */
.grade-badge {
  padding: 5px 12px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 1.1rem;
}

.grade-excellent {
  background: #27ae6015;
  color: #27ae60;
}

.grade-good {
  background: #3498db15;
  color: #3498db;
}

.grade-average {
  background: #f39c1215;
  color: #f39c12;
}

.grade-poor {
  background: #e74c3c15;
  color: #e74c3c;
}

/* Timeline */
.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline-item {
  position: relative;
  padding-bottom: 25px;
  border-left: 2px solid #e9ecef;
  padding-left: 25px;
  margin-left: -30px;
}

.timeline-item:last-child {
  border-left-color: transparent;
  padding-bottom: 0;
}

.timeline-marker {
  position: absolute;
  left: -9px;
  top: 0;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.timeline-content {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 12px;
  transition: all 0.3s;
}

.timeline-content:hover {
  background: white;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Security Section */
.security-container {
  max-width: 500px;
}

.password-form {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 12px;
}

.password-strength {
  margin-top: 5px;
}

.danger-zone {
  background: #fee9e715;
  padding: 20px;
  border-radius: 12px;
  border: 1px solid #f5c6cb;
}

/* Modal Styles */
:deep(.modal-content) {
  border: none;
  border-radius: 16px;
  overflow: hidden;
}

:deep(.modal-header) {
  border-bottom: none;
  padding: 20px 25px;
}

:deep(.modal-body) {
  padding: 25px;
}

:deep(.modal-footer) {
  border-top: 1px solid #e9ecef;
  padding: 20px 25px;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes shakeX {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate__animated {
  animation-duration: 0.5s;
  animation-fill-mode: both;
}

.animate__fadeIn {
  animation-name: fadeIn;
}

.animate__fadeInUp {
  animation-name: fadeIn;
}

.animate__shakeX {
  animation-name: shakeX;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-header-content {
    padding: 0 20px 20px;
  }
  
  .edit-profile-btn {
    position: static;
    width: 100%;
    margin-top: 15px;
  }
  
  .profile-name {
    font-size: 1.5rem;
  }
  
  .nav-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 5px;
  }
  
  .nav-tabs .nav-link {
    white-space: nowrap;
  }
  
  .summary-card {
    padding: 12px;
  }
  
  .summary-icon {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
  }
  
  .summary-value {
    font-size: 1.2rem;
  }
}
</style>