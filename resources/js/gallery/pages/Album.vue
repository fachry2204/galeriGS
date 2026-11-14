<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../services/api'
const route=useRoute(), album=ref(null), photos=ref([]), children=ref([]), page=ref(1), last=ref(1), childPage=ref(1), childLast=ref(1), selected=ref(-1), error=ref(''), loading=ref(true)
let requestId=0
async function load(){
 const current=++requestId; loading.value=true; error.value=''
 try{
  const slug=encodeURIComponent(route.params.slug)
  const [a,p,c]=await Promise.all([api.get('/albums/'+slug),api.get('/albums/'+slug+'/photos',{params:{page:page.value}}),api.get('/albums/'+slug+'/children',{params:{page:childPage.value}})])
  if(current!==requestId)return
  album.value=a.data;photos.value=p.data.data;last.value=p.data.last_page;children.value=c.data.data;childLast.value=c.data.last_page
 }catch{if(current===requestId){album.value=null;error.value='Album tidak ditemukan atau belum tersedia.'}}
 finally{if(current===requestId)loading.value=false}
}
function keys(e){if(selected.value<0)return;if(e.key==='Escape')selected.value=-1;if(e.key==='ArrowRight')selected.value=(selected.value+1)%photos.value.length;if(e.key==='ArrowLeft')selected.value=(selected.value-1+photos.value.length)%photos.value.length}
watch(()=>route.params.slug,()=>{page.value=1;childPage.value=1;selected.value=-1;album.value=null;load()})
onMounted(()=>{load();window.addEventListener('keydown',keys)})
onUnmounted(()=>window.removeEventListener('keydown',keys))
function changePhotoPage(n){page.value=n;selected.value=-1;load()}
function changeChildPage(n){childPage.value=n;load()}
</script>
<template>
 <main class="wrap listing">
  <router-link :to="album?.parent ? '/albums/'+album.parent.slug : '/albums'" class="text-link">← {{album?.parent ? album.parent.title : 'Semua album'}}</router-link>
  <p v-if="loading && !album" class="notice">Memuat album...</p><p v-else-if="error" class="notice">{{error}}</p>
  <template v-if="album">
   <div class="eyebrow"><i></i> {{album.category?.name}}</div><h1>{{album.title}}<span>.</span></h1><p>{{album.description}}</p>
   <div class="album-details"><span v-if="album.event_date">{{album.event_date.slice(0,10)}} &nbsp; · &nbsp; </span><span v-if="album.location">{{album.location}} &nbsp; · &nbsp; </span>{{album.photos_count}} foto</div>
   <section v-if="children.length" class="subalbums-section"><h2 class="subalbums-heading">Subalbum</h2><div class="album-grid"><router-link v-for="child in children" :key="child.id" :to="'/albums/'+child.slug" class="card"><div class="card-image"><img v-if="child.cover_url || child.cover || child.preview_photo" :src="child.cover_url || (child.cover || child.preview_photo).thumbnail_url" :alt="child.title" loading="lazy"><span v-else class="placeholder">SUBALBUM</span><span class="card-tag">{{child.category?.name}}</span></div><div class="card-meta">{{child.event_date?.slice(0,10) || 'Tanggal tidak tersedia'}} <span>{{child.photos_count}} foto</span></div><h3>{{child.title}} <span>↗</span></h3></router-link></div><div v-if="childLast>1" class="pagination"><button :disabled="childPage<=1" @click="changeChildPage(childPage-1)">← Sebelumnya</button><span>{{childPage}} / {{childLast}}</span><button :disabled="childPage>=childLast" @click="changeChildPage(childPage+1)">Berikutnya →</button></div></section>
   <h2 v-if="children.length" class="subalbums-heading">Foto album</h2><div class="photo-grid"><button v-for="(p,i) in photos" :key="p.id" @click="selected=i"><img :src="p.thumbnail_url" :alt="p.original_filename" loading="lazy"></button></div><p v-if="!photos.length" class="notice">Belum ada foto dalam album ini.</p>
   <div v-if="last>1" class="pagination"><button :disabled="page<=1" @click="changePhotoPage(page-1)">← Sebelumnya</button><span>{{page}} / {{last}}</span><button :disabled="page>=last" @click="changePhotoPage(page+1)">Berikutnya →</button></div>
  </template>
  <div v-if="selected>=0" class="lightbox" @click.self="selected=-1"><button class="close" aria-label="Tutup" @click="selected=-1">×</button><button aria-label="Foto sebelumnya" @click="selected=(selected-1+photos.length)%photos.length">←</button><figure><img :src="photos[selected].thumbnail_url" :alt="photos[selected].original_filename"><figcaption>{{photos[selected].original_filename}} · {{photos[selected].width}} × {{photos[selected].height}} <a :href="api.defaults.baseURL+'/photos/'+photos[selected].id+'/download'">↓ Unduh foto asli</a></figcaption></figure><button aria-label="Foto berikutnya" @click="selected=(selected+1)%photos.length">→</button></div>
 </main>
</template>
