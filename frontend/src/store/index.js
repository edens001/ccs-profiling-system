import { createStore } from 'vuex'
import auth from './modules/auth'
import admin from './modules/admin'
import faculty from './modules/faculty'
import student from './modules/student'

export default createStore({
  modules: {
    auth,
    admin,
    faculty,
    student
  }
})