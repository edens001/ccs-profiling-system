<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Student Management'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <!-- Header -->
          <div class="content-header">
            <div class="d-flex align-items-center">
              <i class="bi bi-people-fill fs-2 text-primary me-3"></i>
              <div>
                <h4 class="mb-1">Student Management</h4>
                <p class="text-muted mb-0">Manage and view all students</p>
              </div>
            </div>
            <div class="d-flex gap-2">
              <!-- BULK APPROVE BUTTON -->
              <button v-if="selectedStudents.length > 0" 
                      class="btn btn-success" 
                      @click="bulkApprove">
                <i class="bi bi-check-all me-2"></i>
                Approve Selected ({{ selectedStudents.length }})
              </button>
              <button class="btn btn-primary" @click="addStudent">
                <i class="bi bi-plus-circle me-2"></i>
                Add Student
              </button>
            </div>
          </div>
          
          <!-- Search and Filter Bar -->
          <div class="filter-bar">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search by student number or name..." 
                    v-model="searchQuery"
                    @keyup.enter="searchStudents"
                  >
                  <button class="btn btn-primary" type="button" @click="searchStudents">
                    Search
                  </button>
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" v-model="courseFilter" @change="fetchStudents">
                  <option value="">All Courses</option>
                  <option v-for="course in courses" :key="course.id" :value="course.course_name">
                    {{ course.course_name }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-select" v-model="yearFilter" @change="fetchStudents">
                  <option value="">All Years</option>
                  <option value="1">1st Year</option>
                  <option value="2">2nd Year</option>
                  <option value="3">3rd Year</option>
                  <option value="4">4th Year</option>
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
            <p class="mt-2">Loading students...</p>
          </div>

          <!-- Results Summary -->
          <div v-else class="results-summary">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-3">
                <!-- SELECT ALL CHECKBOX -->
                <div class="form-check" v-if="hasPendingStudents">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="selectAll" 
                    v-model="selectAll"
                    @change="toggleSelectAll"
                  >
                  <label class="form-check-label" for="selectAll">
                    Select All Pending
                  </label>
                </div>
                <p class="mb-0">
                  <strong>{{ totalStudents }}</strong> students found
                  <span v-if="statusFilter !== 'all'" class="text-muted ms-2">
                    (Filtered by: {{ statusFilter }})
                  </span>
                </p>
              </div>
              <div class="d-flex align-items-center">
                <label class="me-2">Show:</label>
                <select class="form-select form-select-sm" style="width: auto;" v-model="itemsPerPage" @change="fetchStudents">
                  <option value="5">5</option>
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
              </div>
            </div>
          </div>
          
          <!-- Students Table -->
          <div v-if="!loading" class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                
                  <th style="width: 40px;">
                    <!-- Checkbox sa header -->
                  </th>
                  <th>Student #</th>
                  <th>Name</th>
                  <th>Course</th>
                  <th>Year/Section</th>
                  <th>Contact</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                 </thead>
                
              <tbody>
                <tr v-if="students.length === 0">
                  <td colspan="8" class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-0">No students found</p>
                   </td>
                 </tr>
                <tr v-for="student in students" :key="student.id" :class="{'table-warning': student.status === 'Pending'}">
                  <td class="text-center">
                    <!-- CHECKBOX - ONLY FOR PENDING STUDENTS -->
                    <input 
                      v-if="student.status === 'Pending'" 
                      type="checkbox" 
                      class="form-check-input" 
                      :value="student.id"
                      v-model="selectedStudents"
                    >
                   </td>
                  <td>
                    <span class="fw-bold">{{ student.student_number }}</span>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="student-avatar me-2">
                        {{ getUserInitials(student.first_name, student.last_name) }}
                      </div>
                      <div>
                        <div>{{ student.last_name }}, {{ student.first_name }}</div>
                        <small class="text-muted">{{ student.email || 'No email' }}</small>
                      </div>
                    </div>
                  </td>
                  <td>{{ getCourseName(student.course) }}</td>
                  <td>{{ student.year_level }}{{ student.section }}</td>
                  <td>{{ student.contact_number }}</td>
                  <td>
                    <span :class="'badge bg-' + getStatusColor(student.status)">
                      {{ student.status }}
                    </span>
                    <small v-if="student.enrolled_at" class="d-block text-muted">
                      Enrolled: {{ formatDate(student.enrolled_at) }}
                    </small>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <!-- NEW: ENROLL BUTTON - For enrolled students only -->
                      <button v-if="student.status === 'Enrolled'" 
                              class="btn btn-sm btn-primary" 
                              @click="openEnrollModal(student)" 
                              title="Enroll to Class">
                        <i class="bi bi-journal-bookmark-fill"></i>
                      </button>
                      <button class="btn btn-sm btn-info" @click="viewStudent(student.id)" title="View">
                        <i class="bi bi-eye"></i>
                      </button>
                      <button class="btn btn-sm btn-warning" @click="editStudent(student.id)" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" @click="deleteStudent(student.id)" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="pagination-wrapper" v-if="totalPages > 1">
            <nav>
              <ul class="pagination justify-content-center mb-0">
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

    <!-- ENROLLMENT MODAL -->
    <div class="modal fade" id="enrollModal" tabindex="-1" ref="enrollModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>
              Enroll Student: {{ selectedStudent?.full_name || selectedStudent?.last_name + ', ' + selectedStudent?.first_name }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">
                <i class="bi bi-calendar-week me-2 text-primary"></i>
                Select Class Schedule
              </label>
              <select class="form-select" v-model="selectedScheduleId" required>
                <option value="">-- Select a Class --</option>
                <option v-for="schedule in availableSchedules" :key="schedule.id" :value="schedule.id">
                  {{ schedule.course_code }} - {{ schedule.course_name }}
                  ({{ schedule.day_of_week }} {{ schedule.time_start }}-{{ schedule.time_end }})
                  - Faculty: {{ schedule.faculty_name }} | Room: {{ schedule.room_code }}
                </option>
              </select>
            </div>

            <!-- Current Enrollments Preview -->
            <div v-if="currentEnrollments.length > 0" class="mt-4">
              <hr>
              <h6 class="fw-semibold mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                Currently Enrolled Subjects:
              </h6>
              <div class="row g-2">
                <div v-for="enroll in currentEnrollments" :key="enroll.schedule_id" class="col-md-6">
                  <div class="alert alert-info py-2 mb-0">
                    <i class="bi bi-book me-2"></i>
                    {{ enroll.course_code }} - {{ enroll.course_name }}
                    <br>
                    <small>{{ enroll.day_of_week }} {{ enroll.time_start }}-{{ enroll.time_end }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="enrollStudent" :disabled="enrolling">
              <span v-if="enrolling" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="bi bi-check-circle me-2"></i>
              {{ enrolling ? 'Enrolling...' : 'Enroll Student' }}
            </button>
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
import { Modal } from 'bootstrap'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'StudentList',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(false)
    const students = ref([])
    const selectedStudents = ref([])
    const searchQuery = ref('')
    const courseFilter = ref('')
    const yearFilter = ref('')
    const statusFilter = ref('all')
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const totalStudents = ref(0)
    const totalPages = ref(1)
    
    // Enrollment Modal
    const enrollModal = ref(null)
    const selectedStudent = ref(null)
    const selectedScheduleId = ref('')
    const availableSchedules = ref([])
    const currentEnrollments = ref([])
    const enrolling = ref(false)
    
    const courses = ref([
      { id: 1, course_name: 'BS Computer Science' },
      { id: 2, course_name: 'BS Information Technology' },
      { id: 3, course_name: 'BS Information Systems' }
    ])
    
    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const hasPendingStudents = computed(() => {
      return students.value.some(s => s.status === 'Pending')
    })

    const selectAll = computed({
      get: () => {
        const pendingStudents = students.value.filter(s => s.status === 'Pending')
        return pendingStudents.length > 0 && selectedStudents.value.length === pendingStudents.length
      },
      set: (value) => {
        if (value) {
          const pendingIds = students.value.filter(s => s.status === 'Pending').map(s => s.id)
          selectedStudents.value = pendingIds
        } else {
          selectedStudents.value = []
        }
      }
    })

    const toggleSelectAll = () => {
      selectAll.value = !selectAll.value
    }

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
    
    const getCourseName = (courseValue) => {
      if (typeof courseValue === 'string' && isNaN(courseValue)) {
        return courseValue;
      }
      const courseId = parseInt(courseValue);
      const courseMap = {
        1: 'BS Computer Science',
        2: 'BS Information Technology',
        3: 'BS Information Systems'
      };
      return courseMap[courseId] || courseValue || 'N/A';
    }
    
    const fetchStudents = async () => {
      loading.value = true
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/students/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            page: currentPage.value,
            limit: itemsPerPage.value,
            search: searchQuery.value,
            course: courseFilter.value,
            year: yearFilter.value,
            status: statusFilter.value !== 'all' ? statusFilter.value : ''
          }
        })
        
        if (response.data.success) {
          students.value = response.data.data
          totalStudents.value = response.data.pagination.total
          totalPages.value = response.data.pagination.pages
          selectedStudents.value = []
        }
      } catch (error) {
        console.error('Error fetching students:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to fetch students'
        })
      } finally {
        loading.value = false
      }
    }
    
    // =============================================
    // ENROLLMENT FUNCTIONS
    // =============================================
    
    const fetchAvailableSchedules = async () => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/scheduling/available-schedules.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            semester: '1',
            academic_year: '2025-2026'
          }
        })
        
        if (response.data.success) {
          availableSchedules.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching schedules:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load available schedules'
        })
      }
    }
    
    const fetchCurrentEnrollments = async (studentId) => {
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/enrollments/index.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: { student_id: studentId }
        })
        
        if (response.data.success) {
          currentEnrollments.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching enrollments:', error)
      }
    }
    
    const openEnrollModal = async (student) => {
      selectedStudent.value = student
      selectedScheduleId.value = ''
      await fetchAvailableSchedules()
      await fetchCurrentEnrollments(student.id)
      
      const modal = new Modal(document.getElementById('enrollModal'))
      modal.show()
    }
    
    const enrollStudent = async () => {
      if (!selectedScheduleId.value) {
        Swal.fire({
          icon: 'warning',
          title: 'No Selection',
          text: 'Please select a class schedule to enroll the student.'
        })
        return
      }
      
      enrolling.value = true
      
      try {
        const token = store.state.auth.token
        const response = await axios.post(`${API_URL}/admin/enrollments/index.php`, {
          student_id: selectedStudent.value.id,
          schedule_ids: [selectedScheduleId.value]
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
        
        if (response.data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Enrolled!',
            text: `Student has been enrolled successfully.`,
            timer: 1500,
            showConfirmButton: false
          })
          
          // Close modal
          const modal = Modal.getInstance(document.getElementById('enrollModal'))
          if (modal) modal.hide()
          
          // Refresh student list
          fetchStudents()
        } else {
          throw new Error(response.data.message)
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to enroll student'
        })
      } finally {
        enrolling.value = false
      }
    }
    
    // =============================================
    // BULK APPROVE FUNCTIONS
    // =============================================
    
    const bulkApprove = async () => {
      if (selectedStudents.value.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'No Selection',
          text: 'Please select at least one student to approve.'
        })
        return
      }

      const result = await Swal.fire({
        title: 'Approve Selected Students?',
        text: `You are about to approve ${selectedStudents.value.length} student(s).`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#e74c3c',
        confirmButtonText: 'Yes, Approve All',
        cancelButtonText: 'Cancel'
      })

      if (result.isConfirmed) {
        let successCount = 0
        let failCount = 0

        for (const studentId of selectedStudents.value) {
          try {
            const token = store.state.auth.token
            await axios.post(`${API_URL}/admin/students/approve.php`, {
              student_id: studentId
            }, {
              headers: { 'Authorization': `Bearer ${token}` }
            })
            successCount++
          } catch (error) {
            failCount++
          }
        }

        Swal.fire({
          icon: successCount > 0 ? 'success' : 'error',
          title: 'Bulk Approval Complete',
          html: `
            <div class="text-center">
              <p><strong>${successCount}</strong> student(s) approved successfully.</p>
              ${failCount > 0 ? `<p class="text-danger"><strong>${failCount}</strong> student(s) failed.</p>` : ''}
            </div>
          `,
          timer: 3000,
          showConfirmButton: true
        })

        selectedStudents.value = []
        fetchStudents()
      }
    }
    
    const searchStudents = () => {
      currentPage.value = 1
      fetchStudents()
    }
    
    const resetFilters = () => {
      searchQuery.value = ''
      courseFilter.value = ''
      yearFilter.value = ''
      statusFilter.value = 'all'
      currentPage.value = 1
      selectedStudents.value = []
      fetchStudents()
    }
    
    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        currentPage.value = page
        selectedStudents.value = []
        fetchStudents()
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }
    
    const getUserInitials = (first, last) => {
      return (first?.charAt(0) || '') + (last?.charAt(0) || '')
    }
    
    const getStatusColor = (status) => {
      const colors = {
        'Enrolled': 'success',
        'Pending': 'warning',
        'Rejected': 'danger',
        'Inactive': 'secondary'
      }
      return colors[status] || 'secondary'
    }
    
    const formatDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const addStudent = () => {
      router.push('/admin/students/add')
    }
    
    const viewStudent = (id) => {
      router.push(`/admin/students/view/${id}`)
    }
    
    const editStudent = (id) => {
      router.push(`/admin/students/edit/${id}`)
    }
    
    const deleteStudent = async (id) => {
      const result = await Swal.fire({
        title: 'Delete Student?',
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
          const response = await axios.delete(`${API_URL}/admin/students/delete.php?id=${id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          })
          
          if (response.data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: response.data.message,
              timer: 1500,
              showConfirmButton: false
            })
            fetchStudents()
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to delete student'
          })
        }
      }
    }
    
    const importStudents = () => {
      Swal.fire({
        title: 'Import Students',
        html: `
          <div class="mb-3">
            <input type="file" id="fileInput" class="form-control" accept=".xlsx,.xls,.csv">
          </div>
          <p class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Supported formats: Excel (.xlsx, .xls) and CSV
          </p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Import',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
          const file = document.getElementById('fileInput').files[0]
          if (!file) {
            Swal.showValidationMessage('Please select a file')
          }
          return file
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            icon: 'success',
            title: 'Import Started',
            text: 'Students are being imported. You will be notified when complete.',
            showConfirmButton: false,
            timer: 2000
          })
        }
      })
    }
    
    onMounted(() => {
      fetchStudents()
    })
    
    return {
      loading,
      students,
      selectedStudents,
      selectAll,
      hasPendingStudents,
      toggleSelectAll,
      searchQuery,
      courseFilter,
      yearFilter,
      statusFilter,
      currentPage,
      itemsPerPage,
      totalStudents,
      totalPages,
      courses,
      displayedPages,
      fetchStudents,
      searchStudents,
      resetFilters,
      changePage,
      getUserInitials,
      getCourseName,
      getStatusColor,
      formatDate,
      addStudent,
      viewStudent,
      editStudent,
      deleteStudent,
      importStudents,
      bulkApprove,
      // Enrollment
      enrollModal,
      selectedStudent,
      selectedScheduleId,
      availableSchedules,
      currentEnrollments,
      enrolling,
      openEnrollModal,
      enrollStudent
    }
  }
}
</script>

<style scoped>
/* ===== LAYOUT FIXES ===== */
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
  padding: 20px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
}

:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

.container-fluid {
  padding: 0;
  max-width: 1900px;
  margin: 0 auto;
}

.content-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.05);
  padding: 25px;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
}

.status-tabs {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.status-tabs .btn-group {
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  border-radius: 50px;
  overflow: hidden;
}

.status-tabs .btn {
  padding: 8px 20px;
  font-weight: 500;
}

.filter-bar {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.results-summary {
  background: white;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
  border: 1px solid #e9ecef;
}

.table {
  margin-bottom: 0;
}

.table th {
  background: #f8f9fa;
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.9rem;
  border-bottom: 2px solid #e9ecef;
  padding: 15px 10px;
}

.table td {
  vertical-align: middle;
  padding: 15px 10px;
  border-bottom: 1px solid #e9ecef;
}

.table-hover tbody tr:hover {
  background-color: #f8f9fa;
}

.table-warning {
  background-color: #fff3cd;
}

.student-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.8rem;
  text-transform: uppercase;
}

.action-buttons {
  display: flex;
  gap: 5px;
  justify-content: center;
  flex-wrap: wrap;
}

.action-buttons .btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.badge {
  padding: 6px 12px;
  font-weight: 500;
  font-size: 0.8rem;
}

.pagination-wrapper {
  margin-top: 25px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
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
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.content-card {
  animation: fadeIn 0.3s ease;
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

  .status-tabs .btn-group {
    display: flex;
    flex-wrap: wrap;
    border-radius: 10px;
  }

  .status-tabs .btn {
    flex: 1 1 auto;
    padding: 6px 12px;
    font-size: 0.85rem;
  }

  .action-buttons {
    flex-wrap: wrap;
  }

  .table {
    font-size: 0.85rem;
  }
}
</style>