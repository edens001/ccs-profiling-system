<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Scheduling Management'" />
      
      <div class="container-fluid p-4">
        <!-- Header with Gradient -->
        <div class="header-gradient mb-4">
          <div class="header-content">
            <div class="header-left">
              <div class="header-icon-wrapper">
                <i class="bi bi-calendar-week-fill"></i>
              </div>
              <div class="header-text">
                <h1 class="header-title">Scheduling Management</h1>
                <p class="header-subtitle">Manage class schedules, sections, and room assignments</p>
              </div>
            </div>
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

        <!-- Quick Actions Cards -->
        <div class="quick-actions-grid">
          <div class="quick-action-card" @click="goToSections">
            <div class="quick-action-icon bg-primary">
              <i class="bi bi-people-fill"></i>
            </div>
            <div class="quick-action-content">
              <h4>Sections</h4>
              <p>Manage class sections and student assignments</p>
              <span class="btn-link">Manage Sections <i class="bi bi-arrow-right"></i></span>
            </div>
          </div>

          <div class="quick-action-card" @click="goToRooms">
            <div class="quick-action-icon bg-success">
              <i class="bi bi-door-open-fill"></i>
            </div>
            <div class="quick-action-content">
              <h4>Rooms</h4>
              <p>Manage rooms, laboratories, and facilities</p>
              <span class="btn-link">Manage Rooms <i class="bi bi-arrow-right"></i></span>
            </div>
          </div>

          <div class="quick-action-card" @click="goToFacultySchedule">
            <div class="quick-action-icon bg-info">
              <i class="bi bi-person-badge-fill"></i>
            </div>
            <div class="quick-action-content">
              <h4>Faculty Schedule</h4>
              <p>View and manage faculty teaching schedules</p>
              <span class="btn-link">View Schedules <i class="bi bi-arrow-right"></i></span>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading schedule data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <i class="bi bi-exclamation-triangle"></i>
          <h4>Oops! Something went wrong</h4>
          <p>{{ error }}</p>
          <button class="btn-retry" @click="fetchStats">
            <i class="bi bi-arrow-clockwise me-2"></i>
            Try Again
          </button>
        </div>

        <!-- Recent Schedules Section -->
        <div v-else class="recent-section">
          <h4 class="section-title">
            <i class="bi bi-clock-history me-2"></i>
            Recent Schedules
          </h4>

          <div class="recent-schedules">
            <div class="table-responsive">
              <table class="custom-table">
                <thead>
                  
                    <th>Course</th>
                    <th>Section</th>
                    <th>Faculty</th>
                    <th>Room</th>
                    <th>Schedule</th>
                    <th>Status</th>
                  </thead>
                <tbody>
                  <tr v-if="recentSchedules.length === 0" class="empty-row">
                    <td colspan="6">
                      <div class="empty-state-small">
                        <i class="bi bi-calendar-x"></i>
                        <p>No recent schedules found</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="schedule in recentSchedules" :key="schedule.id" class="table-row">
                    <td>
                      <span class="fw-semibold">{{ schedule.course_code }}</span>
                      <small class="d-block text-muted">{{ schedule.course_name }}</small>
                    </td>
                    <td>
                      <span class="badge-section">{{ schedule.section_code }}</span>
                    </td>
                    <td>
                      <div class="faculty-info">
                        <i class="bi bi-person-circle"></i>
                        <span>{{ schedule.faculty_name }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="room-info">
                        <i class="bi bi-door-open"></i>
                        <span>{{ schedule.room_code }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="schedule-time">
                        <i class="bi bi-calendar"></i>
                        <span>{{ schedule.day_of_week }}</span>
                        <i class="bi bi-clock ms-2"></i>
                        <span>{{ schedule.time_start }} - {{ schedule.time_end }}</span>
                      </div>
                    </td>
                    <td>
                      <span class="status-badge" :class="'status-' + schedule.status.toLowerCase()">
                        <span class="status-dot"></span>
                        {{ schedule.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
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
import { useStore } from 'vuex'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Scheduling',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const error = ref(null)
    
    // Stats data
    const stats = ref({
      active_sections: 0,
      available_rooms: 0,
      faculty_assigned: 0,
      total_schedules: 0
    })
    
    const recentSchedules = ref([])
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    // Stats Data for cards
    const statsData = computed(() => [
      {
        label: 'Active Sections',
        value: stats.value.active_sections,
        icon: 'bi bi-people-fill',
        color: '#4361ee',
        sub: 'Currently active'
      },
      {
        label: 'Available Rooms',
        value: stats.value.available_rooms,
        icon: 'bi bi-door-open-fill',
        color: '#10b981',
        sub: 'Ready for use'
      },
      {
        label: 'Faculty Assigned',
        value: stats.value.faculty_assigned,
        icon: 'bi bi-person-badge-fill',
        color: '#06b6d4',
        sub: 'With schedules'
      },
      {
        label: 'Total Schedules',
        value: stats.value.total_schedules,
        icon: 'bi bi-clock-fill',
        color: '#f59e0b',
        sub: 'This semester'
      }
    ])

    // Fetch stats and recent schedules
    const fetchStats = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        
        // Fetch sections count
        const sectionsRes = await axios.get(`${API_URL}/admin/scheduling/sections.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (sectionsRes.data.success) {
          const activeSections = sectionsRes.data.data.filter(s => s.is_active == 1)
          stats.value.active_sections = activeSections.length
        }
        
        // Fetch rooms count
        const roomsRes = await axios.get(`${API_URL}/admin/scheduling/rooms.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (roomsRes.data.success) {
          const activeRooms = roomsRes.data.data.filter(r => r.is_active == 1)
          stats.value.available_rooms = activeRooms.length
        }
        
        // Fetch faculty with schedules
        const facultyRes = await axios.get(`${API_URL}/admin/faculty/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { limit: 100 }
        })
        
        if (facultyRes.data.success) {
          stats.value.faculty_assigned = facultyRes.data.data.length
        }
        
        // Fetch schedules count
        const schedulesRes = await axios.get(`${API_URL}/admin/scheduling/faculty-schedule.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { limit: 100 }
        })
        
        if (schedulesRes.data.success) {
          stats.value.total_schedules = schedulesRes.data.total || schedulesRes.data.schedules?.length || 0
          
          // Get recent schedules (last 5)
          const allSchedules = schedulesRes.data.schedules || []
          recentSchedules.value = allSchedules.slice(0, 5).map(s => ({
            ...s,
            status: getScheduleStatus(s.day_of_week, s.time_start, s.time_end)
          }))
        }
        
      } catch (err) {
        console.error('Error fetching stats:', err)
        error.value = err.response?.data?.message || 'Failed to load scheduling data'
        
        // Fallback data
        stats.value = {
          active_sections: 24,
          available_rooms: 15,
          faculty_assigned: 32,
          total_schedules: 156
        }
        
        recentSchedules.value = [
          {
            id: 1,
            course_code: 'CS101',
            course_name: 'Introduction to Computing',
            section_code: 'A',
            faculty_name: 'Dr. Juan Dela Cruz',
            room_code: 'R101',
            day_of_week: 'Monday',
            time_start: '09:00',
            time_end: '10:30',
            status: 'Active'
          },
          {
            id: 2,
            course_code: 'CS102',
            course_name: 'Computer Programming 1',
            section_code: 'B',
            faculty_name: 'Prof. Maria Santos',
            room_code: 'R102',
            day_of_week: 'Tuesday',
            time_start: '13:00',
            time_end: '14:30',
            status: 'Active'
          },
          {
            id: 3,
            course_code: 'IT201',
            course_name: 'Web Development',
            section_code: 'C',
            faculty_name: 'Dr. Pedro Reyes',
            room_code: 'LAB101',
            day_of_week: 'Wednesday',
            time_start: '10:00',
            time_end: '11:30',
            status: 'Pending'
          },
          {
            id: 4,
            course_code: 'IS301',
            course_name: 'Database Management',
            section_code: 'A',
            faculty_name: 'Prof. Ana Gonzales',
            room_code: 'R201',
            day_of_week: 'Thursday',
            time_start: '09:00',
            time_end: '10:30',
            status: 'Active'
          },
          {
            id: 5,
            course_code: 'CS201',
            course_name: 'Data Structures',
            section_code: 'A',
            faculty_name: 'Dr. Jose Rizal',
            room_code: 'LAB102',
            day_of_week: 'Friday',
            time_start: '14:00',
            time_end: '15:30',
            status: 'Completed'
          }
        ]
      } finally {
        loading.value = false
      }
    }
    
    // Helper function to determine schedule status
    const getScheduleStatus = (day, start, end) => {
      const now = new Date()
      const today = now.toLocaleDateString('en-US', { weekday: 'long' })
      
      if (day !== today) return 'Upcoming'
      
      const [startHour, startMin] = start.split(':')
      const [endHour, endMin] = end.split(':')
      const startTime = new Date().setHours(parseInt(startHour), parseInt(startMin), 0)
      const endTime = new Date().setHours(parseInt(endHour), parseInt(endMin), 0)
      const currentTime = now.getTime()
      
      if (currentTime < startTime) return 'Upcoming'
      if (currentTime >= startTime && currentTime <= endTime) return 'Ongoing'
      return 'Completed'
    }
    
    // Navigation methods
    const goToSections = () => {
      router.push('/admin/scheduling/sections')
    }
    
    const goToRooms = () => {
      router.push('/admin/scheduling/rooms')
    }
    
    const goToFacultySchedule = () => {
      router.push('/admin/scheduling/faculty-schedule')
    }
    
    onMounted(() => {
      fetchStats()
    })
    
    return {
      loading,
      error,
      statsData,
      recentSchedules,
      goToSections,
      goToRooms,
      goToFacultySchedule,
      fetchStats
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
  background: linear-gradient(135deg, #f39c12, #e67e22);
  border-radius: 20px;
  padding: 30px;
  color: white;
  box-shadow: 0 10px 30px rgba(243, 156, 18, 0.3);
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

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 30px;
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
  border-color: #f39c12;
}

.stat-icon {
  width: 55px;
  height: 55px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
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

/* Quick Actions Grid */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 25px;
  margin-bottom: 30px;
}

.quick-action-card {
  background: white;
  border-radius: 20px;
  padding: 25px;
  display: flex;
  gap: 20px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
  transition: all 0.3s;
  cursor: pointer;
  border: 2px solid transparent;
}

.quick-action-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
  border-color: #f39c12;
}

.quick-action-icon {
  width: 70px;
  height: 70px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.2rem;
  color: white;
  flex-shrink: 0;
}

.quick-action-icon.bg-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.quick-action-icon.bg-success {
  background: linear-gradient(135deg, #10b981, #059669);
}

.quick-action-icon.bg-info {
  background: linear-gradient(135deg, #06b6d4, #0891b2);
}

.quick-action-content {
  flex: 1;
}

.quick-action-content h4 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 8px;
}

.quick-action-content p {
  color: #718096;
  font-size: 0.95rem;
  line-height: 1.5;
  margin-bottom: 15px;
}

.btn-link {
  color: #f39c12;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-link:hover {
  color: #e67e22;
}

.btn-link i {
  transition: transform 0.3s;
}

.btn-link:hover i {
  transform: translateX(5px);
}

/* Recent Section */
.recent-section {
  margin-top: 20px;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e2e8f0;
}

.recent-schedules {
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
  padding: 15px 20px;
  font-size: 0.9rem;
  font-weight: 600;
  color: #2d3748;
  text-align: left;
  border-bottom: 2px solid #e2e8f0;
}

.custom-table tbody td {
  padding: 15px 20px;
  border-bottom: 1px solid #edf2f7;
  color: #4a5568;
  vertical-align: middle;
}

.table-row:hover {
  background: #f8fafc;
  transition: background 0.3s;
}

/* Badges */
.badge-section {
  background: linear-gradient(135deg, #667eea15, #764ba215);
  color: #667eea;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.faculty-info, .room-info, .schedule-time {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
}

.faculty-info i {
  color: #10b981;
  font-size: 1rem;
}

.room-info i {
  color: #f59e0b;
  font-size: 1rem;
}

.schedule-time i {
  color: #06b6d4;
  font-size: 0.9rem;
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

.status-active {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.status-active .status-dot {
  background: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.status-pending {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.status-pending .status-dot {
  background: #f59e0b;
  box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
}

.status-upcoming {
  background: rgba(6, 182, 212, 0.1);
  color: #06b6d4;
}

.status-upcoming .status-dot {
  background: #06b6d4;
  box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2);
}

.status-completed {
  background: rgba(156, 163, 175, 0.1);
  color: #6b7280;
}

.status-completed .status-dot {
  background: #9ca3af;
  box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.2);
}

.status-ongoing {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.status-ongoing .status-dot {
  background: #10b981;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(0.8);
    opacity: 0.5;
  }
  50% {
    transform: scale(1.2);
    opacity: 1;
  }
  100% {
    transform: scale(0.8);
    opacity: 0.5;
  }
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
  border-top-color: #f39c12;
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
  background: #f39c12;
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-retry:hover {
  background: #e67e22;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(243, 156, 18, 0.3);
}

/* Empty State Small */
.empty-state-small {
  text-align: center;
  padding: 40px 20px;
}

.empty-state-small i {
  font-size: 3rem;
  color: #cbd5e0;
  margin-bottom: 10px;
  display: block;
}

.empty-state-small p {
  color: #718096;
  margin: 0;
}

/* Responsive */
@media (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .quick-actions-grid {
    grid-template-columns: repeat(2, 1fr);
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

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }

  .quick-action-card {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .custom-table {
    font-size: 0.85rem;
  }

  .custom-table thead th,
  .custom-table tbody td {
    padding: 10px 12px;
  }

  .faculty-info, .room-info, .schedule-time {
    font-size: 0.8rem;
  }
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
</style>