import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import Gallery from './pages/Gallery.vue'
import Albums from './pages/Albums.vue'
import Album from './pages/Album.vue'
import Login from './pages/Login.vue'
import Admin from './pages/Admin.vue'
import './style.css'
const router=createRouter({history:createWebHistory(),scrollBehavior(to){if(to.hash)return {el:to.hash,behavior:'smooth'};return {top:0}},routes:[{path:'/',component:Gallery},{path:'/albums',component:Albums},{path:'/albums/:slug',component:Album},{path:'/admin',redirect:'/admin/login'},{path:'/admin/login',component:Login},{path:'/admin/dashboard',component:Admin},{path:'/:pathMatch(.*)*',redirect:to=>({path:to.path.startsWith('/admin/')?'/admin/login':'/',query:to.query,hash:to.hash})}]})
createApp(App).use(createPinia()).use(router).mount('#app')
