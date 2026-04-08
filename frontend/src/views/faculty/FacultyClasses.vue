<template>
  <div class="app-wrapper">
    <FacultySidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'My Classes'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-book"></i>
              </div>
              <div>
                <h2 class="mb-1">My Classes</h2>
                <p class="text-muted mb-0">View and manage your classes</p>
              </div>
            </div>
          </div>

          <div class="row g-4">
            <div v-for="classItem in classes" :key="classItem.id" class="col-md-6 col-lg-4">
              <div class="class-card">
                <div class="card-header" :class="'bg-' + classItem.color">
                  <h5>{{ classItem.code }}</h5>
                  <span class="badge bg-white text-dark">{{ classItem.section }}</span>
                </div>
                <div class="card-body">
                  <p class="class-title">{{ classItem.title }}</p>
                  <div class="class-details">
                    <p><i class="bi bi-calendar"></i> {{ classItem.schedule }}</p>
                    <p><i class="bi bi-door-open"></i> {{ classItem.room }}</p>
                    <p><i class="bi bi-people"></i> {{ classItem.students }} students</p>
                  </div>
                  <div class="class-actions">
                    <button class="btn btn-outline-primary btn-sm" @click="viewRoster(classItem.id)">
                      <i class="bi bi-people"></i> Roster
                    </button>
                    <button class="btn btn-outline-success btn-sm" @click="enterGrades(classItem.id)">
                      <i class="bi bi-pencil-square"></i> Grades
                    </button>
                    <button class="btn btn-outline-info btn-sm" @click="viewSchedule(classItem.id)">
                      <i class="bi bi-calendar"></i> Schedule
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
</template>

<script>
import { ref } from 'vue'
import FacultySidebar from '@/components/layout/FacultySidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultyClasses',
  components: {
    FacultySidebar,
    TopNav
  },
  setup() {
    const classes = ref([])
    const loading = ref(true)

    const fetchClasses = async () => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/faculty/classes.php`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        if (response.data.success) {
          classes.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching classes:', error)
      }
    }

    const viewRoster = (id) => {
      Swal.fire({
        title: 'Class Roster',
        text: `Viewing roster for class ID: ${id}`,
        icon: 'info'
      })
    }

    const enterGrades = (id) => {
      Swal.fire({
        title: 'Enter Grades',
        text: `Entering grades for class ID: ${id}`,
        icon: 'info'
      })
    }

    const viewSchedule = (id) => {
      Swal.fire({
        title: 'Class Schedule',
        text: `Viewing schedule for class ID: ${id}`,
        icon: 'info'
      })
    }

    return {
      classes,
      viewRoster,
      enterGrades,
      viewSchedule
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

.content-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
  background: linear-gradient(135deg, #27ae60, #229954);
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

.class-card {
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  transition: all 0.3s;
  height: 100%;
}

.class-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.card-header {
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
}

.card-header.bg-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.card-header.bg-success {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.card-header.bg-info {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.card-header h5 {
  margin: 0;
  font-weight: 600;
}

.card-body {
  padding: 20px;
}

.class-title {
  font-size: 1rem;
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 15px;
}

.class-details p {
  margin-bottom: 8px;
  font-size: 0.9rem;
  color: #6c757d;
}

.class-details i {
  width: 20px;
  margin-right: 8px;
  color: #27ae60;
}

.class-actions {
  display: flex;
  gap: 8px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 5px 10px;
  font-size: 0.8rem;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }
}
</style>