<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Edit Faculty'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <i class="bi bi-pencil-square fs-2 text-warning me-3"></i>
              <div>
                <h4 class="mb-1">Edit Faculty</h4>
                <p class="text-muted mb-0">Update faculty information</p>
              </div>
            </div>
            <div>
              <span class="badge bg-warning p-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Faculty ID: {{ form.faculty_number }}
              </span>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading faculty data...</p>
          </div>

          <!-- Form -->
          <form v-else @submit.prevent="updateFaculty">
            <!-- Personal Information Section -->
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
                  >
                </div>
              </div>
            </div>
            
            <!-- Contact Information Section -->
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
                      :class="{ 'is-invalid': errors.email }"
                    >
                  </div>
                  <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
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
                      :class="{ 'is-invalid': errors.contact_number }"
                    >
                  </div>
                  <div v-if="errors.contact_number" class="invalid-feedback">{{ errors.contact_number }}</div>
                </div>
              </div>
            </div>
            
            <!-- Professional Information Section -->
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
                <div class="col-md-6 mb-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" v-model="form.status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
              <button type="submit" class="btn btn-warning" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-save me-2"></i>
                {{ loading ? 'Updating...' : 'Update Faculty' }}
              </button>
              <button type="button" class="btn btn-outline-secondary" @click="cancel">
                <i class="bi bi-x-circle me-2"></i>
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultyEdit',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const route = useRoute()
    const router = useRouter()
    const loading = ref(false)
    const errors = ref({})
    
    const form = ref({
      faculty_number: '',
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
      status: 'Active'
    })
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchFaculty = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const facultyId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/faculty/view.php?id=${facultyId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          const faculty = response.data.data
          form.value = {
            id: faculty.id,
            faculty_number: faculty.faculty_number,
            first_name: faculty.first_name,
            last_name: faculty.last_name,
            middle_name: faculty.middle_name || '',
            email: faculty.email,
            contact_number: faculty.contact_number,
            department: faculty.department,
            designation: faculty.designation,
            specialization: faculty.specialization || '',
            employment_status: faculty.employment_status,
            date_hired: faculty.date_hired,
            status: faculty.status
          }
        }
      } catch (error) {
        console.error('Error fetching faculty:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: error.response?.data?.message || 'Failed to fetch faculty data.'
        }).then(() => {
          router.push('/admin/faculty')
        })
      } finally {
        loading.value = false
      }
    }
    
    const validateForm = () => {
      errors.value = {}
      let isValid = true

      if (!form.value.first_name) {
        errors.value.first_name = 'First name is required'
        isValid = false
      }
      if (!form.value.last_name) {
        errors.value.last_name = 'Last name is required'
        isValid = false
      }
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
      if (!form.value.department) {
        errors.value.department = 'Department is required'
        isValid = false
      }
      if (!form.value.designation) {
        errors.value.designation = 'Designation is required'
        isValid = false
      }
      if (!form.value.employment_status) {
        errors.value.employment_status = 'Employment status is required'
        isValid = false
      }
      if (!form.value.date_hired) {
        errors.value.date_hired = 'Date hired is required'
        isValid = false
      }

      return isValid
    }
    
    const updateFaculty = async () => {
      if (!validateForm()) {
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
        const facultyId = route.params.id
        
        const response = await axios.put(`${API_URL}/admin/faculty/update.php?id=${facultyId}`, form.value, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.data.success) {
          await Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Faculty member has been updated successfully.',
            showConfirmButton: false,
            timer: 1500
          })
          
          router.push('/admin/faculty')
        }
      } catch (error) {
        console.error('Error updating faculty:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: error.response?.data?.message || 'Failed to update faculty member. Please try again.'
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
    
    onMounted(() => {
      fetchFaculty()
    })
    
    return {
      form,
      loading,
      errors,
      updateFaculty,
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