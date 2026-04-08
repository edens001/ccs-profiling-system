<template>
  <div class="app-wrapper">
    <AdminSidebar />
    
    <div class="main-content">
      <TopNav :pageTitle="'Global Search'" />
      
      <div class="container-fluid">
        <div class="content-card">
          <div class="content-header">
            <div class="d-flex align-items-center">
              <div class="header-icon">
                <i class="bi bi-search"></i>
              </div>
              <div>
                <h2 class="mb-1">Global Search</h2>
                <p class="text-muted mb-0">Search across all modules</p>
              </div>
            </div>
          </div>

          <!-- Search Section -->
          <div class="search-section">
            <div class="input-group input-group-lg">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search for students, faculty, courses, events..." 
                v-model="searchQuery"
                @keyup.enter="performSearch"
                :disabled="loading"
              >
              <button class="btn btn-primary" @click="performSearch" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-search me-2"></i>
                {{ loading ? 'Searching...' : 'Search' }}
              </button>
            </div>
            
            <!-- Filters -->
            <div class="filters-section mt-3">
              <select class="form-select form-select-sm" v-model="typeFilter" style="width: auto;">
                <option value="all">All Types</option>
                <option value="student">Students</option>
                <option value="faculty">Faculty</option>
                <option value="course">Courses</option>
                <option value="event">Events</option>
                <option value="section">Sections</option>
                <option value="syllabus">Syllabi</option>
              </select>
            </div>
          </div>

          <!-- Error State -->
          <div v-if="error" class="alert alert-danger mt-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ error }}
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2">Searching "{{ searchQuery }}"...</p>
          </div>

          <!-- Results Section -->
          <div v-else-if="searchPerformed" class="results-section mt-4">
            <div class="results-header d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Search Results <span class="badge bg-primary ms-2">{{ totalResults }}</span></h5>
              <small class="text-muted">Found {{ totalResults }} results for "{{ searchQuery }}"</small>
            </div>
            
            <div v-if="results.length === 0" class="text-center py-5">
              <i class="bi bi-inbox fs-1 text-muted"></i>
              <p class="text-muted mt-2">No results found for "{{ searchQuery }}"</p>
            </div>

            <div v-else>
              <div v-for="result in results" :key="result.type + '-' + result.id" class="result-item">
                <div class="result-icon" :class="'bg-' + result.type">
                  <i :class="getIcon(result.type)"></i>
                </div>
                <div class="result-content">
                  <h6>{{ result.title }}</h6>
                  <p class="text-muted small mb-1">{{ result.subtitle }}</p>
                  <span class="badge" :class="'bg-' + result.type">{{ result.badge || result.type }}</span>
                </div>
                <button class="btn btn-sm btn-outline-primary" @click="viewResult(result)">
                  <i class="bi bi-box-arrow-up-right me-1"></i>
                  View
                </button>
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
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import AdminSidebar from '@/components/layout/AdminSidebar.vue'
import TopNav from '@/components/layout/TopNav.vue'
import Swal from 'sweetalert2'

export default {
  name: 'Search',
  components: {
    AdminSidebar,
    TopNav
  },
  setup() {
    const store = useStore()
    const router = useRouter()
    const searchQuery = ref('')
    const typeFilter = ref('all')
    const searchPerformed = ref(false)
    const loading = ref(false)
    const error = ref(null)
    const results = ref([])
    const totalResults = ref(0)

    const API_URL = 'http://localhost/ccs-profiling-system/backend/api'

    const performSearch = async () => {
      if (!searchQuery.value.trim()) {
        Swal.fire({
          icon: 'warning',
          title: 'Empty Search',
          text: 'Please enter a search term'
        })
        return
      }

      loading.value = true
      error.value = null
      
      try {
        const token = store.state.auth.token
        const response = await axios.get(`${API_URL}/admin/search.php`, {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            q: searchQuery.value,
            type: typeFilter.value,
            limit: 50
          }
        })

        if (response.data.success) {
          results.value = response.data.results
          totalResults.value = response.data.total
          searchPerformed.value = true
        } else {
          throw new Error(response.data.message)
        }
      } catch (err) {
        console.error('Search error:', err)
        error.value = err.response?.data?.message || err.message || 'Failed to perform search'
        
        if (err.response?.status === 401) {
          await store.dispatch('auth/logout')
          router.push('/admin/login')
        }
      } finally {
        loading.value = false
      }
    }

    const getIcon = (type) => {
      const icons = {
        'student': 'bi bi-person',
        'faculty': 'bi bi-person-badge',
        'course': 'bi bi-book',
        'event': 'bi bi-calendar-event',
        'section': 'bi bi-columns-gap',
        'syllabus': 'bi bi-file-text'
      }
      return icons[type] || 'bi bi-search'
    }

    const viewResult = (result) => {
      if (result.url) {
        router.push(result.url)
      } else {
        // Fallback for results without URL
        Swal.fire({
          icon: 'info',
          title: result.title,
          text: result.subtitle,
          confirmButtonText: 'OK'
        })
      }
    }

    return {
      searchQuery,
      typeFilter,
      searchPerformed,
      loading,
      error,
      results,
      totalResults,
      performSearch,
      getIcon,
      viewResult
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
  max-width:1800px;
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

.search-section {
  margin-bottom: 20px;
}

.input-group-lg .form-control {
  padding: 15px;
  font-size: 1rem;
}

.filters-section {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.results-header h5 {
  display: flex;
  align-items: center;
  gap: 10px;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  margin-bottom: 10px;
  transition: all 0.3s;
  animation: slideIn 0.3s ease;
}

.result-item:hover {
  background: #f8f9fa;
  transform: translateX(5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.result-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.bg-student {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.bg-faculty {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.bg-course {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.bg-event {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.bg-section {
  background: linear-gradient(135deg, #9b59b6, #8e44ad);
}

.bg-syllabus {
  background: linear-gradient(135deg, #1abc9c, #16a085);
}

.result-content {
  flex: 1;
  min-width: 0;
}

.result-content h6 {
  margin-bottom: 3px;
  font-weight: 600;
  color: #2c3e50;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.result-content p {
  margin-bottom: 5px;
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.result-content .badge {
  text-transform: capitalize;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 15px;
  }

  .result-item {
    flex-direction: column;
    text-align: center;
    align-items: center;
  }

  .result-content {
    text-align: center;
  }

  .result-content h6 {
    white-space: normal;
  }

  .result-content p {
    white-space: normal;
  }
}
</style>