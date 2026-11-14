<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../services/api'
import { formatGalleryDate as formatDate } from '../utils/date'
const route=useRoute(), albums=ref([]), categories=ref([]), search=ref(typeof route.query.search==='string'?route.query.search:''), category=ref(''), page=ref(1), last=ref(1), total=ref(0), loading=ref(true), error=ref('')
const slideAlbums=ref([]), slideIndex=ref(0)
const currentSlide=computed(()=>slideAlbums.value[slideIndex.value] || null)
const previousSlide=computed(()=>slideAlbums.value.length>1?slideAlbums.value[(slideIndex.value+slideAlbums.value.length-1)%slideAlbums.value.length]:null)
const nextSlide=computed(()=>slideAlbums.value.length>2?slideAlbums.value[(slideIndex.value+1)%slideAlbums.value.length]:null)
const coverUrl=album=>album?.cover_url || album?.cover?.thumbnail_url || album?.preview_photo?.thumbnail_url || null
let timer,slideTimer,requestId=0
async function load(){
 const id=++requestId;loading.value=true;error.value=''
 try {const result=await api.get('/albums',{params:{root_only:1,search:search.value,category:category.value,page:page.value}});if(id!==requestId)return;albums.value=result.data.data;last.value=result.data.last_page;total.value=result.data.total}
 catch{if(id===requestId)error.value='Album belum dapat dimuat. Periksa koneksi ke server.'}
 finally{if(id===requestId)loading.value=false}
}
watch(()=>route.query.search,value=>{search.value=typeof value==='string'?value:'';category.value=''})
watch([search,category],()=>{clearTimeout(timer);page.value=1;timer=setTimeout(load,250)})
function changePage(value){page.value=value;load();document.getElementById('koleksi')?.scrollIntoView({behavior:'smooth'})}
onMounted(()=>{load();api.get('/albums',{params:{root_only:1}}).then(r=>{slideAlbums.value=r.data.data.filter(a=>coverUrl(a))}).catch(()=>{});slideTimer=window.setInterval(()=>{if(!document.hidden && slideAlbums.value.length>1)slideIndex.value=(slideIndex.value+1)%slideAlbums.value.length},5000);api.get('/categories').then(r=>categories.value=r.data).catch(()=>{})})
onUnmounted(()=>{clearTimeout(timer);clearInterval(slideTimer)})
</script>
<template>
 <main class="gallery-page wrap">
  <section class="jakarta-intro" aria-labelledby="gallery-title">
   <div class="intro-copy"><div class="intro-kicker"><span></span> DOKUMENTASI WILAYAH</div><h1 id="gallery-title">Kelurahan<br><em>Grogol Selatan.</em></h1><p>Grogol Selatan merupakan kelurahan di Kecamatan Kebayoran Lama, Kota Administrasi Jakarta Selatan. Galery Kegiatan Kelurahan Grogol Selatan.</p><div class="intro-facts"><span>Kebayoran Lama</span><span>Dokumentasi kegiatan</span><span>Unduh foto asli</span></div></div>
   <div class="intro-art" aria-hidden="true"><div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div><div class="art-panel"><div class="art-panel-top"><span><i></i> KOLEKSI ALBUM</span><span>JAKARTA / ID</span></div><div class="art-image" v-if="currentSlide"><Transition name="slide-fade"><img :key="currentSlide.id" :src="coverUrl(currentSlide)" alt=""></Transition><div class="art-image-caption"><span>ARSIP VISUAL</span><strong>{{currentSlide.title}}</strong></div></div><div class="art-image art-empty" v-else><span>GALERI GROGOL SELATAN</span></div><div class="art-panel-bottom"><span>FOTO &amp; DOKUMENTASI</span><span>{{String(slideAlbums.length ? slideIndex+1 : 0).padStart(2,'0')}} — {{String(slideAlbums.length).padStart(2,'0')}}</span></div></div><div class="art-mini art-mini-one" v-if="previousSlide"><img :src="coverUrl(previousSlide)" alt=""></div><div class="art-mini art-mini-two" v-if="nextSlide"><img :src="coverUrl(nextSlide)" alt=""></div><svg class="monas-ornament" viewBox="0 0 120 230" fill="none"><path d="M60 7c-10 16 6 17-1 33 13-7 12-22 1-33Z" fill="#ffca72"/><path d="M55 42h10l4 119H51L55 42Z" fill="#f3eee6"/><path d="M35 157h50l-5 13H40l-5-13Zm7 13h36v13H42v-13ZM27 183h66v11H27v-11Zm-7 11h80v10H20v-10Z" fill="#f3eee6"/><path d="M10 210h100" stroke="#ffca72" stroke-width="2"/></svg><div class="art-label">MONAS <span> / JAKARTA</span></div></div>
  </section>
  <div class="balang-border" aria-hidden="true"></div>
  <section class="collection-heading" id="koleksi"><div><div class="intro-kicker"><span></span> KOLEKSI PUBLIK</div><h2>Album Foto</h2><p>Temukan album berdasarkan nama atau kategori. Buka album untuk melihat fotonya.</p></div><div v-if="!loading && !error" class="photo-count-card" aria-live="polite"><span class="photo-count-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.6"/><circle cx="8.5" cy="9" r="1.5" stroke="currentColor" stroke-width="1.5"/><path d="m4 17 5-5 3 3 3-4 5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="photo-count-copy"><strong>{{total}}</strong><span>{{search || category ? 'Album ditemukan' : 'Album tersedia'}}</span></span></div></section>
  <nav class="collection-links" aria-label="Jelajahi koleksi">
   <router-link class="collection-link" to="/photos"><span class="collection-link-icon" aria-hidden="true">▧</span><span><strong>Lihat Semua Foto</strong><small>Jelajahi seluruh foto yang dipublikasikan</small></span><b aria-hidden="true">↗</b></router-link>
   <router-link class="collection-link" to="/albums"><span class="collection-link-icon" aria-hidden="true">▤</span><span><strong>Lihat Album</strong><small>Temukan dokumentasi berdasarkan album</small></span><b aria-hidden="true">↗</b></router-link>
  </nav>
  <div class="gallery-toolbar"><div class="gallery-filters"><label class="search-field"><span aria-hidden="true">⌕</span><input v-model="search" aria-label="Cari album" placeholder="Cari nama album..."></label><select v-model="category" aria-label="Filter kategori"><option value="">Semua kategori</option><option v-for="c in categories" :key="c.id" :value="c.slug">{{c.name}}</option></select></div><span v-if="!loading && !error" class="gallery-hint">{{total}} album ditemukan</span></div>
  <div v-if="loading" class="notice">Memuat album...</div><div v-else-if="error" class="notice">{{error}} <button @click="load">Coba lagi</button></div><div v-else-if="!albums.length" class="notice">Tidak ada album yang cocok. Coba kata kunci atau kategori lain.</div>
  <div v-else class="gallery-grid"><router-link v-for="album in albums" :key="album.id" class="gallery-tile album-tile" :to="'/albums/'+album.slug"><img v-if="coverUrl(album)" :src="coverUrl(album)" :alt="'Sampul '+album.title" loading="lazy"><span v-else class="album-cover-placeholder">Tidak ada foto sampul</span><span class="tile-overlay"><span><small>{{album.category?.name || 'Dokumentasi'}}<template v-if="formatDate(album.event_date)"> · {{formatDate(album.event_date)}}</template></small><strong>{{album.title}}</strong><span class="tile-filename">{{album.photos_count}} foto<span v-if="album.children_count"> · {{album.children_count}} subalbum</span></span></span><b aria-hidden="true">↗</b></span></router-link></div>
  <div v-if="last>1" class="pagination"><button :disabled="page<=1" @click="changePage(page-1)">← Sebelumnya</button><span>{{page}} / {{last}}</span><button :disabled="page>=last" @click="changePage(page+1)">Berikutnya →</button></div>
 </main>
</template>
