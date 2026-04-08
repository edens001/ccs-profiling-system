<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Edit Event'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-pencil"></i>
              </div>
              <div>
                <h2 class="mb-1">Edit Event</h2>
                <p class="text-muted mb-0">Update event information</p>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading event details...</p>
          </div>

          <!-- Form -->
          <form v-else @submit.prevent="updateEvent">
            <div class="form-section">
              <div class="row">
                <div class="col-12 mb-3">
                  <label class="form-label">
                    Event Title <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.title" 
                    required
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
                    :class="{ 'is-invalid': errors.description }"
                  ></textarea>
                  <div v-if="errors.description" class="invalid-feedback">{{ errors.description }}</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    Event Type <span class="text-danger">*</span>
                  </label>
                  <select 
                    class="form-select" 
                    v-model="form.event_type" 
                    required
                    :class="{ 'is-invalid': errors.event_type }"
                  >
                    <option value="Curricular">Curricular</option>
                    <option value="Extracurricular">Extracurricular</option>
                  </select>
                  <div v-if="errors.event_type" class="invalid-feedback">{{ errors.event_type }}</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    Event Date <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="form.event_date" 
                    required
                    :class="{ 'is-invalid': errors.event_date }"
                  >
                  <div v-if="errors.event_date" class="invalid-feedback">{{ errors.event_date }}</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Event Time</label>
                  <input 
                    type="time" 
                    class="form-control" 
                    v-model="form.event_time"
                    :class="{ 'is-invalid': errors.event_time }"
                  >
                  <div v-if="errors.event_time" class="invalid-feedback">{{ errors.event_time }}</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">
                    Venue <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.venue" 
                    required
                    :class="{ 'is-invalid': errors.venue }"
                  >
                  <div v-if="errors.venue" class="invalid-feedback">{{ errors.venue }}</div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Organizer</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.organizer"
                    :class="{ 'is-invalid': errors.organizer }"
                  >
                  <div v-if="errors.organizer" class="invalid-feedback">{{ errors.organizer }}</div>
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
              <button type="button" class="btn btn-outline-secondary" @click="cancel">
                <i class="bi bi-x-circle me-2"></i>
                Cancel
              </button>
              <button type="submit" class="btn btn-warning" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-save me-2"></i>
                {{ saving ? 'Updating...' : 'Update Event' }}
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
  name: 'EventEdit',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const store = useStore()
    const loading = ref(true)
    const saving = ref(false)
    const errors = ref({})

    const form = ref({
      title: '',
      description: '',
      event_type: '',
      event_date: '',
      event_time: '',
      venue: '',
      organizer: ''
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchEvent = async () => {
      try {
        const token = store.state.auth.token
        const eventId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/events/view.php?id=${eventId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          const event = response.data.data
          form.value = {
            title: event.title,
            description: event.description || '',
            event_type: event.event_type,
            event_date: event.event_date,
            event_time: event.event_time || '',
            venue: event.venue,
            organizer: event.organizer || ''
          }
        }
      } catch (error) {
        console.error('Error fetching event:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to load event'
        }).then(() => {
          router.push('/admin/events')
        })
      } finally {
        loading.value = false
      }
    }

    const validateForm = () => {
      errors.value = {}
      let isValid = true

      if (!form.value.title) {
        errors.value.title = 'Title is required'
        isValid = false
      }
      if (!form.value.event_type) {
        errors.value.event_type = 'Event type is required'
        isValid = false
      }
      if (!form.value.event_date) {
        errors.value.event_date = 'Event date is required'
        isValid = false
      }
      if (!form.value.venue) {
        errors.value.venue = 'Venue is required'
        isValid = false
      }

      return isValid
    }

    const updateEvent = async () => {
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
        const eventId = route.params.id
        
        const response = await axios.put(`${API_URL}/admin/events/update.php?id=${eventId}`, form.value, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          await Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Event has been updated successfully.',
            timer: 1500,
            showConfirmButton: false
          })
          router.push('/admin/events')
        }
      } catch (error) {
        console.error('Error updating event:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: error.response?.data?.message || 'Failed to update event'
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
          router.push('/admin/events')
        }
      })
    }

    onMounted(() => {
      fetchEvent()
    })

    return {
      loading,
      saving,
      form,
      errors,
      updateEvent,
      cancel
    }
  }
}
</script>

<style scoped>
/* Same styles as EventAdd.vue */
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

.form-section {
  background: #f8f9fa;
  border-radius: 15px;
  padding: 25px;
  margin-bottom: 25px;
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

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

.btn {
  padding: 12px 25px;
  border-radius: 10px;
  font-weight: 600;
}

.btn-warning {
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border: none;
  color: white;
}

.btn-warning:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
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