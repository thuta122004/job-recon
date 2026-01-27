import { createRouter, createWebHistory } from 'vue-router'
import RolesView from '@/views/RolesView.vue'
import UsersView from '@/views/UsersView.vue'
import JobSeekerProfilesView from '@/views/JobSeekerProfilesView.vue'
import JobSeekerExperiencesView from '@/views/JobSeekerExperiencesView.vue'
import JobSeekerEducationsView from '@/views/JobSeekerEducationsView.vue'
import JobSeekerSkillsView from '@/views/JobSeekerSkillsView.vue'
import SkillsView from '@/views/SkillsView.vue'
import JobCategoriesView from '../views/JobCategoriesView.vue'
import EmployerProfilesView from '../views/EmployerProfilesView.vue'

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
    },
    {
        path: '/job-seekers/:profileId/experience',
        name: 'JobSeekerExperience',
        component: JobSeekerExperiencesView,
        meta: { 
        parent: 'Talent Directory', 
        label: 'Experiences' 
      }
    },
    {
        path: '/job-seekers/:profileId/education',
        name: 'JobSeekerEducation',
        component: JobSeekerEducationsView,
        meta: { 
        parent: 'Talent Directory', 
        label: 'Educations' 
      }
    },
    {
      path: '/skills',
      name: 'skills',
      component: SkillsView,
        meta: { 
        parent: 'Management', 
        label: 'Skills' 
      }
    },
    {
        path: '/job-seekers/:profileId/skills',
        name: 'JobSeekerSkills',
        component: JobSeekerSkillsView,
        meta: { 
          parent: 'Talent Directory', 
          label: 'Skills' 
        }
    },
    {
        path: '/job-categories',
        name: 'JobCategories',
        component: JobCategoriesView,
        meta: { 
          parent: 'Management', 
          label: 'Job Categories' 
        }
    },
    {
      path: '/employer-profiles',
      name: 'employer-profiles',
      component: EmployerProfilesView,
      meta: { 
        parent: 'Management', 
        label: 'Employers' 
      }
    },
  ]
})

export default router