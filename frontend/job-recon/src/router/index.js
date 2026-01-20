import { createRouter, createWebHistory } from 'vue-router'
import RolesView from '@/views/RolesView.vue'
import UsersView from '../views/UsersView.vue'
import JobSeekerProfilesView from '../views/JobSeekerProfilesView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/roles',
      name: 'roles',
      component: RolesView,
        meta: { 
        parent: 'Management', 
        label: 'Roles' 
      }
    },
    {
      path: '/users',
      name: 'users',
      component: UsersView,
      meta: { 
        parent: 'Management', 
        label: 'Users' 
      }
    },
    {
      path: '/job-seeker-profiles',
      name: 'job-seeker-profiles',
      component: JobSeekerProfilesView,
      meta: { 
        parent: 'Management', 
        label: 'Job Seekers' 
      }
    }
  ]
})

export default router