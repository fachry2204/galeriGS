<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { api } from '../services/api'
import { formatGalleryDate as formatDate } from '../utils/date'
const photos=ref([]), categories=ref([]), search=ref(''), category=ref(''), page=ref(1), last=ref(1), total=ref(0), selected=ref(-1), loading=ref(true), error=ref(''), closeButton=ref(null)
let timer, previousFocus, requestId=0
async function load(){const id=++requestId;loading.value=true;error.value='';try{const result=await api.get('/photos',{params:{search:search.value,category:category.value,page:page.value}});if(id!==requestId)return;photos.value=result.data.data;last.value=result.data.last_page;total.value=result.data.total}catch{if(id===requestId)error.value='Foto belum dapat dimuat. Periksa koneksi ke server.'}finally{if(id===requestId)loading.value=false}}
function openPhoto(index){previousFocus=document.activeElement;selected.value=index;document.body.style.overflow='hidden';nextTick(()=>closeButton.value?.focus())}
function closePhoto(){selected.value=-1;document.body.style.overflow='';nextTick(()=>previousFocus?.focus())}
function move(delta){selected.value=(selected.value+delta+photos.value.length)%photos.value.length}
function keys(e){if(selected.value<0)return;if(e.key==='Escape')closePhoto();else if(e.key==='ArrowRight')move(1);else if(e.key==='ArrowLeft')move(-1)}
watch([search,category],()=>{clearTimeout(timer);page.value=1;timer=setTimeout(load,250)})
function changePage(value){page.value=value;load();window.scrollTo({top:0,behavior:'smooth'})}
onMounted(()=>{load();api.get('/categories').then(r=>categories.value=r.data).catch(()=>{});window.addEventListener('keydown',keys)})
onUnmounted(()=>{clearTimeout(timer);window.removeEventListener('keydown',keys);document.body.style.overflow=''})
</script>
<template>
 <main class="gallery-page photos-page wrap">
  <div class="photos-page-inner"><router-link class="text-link" to="/">← Kembali ke halaman depan</router-link>
   <section class="collection-heading photos-heading"><div><div class="intro-kicker"><span></span> KOLEKSI PUBLIK</div><h1>Semua Foto</h1><p>Cari foto berdasarkan nama file, album, atau kategori.</p></div><div v-if="!loading && !error" class="photo-count-card" aria-live="polite"><span class="photo-count-icon" aria-hidden="true">▧</span><span class="photo-count-copy"><strong>{{total}}</strong><span>{{search || category ? 'Foto ditemukan' : 'Foto tersedia'}}</span></span></div></section>
   <div class="gallery-toolbar"><div class="gallery-filters"><label class="search-field"><span aria-hidden="true">⌕</span><input v-model="search" aria-label="Cari foto atau album" placeholder="Cari foto atau album..."></label><select v-model="category" aria-label="Filter kategori"><option value="">Semua kategori</option><option v-for="c in categories" :key="c.id" :value="c.slug">{{c.name}}</option></select></div><span v-if="!loading && !error" class="gallery-hint">{{total}} foto ditemukan</span></div>
   <p v-if="loading" class="notice">Memuat foto...</p><p v-else-if="error" class="notice">{{error}} <button @click="load">Coba lagi</button></p><p v-else-if="!photos.length" class="notice">Tidak ada foto yang cocok. Coba kata kunci atau kategori lain.</p>
   <div v-else class="gallery-grid"><button v-for="(p,i) in photos" :key="p.id" class="gallery-tile" @click="openPhoto(i)" :aria-label="'Perbesar '+p.original_filename"><img :src="p.thumbnail_url" :alt="p.original_filename" loading="lazy"><span class="tile-overlay"><span><small>{{p.album?.category?.name || 'Dokumentasi'}}<template v-if="formatDate(p.album?.event_date)"> · {{formatDate(p.album.event_date)}}</template></small><strong>{{p.album?.title}}</strong><span class="tile-filename">{{p.original_filename}}</span></span><b aria-hidden="true">↗</b></span></button></div>
   <div v-if="last>1" class="pagination"><button :disabled="page<=1" @click="changePage(page-1)">← Sebelumnya</button><span>{{page}} / {{last}}</span><button :disabled="page>=last" @click="changePage(page+1)">Berikutnya →</button></div>
  </div>
  <div v-if="selected>=0" class="lightbox gallery-modal" role="dialog" aria-modal="true" aria-label="Pratinjau foto" @click.self="closePhoto"><button ref="closeButton" class="close" aria-label="Tutup" @click="closePhoto">×</button><button class="modal-arrow" aria-label="Foto sebelumnya" @click="move(-1)">←</button><figure><img :src="photos[selected].thumbnail_url" :alt="photos[selected].original_filename"><figcaption><span><strong>{{photos[selected].album?.title}}</strong><br>{{photos[selected].original_filename}}<template v-if="photos[selected].width && photos[selected].height"> · {{photos[selected].width}} × {{photos[selected].height}} px</template><template v-if="formatDate(photos[selected].album?.event_date)"> · {{formatDate(photos[selected].album.event_date)}}</template><template v-if="photos[selected].album?.location"> · {{photos[selected].album.location}}</template></span><a class="download-link" :href="api.defaults.baseURL+'/photos/'+photos[selected].id+'/download'">↓ &nbsp; Unduh foto</a></figcaption></figure><button class="modal-arrow" aria-label="Foto berikutnya" @click="move(1)">→</button></div>
 </main>
</template>
