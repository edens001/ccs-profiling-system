<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Rooms Management'" />
      
      <div class="container-fluid p-4">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon bg-success">
                <i class="bi bi-door-open-fill text-white"></i>
              </div>
              <div>
                <h2 class="mb-1">Rooms Management</h2>
                <p class="text-muted mb-0">Manage rooms and facilities</p>
              </div>
            </div>
            <button class="btn btn-success" @click="openAddModal">
              <i class="bi bi-plus-circle me-2"></i>
              Add Room
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-success" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <!-- Rooms Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Room Code</th>
                  <th>Building</th>
                  <th>Capacity</th>
                  <th>Schedules</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="rooms.length === 0">
                  <td colspan="6" class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted d-block"></i>
                    <p class="text-muted">No rooms found</p>
                  </td>
                </tr>
                <tr v-for="room in rooms" :key="room.id">
                  <td>
                    <span class="fw-semibold">{{ room.room_code }}</span>
                  </td>
                  <td>{{ room.building }}</td>
                  <td>{{ room.capacity }}</td>
                  <td>
                    <span class="badge bg-info">{{ room.schedule_count || 0 }}</span>
                  </td>
                  <td>
                    <span :class="'badge bg-' + (room.is_active ? 'success' : 'secondary')">
                      {{ room.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-info" @click="viewRoom(room)" title="View">
                        <i class="bi bi-eye"></i>
                      </button>
                      <button class="btn btn-sm btn-warning" @click="editRoom(room)" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" @click="deleteRoom(room.id)" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i :class="modalIcon" class="me-2"></i>
              {{ modalTitle }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveRoom">
              <div class="mb-3">
                <label class="form-label">Room Code</label>
                <input type="text" class="form-control" v-model="form.room_code" 
                       placeholder="e.g., R101, LAB101" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Building</label>
                <input type="text" class="form-control" v-model="form.building" 
                       placeholder="e.g., Main Building, Science Lab" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Capacity</label>
                <input type="number" class="form-control" v-model="form.capacity" 
                       min="1" required>
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive">
                  <label class="form-check-label" for="isActive">Active</label>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" @click="saveRoom" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              {{ saving ? 'Saving...' : 'Save Room' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-eye-fill me-2 text-info"></i>
              Room Details
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="view-details" v-if="viewData">
              <div class="detail-row">
                <label>Room Code:</label>
                <span class="fw-semibold">{{ viewData.room_code }}</span>
              </div>
              <div class="detail-row">
                <label>Building:</label>
                <span>{{ viewData.building }}</span>
              </div>
              <div class="detail-row">
                <label>Capacity:</label>
                <span>{{ viewData.capacity }} students</span>
              </div>
              <div class="detail-row">
                <label>Total Schedules:</label>
                <span class="badge bg-info">{{ viewData.schedule_count || 0 }}</span>
              </div>
              <div class="detail-row">
                <label>Status:</label>
                <span :class="'badge bg-' + (viewData.is_active ? 'success' : 'secondary')">
                  {{ viewData.is_active ? 'Active' : 'Inactive' }}
                </span>
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
import { ref, onMounted, computed } from 'vue'
import { useStore } from 'vuex'
import axios from 'axios'
import { Modal } from 'bootstrap'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'RoomsView',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const loading = ref(false)
    const saving = ref(false)
    const rooms = ref([])
    
    const form = ref({
      id: null,
      room_code: '',
      building: '',
      capacity: 30,
      is_active: true
    })

    const viewData = ref(null)

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const modalTitle = computed(() => {
      return form.value.id ? 'Edit Room' : 'Add New Room'
    })

    const modalIcon = computed(() => {
      return form.value.id ? 'bi bi-pencil-fill' : 'bi bi-plus-circle-fill'
    })

    const fetchRooms = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/scheduling/rooms.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        if (response.data.success) {
          rooms.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching rooms:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to fetch rooms'
        })
      } finally {
        loading.value = false
      }
    }

    const openAddModal = () => {
      form.value = {
        id: null,
        room_code: '',
        building: '',
        capacity: 30,
        is_active: true
      }
      new Modal(document.getElementById('roomModal')).show()
    }

    const editRoom = (room) => {
      form.value = {
        id: room.id,
        room_code: room.room_code,
        building: room.building,
        capacity: room.capacity,
        is_active: room.is_active == 1
      }
      new Modal(document.getElementById('roomModal')).show()
    }

    const viewRoom = (room) => {
      viewData.value = room
      new Modal(document.getElementById('viewModal')).show()
    }

    const saveRoom = async () => {
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
          Modal.getInstance(document.getElementById('roomModal')).hide()
          await Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: `Room ${form.value.id ? 'updated' : 'created'} successfully`,
            timer: 1500,
            showConfirmButton: false
          })
          fetchRooms()
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to save room'
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
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete room'
          })
        }
      }
    }

    onMounted(() => {
      fetchRooms()
    })

    return {
      loading,
      saving,
      rooms,
      form,
      viewData,
      modalTitle,
      modalIcon,
      openAddModal,
      editRoom,
      viewRoom,
      saveRoom,
      deleteRoom
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
  max-width: 1800px;
  margin: 0 auto;
}

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

.content-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.info-card {
  background: white;
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #f0f0f0;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  color: #7f8c8d;
  font-size: 0.9rem;
}

.info-value {
  font-weight: 500;
  color: #2c3e50;
  text-align: right;
}

.content-text {
  line-height: 1.8;
  color: #2c3e50;
}

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

  .info-item {
    flex-direction: column;
    gap: 5px;
  }

  .info-value {
    text-align: left;
  }
}
</style>