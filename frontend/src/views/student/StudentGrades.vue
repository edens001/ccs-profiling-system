<template>
  <div class="app-wrapper">
    <StudentSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'My Grades'" />
      
      <div class="container-fluid">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading grades data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <strong>Error!</strong> {{ error }}
          </div>
          <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchGradesData">
            <i class="bi bi-arrow-clockwise"></i> Retry
          </button>
        </div>

        <!-- Grades Content -->
        <template v-else>
          <!-- GPA Summary Cards -->
          <div class="row mb-4">
            <div class="col-md-3 mb-3" v-for="stat in gpaStats" :key="stat.label">
              <div class="card stat-card" :class="'border-' + stat.color">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="card-title text-muted mb-2">{{ stat.label }}</h6>
                      <h2 class="mb-0" :class="'text-' + stat.color">{{ stat.value }}</h2>
                      <small class="text-muted">{{ stat.subtext }}</small>
                    </div>
                    <div class="stat-icon" :class="'bg-' + stat.color + '-light'">
                      <!-- <i :class="stat.icon":class="'text-' + stat.color"></i> -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Filters -->
          <div class="table-container mb-4">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">School Year</label>
                <select class="form-select" v-model="filters.year">
                  <option value="">All Years</option>
                  <option v-for="year in availableYears" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Semester</label>
                <select class="form-select" v-model="filters.semester">
                  <option value="">All Semesters</option>
                  <option value="1">1st Semester</option>
                  <option value="2">2nd Semester</option>
                  <option value="3">Summer</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Course</label>
                <select class="form-select" v-model="filters.course">
                  <option value="">All Courses</option>
                  <option v-for="course in availableCourses" :key="course" :value="course">
                    {{ course }}
                  </option>
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" @click="applyFilters">
                  <i class="bi bi-funnel"></i> Apply Filters
                </button>
              </div>
            </div>
          </div>
          
          <!-- Grades by Semester -->
          <div v-for="semester in gradesBySemester" :key="semester.academic_year + semester.semester" class="table-container mb-4">
            <div class="table-header">
              <h5 class="table-title">
                <i class="bi bi-calendar-range me-2 text-primary"></i>
                {{ semester.academic_year }} - {{ semester.semester_name }}
              </h5>
              <div class="d-flex align-items-center">
                <span class="badge bg-info me-2">GPA: {{ semester.gpa }}</span>
                <span class="badge bg-secondary">Units: {{ semester.total_units }}</span>
              </div>
            </div>
            
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Course Code</th>
                    <th>Course Description</th>
                    <th>Units</th>
                    <th>Prelim</th>
                    <th>Midterm</th>
                    <th>Final</th>
                    <th>Final Grade</th>
                    <th>Remarks</th>
                    <th>Instructor</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="grade in semester.courses" :key="grade.course_code">
                    <td><strong>{{ grade.course_code }}</strong></td>
                    <td>{{ grade.course_name }}</td>
                    <td>{{ grade.units }}</td>
                    <td>
                      <span :class="getGradeBadgeClass(grade.prelim)">
                        {{ grade.prelim }}
                      </span>
                    </td>
                    <td>
                      <span :class="getGradeBadgeClass(grade.midterm)">
                        {{ grade.midterm }}
                      </span>
                    </td>
                    <td>
                      <span :class="getGradeBadgeClass(grade.final)">
                        {{ grade.final }}
                      </span>
                    </td>
                    <td>
                      <span :class="'badge bg-' + getGradeColor(grade.final)">
                        {{ grade.final }}
                      </span>
                    </td>
                    <td>
                      <span :class="'badge bg-' + getRemarksColor(grade.remarks)">
                        {{ grade.remarks }}
                      </span>
                    </td>
                    <td>{{ grade.instructor }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Grade Distribution Chart -->
          <div class="row mt-4">
            <div class="col-md-6">
              <div class="table-container">
                <div class="table-header">
                  <h5 class="table-title">
                    <i class="bi bi-pie-chart me-2 text-primary"></i>
                    Grade Distribution
                  </h5>
                </div>
                <div class="row">
                  <div v-for="dist in gradeDistribution" :key="dist.range" class="col-6 mb-3">
                    <div class="card">
                      <div class="card-body text-center">
                        <h3 class="mb-1" :class="'text-' + dist.color">{{ dist.count }}</h3>
                        <h6 class="text-muted">{{ dist.range }}</h6>
                        <div class="progress mt-2" style="height: 8px;">
                          <div class="progress-bar" :class="'bg-' + dist.color" 
                               :style="{ width: dist.percentage + '%' }"></div>
                        </div>
                        <small class="text-muted">{{ dist.percentage }}%</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="table-container">
                <div class="table-header">
                  <h5 class="table-title">
                    <i class="bi bi-graph-up me-2 text-primary"></i>
                    Academic Summary
                  </h5>
                </div>
                <table class="table">
                  <tr>
                    <th>Overall GPA:</th>
                    <td><span class="badge bg-primary fs-6">{{ summary.overall_gpa }}</span></td>
                  </tr>
                  <tr>
                    <th>Total Units Taken:</th>
                    <td>{{ summary.total_units }}</td>
                  </tr>
                  <tr>
                    <th>Total Courses:</th>
                    <td>{{ summary.total_courses }}</td>
                  </tr>
                  <tr>
                    <th>Completed Courses:</th>
                    <td>{{ summary.completed_courses }}</td>
                  </tr>
                  <tr>
                    <th>Failed Courses:</th>
                    <td>{{ summary.failed_courses }}</td>
                  </tr>
                  <tr>
                    <th>Incomplete Courses:</th>
                    <td>{{ summary.incomplete_courses }}</td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import StudentSidebar from '@/components/layout/StudentSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'StudentGrades',
  components: {
    StudentSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const loading = ref(true)
    const error = ref(null)
    
    const gradesData = ref({
      summary: {},
      grades_by_semester: [],
      grade_distribution: []
    })

    const filters = ref({
      year: '',
      semester: '',
      course: ''
    })

    const availableYears = ref([])
    const availableCourses = ref([])

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const gpaStats = computed(() => [
      {
        label: 'Current GPA',
        value: summary.value.overall_gpa,
        subtext: `${summary.value.total_units} units`,
        icon: 'bi bi-star-fill',
        color: 'primary'
      },
      {
        label: 'Total Credits',
        value: summary.value.total_units,
        subtext: `${summary.value.total_courses} courses`,
        icon: 'bi bi-book-fill',
        color: 'success'
      },
      {
        label: 'Completion Rate',
        value: summary.value.total_courses ? 
          Math.round((summary.value.completed_courses / summary.value.total_courses) * 100) + '%' : '0%',
        subtext: `${summary.value.completed_courses} passed`,
        icon: 'bi bi-check-circle-fill',
        color: 'info'
      },
      {
        label: 'Academic Standing',
        value: getAcademicStanding(),
        subtext: summary.value.failed_courses ? `${summary.value.failed_courses} failed` : 'Good standing',
        icon: 'bi bi-trophy-fill',
        color: getStandingColor()
      }
    ])

    const summary = computed(() => gradesData.value.summary || {})
    const gradesBySemester = computed(() => gradesData.value.grades_by_semester || [])
    const gradeDistribution = computed(() => gradesData.value.grade_distribution || [])

    const fetchGradesData = async () => {
      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        if (!token) {
          router.push('/student/login')
          return
        }

        const params = new URLSearchParams()
        if (filters.value.year) params.append('year', filters.value.year)
        if (filters.value.semester) params.append('semester', filters.value.semester)
        if (filters.value.course) params.append('course', filters.value.course)

        const response = await axios.get(`${API_URL}/student/grades.php?${params.toString()}`, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.data.success) {
          gradesData.value = response.data
          
          // Extract available filters
          if (response.data.available_filters) {
            availableYears.value = [...new Set(response.data.available_filters.map(f => f.academic_year))]
            availableCourses.value = [...new Set(response.data.available_filters.map(f => f.course_code))]
          }
        } else {
          throw new Error(response.data.message || 'Failed to load grades')
        }
      } catch (err) {
        console.error('Grades error:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to load grades data'
        
        if (err.response?.status === 401) {
          await store.dispatch('auth/logout')
          router.push('/student/login')
        }
      } finally {
        loading.value = false
      }
    }

    const getAcademicStanding = () => {
      const gpa = parseFloat(summary.value.overall_gpa || '0')
      if (gpa <= 1.5) return 'Dean\'s List'
      if (gpa <= 2.0) return 'Good Standing'
      if (gpa <= 2.5) return 'Satisfactory'
      if (gpa <= 3.0) return 'Probation'
      return 'At Risk'
    }

    const getStandingColor = () => {
      const standing = getAcademicStanding()
      if (standing === 'Dean\'s List') return 'warning'
      if (standing === 'Good Standing') return 'success'
      if (standing === 'Satisfactory') return 'info'
      if (standing === 'Probation') return 'warning'
      return 'danger'
    }

    const getGradeBadgeClass = (grade) => {
      if (!grade || grade === '—') return 'badge bg-secondary'
      return 'badge bg-light text-dark'
    }

    const getGradeColor = (grade) => {
      if (!grade || grade === '—') return 'secondary'
      const numGrade = parseFloat(grade)
      if (numGrade <= 1.5) return 'success'
      if (numGrade <= 2.0) return 'info'
      if (numGrade <= 2.5) return 'warning'
      if (numGrade <= 3.0) return 'primary'
      return 'danger'
    }

    const getRemarksColor = (remarks) => {
      const colors = {
        'Passed': 'success',
        'Failed': 'danger',
        'Incomplete': 'warning',
        'Dropped': 'secondary'
      }
      return colors[remarks] || 'secondary'
    }

    const applyFilters = () => {
      fetchGradesData()
      Swal.fire({
        icon: 'success',
        title: 'Filters Applied',
        text: 'Grades have been filtered successfully.',
        timer: 1500,
        showConfirmButton: false
      })
    }

    onMounted(() => {
      fetchGradesData()
    })

    return {
      loading,
      error,
      filters,
      availableYears,
      availableCourses,
      gpaStats,
      summary,
      gradesBySemester,
      gradeDistribution,
      fetchGradesData,
      applyFilters,
      getGradeBadgeClass,
      getGradeColor,
      getRemarksColor
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
  position: relative;
  width: 100%;
}

.main-content {
  flex: 1;
  margin-left: 280px; /* Same as sidebar width */
  width: calc(100% - 280px);
  min-height: 100vh;
  padding: 20px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
}

/* When sidebar is collapsed */
:deep(.sidebar.sidebar-collapsed) ~ .main-content {
  margin-left: 80px;
  width: calc(100% - 80px);
}

/* Mobile responsive */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }
}

/* Container adjustments */
.container-fluid {
  padding-left: 0;
  padding-right: 0;
  max-width: 100%;
}

/* Row adjustments */
.row {
  margin-left: 0;
  margin-right: 0;
}

/* ===== YOUR EXISTING COMPONENT STYLES ===== */
/* Keep all your existing styles below this line */
/* For example: */
.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  transition: all 0.3s;
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 15px;
}

.stat-value {
  font-size: 2rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: #2c3e50;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 500;
}

.table-container {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  height: 100%;
  margin-bottom: 20px;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.table-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
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
}

.table td {
  vertical-align: middle;
  font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 768px) {
  .stat-value {
    font-size: 1.5rem;
  }
  
  .table-container {
    padding: 15px;
  }
  
  .table th, .table td {
    font-size: 0.85rem;
  }
}

/* Animations */
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

.animate__animated {
  animation-duration: 0.5s;
}

/* Grades-specific styles */
.bg-primary-light {
  background-color: rgba(52, 152, 219, 0.1);
}

.bg-success-light {
  background-color: rgba(46, 204, 113, 0.1);
}

.bg-info-light {
  background-color: rgba(52, 152, 219, 0.1);
}

.bg-warning-light {
  background-color: rgba(241, 196, 15, 0.1);
}

.stat-card {
  border-left: 4px solid transparent;
}

/* Filter panel */
.filter-panel {
  border: 1px solid #dee2e6;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>