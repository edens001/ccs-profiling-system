<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Extracurricular Events'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-trophy"></i>
              </div>
              <div>
                <h2 class="mb-1">Extracurricular Events</h2>
                <p class="text-muted mb-0">Manage non-academic events and activities</p>
              </div>
            </div>
            <div class="header-actions">
              <button class="btn btn-primary" @click="openAddModal">
                <i class="bi bi-plus-circle me-2"></i>
                Add Event
              </button>
            </div>
          </div>

          <!-- Filters -->
          <div class="filters-bar">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search events..." 
                    v-model="filters.search"
                    @keyup.enter="fetchEvents"
                  >
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="filters.status" @change="fetchEvents">
                  <option value="">All Status</option>
                  <option value="upcoming">Upcoming</option>
                  <option value="today">Today</option>
                  <option value="past">Past</option>
                </select>
              </div>
              <div class="col-md-3">
                <button class="btn btn-outline-secondary w-100" @click="resetFilters">
                  <i class="bi bi-arrow-counterclockwise me-2"></i>
                  Reset Filters
                </button>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading events...</p>
          </div>

          <!-- Events Grid -->
          <div v-else class="events-grid">
            <div v-if="events.length === 0" class="empty-state">
              <i class="bi bi-calendar-x"></i>
              <h5>No Extracurricular Events Found</h5>
              <p class="text-muted">Click the "Add Event" button to create a new event.</p>
            </div>

            <div v-else class="row g-4">
              <div v-for="event in events" :key="event.id" class="col-md-6 col-lg-4">
                <div class="event-card" :class="'event-' + event.status.toLowerCase()">
                  <div class="event-header">
                    <span class="event-type">Extracurricular</span>
                    <span class="event-status" :class="'status-' + event.status.toLowerCase()">
                      {{ event.status }}
                    </span>
                  </div>
                  
                  <div class="event-body">
                    <h5 class="event-title">{{ event.title }}</h5>
                    <p class="event-description">{{ event.description || 'No description' }}</p>
                    
                    <div class="event-details">
                      <div class="detail-item">
                        <i class="bi bi-calendar"></i>
                        <span>{{ formatDate(event.event_date) }}</span>
                      </div>
                      <div class="detail-item" v-if="event.event_time">
                        <i class="bi bi-clock"></i>
                        <span>{{ formatTime(event.event_time) }}</span>
                      </div>
                      <div class="detail-item">
                        <i class="bi bi-geo-alt"></i>
                        <span>{{ event.venue }}</span>
                      </div>
                      <div class="detail-item" v-if="event.organizer">
                        <i class="bi bi-person"></i>
                        <span>{{ event.organizer }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="event-footer">
                    <button class="btn btn-sm btn-outline-primary" @click="viewEvent(event)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" @click="editEvent(event)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="deleteEvent(event.id)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination-wrapper">
              <nav>
                <ul class="pagination">
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                      <i class="bi bi-chevron-left"></i>
                    </a>
                  </li>
                  <li class="page-item" v-for="page in displayedPages" :key="page" 
                      :class="{ active: currentPage === page }">
                    <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                  </li>
                  <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                      <i class="bi bi-chevron-right"></i>
                    </a>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Event Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="modalIcon" class="me-2"></i>
              {{ modalTitle }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveEvent">
              <div class="row">
                <div class="col-12 mb-3">
                  <label class="form-label">Event Title <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="eventForm.title" required>
                </div>
                
                <div class="col-12 mb-3">
                  <label class="form-label">Description</label>
                  <textarea class="form-control" rows="3" v-model="eventForm.description"></textarea>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" v-model="eventForm.event_date" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Time</label>
                  <input type="time" class="form-control" v-model="eventForm.event_time">
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Venue <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" v-model="eventForm.venue" required>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Organizer</label>
                  <input type="text" class="form-control" v-model="eventForm.organizer">
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveEvent" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              {{ saving ? 'Saving...' : 'Save Event' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Event Modal -->
    <div class="modal fade" id="viewEventModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="bi bi-eye me-2"></i>
              Event Details
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedEvent">
            <div class="text-center mb-4">
              <span class="badge" :class="'bg-' + getStatusBadgeColor(selectedEvent.status)">
                {{ selectedEvent.status }}
              </span>
            </div>

            <table class="table table-borderless">
              <tr>
                <td width="40%"><i class="bi bi-tag me-2 text-primary"></i>Title:</td>
                <td><strong>{{ selectedEvent.title }}</strong></td>
              </tr>
              <tr>
                <td><i class="bi bi-card-text me-2 text-primary"></i>Description:</td>
                <td>{{ selectedEvent.description || 'No description' }}</td>
              </tr>
              <tr>
                <td><i class="bi bi-calendar me-2 text-primary"></i>Date:</td>
                <td>{{ formatDate(selectedEvent.event_date) }}</td>
              </tr>
              <tr v-if="selectedEvent.event_time">
                <td><i class="bi bi-clock me-2 text-primary"></i>Time:</td>
                <td>{{ formatTime(selectedEvent.event_time) }}</td>
              </tr>
              <tr>
                <td><i class="bi bi-geo-alt me-2 text-primary"></i>Venue:</td>
                <td>{{ selectedEvent.venue }}</td>
              </tr>
              <tr v-if="selectedEvent.organizer">
                <td><i class="bi bi-person me-2 text-primary"></i>Organizer:</td>
                <td>{{ selectedEvent.organizer }}</td>
              </tr>
              <tr>
                <td><i class="bi bi-person-badge me-2 text-primary"></i>Created By:</td>
                <td>{{ selectedEvent.organizer_name || 'System' }}</td>
              </tr>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { Modal } from 'bootstrap'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'ExtracurricularEvents',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const saving = ref(false)
    const events = ref([])
    const selectedEvent = ref(null)
    
    const currentPage = ref(1)
    const itemsPerPage = ref(12)
    const totalPages = ref(1)
    const totalEvents = ref(0)
    
    const filters = ref({
      search: '',
      status: ''
    })

    const eventForm = ref({
      id: null,
      title: '',
      description: '',
      event_date: '',
      event_time: '',
      venue: '',
      organizer: ''
    })

    const modalTitle = computed(() => {
      return eventForm.value.id ? 'Edit Event' : 'Add New Event'
    })

    const modalIcon = computed(() => {
      return eventForm.value.id ? 'bi bi-pencil' : 'bi bi-plus-circle'
    })

    const displayedPages = computed(() => {
      const delta = 2
      const range = []
      const rangeWithDots = []
      let l

      for (let i = 1; i <= totalPages.value; i++) {
        if (i === 1 || i === totalPages.value || (i >= currentPage.value - delta && i <= currentPage.value + delta)) {
          range.push(i)
        }
      }

      range.forEach((i) => {
        if (l) {
          if (i - l === 2) {
            rangeWithDots.push(l + 1)
          } else if (i - l !== 1) {
            rangeWithDots.push('...')
          }
        }
        rangeWithDots.push(i)
        l = i
      })

      return rangeWithDots
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchEvents = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/events/extracurricular.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            page: currentPage.value,
            limit: itemsPerPage.value,
            search: filters.value.search,
            status: filters.value.status
          }
        })

        if (response.data.success) {
          events.value = response.data.data
          totalEvents.value = response.data.pagination.total
          totalPages.value = response.data.pagination.pages
        }
      } catch (error) {
        console.error('Error fetching events:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to fetch events'
        })
      } finally {
        loading.value = false
      }
    }

    const resetFilters = () => {
      filters.value = {
        search: '',
        status: ''
      }
      currentPage.value = 1
      fetchEvents()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        fetchEvents()
      }
    }

    const openAddModal = () => {
      eventForm.value = {
        id: null,
        title: '',
        description: '',
        event_date: '',
        event_time: '',
        venue: '',
        organizer: ''
      }
      const modal = new Modal(document.getElementById('eventModal'))
      modal.show()
    }

    const editEvent = (event) => {
      eventForm.value = { ...event }
      const modal = new Modal(document.getElementById('eventModal'))
      modal.show()
    }

    const viewEvent = (event) => {
      selectedEvent.value = event
      const modal = new Modal(document.getElementById('viewEventModal'))
      modal.show()
    }

    const saveEvent = async () => {
      if (!eventForm.value.title || !eventForm.value.event_date || !eventForm.value.venue) {
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
        const url = eventForm.value.id 
          ? `${API_URL}/admin/events/update.php?id=${eventForm.value.id}`
          : `${API_URL}/admin/events/extracurricular.php`
        
        const method = eventForm.value.id ? 'put' : 'post'
        
        const response = await axios({
          method,
          url,
          data: eventForm.value,
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          Modal.getInstance(document.getElementById('eventModal')).hide()
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: `Event ${eventForm.value.id ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          fetchEvents()
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save event'
        })
      } finally {
        saving.value = false
      }
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
            fetchEvents()
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

    const formatDate = (date) => {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('en-US', {
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

    const getStatusBadgeColor = (status) => {
      const colors = {
        'Upcoming': 'primary',
        'Today': 'success',
        'Past': 'secondary'
      }
      return colors[status] || 'secondary'
    }

    onMounted(() => {
      fetchEvents()
    })

    return {
      loading,
      saving,
      events,
      selectedEvent,
      filters,
      eventForm,
      currentPage,
      totalPages,
      displayedPages,
      modalTitle,
      modalIcon,
      fetchEvents,
      resetFilters,
      changePage,
      openAddModal,
      editEvent,
      viewEvent,
      saveEvent,
      deleteEvent,
      formatDate,
      formatTime,
      getStatusBadgeColor
    }
  }
}
</script>

<style scoped>
/* Same styles as Curricular.vue */
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background: linear-gradient(135deg, #e67e22, #d35400);
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
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 3px solid #f0f0f0;
}

.header-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #e67e22, #d35400);
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

.header-actions {
  display: flex;
  gap: 10px;
}

.filters-bar {
  background: #f8f9fa;
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 25px;
}

/* Events Grid */
.events-grid {
  min-height: 400px;
}

.event-card {
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  transition: all 0.3s;
  height: 100%;
  display: flex;
  flex-direction: column;
  border-left: 4px solid;
}

.event-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.event-upcoming {
  border-left-color: #3498db;
}

.event-today {
  border-left-color: #27ae60;
}

.event-past {
  border-left-color: #95a5a6;
}

.event-header {
  padding: 15px;
  background: #f8f9fa;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e9ecef;
}

.event-type {
  font-size: 0.8rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.event-status {
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-upcoming {
  background: rgba(52, 152, 219, 0.15);
  color: #3498db;
}

.status-today {
  background: rgba(46, 204, 113, 0.15);
  color: #27ae60;
}

.status-past {
  background: rgba(149, 165, 166, 0.15);
  color: #7f8c8d;
}

.event-body {
  padding: 20px;
  flex: 1;
}

.event-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 10px;
}

.event-description {
  font-size: 0.9rem;
  color: #7f8c8d;
  margin-bottom: 15px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.85rem;
  color: #6c757d;
}

.detail-item i {
  font-size: 1rem;
  color: #e67e22;
  width: 20px;
}

.event-footer {
  padding: 15px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-state i {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 15px;
}

.empty-state h5 {
  color: #2c3e50;
  margin-bottom: 5px;
}

/* Pagination */
.pagination-wrapper {
  margin-top: 30px;
  display: flex;
  justify-content: center;
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
  background: linear-gradient(135deg, #e67e22, #d35400);
  color: white;
}

/* Buttons */
.btn {
  padding: 8px 16px;
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-primary {
  background: linear-gradient(135deg, #e67e22, #d35400);
  border: none;
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(230, 126, 34, 0.3);
}

.btn-outline-primary {
  border: 1px solid #e67e22;
  color: #e67e22;
}

.btn-outline-primary:hover {
  background: #e67e22;
  color: white;
}

/* Modal */
.modal-content {
  border-radius: 20px;
  overflow: hidden;
}

.modal-header {
  background: linear-gradient(135deg, #e67e22, #d35400);
  color: white;
  border-bottom: none;
  padding: 15px 20px;
}

.modal-header .btn-close {
  filter: brightness(0) invert(1);
}

.modal-body {
  padding: 25px;
}

.modal-footer {
  border-top: 2px solid #f0f0f0;
  padding: 15px 20px;
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

  .btn {
    width: 100%;
  }
}
</style>