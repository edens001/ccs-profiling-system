<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Events Management'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-calendar-event"></i>
              </div>
              <div>
                <h2 class="mb-1">Events Management</h2>
                <p class="text-muted mb-0">Manage all academic and non-academic events</p>
              </div>
            </div>
            <div class="header-actions">
              <button class="btn btn-primary" @click="$router.push('/admin/events/add')">
                <i class="bi bi-plus-circle me-2"></i>
                Add Event
              </button>
            </div>
          </div>

          <!-- Event Type Cards -->
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <div class="type-card curricular" @click="$router.push('/admin/events/curricular')">
                <div class="type-icon">
                  <i class="bi bi-book"></i>
                </div>
                <div class="type-content">
                  <h3>Curricular Events</h3>
                  <p>Academic events, seminars, workshops, and conferences</p>
                  <span class="btn-link">View All <i class="bi bi-arrow-right"></i></span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="type-card extracurricular" @click="$router.push('/admin/events/extracurricular')">
                <div class="type-icon">
                  <i class="bi bi-trophy"></i>
                </div>
                <div class="type-content">
                  <h3>Extracurricular Events</h3>
                  <p>Sports, cultural activities, celebrations, and social events</p>
                  <span class="btn-link">View All <i class="bi bi-arrow-right"></i></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Events -->
          <div class="recent-section">
            <h4 class="section-title">
              <i class="bi bi-clock-history me-2"></i>
              Recent Events
            </h4>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2">Loading recent events...</p>
            </div>

            <!-- Events List -->
            <div v-else class="events-list">
              <div v-if="recentEvents.length === 0" class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <h5>No Events Found</h5>
                <p class="text-muted">Click the "Add Event" button to create your first event.</p>
              </div>

              <div v-else class="row g-4">
                <div v-for="event in recentEvents" :key="event.id" class="col-md-6">
                  <div class="event-item" :class="'event-' + event.event_type.toLowerCase()">
                    <div class="event-date">
                      <span class="day">{{ formatDay(event.event_date) }}</span>
                      <span class="month">{{ formatMonth(event.event_date) }}</span>
                    </div>
                    <div class="event-info">
                      <div class="event-header">
                        <h5 class="event-title">{{ event.title }}</h5>
                        <span class="badge" :class="'badge-' + event.event_type.toLowerCase()">
                          {{ event.event_type }}
                        </span>
                      </div>
                      <p class="event-description">{{ event.description || 'No description' }}</p>
                      <div class="event-meta">
                        <span><i class="bi bi-clock"></i> {{ formatTime(event.event_time) || 'No time' }}</span>
                        <span><i class="bi bi-geo-alt"></i> {{ event.venue }}</span>
                      </div>
                      <div class="event-actions">
                        <button class="btn btn-sm btn-outline-primary" @click="viewEvent(event.id)">
                          <i class="bi bi-eye"></i> View
                        </button>
                        <button class="btn btn-sm btn-outline-warning" @click="editEvent(event.id)">
                          <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="deleteEvent(event.id)">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Events',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const router = useRouter()
    const store = useStore()
    const loading = ref(false)
    const recentEvents = ref([])

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchRecentEvents = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/events/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            limit: 4,
            status: 'upcoming'
          }
        })

        if (response.data.success) {
          recentEvents.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching events:', error)
      } finally {
        loading.value = false
      }
    }

    const formatDay = (date) => {
      if (!date) return ''
      return new Date(date).getDate()
    }

    const formatMonth = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', { month: 'short' })
    }

    const formatTime = (time) => {
      if (!time) return ''
      return new Date('1970-01-01T' + time).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      })
    }

    const viewEvent = (id) => {
      router.push(`/admin/events/view/${id}`)
    }

    const editEvent = (id) => {
      router.push(`/admin/events/edit/${id}`)
    }

    const deleteEvent = async (id) => {
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
          const response = await axios.delete(`${API_URL}/admin/events/delete.php?id=${id}`, {
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
            fetchRecentEvents()
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
      fetchRecentEvents()
    })

    return {
      loading,
      recentEvents,
      formatDay,
      formatMonth,
      formatTime,
      viewEvent,
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

.content-card {
  background: white;
  border-radius: 30px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
  padding: 30px;
}

.content-header {
  display: flex;
  justify-content: space-between;
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

/* Type Cards */
.type-card {
  background: white;
  border-radius: 20px;
  padding: 25px;
  display: flex;
  align-items: center;
  gap: 20px;
  cursor: pointer;
  transition: all 0.3s;
  border: 2px solid #f0f0f0;
}

.type-card.curricular:hover {
  border-color: #3498db;
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(52, 152, 219, 0.2);
}

.type-card.extracurricular:hover {
  border-color: #e67e22;
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(230, 126, 34, 0.2);
}

.type-icon {
  width: 70px;
  height: 70px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.type-card.curricular .type-icon {
  background: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.type-card.extracurricular .type-icon {
  background: rgba(230, 126, 34, 0.15);
  color: #e67e22;
}

.type-content {
  flex: 1;
}

.type-content h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 5px;
}

.type-content p {
  color: #7f8c8d;
  margin-bottom: 10px;
}

.btn-link {
  color: #3498db;
  text-decoration: none;
  font-weight: 500;
}

/* Recent Section */
.recent-section {
  margin-top: 40px;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f0f0;
}

/* Event Items */
.event-item {
  background: white;
  border-radius: 15px;
  padding: 20px;
  display: flex;
  gap: 20px;
  border: 2px solid #f0f0f0;
  transition: all 0.3s;
  height: 100%;
}

.event-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.event-item.event-curricular {
  border-left: 4px solid #3498db;
}

.event-item.event-extracurricular {
  border-left: 4px solid #e67e22;
}

.event-date {
  width: 60px;
  height: 70px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.event-item.event-curricular .event-date {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.event-item.event-extracurricular .event-date {
  background: linear-gradient(135deg, #e67e22, #d35400);
}

.event-date .day {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.event-date .month {
  font-size: 0.8rem;
  text-transform: uppercase;
}

.event-info {
  flex: 1;
}

.event-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.event-title {
  font-size: 1rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.badge-curricular {
  background: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.badge-extracurricular {
  background: rgba(230, 126, 34, 0.15);
  color: #e67e22;
}

.event-description {
  font-size: 0.9rem;
  color: #7f8c8d;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-meta {
  display: flex;
  gap: 15px;
  font-size: 0.85rem;
  color: #95a5a6;
  margin-bottom: 15px;
}

.event-meta span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.event-actions {
  display: flex;
  gap: 8px;
}

.btn-sm {
  padding: 4px 10px;
  font-size: 0.8rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 50px 20px;
}

.empty-state i {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 15px;
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

  .event-item {
    flex-direction: column;
  }

  .event-date {
    width: 100%;
    height: 50px;
    flex-direction: row;
    gap: 10px;
  }
}
</style>