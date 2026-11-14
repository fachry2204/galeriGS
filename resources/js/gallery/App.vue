<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from './services/api'
import logoDki from './assets/logo-dki-jakarta.svg'
const settings = ref({}), photoCount = ref(null), albumCount = ref(null), query = ref('')
const route = useRoute(), router = useRouter()
watch(() => route.query.search, value => { query.value = typeof value === 'string' ? value : '' }, { immediate: true })
function searchGallery() {
  const value = query.value.trim()
  router.push({ path: '/', query: value ? { search: value } : {}, hash: '#koleksi' })
}
onMounted(async () => {
  const [site, photos, albums] = await Promise.allSettled([api.get('/settings'), api.get('/photos', { params: { page: 1 } }), api.get('/albums', { params: { page: 1 } })])
  if (site.status === 'fulfilled') settings.value = site.value.data
  if (photos.status === 'fulfilled') photoCount.value = photos.value.data.total
  if (albums.status === 'fulfilled') albumCount.value = albums.value.data.total
})
</script>
<template>
  <header class="site-header">
    <div class="header-info"><div class="header-info-inner"><span class="header-context"><span class="header-dot"></span> ARSIP FOTO JAKARTA</span><div class="header-totals"><span v-if="photoCount !== null">{{ photoCount }} foto</span><span v-if="albumCount !== null">{{ albumCount }} album</span><span class="header-city">Jakarta, Indonesia</span></div></div></div>
    <div class="nav">
      <router-link class="brand" to="/" aria-label="Kelurahan Grogol Selatan - Galeri Foto"><img class="dki-logo" :src="logoDki" alt="Lambang DKI Jakarta"><span class="brand-name"><small>KELURAHAN</small><br>GROGOL SELATAN</span></router-link>
      <nav aria-label="Navigasi utama"><router-link to="/">Galeri Foto</router-link><router-link to="/albums">Album</router-link></nav>
      <form class="header-search" role="search" @submit.prevent="searchGallery"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="10.5" cy="10.5" r="6.5" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><input v-model="query" type="search" aria-label="Cari foto atau album" placeholder="Cari foto atau album"><button type="submit" aria-label="Cari">Cari</button></form>
    </div>
  </header>
  <router-view />
  <footer id="tentang">
    <div><div class="brand footer-brand"><img class="dki-logo" :src="logoDki" alt="Lambang DKI Jakarta"><span class="brand-name"><small>KELURAHAN</small><br>GROGOL SELATAN</span></div><p>{{ settings.description || 'Arsip foto kegiatan, tempat, dan kehidupan di Jakarta.' }}</p></div>
    <div><strong>Jelajahi</strong><p><router-link to="/">Galeri foto</router-link></p><p><router-link to="/albums">Album</router-link></p></div>
    <div><strong>Kontak</strong><p v-if="settings.email"><a :href="'mailto:' + settings.email">{{ settings.email }}</a></p><p v-else>Jakarta, Indonesia</p></div>
    <div class="copyright">{{ settings.copyright || `© ${new Date().getFullYear()} Jakarta Photo Gallery` }}</div>
  </footer>
</template>





