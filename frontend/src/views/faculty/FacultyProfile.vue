<template>
  <div class="app-wrapper">
    <FacultySidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'My Profile'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading profile...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchProfile">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Profile Content -->
        <template v-else-if="profile && profile.full_name">
          <div class="row">
            <!-- Profile Card -->
            <div class="col-md-4 mb-4">
              <div class="profile-card">
                <div class="profile-cover"></div>
                <div class="profile-avatar-wrapper">
                  <div class="profile-avatar">
                    {{ userInitials }}
                  </div>
                </div>
                <div class="profile-body text-center">
                  <h4 class="mb-1">{{ profile.full_name || 'Faculty Member' }}</h4>
                  <p class="text-muted mb-2">{{ profile.faculty_number || 'N/A' }}</p>
                  
                  <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success">{{ profile.employment_status || 'N/A' }}</span>
                    <span class="badge bg-info">{{ profile.designation || 'N/A' }}</span>
                  </div>

                  <hr>

                  <div class="profile-stats">
                    <div class="stat-item">
                      <div class="stat-value">{{ summary.total_classes || 0 }}</div>
                      <div class="stat-label">Classes</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-value">{{ summary.total_subjects || 0 }}</div>
                      <div class="stat-label">Subjects</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-value">{{ summary.total_sections || 0 }}</div>
                      <div class="stat-label">Sections</div>
                    </div>
                  </div>

                  <hr>

                  <div class="text-start">
                    <div class="info-item">
                      <i class="bi bi-envelope text-primary"></i>
                      <span>{{ profile.email || 'Not provided' }}</span>
                    </div>
                    <div class="info-item">
                      <i class="bi bi-telephone text-primary"></i>
                      <span>{{ profile.contact_number || 'Not provided' }}</span>
                    </div>
                    <div class="info-item">
                      <i class="bi bi-building text-primary"></i>
                      <span>{{ profile.department || 'Not provided' }}</span>
                    </div>
                    <div class="info-item">
                      <i class="bi bi-briefcase text-primary"></i>
                      <span>{{ profile.designation || 'Not provided' }}</span>
                    </div>
                  </div>

                  <hr>

                  <button class="btn btn-outline-primary w-100" @click="editProfile">
                    <i class="bi bi-pencil me-2"></i> Edit Profile
                  </button>
                </div>
              </div>
            </div>

            <!-- Details -->
            <div class="col-md-8 mb-4">
              <div class="details-card mb-4">
                <div class="card-header">
                  <i class="bi bi-person me-2"></i>
                  <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">First Name</label>
                      <p class="fw-bold">{{ profile.first_name || '—' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">Last Name</label>
                      <p class="fw-bold">{{ profile.last_name || '—' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">Middle Name</label>
                      <p class="fw-bold">{{ profile.middle_name || '—' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">Username</label>
                      <p class="fw-bold">{{ profile.username || '—' }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="details-card mb-4">
                <div class="card-header">
                  <i class="bi bi-briefcase me-2"></i>
                  <h5 class="mb-0">Professional Information</h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">Department</label>
                      <p class="fw-bold">{{ profile.department || '—' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">Designation</label>
                      <p class="fw-bold">{{ profile.designation || '—' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">Specialization</label>
                      <p class="fw-bold">{{ profile.specialization || '—' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="text-muted">Date Hired</label>
                      <p class="fw-bold">{{ formatDate(profile.date_hired) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import FacultySidebar from '@/components/layout/FacultySidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultyProfile',
  components: {
    FacultySidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    const profile = ref({
      full_name: '',
      first_name: '',
      last_name: '',
      middle_name: '',
      username: '',
      email: '',
      contact_number: '',
      department: '',
      designation: '',
      specialization: '',
      date_hired: '',
      faculty_number: '',
      employment_status: ''
    })
    const summary = ref({
      total_classes: 0,
      total_subjects: 0,
      total_sections: 0
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const userInitials = computed(() => {
      // Add a check to prevent accessing undefined
      if (!profile.value || !profile.value.full_name) {
        return 'FC' // Default fallback
      }
      const name = profile.value.full_name
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    })

    const fetchProfile = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          router.push('/faculty/login')
          return
        }

        const response = await axios.get(`${API_URL}/faculty/profile.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          profile.value = response.data.profile
          summary.value = response.data.summary || summary.value
        } else {
          error.value = response.data.message || 'Failed to load profile'
        }
      } catch (err) {
        console.error('Profile error:', err)
        error.value = err.response?.data?.message || 'Failed to load profile'
        
        if (err.response?.status === 401) {
          await store.dispatch('auth/logout')
          router.push('/faculty/login')
        }
      } finally {
        loading.value = false
      }
    }

    const formatDate = (date) => {
      if (!date) return '—'
      try {
        return new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      } catch (e) {
        return '—'
      }
    }

    const editProfile = () => {
      Swal.fire({
        title: 'Edit Profile',
        text: 'This feature is coming soon!',
        icon: 'info'
      })
    }

    onMounted(() => {
      fetchProfile()
    })

    return {
      loading,
      error,
      profile,
      summary,
      userInitials,
      formatDate,
      editProfile,
      fetchProfile
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
  max-width: 1400px;
  margin: 0 auto;
}

.profile-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.profile-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.profile-cover {
  height: 120px;
  background: linear-gradient(135deg, #27ae60, #1e8449);
}

.profile-avatar-wrapper {
  display: flex;
  justify-content: center;
  margin-top: -60px;
  margin-bottom: 20px;
}

.profile-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: linear-gradient(135deg, #27ae60, #1e8449);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  font-weight: bold;
  color: white;
  border: 5px solid white;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.profile-body {
  padding: 0 25px 25px 25px;
}

.profile-stats {
  display: flex;
  justify-content: space-around;
  margin: 20px 0;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #27ae60;
}

.stat-label {
  font-size: 0.85rem;
  color: #6c757d;
  margin-top: 5px;
}

.info-item {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 10px;
  transition: background 0.2s ease;
}

.info-item:hover {
  background: #e9ecef;
}

.info-item i {
  width: 30px;
  font-size: 1.1rem;
}

.info-item span {
  flex: 1;
  color: #2c3e50;
}

.details-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.card-header {
  background: linear-gradient(135deg, #27ae60, #1e8449);
  color: white;
  padding: 15px 25px;
  display: flex;
  align-items: center;
}

.card-header i {
  font-size: 1.2rem;
  margin-right: 10px;
}

.card-header h5 {
  margin: 0;
  font-weight: 600;
}

.card-body {
  padding: 25px;
}

.card-body label {
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
  color: #6c757d;
  display: block;
  margin-bottom: 5px;
}

.card-body p {
  margin: 0;
  font-size: 1rem;
  color: #2c3e50;
  font-weight: 500;
}

hr {
  margin: 20px 0;
  border-color: #e9ecef;
}

.btn-outline-primary {
  border-color: #27ae60;
  color: #27ae60;
  padding: 10px;
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-outline-primary:hover {
  background: #27ae60;
  border-color: #27ae60;
  color: white;
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }
  
  .profile-avatar {
    width: 100px;
    height: 100px;
    font-size: 2.5rem;
  }
  
  .profile-cover {
    height: 100px;
  }
  
  .card-body {
    padding: 20px;
  }
}
</style>