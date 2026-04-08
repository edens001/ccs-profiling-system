<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Event Details'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading event details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchEvent">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Event Details -->
        <template v-else>
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon" :class="'bg-' + event.event_type.toLowerCase()">
                <i :class="event.event_type === 'Curricular' ? 'bi bi-book' : 'bi bi-trophy'"></i>
              </div>
              <div>
                <div class="breadcrumb">
                  <router-link to="/admin/dashboard">Dashboard</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <router-link to="/admin/events">Events</router-link>
                  <i class="bi bi-chevron-right"></i>
                  <span>Event Details</span>
                </div>
                <h2 class="mb-0">{{ event.title }}</h2>
              </div>
            </div>
            <div class="header-actions">
              <button class="btn btn-outline-secondary" @click="goBack">
                <i class="bi bi-arrow-left me-2"></i>
                Back
              </button>
              <button class="btn btn-warning" @click="editEvent">
                <i class="bi bi-pencil me-2"></i>
                Edit
              </button>
              <button class="btn btn-danger" @click="deleteEvent">
                <i class="bi bi-trash me-2"></i>
                Delete
              </button>
            </div>
          </div>

          <!-- Event Details Card -->
          <div class="details-card">
            <div class="card-header">
              <span class="badge" :class="'badge-' + event.event_type.toLowerCase()">
                {{ event.event_type }}
              </span>
              <span class="badge" :class="'badge-' + getStatusBadgeColor(event.status)">
                {{ event.status }}
              </span>
            </div>

            <div class="card-body">
              <div class="row">
                <div class="col-md-8">
                  <h5 class="section-title">Description</h5>
                  <p class="description-text">{{ event.description || 'No description provided.' }}</p>

                  <div class="info-grid">
                    <div class="info-item">
                      <i class="bi bi-calendar"></i>
                      <div>
                        <span class="info-label">Date</span>
                        <span class="info-value">{{ formatDate(event.event_date) }}</span>
                      </div>
                    </div>

                    <div class="info-item" v-if="event.event_time">
                      <i class="bi bi-clock"></i>
                      <div>
                        <span class="info-label">Time</span>
                        <span class="info-value">{{ formatTime(event.event_time) }}</span>
                      </div>
                    </div>

                    <div class="info-item">
                      <i class="bi bi-geo-alt"></i>
                      <div>
                        <span class="info-label">Venue</span>
                        <span class="info-value">{{ event.venue }}</span>
                      </div>
                    </div>

                    <div class="info-item" v-if="event.organizer">
                      <i class="bi bi-person"></i>
                      <div>
                        <span class="info-label">Organizer</span>
                        <span class="info-value">{{ event.organizer }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="meta-card">
                    <h6 class="meta-title">Event Information</h6>
                    <div class="meta-item">
                      <span class="meta-label">Created By:</span>
                      <span class="meta-value">{{ event.creator_name || 'System' }}</span>
                    </div>
                    <div class="meta-item">
                      <span class="meta-label">Created At:</span>
                      <span class="meta-value">{{ formatDateTime(event.created_at) }}</span>
                    </div>
                    <div class="meta-item">
                      <span class="meta-label">Event ID:</span>
                      <span class="meta-value">#{{ event.id }}</span>
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
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'EventView',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const store = useStore()
    const loading = ref(true)
    const error = ref(null)
    const event = ref({})

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchEvent = async () => {
      loading.value = true
      error.value = null

      try {
        const token = store.state.auth.token
        const eventId = route.params.id
        
        const response = await axios.get(`${API_URL}/admin/events/view.php?id=${eventId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          event.value = response.data.data
        }
      } catch (err) {
        console.error('Error fetching event:', err)
        error.value = err.response?.data?.message || 'Failed to load event'
        
        if (err.response?.status === 401) {
          await store.dispatch('auth/logout')
          router.push('/admin/login')
        }
      } finally {
        loading.value = false
      }
    }

    const formatDate = (date) => {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const formatTime = (time) => {
      if (!time) return '—'
      return new Date('1970-01-01T' + time).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      })
    }

    const formatDateTime = (datetime) => {
      if (!datetime) return '—'
      return new Date(datetime).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
      })
    }

    const getStatusBadgeColor = (status) => {
      const colors = {
        'Upcoming': 'primary',
        'Today': 'success',
        'Past': 'secondary'
      }
      return colors[status] || 'secondary'
    }

    const goBack = () => {
      router.push('/admin/events')
    }

    const editEvent = () => {
      router.push(`/admin/events/edit/${route.params.id}`)
    }

    const deleteEvent = async () => {
      const result = await Swal.fire({
        title: 'Delete Event?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#3498db',
        confirmButtonText: 'Yes, delete'
      })

      if (result.isConfirmed) {
        try {
          const token = store.state.auth.token
          const response = await axios.delete(`${API_URL}/admin/events/delete.php?id=${route.params.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Event has been deleted',
              timer: 1500,
              showConfirmButton: false
            })
            router.push('/admin/events')
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete event'
          })
        }
      }
    }

    onMounted(() => {
      fetchEvent()
    })

    return {
      loading,
      error,
      event,
      formatDate,
      formatTime,
      formatDateTime,
      getStatusBadgeColor,
      goBack,
      editEvent,
      deleteEvent
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

/* Header */
.content-header {
  background: white;
  border-radius: 20px;
  padding: 20px 25px;
  margin-bottom: 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.header-icon {
  width: 60px;
  height: 60px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20px;
  font-size: 2rem;
  color: white;
}

.header-icon.bg-curricular {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.header-icon.bg-extracurricular {
  background: linear-gradient(135deg, #e67e22, #d35400);
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

.header-actions {
  display: flex;
  gap: 10px;
}

/* Details Card */
.details-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.card-header {
  padding: 15px 25px;
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
  display: flex;
  gap: 10px;
}

.badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.badge-curricular {
  background: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.badge-extracurricular {
  background: rgba(230, 126, 34, 0.15);
  color: #e67e22;
}

.badge-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
}

.badge-success {
  background: linear-gradient(135deg, #27ae60, #2ecc71);
  color: white;
}

.badge-secondary {
  background: linear-gradient(135deg, #95a5a6, #7f8c8d);
  color: white;
}

.card-body {
  padding: 30px;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 15px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.description-text {
  color: #555;
  line-height: 1.6;
  margin-bottom: 30px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 10px;
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 10px;
}

.info-item i {
  font-size: 1.5rem;
  color: #3498db;
  width: 30px;
}

.info-label {
  display: block;
  font-size: 0.8rem;
  color: #7f8c8d;
  margin-bottom: 3px;
}

.info-value {
  font-size: 1rem;
  color: #2c3e50;
  font-weight: 500;
}

/* Meta Card */
.meta-card {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 15px;
  padding: 20px;
  color: white;
}

.meta-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.2);
}

.meta-item {
  margin-bottom: 12px;
}

.meta-label {
  display: block;
  font-size: 0.8rem;
  opacity: 0.8;
  margin-bottom: 3px;
}

.meta-value {
  font-size: 1rem;
  font-weight: 500;
}

/* Buttons */
.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-outline-secondary {
  border: 1px solid #e9ecef;
  color: #6c757d;
}

.btn-outline-secondary:hover {
  background: #f8f9fa;
  border-color: #3498db;
  color: #3498db;
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

.btn-danger {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  border: none;
  color: white;
}

.btn-danger:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
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

  .header-actions {
    width: 100%;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }

  .btn {
    width: 100%;
  }
}
</style>