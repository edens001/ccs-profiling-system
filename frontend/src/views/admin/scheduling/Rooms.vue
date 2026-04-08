<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Rooms Management'" />
      
      <div class="container-fluid p-4">
        <!-- Header with Gradient -->
        <div class="header-gradient mb-4">
          <div class="header-content">
            <div class="header-left">
              <div class="header-icon-wrapper">
                <i class="bi bi-door-open-fill"></i>
              </div>
              <div class="header-text">
                <h1 class="header-title">Rooms Management</h1>
                <p class="header-subtitle">Manage classrooms, laboratories, and facilities</p>
              </div>
            </div>
            <button class="btn-add" @click="openAddModal">
              <i class="bi bi-plus-lg"></i>
              <span>Add New Room</span>
            </button>
          </div>
        </div>

        <!-- Stats Cards with Animation -->
        <div class="stats-grid">
          <div class="stat-card" v-for="(stat, index) in statsData" :key="stat.label" 
               :style="{ animationDelay: index * 0.1 + 's' }">
            <div class="stat-icon" :style="{ background: stat.color + '15' }">
              <i :class="stat.icon" :style="{ color: stat.color }"></i>
            </div>
            <div class="stat-info">
              <span class="stat-value">{{ stat.value }}</span>
              <span class="stat-label">{{ stat.label }}</span>
              <span class="stat-sub" v-if="stat.sub">{{ stat.sub }}</span>
            </div>
          </div>
        </div>

        <!-- Filters and Search -->
        <div class="filters-card">
          <div class="filters-header">
            <div class="filters-title">
              <i class="bi bi-funnel me-2"></i>
              <span>Filter Rooms</span>
            </div>
            <div class="filters-actions">
              <div class="search-box">
                <i class="bi bi-search"></i>
                <input 
                  type="text" 
                  class="search-input" 
                  placeholder="Search by room code or building..."
                  v-model="searchQuery"
                  @input="filterRooms"
                >
              </div>
              <button class="btn-reset" @click="resetFilters" title="Reset filters">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span class="d-none d-md-inline ms-1">Reset</span>
              </button>
            </div>
          </div>
          
          <div class="filters-body">
            <div class="filter-chips">
              <span class="filter-chip" :class="{ active: buildingFilter === '' }" @click="buildingFilter = ''">
                All Buildings
              </span>
              <span v-for="building in uniqueBuildings" :key="building" 
                    class="filter-chip" 
                    :class="{ active: buildingFilter === building }"
                    @click="buildingFilter = building">
                {{ building }}
              </span>
            </div>
          </div>
        </div>

        <!-- Results Header -->
        <div class="results-header">
          <div class="results-info">
            <i class="bi bi-door-open me-2"></i>
            <span>Showing <strong>{{ filteredRooms.length }}</strong> of <strong>{{ rooms.length }}</strong> rooms</span>
          </div>
          <div class="results-per-page">
            <span>Show:</span>
            <select class="per-page-select" v-model="itemsPerPage" @change="changePage(1)">
              <option value="5">5</option>
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading rooms...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <i class="bi bi-exclamation-triangle"></i>
          <h4>Oops! Something went wrong</h4>
          <p>{{ error }}</p>
          <button class="btn-retry" @click="fetchRooms">
            <i class="bi bi-arrow-clockwise me-2"></i>
            Try Again
          </button>
        </div>

        <!-- Rooms Table -->
        <div v-else class="table-container">
          <table class="custom-table">
            <thead>
              
                <th @click="sort('room_code')" class="sortable">
                  <span>Room Code</span>
                  <i :class="getSortIcon('room_code')"></i>
                </th>
                <th @click="sort('building')" class="sortable">
                  <span>Building</span>
                  <i :class="getSortIcon('building')"></i>
                </th>
                <th @click="sort('capacity')" class="sortable">
                  <span>Capacity</span>
                  <i :class="getSortIcon('capacity')"></i>
                </th>
                <th>Room Type</th>
                <th>Schedules</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
              </thead>
            <tbody>
              <tr v-if="paginatedRooms.length === 0" class="empty-row">
                <td colspan="7">
                  <div class="empty-state">
                    <i class="bi bi-door-closed"></i>
                    <h4>No Rooms Found</h4>
                    <p>No rooms match your search criteria.</p>
                    <button class="btn-reset-state" @click="resetFilters">
                      <i class="bi bi-arrow-counterclockwise me-2"></i>
                      Clear Filters
                    </button>
                  </div>
                 </td>
               </tr>
              <tr v-for="room in paginatedRooms" :key="room.id" class="table-row" :class="{ 'inactive-row': !room.is_active }">
                <td>
                  <div class="room-code-cell">
                    <span class="room-badge" :class="'badge-' + getRoomTypeColor(room.room_code)">
                      {{ room.room_code }}
                    </span>
                  </div>
                </td>
                <td>
                  <div class="building-info">
                    <i class="bi bi-building"></i>
                    <span>{{ room.building }}</span>
                  </div>
                </td>
                <td>
                  <div class="capacity-info">
                    <i class="bi bi-people"></i>
                    <span>{{ room.capacity }}</span>
                  </div>
                </td>
                <td>
                  <span class="room-type-badge" :class="'type-' + getRoomTypeColor(room.room_code)">
                    {{ getRoomType(room.room_code) }}
                  </span>
                </td>
                <td>
                  <div class="schedule-info">
                    <span class="schedule-count">{{ room.schedule_count || 0 }}</span>
                    <small class="schedule-label">schedules</small>
                  </div>
                </td>
                <td>
                  <span class="status-badge" :class="room.is_active ? 'status-available' : 'status-inactive'">
                    <span class="status-dot"></span>
                    {{ room.is_active ? 'Available' : 'Inactive' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-icon btn-view" @click="viewRoom(room)" title="View Details">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn-icon btn-edit" @click="editRoom(room)" title="Edit Room">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-icon btn-delete" @click="deleteRoom(room.id)" title="Delete Room">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination-container">
            <button class="pagination-btn" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
              <i class="bi bi-chevron-left"></i>
            </button>
            
            <div class="pagination-pages">
              <template v-for="page in displayedPages" :key="page">
                <button v-if="page === '...'" class="pagination-dots" disabled>...</button>
                <button v-else class="pagination-page" 
                        :class="{ active: currentPage === page }"
                        @click="changePage(page)">
                  {{ page }}
                </button>
              </template>
            </div>
            
            <button class="pagination-btn" :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1" ref="modalElement">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="modalIcon" class="me-2"></i>
              {{ modalTitle }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveRoom">
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-door-open me-1"></i>
                  Room Code <span class="required">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.room_code" 
                       placeholder="e.g., R101, LAB101, CL101" required>
                <small class="form-text">Unique identifier for the room</small>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-building me-1"></i>
                  Building <span class="required">*</span>
                </label>
                <input type="text" class="form-control" v-model="form.building" 
                       placeholder="e.g., Main Building, Science Building" required>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-people me-1"></i>
                  Capacity <span class="required">*</span>
                </label>
                <input type="number" class="form-control" v-model="form.capacity" 
                       min="1" max="500" required>
              </div>

              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-tag me-1"></i>
                  Room Type
                </label>
                <select class="form-control" v-model="form.room_type">
                  <option value="">Auto-detect from code</option>
                  <option value="Classroom">Classroom</option>
                  <option value="Laboratory">Laboratory</option>
                  <option value="Computer Lab">Computer Lab</option>
                  <option value="Lecture Hall">Lecture Hall</option>
                  <option value="Conference Room">Conference Room</option>
                </select>
              </div>

              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive">
                <label class="form-check-label" for="isActive">
                  Active Room
                </label>
                <small class="d-block text-muted mt-1">
                  Inactive rooms will not appear in scheduling dropdowns
                </small>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" @click="saveRoom" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else :class="modalIcon" class="me-2"></i>
              {{ saving ? 'Saving...' : (form.id ? 'Update Room' : 'Create Room') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title">
              <i class="bi bi-eye-fill me-2"></i>
              Room Details
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div v-if="viewData" class="details-container">
              <div class="detail-header" :class="'bg-' + getRoomTypeColor(viewData.room_code)">
                <span class="detail-code">{{ viewData.room_code }}</span>
                <span class="detail-type">{{ getRoomType(viewData.room_code) }}</span>
              </div>
              
              <div class="detail-body">
                <div class="detail-row">
                  <span class="detail-label">
                    <i class="bi bi-building"></i>
                    Building:
                  </span>
                  <span class="detail-value">{{ viewData.building }}</span>
                </div>
                
                <div class="detail-row">
                  <span class="detail-label">
                    <i class="bi bi-people"></i>
                    Capacity:
                  </span>
                  <span class="detail-value">
                    <span class="capacity-badge">{{ viewData.capacity }} students</span>
                  </span>
                </div>
                
                <div class="detail-row">
                  <span class="detail-label">
                    <i class="bi bi-calendar-check"></i>
                    Schedules:
                  </span>
                  <span class="detail-value">{{ viewData.schedule_count || 0 }}</span>
                </div>
                
                <div class="detail-row">
                  <span class="detail-label">
                    <i class="bi bi-toggle-on"></i>
                    Status:
                  </span>
                  <span class="detail-value">
                    <span class="status-badge" :class="viewData.is_active ? 'status-available' : 'status-inactive'">
                      <span class="status-dot"></span>
                      {{ viewData.is_active ? 'Available' : 'Inactive' }}
                    </span>
                  </span>
                </div>
                
                <div class="detail-row">
                  <span class="detail-label">
                    <i class="bi bi-calendar"></i>
                    Created:
                  </span>
                  <span class="detail-value">{{ formatDate(viewData.created_at) }}</span>
                </div>
                
                <div class="detail-row">
                  <span class="detail-label">
                    <i class="bi bi-arrow-repeat"></i>
                    Updated:
                  </span>
                  <span class="detail-value">{{ formatDate(viewData.updated_at) || 'Never' }}</span>
                </div>
              </div>
            </div>
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
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useStore } from 'vuex'
import axios from 'axios'
import { Modal } from 'bootstrap'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Rooms',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const loading = ref(false)
    const saving = ref(false)
    const error = ref(null)
    const rooms = ref([])
    const searchQuery = ref('')
    const buildingFilter = ref('')
    
    // Pagination
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    
    // Sorting
    const sortField = ref('room_code')
    const sortDirection = ref('asc')

    // Modal reference
    const modalElement = ref(null)
    let modalInstance = null

    const form = ref({
      id: null,
      room_code: '',
      building: '',
      capacity: 30,
      room_type: '',
      is_active: true
    })

    const viewData = ref(null)

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Stats Data
    const statsData = computed(() => [
      {
        label: 'Total Rooms',
        value: totalRooms.value,
        icon: 'bi bi-door-open',
        color: '#4361ee',
        sub: 'All rooms'
      },
      {
        label: 'Buildings',
        value: totalBuildings.value,
        icon: 'bi bi-building',
        color: '#06b6d4',
        sub: 'Unique buildings'
      },
      {
        label: 'Total Capacity',
        value: totalCapacity.value.toLocaleString(),
        icon: 'bi bi-people',
        color: '#10b981',
        sub: 'Maximum students'
      },
      {
        label: 'Active Rooms',
        value: activeRooms.value,
        icon: 'bi bi-check-circle',
        color: '#f59e0b',
        sub: 'Available for use'
      }
    ])

    // Unique buildings for filter
    const uniqueBuildings = computed(() => {
      const buildings = rooms.value.map(r => r.building)
      return [...new Set(buildings)].sort()
    })

    // Filtered rooms based on search and building filter
    const filteredRooms = computed(() => {
      let filtered = rooms.value

      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(r => 
          r.room_code.toLowerCase().includes(query) ||
          r.building.toLowerCase().includes(query)
        )
      }

      // Apply building filter
      if (buildingFilter.value) {
        filtered = filtered.filter(r => r.building === buildingFilter.value)
      }

      // Apply sorting
      filtered.sort((a, b) => {
        let aVal = a[sortField.value]
        let bVal = b[sortField.value]
        
        if (sortField.value === 'capacity' || sortField.value === 'schedule_count') {
          aVal = aVal || 0
          bVal = bVal || 0
        } else {
          aVal = aVal?.toString().toLowerCase() || ''
          bVal = bVal?.toString().toLowerCase() || ''
        }

        if (sortDirection.value === 'asc') {
          return aVal > bVal ? 1 : -1
        } else {
          return aVal < bVal ? 1 : -1
        }
      })

      return filtered
    })

    // Paginated rooms
    const paginatedRooms = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredRooms.value.slice(start, end)
    })

    // Total pages
    const totalPages = computed(() => {
      return Math.ceil(filteredRooms.value.length / itemsPerPage.value)
    })

    // Displayed pages for pagination
    const displayedPages = computed(() => {
      const total = totalPages.value
      const current = currentPage.value
      const delta = 2
      const pages = []

      for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
          pages.push(i)
        }
      }

      const result = []
      let prev = 0
      for (const page of pages) {
        if (prev && page - prev > 1) {
          result.push('...')
        }
        result.push(page)
        prev = page
      }
      
      return result
    })

    // Stats
    const totalRooms = computed(() => rooms.value.length)
    const totalBuildings = computed(() => uniqueBuildings.value.length)
    const totalCapacity = computed(() => {
      return rooms.value.reduce((sum, r) => sum + (r.capacity || 0), 0)
    })
    const activeRooms = computed(() => {
      return rooms.value.filter(r => r.is_active).length
    })

    const modalTitle = computed(() => {
      return form.value.id ? 'Edit Room' : 'Add New Room'
    })

    const modalIcon = computed(() => {
      return form.value.id ? 'bi bi-pencil-fill' : 'bi bi-plus-circle-fill'
    })

    // Initialize modal
    const initModal = () => {
      if (modalElement.value && !modalInstance) {
        try {
          modalInstance = new Modal(modalElement.value)
        } catch (e) {
          console.error('Modal initialization error:', e)
        }
      }
    }

    // Methods
    const fetchRooms = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/scheduling/rooms.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          rooms.value = response.data.data.map(room => ({
            ...room,
            is_active: room.is_active == 1
          }))
        } else {
          throw new Error(response.data.message)
        }
      } catch (err) {
        console.error('Error fetching rooms:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to fetch rooms'
        
        // Fallback mock data
        rooms.value = [
          {
            id: 1,
            room_code: 'R101',
            building: 'Main Building',
            capacity: 40,
            is_active: true,
            schedule_count: 15,
            created_at: '2024-01-15',
            updated_at: '2024-02-20'
          },
          {
            id: 2,
            room_code: 'R102',
            building: 'Main Building',
            capacity: 35,
            is_active: true,
            schedule_count: 12,
            created_at: '2024-01-15',
            updated_at: '2024-02-18'
          },
          {
            id: 3,
            room_code: 'LAB101',
            building: 'Science Building',
            capacity: 30,
            is_active: true,
            schedule_count: 20,
            created_at: '2024-01-20',
            updated_at: '2024-02-25'
          },
          {
            id: 4,
            room_code: 'CL101',
            building: 'IT Building',
            capacity: 40,
            is_active: true,
            schedule_count: 25,
            created_at: '2024-01-25',
            updated_at: '2024-02-28'
          },
          {
            id: 5,
            room_code: 'LEC201',
            building: 'Main Building',
            capacity: 80,
            is_active: true,
            schedule_count: 30,
            created_at: '2024-01-10',
            updated_at: '2024-02-10'
          }
        ]
      } finally {
        loading.value = false
      }
    }

    const getRoomType = (roomCode) => {
      if (roomCode.toLowerCase().includes('lab')) return 'Laboratory'
      if (roomCode.toLowerCase().includes('cl')) return 'Computer Lab'
      if (roomCode.toLowerCase().includes('lec')) return 'Lecture Hall'
      if (roomCode.toLowerCase().includes('conf')) return 'Conference Room'
      if (roomCode.toLowerCase().includes('aud')) return 'Auditorium'
      return 'Classroom'
    }

    const getRoomTypeColor = (roomCode) => {
      if (roomCode.toLowerCase().includes('lab')) return 'purple'
      if (roomCode.toLowerCase().includes('cl')) return 'blue'
      if (roomCode.toLowerCase().includes('lec')) return 'green'
      if (roomCode.toLowerCase().includes('conf')) return 'orange'
      if (roomCode.toLowerCase().includes('aud')) return 'red'
      return 'primary'
    }

    const filterRooms = () => {
      currentPage.value = 1
    }

    const resetFilters = () => {
      searchQuery.value = ''
      buildingFilter.value = ''
      currentPage.value = 1
    }

    const sort = (field) => {
      if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortField.value = field
        sortDirection.value = 'asc'
      }
    }

    const getSortIcon = (field) => {
      if (sortField.value !== field) return 'bi bi-arrow-down-up text-muted'
      return sortDirection.value === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down'
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const openAddModal = () => {
      form.value = {
        id: null,
        room_code: '',
        building: '',
        capacity: 30,
        room_type: '',
        is_active: true
      }
      if (modalInstance) {
        modalInstance.show()
      } else {
        initModal()
        nextTick(() => {
          if (modalInstance) modalInstance.show()
        })
      }
    }

    const editRoom = (room) => {
      form.value = {
        id: room.id,
        room_code: room.room_code,
        building: room.building,
        capacity: room.capacity,
        room_type: room.room_type || '',
        is_active: room.is_active
      }
      if (modalInstance) {
        modalInstance.show()
      } else {
        initModal()
        nextTick(() => {
          if (modalInstance) modalInstance.show()
        })
      }
    }

    const viewRoom = (room) => {
      viewData.value = room
      const viewModal = new Modal(document.getElementById('viewModal'))
      viewModal.show()
    }

    const saveRoom = async () => {
      if (!form.value.room_code || !form.value.building || !form.value.capacity) {
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
        const url = form.value.id 
          ? `${API_URL}/admin/scheduling/rooms.php?id=${form.value.id}`
          : `${API_URL}/admin/scheduling/rooms.php`
        
        const method = form.value.id ? 'put' : 'post'
        
        const response = await axios({
          method,
          url,
          data: form.value,
          headers: { 'Authorization': `Bearer ${token}` }
        })

        if (response.data.success) {
          if (modalInstance) {
            modalInstance.hide()
          }
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: `Room ${form.value.id ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          fetchRooms()
        } else {
          throw new Error(response.data.message)
        }
      } catch (err) {
        console.error('Error saving room:', err)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err.response?.data?.message || err.message || 'Failed to save room'
        })
      } finally {
        saving.value = false
      }
    }

    const deleteRoom = async (id) => {
      const result = await Swal.fire({
        title: 'Delete Room?',
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
          const response = await axios.delete(`${API_URL}/admin/scheduling/rooms.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })

          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Room deleted successfully',
              timer: 1500,
              showConfirmButton: false
            })
            fetchRooms()
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.response?.data?.message || 'Failed to delete room'
          })
        }
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    onMounted(() => {
      fetchRooms()
      nextTick(() => {
        initModal()
      })
    })

    onBeforeUnmount(() => {
      if (modalInstance) {
        modalInstance.dispose()
        modalInstance = null
      }
    })

    // Watch for filter changes
    watch([searchQuery, buildingFilter, sortField, sortDirection], () => {
      currentPage.value = 1
    })

    return {
      loading,
      saving,
      error,
      rooms,
      form,
      viewData,
      searchQuery,
      buildingFilter,
      currentPage,
      itemsPerPage,
      sortField,
      sortDirection,
      totalRooms,
      totalBuildings,
      totalCapacity,
      activeRooms,
      uniqueBuildings,
      filteredRooms,
      paginatedRooms,
      totalPages,
      displayedPages,
      statsData,
      modalTitle,
      modalIcon,
      modalElement,
      getRoomType,
      getRoomTypeColor,
      filterRooms,
      resetFilters,
      sort,
      getSortIcon,
      changePage,
      openAddModal,
      editRoom,
      viewRoom,
      saveRoom,
      deleteRoom,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Modern UI Styles */
.app-wrapper {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
  width: 100%;
}

.main-content {
  flex: 1;
  margin-left: 280px;
  width: calc(100% - 280px);
  min-height: 100vh;
  padding: 25px;
  transition: margin-left 0.3s ease;
  background: #f8fafc;
}

:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

.container-fluid {
  padding: 0;
  max-width: 1600px;
  margin: 0 auto;
}

/* Header Gradient */
.header-gradient {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-radius: 20px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
  animation: slideDown 0.5s ease;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.header-icon-wrapper {
  width: 70px;
  height: 70px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.header-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  line-height: 1.2;
}

.header-subtitle {
  margin: 5px 0 0;
  opacity: 0.9;
  font-size: 0.95rem;
}

.btn-add {
  background: white;
  color: #10b981;
  border: none;
  padding: 12px 25px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-add:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
  background: #f8f9fa;
}

.btn-add i {
  font-size: 1.2rem;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 25px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  transition: all 0.3s;
  border: 1px solid #f0f0f0;
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  border-color: #10b981;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #2d3748;
  line-height: 1.2;
  display: block;
}

.stat-label {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
  display: block;
  margin-bottom: 2px;
}

.stat-sub {
  color: #a0aec0;
  font-size: 0.75rem;
}

/* Filters Card */
.filters-card {
  background: white;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  border: 1px solid #f0f0f0;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  flex-wrap: wrap;
  gap: 15px;
}

.filters-title {
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #2d3748;
  font-size: 1rem;
}

.filters-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.search-box {
  position: relative;
  width: 300px;
}

.search-box i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 0.9rem;
}

.search-input {
  width: 100%;
  padding: 8px 12px 8px 35px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.9rem;
  transition: all 0.3s;
}

.search-input:focus {
  border-color: #10b981;
  outline: none;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.btn-reset {
  background: none;
  border: 2px solid #e2e8f0;
  padding: 8px 15px;
  border-radius: 10px;
  color: #718096;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
}

.btn-reset:hover {
  background: #f7fafc;
  border-color: #10b981;
  color: #10b981;
}

.filter-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-chip {
  padding: 6px 15px;
  background: #f7fafc;
  border: 2px solid #e2e8f0;
  border-radius: 30px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #718096;
  cursor: pointer;
  transition: all 0.3s;
}

.filter-chip:hover {
  border-color: #10b981;
  color: #10b981;
}

.filter-chip.active {
  background: #10b981;
  border-color: #10b981;
  color: white;
}

/* Results Header */
.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 0 5px;
  flex-wrap: wrap;
  gap: 15px;
}

.results-info {
  color: #4a5568;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
}

.results-per-page {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  color: #718096;
}

.per-page-select {
  padding: 6px 10px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  cursor: pointer;
}

.per-page-select:focus {
  border-color: #10b981;
  outline: none;
}

/* Table Styles */
.table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #f0f0f0;
  overflow: hidden;
}

.custom-table {
  width: 100%;
  border-collapse: collapse;
}

.custom-table thead th {
  background: #f8fafc;
  padding: 16px 20px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #2d3748;
  text-align: left;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.custom-table thead th.sortable {
  cursor: pointer;
  transition: background 0.3s;
}

.custom-table thead th.sortable:hover {
  background: #edf2f7;
}

.custom-table thead th span {
  margin-right: 8px;
}

.custom-table tbody td {
  padding: 16px 20px;
  border-bottom: 1px solid #edf2f7;
  color: #4a5568;
  font-size: 0.95rem;
  vertical-align: middle;
}

.table-row:hover {
  background: #f8fafc;
  transition: background 0.3s;
}

.inactive-row {
  opacity: 0.6;
  background: #f9f9f9;
}

/* Room Code Badge */
.room-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  color: white;
}

.badge-primary {
  background: linear-gradient(135deg, #4361ee, #3a56d4);
}

.badge-purple {
  background: linear-gradient(135deg, #9b59b6, #8e44ad);
}

.badge-blue {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.badge-green {
  background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.badge-orange {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.badge-red {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

/* Building Info */
.building-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.building-info i {
  color: #10b981;
  font-size: 1.1rem;
}

/* Capacity Info */
.capacity-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.capacity-info i {
  color: #f59e0b;
  font-size: 1.1rem;
}

/* Room Type Badge */
.room-type-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  color: white;
}

.type-primary {
  background: #4361ee;
}

.type-purple {
  background: #9b59b6;
}

.type-blue {
  background: #3498db;
}

.type-green {
  background: #2ecc71;
}

.type-orange {
  background: #f39c12;
}

.type-red {
  background: #e74c3c;
}

/* Schedule Info */
.schedule-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.schedule-count {
  font-weight: 600;
  color: #2d3748;
  font-size: 1.1rem;
}

.schedule-label {
  color: #a0aec0;
  font-size: 0.7rem;
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-available {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.status-available .status-dot {
  background: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.status-inactive {
  background: rgba(156, 163, 175, 0.1);
  color: #6b7280;
}

.status-inactive .status-dot {
  background: #9ca3af;
  box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.2);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1rem;
}

.btn-view {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.btn-view:hover {
  background: #10b981;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.btn-edit {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.btn-edit:hover {
  background: #f59e0b;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
}

.btn-delete {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.btn-delete:hover {
  background: #ef4444;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  padding: 20px;
  border-top: 1px solid #edf2f7;
}

.pagination-pages {
  display: flex;
  gap: 5px;
}

.pagination-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-btn:hover:not(:disabled) {
  background: #10b981;
  border-color: #10b981;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-page {
  min-width: 40px;
  height: 40px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #4a5568;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
}

.pagination-page:hover {
  border-color: #10b981;
  color: #10b981;
}

.pagination-page.active {
  background: #10b981;
  border-color: #10b981;
  color: white;
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.pagination-dots {
  min-width: 40px;
  height: 40px;
  border: none;
  background: none;
  color: #a0aec0;
  cursor: default;
  font-weight: 500;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 16px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 3px solid #e2e8f0;
  border-top-color: #10b981;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error State */
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 16px;
}

.error-state i {
  font-size: 4rem;
  color: #ef4444;
  margin-bottom: 20px;
}

.error-state h4 {
  color: #2d3748;
  margin-bottom: 10px;
  font-size: 1.3rem;
}

.error-state p {
  color: #718096;
  margin-bottom: 20px;
}

.btn-retry {
  padding: 10px 25px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-retry:hover {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-state i {
  font-size: 4rem;
  color: #cbd5e0;
  margin-bottom: 20px;
}

.empty-state h4 {
  color: #2d3748;
  margin-bottom: 10px;
  font-size: 1.3rem;
}

.empty-state p {
  color: #718096;
  margin-bottom: 20px;
}

.btn-reset-state {
  padding: 10px 25px;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-reset-state:hover {
  background: #059669;
}

/* Modal Styles */
.custom-modal {
  border-radius: 20px;
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.custom-modal .modal-header {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 20px 25px;
}

.custom-modal .modal-header .btn-close {
  filter: brightness(0) invert(1);
}

.custom-modal .modal-body {
  padding: 25px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #2d3748;
  font-size: 0.95rem;
}

.form-label i {
  color: #10b981;
}

.required {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s;
}

.form-control:focus {
  border-color: #10b981;
  outline: none;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-text {
  display: block;
  margin-top: 5px;
  color: #a0aec0;
  font-size: 0.8rem;
}

.form-check {
  margin-top: 15px;
}

.form-check-input {
  width: 3rem;
  height: 1.5rem;
  margin-right: 10px;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #10b981;
  border-color: #10b981;
}

.form-check-label {
  font-weight: 500;
  color: #2d3748;
  cursor: pointer;
}

.modal-footer {
  padding: 15px 25px;
  border-top: 2px solid #edf2f7;
}

/* Details Container */
.details-container {
  padding: 10px;
}

.detail-header {
  padding: 20px;
  color: white;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.detail-header.bg-primary {
  background: linear-gradient(135deg, #4361ee, #3a56d4);
}

.detail-header.bg-purple {
  background: linear-gradient(135deg, #9b59b6, #8e44ad);
}

.detail-header.bg-blue {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.detail-header.bg-green {
  background: linear-gradient(135deg, #2ecc71, #27ae60);
}

.detail-header.bg-orange {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.detail-header.bg-red {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.detail-code {
  font-size: 1.8rem;
  font-weight: 700;
}

.detail-type {
  background: rgba(255, 255, 255, 0.2);
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.detail-body {
  padding: 20px;
}

.detail-row {
  display: flex;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #edf2f7;
}

.detail-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.detail-label {
  width: 100px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #718096;
  font-size: 0.9rem;
}

.detail-label i {
  color: #10b981;
  font-size: 1rem;
}

.detail-value {
  flex: 1;
  color: #2d3748;
  font-weight: 500;
}

.capacity-badge {
  background: #10b981;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
}

/* Animations */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 1200px) {
  .custom-table {
    font-size: 0.85rem;
  }
  
  .custom-table thead th,
  .custom-table tbody td {
    padding: 12px 15px;
  }
}

@media (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .custom-table {
    min-width: 900px;
  }
  
  .table-container {
    overflow-x: auto;
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .header-left {
    flex-direction: column;
    text-align: center;
  }

  .header-title {
    font-size: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters-header {
    flex-direction: column;
    align-items: stretch;
  }

  .filters-actions {
    flex-direction: column;
  }

  .search-box {
    width: 100%;
  }

  .results-header {
    flex-direction: column;
    align-items: stretch;
  }

  .pagination-container {
    flex-wrap: wrap;
  }

  .detail-row {
    flex-direction: column;
    gap: 5px;
  }

  .detail-label {
    width: 100%;
  }
}
</style>