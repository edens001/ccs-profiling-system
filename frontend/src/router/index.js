import { createRouter, createWebHistory } from 'vue-router'
import store from '@/store'
import Swal from 'sweetalert2'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/Home.vue'),
    meta: { title: 'Home' }
  },
  
  // Admin Routes
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/views/admin/AdminLogin.vue'),
    meta: { guest: true, title: 'Admin Login' }
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('@/views/admin/AdminDashboard.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Admin Dashboard' }
  },
  
  // Admin Student Routes
  {
    path: '/admin/students',
    name: 'StudentList',
    component: () => import('@/views/admin/students/StudentList.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Student List' }
  },
  {
    path: '/admin/students/add',
    name: 'StudentAdd',
    component: () => import('@/views/admin/students/StudentAdd.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Add Student' }
  },
  {
    path: '/admin/students/edit/:id',
    name: 'StudentEdit',
    component: () => import('@/views/admin/students/StudentEdit.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Edit Student' }
  },
  {
    path: '/admin/students/view/:id',
    name: 'StudentView',
    component: () => import('@/views/admin/students/StudentView.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'View Student' }
  },
  
  // Admin Faculty Routes
  {
    path: '/admin/faculty',
    name: 'FacultyList',
    component: () => import('@/views/admin/faculty/FacultyList.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Faculty List' }
  },
  {
    path: '/admin/faculty/add',
    name: 'FacultyAdd',
    component: () => import('@/views/admin/faculty/FacultyAdd.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean'], title: 'Add Faculty' }
  },
  {
    path: '/admin/faculty/edit/:id',
    name: 'FacultyEdit',
    component: () => import('@/views/admin/faculty/FacultyEdit.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean'], title: 'Edit Faculty' }
  },
  {
    path: '/admin/faculty/view/:id',
    name: 'FacultyView',
    component: () => import('@/views/admin/faculty/FacultyView.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'View Faculty' }
  },
  
  // Admin Course Routes
  {
    path: '/admin/courses',
    name: 'CourseList',
    component: () => import('@/views/admin/courses/CourseList.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Courses' }
  },
  {
    path: '/admin/courses/add',
    name: 'CourseAdd',
    component: () => import('@/views/admin/courses/CourseAdd.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean'], title: 'Add Course' }
  },
  {
    path: '/admin/courses/edit/:id',
    name: 'CourseEdit',
    component: () => import('@/views/admin/courses/CourseEdit.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean'], title: 'Edit Course' }
  },
  {
    path: '/admin/courses/view/:id',
    name: 'CourseView',
    component: () => import('@/views/admin/courses/CourseView.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'View Course' }
  },
  
  // ✅ FIXED: Admin Scheduling Routes - Complete and Properly Formatted
  {
    path: '/admin/scheduling',
    name: 'Scheduling',
    component: () => import('@/views/admin/scheduling/Scheduling.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Scheduling' }
  },
  {
    path: '/admin/scheduling/sections',
    name: 'Sections',
    component: () => import('@/views/admin/scheduling/Sections.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Sections' }
  },
  {
    path: '/admin/scheduling/rooms',
    name: 'Rooms',
    component: () => import('@/views/admin/scheduling/Rooms.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Rooms' }
  },
  {
    path: '/admin/scheduling/faculty-schedule',
    name: 'AdminFacultySchedule',  // Changed name to avoid conflict with faculty route
    component: () => import('@/views/admin/scheduling/FacultySchedule.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Faculty Schedule' }
  },
  
  // Admin Instruction Routes
  {
    path: '/admin/instruction',
    name: 'Instruction',
    component: () => import('@/views/admin/instruction/Instruction.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Instruction' }
  },
  {
    path: '/admin/instruction/syllabus',
    name: 'Syllabus',
    component: () => import('@/views/admin/instruction/Syllabus.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Syllabus' }
  },
  {
    path: '/admin/instruction/syllabus/view/:id',
    name: 'SyllabusView',
    component: () => import('@/views/admin/instruction/SyllabusView.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'View Syllabus' }
  },
  {
    path: '/admin/instruction/syllabus/edit/:id',
    name: 'SyllabusEdit',
    component: () => import('@/views/admin/instruction/SyllabusEdit.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Edit Syllabus' }
  },
  {
    path: '/admin/instruction/lessons',
    name: 'Lessons',
    component: () => import('@/views/admin/instruction/Lessons.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Lessons' }
  },
  {
    path: '/admin/instruction/lessons/view/:id',
    name: 'LessonView',
    component: () => import('@/views/admin/instruction/LessonView.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'View Lesson' }
  },
  {
    path: '/admin/instruction/lessons/edit/:id',
    name: 'LessonEdit',
    component: () => import('@/views/admin/instruction/LessonEdit.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Edit Lesson' }
  },
  {
    path: '/admin/instruction/curriculum',
    name: 'Curriculum',
    component: () => import('@/views/admin/instruction/Curriculum.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Curriculum' }
  },
  {
    path: '/admin/instruction/curriculum/view/:id',
    name: 'CurriculumView',
    component: () => import('@/views/admin/instruction/CurriculumView.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'View Curriculum' }
  },
  {
    path: '/admin/instruction/curriculum/edit/:id',
    name: 'CurriculumEdit',
    component: () => import('@/views/admin/instruction/CurriculumEdit.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair'], title: 'Edit Curriculum' }
  },
  
  // Admin Events Routes
  {
    path: '/admin/events',
    name: 'Events',
    component: () => import('@/views/admin/events/Events.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Events' }
  },
  {
    path: '/admin/events/curricular',
    name: 'CurricularEvents',
    component: () => import('@/views/admin/events/Curricular.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Curricular Events' }
  },
  {
    path: '/admin/events/extracurricular',
    name: 'ExtracurricularEvents',
    component: () => import('@/views/admin/events/Extracurricular.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Extracurricular Events' }
  },
  {
    path: '/admin/events/add',
    name: 'EventAdd',
    component: () => import('@/views/admin/events/EventAdd.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Add Event' }
  },
  {
    path: '/admin/events/edit/:id',
    name: 'EventEdit',
    component: () => import('@/views/admin/events/EventEdit.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Edit Event' }
  },
  {
    path: '/admin/events/view/:id',
    name: 'EventView',
    component: () => import('@/views/admin/events/EventView.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'View Event' }
  },
  
  // Admin Search Route
  {
    path: '/admin/search',
    name: 'AdminSearch',
    component: () => import('@/views/admin/search/Search.vue'),
    meta: { requiresAuth: true, role: ['admin', 'dean', 'dept_chair', 'secretary'], title: 'Search' }
  },
  
  // Faculty Routes (for faculty users)
  {
    path: '/faculty/login',
    name: 'FacultyLogin',
    component: () => import('@/views/faculty/FacultyLogin.vue'),
    meta: { guest: true, title: 'Faculty Login' }
  },
  {
    path: '/faculty/dashboard',
    name: 'FacultyDashboard',
    component: () => import('@/views/faculty/FacultyDashboard.vue'),
    meta: { requiresAuth: true, role: ['faculty'], title: 'Faculty Dashboard' }
  },
  {
    path: '/faculty/schedule',
    name: 'FacultySchedule',
    component: () => import('@/views/faculty/FacultySchedule.vue'),
    meta: { requiresAuth: true, role: ['faculty'], title: 'Faculty Schedule' }
  },
  {
    path: '/faculty/students',
    name: 'FacultyStudents',
    component: () => import('@/views/faculty/FacultyStudents.vue'),
    meta: { requiresAuth: true, role: ['faculty'], title: 'My Students' }
  },
  {
    path: '/faculty/classes',
    name: 'FacultyClasses',
    component: () => import('@/views/faculty/FacultyClasses.vue'),
    meta: { requiresAuth: true, role: ['faculty'], title: 'My Classes' }
  },
  {
    path: '/faculty/profile',
    name: 'FacultyProfile',
    component: () => import('@/views/faculty/FacultyProfile.vue'),
    meta: { requiresAuth: true, role: ['faculty'], title: 'My Profile' }
  },
  
  // Student Routes
  {
    path: '/student/login',
    name: 'StudentLogin',
    component: () => import('@/views/student/StudentLogin.vue'),
    meta: { guest: true, title: 'Student Login' }
  },
  {
    path: '/student/register',
    name: 'StudentRegister',
    component: () => import('@/views/student/StudentRegister.vue'),
    meta: { guest: true, title: 'Student Registration' }
  },
  {
    path: '/student/dashboard',
    name: 'StudentDashboard',
    component: () => import('@/views/student/StudentDashboard.vue'),
    meta: { requiresAuth: true, role: ['student'], title: 'Student Dashboard' }
  },
  {
    path: '/student/schedule',
    name: 'StudentSchedule',
    component: () => import('@/views/student/StudentSchedule.vue'),
    meta: { requiresAuth: true, role: ['student'], title: 'Class Schedule' }
  },
  {
    path: '/student/grades',
    name: 'StudentGrades',
    component: () => import('@/views/student/StudentGrades.vue'),
    meta: { requiresAuth: true, role: ['student'], title: 'My Grades' }
  },
  {
    path: '/student/profile',
    name: 'StudentProfile',
    component: () => import('@/views/student/StudentProfile.vue'),
    meta: { requiresAuth: true, role: ['student'], title: 'My Profile' }
  },
  
  // Catch all route for 404
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue'),
    meta: { title: '404 - Page Not Found' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const isAuthenticated = store.getters['auth/isAuthenticated']
  const userRole = store.getters['auth/userRole']
  
  // Set page title
  document.title = to.meta.title ? `${to.meta.title} | CCS Profiling System` : 'CCS Profiling System'
  
  console.log('Navigation to:', to.path)
  console.log('Auth status:', isAuthenticated)
  console.log('User role:', userRole)
  
  // Check if token exists but user data is corrupted
  if (isAuthenticated && !userRole) {
    console.log('Token exists but user role is missing - clearing auth')
    store.dispatch('auth/logout')
    return next(to.path) // Retry navigation after logout
  }
  
  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      console.log('Not authenticated, redirecting to login')
      // Redirect to appropriate login page based on route
      if (to.path.includes('/admin')) {
        next('/admin/login')
      } else if (to.path.includes('/faculty')) {
        next('/faculty/login')
      } else if (to.path.includes('/student')) {
        next('/student/login')
      } else {
        next('/')
      }
    } else if (to.meta.role && !to.meta.role.includes(userRole)) {
      console.log('Insufficient permissions, redirecting to home')
      // User doesn't have required role
      Swal.fire({
        icon: 'error',
        title: 'Access Denied',
        text: 'You do not have permission to access this page.',
        timer: 2000,
        showConfirmButton: false
      })
      next('/')
    } else {
      console.log('Navigation allowed to protected route')
      next()
    }
  } else if (to.meta.guest) {
    if (isAuthenticated && userRole) {
      console.log('Authenticated user on guest page, redirecting to dashboard')
      // Redirect authenticated users away from guest pages
      if (userRole === 'admin' || userRole === 'dean' || userRole === 'dept_chair' || userRole === 'secretary') {
        next('/admin/dashboard')
      } else if (userRole === 'faculty') {
        next('/faculty/dashboard')
      } else if (userRole === 'student') {
        next('/student/dashboard')
      } else {
        next('/')
      }
    } else {
      console.log('Guest navigation allowed')
      next()
    }
  } else {
    console.log('Navigation allowed')
    next()
  }
})

export default router