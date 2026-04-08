import axios from 'axios'

const API_URL = 'http://localhost/ccs-profiling-system/backend/api/admin'

export default {
  namespaced: true,
  
  state: {
    dashboardData: null,
    students: [],
    faculty: [],
    courses: [],
    schedules: []
  },
  
  mutations: {
    SET_DASHBOARD_DATA(state, data) {
      state.dashboardData = data
    },
    
    SET_STUDENTS(state, students) {
      state.students = students
    },
    
    SET_FACULTY(state, faculty) {
      state.faculty = faculty
    },
    
    SET_COURSES(state, courses) {
      state.courses = courses
    },
    
    SET_SCHEDULES(state, schedules) {
      state.schedules = schedules
    }
  },
  
  actions: {
    async getDashboardData({ commit }) {
      try {
        const response = await axios.get(`${API_URL}/dashboard.php`)
        commit('SET_DASHBOARD_DATA', response.data)
        return response.data
      } catch (error) {
        console.error('Dashboard data fetch error:', error)
        throw error
      }
    },
    
    async getStudents({ commit }, params = {}) {
      try {
        const response = await axios.get(`${API_URL}/students/`, { params })
        commit('SET_STUDENTS', response.data)
        return response.data
      } catch (error) {
        console.error('Students fetch error:', error)
        throw error
      }
    },
    
    async createStudent({ dispatch }, studentData) {
      try {
        const response = await axios.post(`${API_URL}/students/`, studentData)
        await dispatch('getStudents')
        return response.data
      } catch (error) {
        console.error('Student creation error:', error)
        throw error
      }
    },
    
    async updateStudent({ dispatch }, { id, data }) {
      try {
        const response = await axios.put(`${API_URL}/students/update.php?id=${id}`, data)
        await dispatch('getStudents')
        return response.data
      } catch (error) {
        console.error('Student update error:', error)
        throw error
      }
    },
    
    async deleteStudent({ dispatch }, id) {
      try {
        const response = await axios.delete(`${API_URL}/students/delete.php?id=${id}`)
        await dispatch('getStudents')
        return response.data
      } catch (error) {
        console.error('Student deletion error:', error)
        throw error
      }
    }
  },
  
  getters: {
    dashboardData: state => state.dashboardData,
    students: state => state.students,
    faculty: state => state.faculty,
    courses: state => state.courses,
    schedules: state => state.schedules
  }
}