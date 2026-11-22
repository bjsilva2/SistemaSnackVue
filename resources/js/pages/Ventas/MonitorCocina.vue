<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ClipboardList, RefreshCw, LogOut, CheckCircle2, Clock3, ChefHat } from 'lucide-vue-next';

type Item = { pedido_id:number; nombre:string; cantidad:number|string };
type Pedido = { id:number; codigo:string; modalidad:string; nombre_cliente:string|null; nota_cocina:string|null; cocina_estado:'EN_COCINA'|'LISTO'; created_at:string; cocina_listo_at:string|null; mesa_numero:number|null; items:Item[] };
const props = defineProps<{pedidos:Pedido[];horaServidor:string}>();
const filtro=ref<'TODOS'|'EN_COCINA'|'LISTO'>('TODOS');
const ahora=ref(Date.parse(props.horaServidor));
let reloj:ReturnType<typeof setInterval>|undefined;
onMounted(()=>{reloj=setInterval(()=>{ahora.value+=1000},1000)});
onUnmounted(()=>{if(reloj)clearInterval(reloj)});
const pendientes=computed(()=>props.pedidos.filter(p=>p.cocina_estado==='EN_COCINA').length);
const listos=computed(()=>props.pedidos.filter(p=>p.cocina_estado==='LISTO').length);
const filtrados=computed(()=>props.pedidos.filter(p=>filtro.value==='TODOS'||p.cocina_estado===filtro.value));
const procesando=ref<number|null>(null);
function tiempo(p:Pedido){const minutos=Math.max(0,Math.floor((ahora.value-Date.parse(p.created_at.replace(' ','T')))/60000));return minutos<60?minutos+'m':Math.floor(minutos/60)+'h '+minutos%60+'m'}
function marcarListo(p:Pedido){if(!confirm('¿Marcar '+p.codigo+' como LISTO?'))return;procesando.value=p.id;router.patch('/ventas/cocina/'+p.id+'/estado',{estado:'LISTO'},{preserveScroll:true,onFinish:()=>{procesando.value=null}})}
function actualizar(){router.reload({only:['pedidos','horaServidor'],onSuccess:()=>{ahora.value=Date.parse(props.horaServidor)}})}
const relojTexto=computed(()=>new Date(ahora.value).toLocaleTimeString('es-BO',{hour:'2-digit',minute:'2-digit',hour12:true}));
</script>

<template>
<Head title="Pedidos de Cocina"/>
<div class="monitor">
 <header class="barra-superior">
   <div class="marca"><ClipboardList :size="23"/><div><div class="titulo"><span>🍳</span> PEDIDOS DE COCINA</div><small>Sistema de visualización de pedidos</small></div></div>
   <div class="herramientas"><button type="button" class="refrescar" @click="actualizar"><RefreshCw :size="13"/> Actualizar</button><b>{{relojTexto}}</b><a href="/ventas/pedidos" class="volver" title="Volver a pedidos"><LogOut :size="15"/></a></div>
 </header>
 <main class="cuerpo">
  <nav class="filtros" aria-label="Filtros de pedidos">
   <button type="button" :class="{activo:filtro==='TODOS'}" @click="filtro='TODOS'">Todos ({{pedidos.length}})</button>
   <button type="button" :class="{activo:filtro==='EN_COCINA'}" @click="filtro='EN_COCINA'"><span class="punto naranja"/>En Cocina ({{pendientes}})</button>
   <button type="button" :class="{activo:filtro==='LISTO'}" @click="filtro='LISTO'"><span class="punto verde"/>Listos ({{listos}})</button>
  </nav>
  <section class="tarjetas">
   <article v-for="p in filtrados" :key="p.id" class="pedido" :class="{listo:p.cocina_estado==='LISTO'}">
    <div class="cabecera"><div><h2>{{p.modalidad==='LLEVAR'?'Para Llevar':p.modalidad==='YANGO'?'Yango':'Mesa '+(p.mesa_numero??'')}} - #{{p.id}}</h2>
     <div class="datos"><span>♙ {{p.nombre_cliente||'Sin nombre'}}</span><span><Clock3 :size="11"/> Hace {{tiempo(p)}}</span></div>
     <div class="estados"><span class="cobrado">{{p.cocina_estado==='LISTO'?'✓ LISTO':'◷ EN COCINA'}}</span><span v-if="p.cocina_estado==='EN_COCINA'" class="alerta">● EN PREPARACIÓN</span></div>
    </div><span v-if="p.cocina_estado==='EN_COCINA'" class="urgente">¡URGENTE!</span><span v-else class="listo-etiqueta">LISTO</span></div>
    <div class="productos"><div v-for="(item,i) in p.items" :key="i" class="item"><span class="cantidad">{{Number(item.cantidad)}}x</span><strong>{{item.nombre}}</strong></div></div>
    <p v-if="p.nota_cocina" class="nota">NOTA: {{p.nota_cocina}}</p>
    <footer v-if="p.cocina_estado==='EN_COCINA'"><button type="button" :disabled="procesando===p.id" @click="marcarListo(p)"><CheckCircle2 :size="15"/> MARCAR LISTO</button></footer>
   </article>
  </section>
  <div v-if="!filtrados.length" class="vacio"><ChefHat :size="35"/><strong>No hay pedidos en esta sección</strong><p>Los nuevos pedidos aparecerán aquí al registrarlos en Nuevo Pedido Mesa.</p></div>
 </main>
</div>
</template>

<style scoped>
.monitor{min-height:100vh;background:#f1f5f9;color:#152238;font-family:Arial,Helvetica,sans-serif}.barra-superior{height:57px;background:#1e293b;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:7px 16px;color:white}.marca{display:flex;align-items:center;gap:9px}.titulo{font-weight:900;font-size:14px;white-space:nowrap}.marca small{font-size:10px;color:#cbd5e1}.herramientas{display:flex;align-items:center;gap:8px}.herramientas b{font-size:11px;white-space:nowrap}.refrescar{display:flex;align-items:center;gap:5px;background:#ffcd37;color:#2f3846;padding:8px 10px;border-radius:3px;font-size:10px;font-weight:800}.volver{background:#e63846;color:white;border-radius:3px;padding:8px}.cuerpo{padding:20px 16px}.filtros{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:15px}.filtros button{display:flex;align-items:center;gap:4px;background:#fff;border:1px solid #dbe3ee;color:#334155;border-radius:4px;padding:8px 12px;font-size:11px;font-weight:700}.filtros button.activo{background:#17243a;color:white;border-color:#17243a}.punto{width:8px;height:8px;border-radius:50%;display:inline-block}.naranja{background:#f97316}.verde{background:#16a34a}.tarjetas{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,320px));align-items:start;gap:14px}.pedido{background:#fff0f0;border-left:4px solid #f33b49;border-radius:7px;box-shadow:0 1px 4px #1111;overflow:hidden}.pedido.listo{background:#edfdf1;border-left-color:#16a34a}.cabecera{display:flex;justify-content:space-between;gap:8px;min-height:82px;padding:14px 10px}.cabecera h2{font-size:17px;font-weight:900}.datos{display:flex;gap:8px;flex-wrap:wrap;margin:5px 0;color:#64748b;font-size:10px}.datos span{display:flex;align-items:center;gap:3px}.estados{display:flex;align-items:center;gap:12px;font-size:10px;font-weight:700}.cobrado{color:#16a34a}.alerta{color:#fb923c}.urgente{height:max-content;background:#e42a3b;color:white;border-radius:12px;padding:5px 7px;font-size:8px;font-weight:900}.listo-etiqueta{background:#16a34a;color:white;border-radius:12px;padding:5px 7px;font-size:9px;font-weight:900}.productos{background:#fffafa;padding:10px 11px}.pedido.listo .productos{background:#fafffb}.item{display:flex;align-items:center;gap:6px;border-bottom:1px dashed #e5e7eb;padding:7px 0;font-size:13px}.item:last-child{border:0}.cantidad{background:#334155;color:white;padding:3px 6px;border-radius:3px;font-weight:800}.nota{font-size:11px;margin:8px 10px;color:#a34415}.pedido footer{padding:8px 10px;display:flex;justify-content:flex-end}.pedido footer button{display:flex;gap:5px;align-items:center;background:#22a453;color:white;font-weight:800;font-size:10px;padding:8px;border-radius:5px}.pedido footer button:disabled{opacity:.5}.vacio{display:flex;flex-direction:column;gap:9px;align-items:center;justify-content:center;text-align:center;padding:70px 12px;color:#64748b}.vacio strong{font-size:15px}.vacio p{font-size:12px}@media(max-width:540px){.barra-superior{padding:7px}.titulo{font-size:11px}.marca small{font-size:9px}.cuerpo{padding:12px 8px}.tarjetas{grid-template-columns:1fr}.herramientas b{display:none}}
</style>
