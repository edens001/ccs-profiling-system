<template>
  <div class="app-wrapper">
    <FacultySidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'My Students'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-people"></i>
              </div>
              <div>
                <h2 class="mb-1">My Students</h2>
                <p class="text-muted mb-0">View and manage your students</p>
              </div>
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
                    placeholder="Search students..." 
                    v-model="filters.search"
                    @keyup.enter="fetchStudents"
                  >
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="filters.course_id" @change="fetchStudents">
                  <option value="0">All Courses</option>
                  <option v-for="course in filterOptions.courses" :key="course.course_id" :value="course.course_id">
                    {{ course.course_code }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="filters.section_id" @change="fetchStudents">
                  <option value="0">All Sections</option>
                  <option v-for="section in filterOptions.sections" :key="section.section_id" :value="section.section_id">
                    {{ section.section_code }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" @click="resetFilters">
                  <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading students...</p>
          </div>

          <!-- Students Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover">
              <thead>
                
                  <th>Student #</th>
                  <th>Name</th>
                  <th>Course</th>
                  <th>Year/Section</th>
                  <th>Contact</th>
                  <th>Actions</th>
                 </thead>
                 
              <tbody>
                <tr v-if="students.length === 0">
                  <td colspan="6" class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No students found</p>
                  </td>
                 </tr>
                <tr v-for="student in students" :key="student.id">
                  <td><strong>{{ student.student_number }}</strong></td>
                  <td>{{ student.last_name }}, {{ student.first_name }}</td>
                  <td>{{ student.course_code }}</td>
                  <td>{{ student.year_level }}{{ student.section }}</td>
                  <td>{{ student.contact_number }}</td>
                  <td>
                    <button class="btn btn-sm btn-info me-1" @click="viewStudent(student.id)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" @click="gradeStudent(student.id)">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Summary -->
          <div class="summary-bar">
            <span class="badge bg-primary">Total: {{ totalStudents }} students</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import FacultySidebar from '@/components/layout/FacultySidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'FacultyStudents',
  components: {
    FacultySidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const students = ref([])
    const totalStudents = ref(0)
    const filterOptions = ref({
      courses: [],
      sections: []
    })
    
    const filters = ref({
      search: '',
      course_id: 0,
      section_id: 0
    })

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const fetchStudents = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/faculty/students.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            search: filters.value.search,
            course_id: filters.value.course_id,
            section_id: filters.value.section_id
          }
        })

        console.log('Students response:', response.data)

        if (response.data.success) {
          students.value = response.data.data || []
          totalStudents.value = response.data.total || 0
          
          // Handle filters
          if (response.data.filters) {
            filterOptions.value.courses = response.data.filters.courses || []
            filterOptions.value.sections = response.data.filters.sections || []
          } else {
            filterOptions.value.courses = []
            filterOptions.value.sections = []
          }
        }
      } catch (error) {
        console.error('Error fetching students:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to load students'
        })
      } finally {
        loading.value = false
      }
    }

    const resetFilters = () => {
      filters.value = {
        search: '',
        course_id: 0,
        section_id: 0
      }
      fetchStudents()
    }

    const viewStudent = (id) => {
      router.push(`/faculty/students/${id}`)
    }

    const gradeStudent = (id) => {
      router.push(`/faculty/grades/student/${id}`)
    }

    onMounted(() => {
      fetchStudents()
    })

    return {
      loading,
      students,
      totalStudents,
      filterOptions,
      filters,
      fetchStudents,
      resetFilters,
      viewStudent,
      gradeStudent
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

.filters-bar {
  margin-bottom: 30px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 12px;
}

.table {
  margin-bottom: 0;
}

.table th {
  background: #f8f9fa;
  border-bottom: 2px solid #27ae60;
  color: #2c3e50;
  font-weight: 600;
}

.table td {
  vertical-align: middle;
}

.btn-sm {
  padding: 5px 10px;
  margin: 0 2px;
}

.summary-bar {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px solid #f0f0f0;
  text-align: right;
}

.badge {
  padding: 8px 15px;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }
  
  .content-card {
    padding: 20px;
  }
  
  .filters-bar .row > div {
    margin-bottom: 10px;
  }
}
</style>