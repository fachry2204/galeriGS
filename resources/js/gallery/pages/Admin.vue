<script setup>
import { ref,onMounted,onUnmounted } from 'vue'; import { useRouter } from 'vue-router'; import Swal from 'sweetalert2'; import { api } from '../services/api'
const router=useRouter(),token=sessionStorage.getItem('gallery_token'),user=ref(null),albums=ref([]),parents=ref([]),categories=ref([]),section=ref('albums'),editing=ref(null),photos=ref([]),files=ref([]),progress=ref(0),message=ref(''),form=ref({title:'',slug:'',parent_id:null,category_id:'',description:'',event_date:'',location:'',is_published:false,is_featured:false}),cat=ref({name:'',slug:'',description:'',is_active:true})
const albumPage=ref(1), albumLast=ref(1)
const uploading=ref(false), uploadResults=ref([]), fileInput=ref(null), saving=ref(false), coverFile=ref(null), coverPreview=ref(null), coverInput=ref(null)
const auth={headers:{Authorization:'Bearer '+token}}; if(!token)router.replace('/admin/login')
async function load(){try{let [u,a,c,p]=await Promise.all([api.get('/admin/me',auth),api.get('/admin/albums',{...auth,params:{page:albumPage.value}}),api.get('/admin/categories',auth),api.get('/admin/album-parents',auth)]);user.value=u.data;albums.value=a.data.data;albumLast.value=a.data.last_page;categories.value=c.data;parents.value=p.data}catch{sessionStorage.removeItem('gallery_token');router.replace('/admin/login')}}
onMounted(()=>{if(token)load()});function slug(s){return s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')}
function fresh(parent=null){clearCoverSelection();editing.value=null;photos.value=[];files.value=[];uploadResults.value=[];form.value={title:'',slug:'',parent_id:parent?.id || null,category_id:parent?.category_id || '',description:'',event_date:'',location:'',is_published:false,is_featured:false};section.value='edit'}
async function changeAlbumPage(value){albumPage.value=value;await load()}
async function addSubalbum(){
 const result=await Swal.fire({title:'Buat subalbum?',text:'Simpan perubahan pada album ini terlebih dahulu. Formulir subalbum akan dibuka.',showCancelButton:true,confirmButtonText:'Buat subalbum',cancelButtonText:'Batal'})
 if(result.isConfirmed)fresh({id:editing.value,category_id:form.value.category_id})
}
async function edit(a){try{const r=await api.get('/admin/albums/'+a.id,auth);clearCoverSelection();editing.value=a.id;form.value={...r.data,event_date:r.data.event_date?.slice(0,10)||''};photos.value=r.data.photos;files.value=[];section.value='edit'}catch{message.value='Album gagal dimuat.'}}
function clearCoverSelection(){if(coverPreview.value)URL.revokeObjectURL(coverPreview.value);coverPreview.value=null;coverFile.value=null;if(coverInput.value)coverInput.value.value=''}
function selectCover(event){const file=event.target.files?.[0];clearCoverSelection();if(!file)return;if(!/\.(jpe?g|png|webp)$/i.test(file.name) || file.size>20*1024*1024){message.value='Sampul harus berformat JPEG, PNG, atau WebP dan maksimal 20 MB.';return}coverFile.value=file;coverPreview.value=URL.createObjectURL(file);message.value=''}
onUnmounted(clearCoverSelection)
async function save(){
 if(saving.value)return
 saving.value=true;message.value=''
 try{
  const payload={...form.value,category_id:Number(form.value.category_id)}
  const response=editing.value?await api.put('/admin/albums/'+editing.value,payload,auth):await api.post('/admin/albums',payload,auth)
  editing.value=response.data.id
  if(coverFile.value){
   try{const data=new FormData();data.append('cover',coverFile.value);const result=await api.post('/admin/albums/'+editing.value+'/cover',data,auth);form.value.cover_url=result.data.cover_url;form.value.cover_photo_id=null;form.value.cover=null;clearCoverSelection()}
   catch(e){message.value='Album tersimpan, tetapi sampul gagal diunggah: '+(e.response?.data?.message||'periksa koneksi atau format gambar.')+' Coba simpan lagi.';await load();return}
  }
  message.value='Album tersimpan.';await load()
 }catch(e){message.value=e.response?.data?.message||'Gagal menyimpan album.'}finally{saving.value=false}
}
function selectFiles(event){files.value=Array.from(event.target.files || []);uploadResults.value=[];progress.value=0}
function uploadError(error){
 if(error.response?.status===413)return 'Ukuran file melebihi batas server. Periksa upload_max_filesize dan post_max_size PHP.'
 if(error.response?.status===401 || error.response?.status===403)return 'Sesi tidak berlaku atau akses ditolak. Silakan masuk kembali.'
 const errors=error.response?.data?.errors
 if(errors)return Object.values(errors).flat().join(' ')
 return error.response?.data?.message || 'Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.'
}
async function upload(){
 if(!editing.value || uploading.value || !files.value.length)return
 const queue=Array.from(files.value), failed=[]; let success=0
 uploadResults.value=[];progress.value=0;uploading.value=true;message.value=''
 try{
  for(let i=0;i<queue.length;i++){
   const file=queue[i];uploadResults.value.push({name:file.name,status:'Mengunggah',error:'',thumbnail:null});const entry=uploadResults.value[uploadResults.value.length-1]
   try{
    if(!/\.(jpe?g|png|webp)$/i.test(file.name))throw new Error('Format tidak didukung. Gunakan JPEG, PNG, atau WebP.')
    if(file.size>20*1024*1024)throw new Error('Ukuran melebihi 20 MB.')
    const data=new FormData();data.append('photos[]',file)
    const response=await api.post('/admin/albums/'+editing.value+'/photos',data,{...auth,onUploadProgress:e=>{progress.value=Math.round((i+(e.total?e.loaded/e.total:0))/queue.length*100)}})
    entry.thumbnail=response.data?.[0]?.thumbnail_url || null;entry.status='Berhasil';success++
   }catch(error){entry.status='Gagal';entry.error=error.response ? uploadError(error) : (error.message || uploadError(error));failed.push(file)}
   progress.value=Math.round((i+1)/queue.length*100)
  }
  files.value=failed
  if(fileInput.value)fileInput.value.value=''
  message.value=`${success} foto berhasil diunggah${failed.length ? `; ${failed.length} gagal. Anda dapat mencoba kembali foto yang gagal.` : '.'}`
  try{const result=await api.get('/admin/albums/'+editing.value,auth);photos.value=result.data.photos;await load()}catch{message.value+=' Daftar foto belum dapat diperbarui; buka kembali album.'}
 }finally{uploading.value=false}
}
async function removePhoto(p){
 const result=await Swal.fire({title:'Hapus foto ini?',text:`Hanya foto ${p.original_filename} yang akan dihapus.`,showCancelButton:true,confirmButtonText:'Hapus foto',cancelButtonText:'Batal'})
 if(!result.isConfirmed)return
 try{await api.delete('/admin/photos/'+p.id,auth);photos.value=photos.value.filter(photo=>photo.id!==p.id);if(form.value.cover_photo_id===p.id)form.value.cover_photo_id=null;message.value='Foto berhasil dihapus.';await load()}
 catch(e){await Swal.fire({icon:'error',title:'Foto gagal dihapus',text:e.response?.data?.message||'Periksa koneksi lalu coba lagi.'})}
}
async function cover(p){try{const result=await api.put('/admin/albums/'+editing.value+'/cover-photo',{photo_id:p.id},auth);form.value.cover_photo_id=p.id;form.value.cover_url=result.data.cover_url;form.value.cover=result.data.cover;clearCoverSelection();message.value='Sampul album diperbarui.';await load()}catch(e){message.value=e.response?.data?.message||'Sampul album gagal diperbarui.'}}
async function removeAlbum(a){
 if(a.children_count>0){await Swal.fire({icon:'info',title:'Album masih memiliki subalbum',text:'Pindahkan atau hapus subalbum terlebih dahulu.'});return}
 const result=await Swal.fire({icon:'warning',title:'Hapus album?',text:`Album ${a.title} beserta ${a.photos_count} foto dan thumbnail di dalamnya akan dihapus dari server. Tindakan ini tidak dapat dibatalkan.`,showCancelButton:true,confirmButtonText:'Ya, hapus album',cancelButtonText:'Batal'})
 if(!result.isConfirmed)return
 try{await api.delete('/admin/albums/'+a.id,auth);if(albums.value.length===1 && albumPage.value>1)albumPage.value--;await load();message.value='Album dan foto di dalamnya berhasil dihapus.'}
 catch(e){await load();await Swal.fire({icon:'error',title:'Album gagal dihapus',text:e.response?.data?.message||'Periksa koneksi dan coba lagi.'})}
}
async function saveCategory(){try{if(cat.value.id)await api.put('/admin/categories/'+cat.value.id,cat.value,auth);else await api.post('/admin/categories',cat.value,auth);cat.value={name:'',slug:'',description:'',is_active:true};await load();message.value='Kategori tersimpan.'}catch(e){message.value=e.response?.data?.message||'Kategori gagal disimpan.'}}
async function deleteCategory(c){
 const total=c.albums_total_count ?? c.albums_count
 let replacement=null
 if(total>0){
  const choices=Object.fromEntries(categories.value.filter(item=>item.id!==c.id).map(item=>[item.id,item.name]))
  if(!Object.keys(choices).length){await Swal.fire({icon:'info',title:'Kategori pengganti diperlukan',text:'Buat kategori lain terlebih dahulu untuk memindahkan album yang terkait.'});return}
  const result=await Swal.fire({title:'Pindahkan album dan hapus kategori?',text:`Kategori ini terkait dengan ${total} album, termasuk album yang sudah dihapus. Pilih kategori pengganti.`,input:'select',inputOptions:choices,inputPlaceholder:'Pilih kategori pengganti',inputValidator:value=>!value?'Pilih kategori pengganti.':undefined,showCancelButton:true,confirmButtonText:'Pindahkan dan hapus',cancelButtonText:'Batal'})
  if(!result.isConfirmed)return
  replacement=Number(result.value)
 }else if(!(await Swal.fire({title:'Hapus kategori?',text:`Kategori ${c.name} akan dihapus.`,showCancelButton:true,confirmButtonText:'Hapus',cancelButtonText:'Batal'})).isConfirmed)return
 try{await api.delete('/admin/categories/'+c.id,{...auth,data:replacement?{replacement_category_id:replacement}:{}});if(cat.value.id===c.id)cat.value={name:'',slug:'',description:'',is_active:true};await load();message.value='Kategori berhasil dihapus.'}catch(e){await Swal.fire({icon:'error',title:'Kategori gagal dihapus',text:e.response?.data?.message||'Periksa koneksi dan coba lagi.'})}
}
async function logout(){try{await api.post('/admin/logout',{},auth)}finally{sessionStorage.removeItem('gallery_token');router.replace('/admin/login')}}
</script>
<template><div class="admin"><aside><div class="brand">KELURAHAN <small>GROGOL SELATAN</small></div><p>ADMINISTRATION</p><button :class="{active: section === 'albums' || section === 'edit'}" @click="section='albums'">▦ &nbsp; Album</button><button :class="{active: section === 'categories'}" @click="section='categories'">◇ &nbsp; Kategori</button><button @click="logout">↗ &nbsp; Keluar</button></aside><main><header><div><div class="eyebrow"><i></i> PANEL ADMIN</div><h1>{{section==='edit'?'Kelola album':section==='categories'?'Kategori':'Album foto'}}</h1></div><span>{{user?.name}}</span></header><p v-if="message" class="notice">{{message}}</p><template v-if="section==='albums'"><button class="button dark" @click="fresh()">+ Tambah album</button><div class="admin-table"><div v-for="a in albums" :key="a.id" class="admin-row"><div><strong>{{a.title}}</strong><small>{{a.parent ? 'Subalbum dari '+a.parent.title+' · ' : ''}}{{a.category?.name}} · {{a.photos_count}} foto · {{a.children_count}} subalbum · {{a.is_published?'Publik':'Draft'}}</small></div><button v-if="!a.parent_id" @click="fresh(a)">Tambah subalbum</button><button @click="edit(a)">Edit</button><button @click="removeAlbum(a)">Hapus album</button></div><p v-if="!albums.length">Belum ada album.</p></div><div v-if="albumLast>1" class="pagination"><button :disabled="albumPage<=1" @click="changeAlbumPage(albumPage-1)">← Sebelumnya</button><span>{{albumPage}} / {{albumLast}}</span><button :disabled="albumPage>=albumLast" @click="changeAlbumPage(albumPage+1)">Berikutnya →</button></div></template><template v-else-if="section==='edit'"><button class="text-link" @click="section='albums'">← Kembali ke album</button><form @submit.prevent="save" class="admin-form"><label>Nama album<input v-model="form.title" @input="!editing && (form.slug=slug(form.title))" required></label><label>Slug<input v-model="form.slug" required></label><label>Album induk (untuk subalbum)<select v-model="form.parent_id"><option :value="null">Tidak ada — album utama</option><option v-for="parent in parents.filter(p=>p.id!==editing)" :key="parent.id" :value="parent.id">{{parent.title}}</option></select></label><label>Kategori<select v-model="form.category_id" required><option value="">Pilih kategori</option><option v-for="c in categories" :value="c.id">{{c.name}}</option></select></label><label>Tanggal<input v-model="form.event_date" type="date"></label><label>Lokasi<input v-model="form.location"></label><label class="wide">Deskripsi<textarea v-model="form.description"></textarea></label><label class="wide cover-upload">Sampul album <span class="cover-help">JPEG, PNG, atau WebP; maksimal 20 MB. Sampul disimpan terpisah dari foto album.</span><input ref="coverInput" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" :disabled="saving" @change="selectCover"><img v-if="coverPreview || form.cover_url || form.cover?.thumbnail_url" class="cover-preview" :src="coverPreview || form.cover_url || form.cover?.thumbnail_url" alt="Pratinjau sampul album"><span v-else class="cover-placeholder">Belum ada sampul. Pilih gambar untuk ditampilkan pada kartu album.</span></label><label class="check"><input type="checkbox" v-model="form.is_published"> Publikasikan</label><label class="check"><input type="checkbox" v-model="form.is_featured"> Featured</label><button class="button dark" :disabled="saving">{{saving ? 'Menyimpan...' : 'Simpan album'}}</button></form><section v-if="!form.parent_id" class="subalbum-section"><h2>Subalbum</h2><p class="upload-help">Buat subalbum untuk mengelompokkan foto di dalam album ini. Subalbum hanya dapat dibuat satu tingkat.</p><button v-if="editing" type="button" class="button dark" @click="addSubalbum">+ Buat subalbum</button><p v-else class="upload-help">Simpan album terlebih dahulu, lalu pilih tombol Buat subalbum.</p></section><p v-if="form.parent_id" class="upload-help">Subalbum hanya tampil kepada pengunjung ketika album induk dan subalbum sama-sama dipublikasikan.</p><p v-if="!editing" class="notice">Simpan album terlebih dahulu untuk menambahkan foto.</p><template v-if="editing"><h2>Foto album</h2><p class="upload-help">Pilih foto JPEG, PNG, atau WebP. Maksimal 20 MB per foto. Foto dikirim satu per satu.</p><div class="upload"><input ref="fileInput" type="file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" :disabled="uploading" @change="selectFiles"><button class="button dark" :disabled="!files.length || uploading" @click="upload">{{uploading ? 'Mengunggah...' : `Unggah ${files.length} foto`}}</button><progress v-if="uploading || progress" :value="progress" max="100" :aria-label="`Kemajuan upload ${progress}%`"></progress><span v-if="uploading || progress">{{progress}}%</span></div><ul v-if="uploadResults.length" class="upload-results"><li v-for="(result,i) in uploadResults" :key="i"><img v-if="result.thumbnail" :src="result.thumbnail" :alt="result.name" loading="lazy"><div><strong>{{result.status}}</strong> — {{result.name}}<span v-if="result.error">: {{result.error}}</span></div></li></ul><div class="admin-photos"><div v-for="p in photos" :key="p.id"><img :src="p.thumbnail_url" :alt="p.original_filename"><small>{{p.original_filename}}</small><button @click="cover(p)">{{!form.cover_url && form.cover_photo_id===p.id?'✓ Cover':'Jadikan cover'}}</button><button @click="removePhoto(p)">Hapus foto</button></div></div></template></template><template v-else><form @submit.prevent="saveCategory" class="admin-form"><label>Nama kategori<input v-model="cat.name" @input="!cat.id && (cat.slug=slug(cat.name))" required></label><label>Slug<input v-model="cat.slug" required></label><label>Deskripsi<input v-model="cat.description"></label><label class="check"><input type="checkbox" v-model="cat.is_active"> Aktif</label><button class="button dark">Simpan kategori</button></form><div class="admin-table"><div v-for="c in categories" class="admin-row"><div><strong>{{c.name}}</strong><small>{{c.albums_count}} album</small></div><button @click="cat={...c}">Edit</button><button @click="deleteCategory(c)">Hapus</button></div></div></template></main></div></template>
