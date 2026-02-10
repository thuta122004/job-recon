import { createRouter, createWebHistory } from 'vue-router'
import RolesView from '@/views/RolesView.vue'
import UsersView from '@/views/UsersView.vue'
import JobSeekerProfilesView from '@/views/JobSeekerProfilesView.vue'
import JobSeekerExperiencesView from '@/views/JobSeekerExperiencesView.vue'
import JobSeekerEducationsView from '@/views/JobSeekerEducationsView.vue'
import JobSeekerSkillsView from '@/views/JobSeekerSkillsView.vue'
import SkillsView from '@/views/SkillsView.vue'
import JobCategoriesView from '@/views/JobCategoriesView.vue'
import EmployerProfilesView from '@/views/EmployerProfilesView.vue'
import EmployerJobPostsView from '@/views/EmployerJobPostsView.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminDashboardView from '@/views/AdminDashboardView.vue'
import LoginView from '@/views/LoginView.vue'
import JobSeekerHomeView from '@/views/JobSeekerHomeView.vue'
import JobDetailView from '@/views/JobDetailView.vue'
import JobPostsView from '@/views/JobPostsView.vue'
import SeekerLayout from '@/layouts/SeekerLayout.vue'
import SeekerProfile from '@/views/SeekerProfile.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: { name: 'login' }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/seeker',
      component: SeekerLayout,
      children: [
        {
          path: 'home',
          name: 'seeker-home',
          component: JobSeekerHomeView,
          meta: { requiresAuth: false } 
        },
        {
          path: 'jobs',
          name: 'job-listings',
          component: JobPostsView,
          meta: { requiresAuth: false }
        },
        {
          path: 'job/:slug',
          name: 'job-detail',
          component: JobDetailView,
          props: true,
          meta: { requiresAuth: true, role: 2, label: 'Job Details' }
        },
        {
          path: 'profile',
          name: 'seeker-profile',
          component: SeekerProfile,
          meta: { requiresAuth: true, role: 2, label: 'My Profile' }
        },
        {
          path: 'education',
          name: 'seeker-education',
          component: JobSeekerEducationsView,
          meta: { requiresAuth: true, role: 2, label: 'My Education' }
        },
        {
          path: 'experience',
          name: 'seeker-experience',
          component: JobSeekerExperiencesView,
          meta: { requiresAuth: true, role: 2, label: 'My Experience' }
        },
      ]
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, role: 1 },
      redirect: { name: 'admin-dashboard' },
      children: [
        {
          path: 'dashboard',
          name: 'admin-dashboard',
          component: AdminDashboardView,
          meta: { parent: 'System Management', label: 'Admin Dashboard' }
        },
        {
          path: 'roles',
          name: 'roles',
          component: RolesView,
          meta: { parent: 'System Management', label: 'Access Roles' }
        },
        {
          path: 'users',
          name: 'users',
          component: UsersView,
          meta: { parent: 'System Management', label: 'User Accounts' }
        },
        {
          path: 'job-seeker-profiles',
          name: 'job-seeker-profiles',
          component: JobSeekerProfilesView,
          meta: { parent: 'Talent Management', label: 'Talent Profiles' }
        },
        {
          path: 'job-seekers/:profileId/experience',
          name: 'job-seeker-experience',
          component: JobSeekerExperiencesView,
          meta: { parent: 'Talent Directory', label: 'Work Experience' }
        },
        {
          path: 'job-seekers/:profileId/education',
          name: 'job-seeker-education',
          component: JobSeekerEducationsView,
          meta: { parent: 'Talent Directory', label: 'Education History' }
        },
        {
          path: 'skills',
          name: 'skills',
          component: SkillsView,
          meta: { parent: 'System Management', label: 'Competency Library' }
        },
        {
          path: 'job-seekers/:profileId/skills',
          name: 'job-seeker-skills',
          component: JobSeekerSkillsView,
          meta: { parent: 'Talent Directory', label: 'Acquired Skills' }
        },
        {
          path: 'job-categories',
          name: 'job-categories',
          component: JobCategoriesView,
          meta: { parent: 'System Management', label: 'Job Categories' }
        },
        {
          path: 'employer-profiles',
          name: 'employer-profiles',
          component: EmployerProfilesView,
          meta: { parent: 'Employer Management', label: 'Employer Profiles' }
        },
        {
          path: 'employer-profiles/:profileId/job-posts',
          name: 'employer-job-posts',
          component: EmployerJobPostsView,
          props: true,
          meta: { parent: 'Employer Management', label: 'Active Job Postings' }
        },
      ]
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    }
  ]
})
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token');
  const userRole = parseInt(localStorage.getItem('user_role'));

  if (to.meta.requiresAuth && !token) {
    return to.name === 'login' ? next() : next({ name: 'login' });
  }

  if (to.meta.role && userRole !== to.meta.role) {
    console.warn("Unauthorized Role Access");
    return next(userRole === 1 ? { name: 'admin-dashboard' } : { name: 'seeker-home' });
  }

  if (to.name === 'login' && token) {
    return next(userRole === 1 ? { name: 'admin-dashboard' } : { name: 'seeker-home' });
  }

  next();
});
export default router