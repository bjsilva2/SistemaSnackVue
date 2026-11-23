<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { Package, Users, Truck, Search, CalendarDays, ChevronDown, ChevronUp, BarChart3, Eye } from 'lucide-vue-next';

type Vendido={id:number;nombre:string;cantidad:string|number;importe:string|number};
type Stock={id:number;codigo:string;nombre:string;stock_actual:string|number;stock_minimo?:string|number};
type Visibilidad={masVendidos:boolean;stockCero:boolean;stockMinimo:boolean};
const props=defineProps<{
  estadisticas:{productos:number;clientes:number;proveedores:number};
  filtros:{desde:string;hasta:string};
  masVendidos:Vendido[];stockCero:Stock[];stockMinimo:Stock[];
  visibilidad:Visibilidad;
}>();
const form=reactive({...props.filtros});
const agotadosAbierto=ref(false);
const bajosAbierto=ref(false);
const totalCantidad=computed(()=>props.masVendidos.reduce((s,p)=>s+Number(p.cantidad),0));
const colores=['#ff5978','#459fea','#ffcc51','#45c7af','#8b78ed','#fa923c'];
const segmentos=computed(()=>{
 const total=totalCantidad.value;let cursor=0;
 return props.masVendidos.map((p,i)=>{const porcentaje=total?Number(p.cantidad)/total*100:0;const s={...p,porcentaje,color:colores[i%colores.length],inicio:cursor};cursor+=porcentaje;return s;});
});
const anillo=computed(()=>{if(!segmentos.value.length)return 'conic-gradient(#e2e8f0 0 100%)';return 'conic-gradient('+segmentos.value.map(s=>s.color+' '+s.inicio+'% '+(s.inicio+s.porcentaje)+'%').join(',')+')';});
function buscar(){router.get('/dashboard',{desde:form.desde,hasta:form.hasta},{preserveScroll:true,preserveState:true,replace:true});}
</script>
<template>
<Head title="Inicio - SistemaSnack"/>
<AppLayout>
<main class="dashboard" translate="no">
 <h1>¡Bienvenido! Sistema Snack <span aria-hidden="true">🎉</span></h1>
 <div class="tarjetas">
  <div class="tarjeta"><div class="icono productos"><Package :size="18"/></div><div><strong>{{estadisticas.productos}}</strong><small>Productos</small></div><span class="ojo"><Eye :size="15"/></span></div>
  <div class="tarjeta"><div class="icono clientes"><Users :size="18"/></div><div><strong>{{estadisticas.clientes}}</strong><small>Clientes</small></div><span class="ojo"><Eye :size="15"/></span></div>
  <div class="tarjeta"><div class="icono proveedores"><Truck :size="18"/></div><div><strong>{{estadisticas.proveedores}}</strong><small>Proveedores</small></div><span class="ojo"><Eye :size="15"/></span></div>
 </div>
 <div class="contenido">
  <section v-if="visibilidad.masVendidos" class="grafico">
    <header><BarChart3 :size="16" color="#159cdb"/><h2>Productos y Servicios Más Vendidos</h2></header>
    <form class="filtros" @submit.prevent="buscar">
      <label><span><CalendarDays :size="13"/> Fecha Inicio</span><input v-model="form.desde" type="date" required/></label>
      <label><span><CalendarDays :size="13"/> Fecha Fin</span><input v-model="form.hasta" type="date" :min="form.desde" required/></label>
      <button type="submit"><Search :size="15"/> Buscar</button>
    </form>
    <div class="grafico-cuerpo">
      <p class="titulo-grafico"><Package :size="14"/> Productos</p>
      <template v-if="masVendidos.length">
        <div class="donut" :style="{background:anillo}"><div class="agujero"><strong>{{Number(totalCantidad).toLocaleString('es-BO')}}</strong><small>Unidades</small></div></div>
        <div class="leyenda"><span v-for="p in segmentos" :key="p.id"><i :style="{background:p.color}"></i>{{p.nombre}} ({{Number(p.cantidad).toLocaleString('es-BO')}})</span></div>
      </template>
      <div v-else class="sin-datos">No hay productos vendidos en las fechas seleccionadas.</div>
    </div>
  </section>
  <div class="alertas">
    <section v-if="visibilidad.stockCero" class="alerta">
      <button type="button" class="alerta-titulo" @click="agotadosAbierto=!agotadosAbierto"><strong>PRODUCTOS CON STOCK EN CERO</strong><ChevronUp v-if="agotadosAbierto" :size="16"/><ChevronDown v-else :size="16"/></button>
      <div v-if="agotadosAbierto" class="lista-stock"><p v-if="!stockCero.length">No hay productos agotados.</p><div v-for="p in stockCero" :key="p.id"><span>{{p.nombre}} <small>{{p.codigo}}</small></span><b class="agotado">0</b></div></div>
    </section>
    <section v-if="visibilidad.stockMinimo" class="alerta">
      <button type="button" class="alerta-titulo" @click="bajosAbierto=!bajosAbierto"><strong>PRODUCTOS CON STOCK MÍNIMO</strong><ChevronUp v-if="bajosAbierto" :size="16"/><ChevronDown v-else :size="16"/></button>
      <div v-if="bajosAbierto" class="lista-stock"><p v-if="!stockMinimo.length">No hay productos con stock mínimo.</p><div v-for="p in stockMinimo" :key="p.id"><span>{{p.nombre}} <small>{{p.codigo}} · mínimo {{p.stock_minimo}}</small></span><b class="bajo">{{p.stock_actual}}</b></div></div>
    </section>
    <div v-if="!visibilidad.stockCero&&!visibilidad.stockMinimo&&!visibilidad.masVendidos" class="sin-autorizacion">Las secciones del panel están ocultas por los permisos asignados a tu rol.</div>
  </div>
 </div>
</main>
</AppLayout>
</template>
<style scoped>
.dashboard{padding:20px 24px;min-height:100vh;background:#f6f8fb;color:#253248}.dashboard h1{font-size:20px;font-weight:800;margin:2px 0 18px}.tarjetas{display:flex;gap:12px;margin-bottom:15px;flex-wrap:wrap}.tarjeta{background:white;border-radius:15px;box-shadow:0 2px 18px #20304a09;display:flex;gap:10px;align-items:center;padding:11px 14px;min-width:165px;flex:0 1 180px}.tarjeta .icono{width:38px;height:38px;display:flex;align-items:center;justify-content:center;color:white;border-radius:10px}.icono.productos{background:linear-gradient(140deg,#3196d6,#22475c)}.icono.clientes{background:linear-gradient(140deg,#fa537a,#ba094e)}.icono.proveedores{background:linear-gradient(140deg,#ffb728,#ff7800)}.tarjeta strong{display:block;font-size:22px;line-height:1.15}.tarjeta small{display:block;font-size:11px;color:#64748b}.ojo{margin-left:auto;background:#f5faff;color:#3699d8;padding:8px;border-radius:50%}.contenido{display:grid;grid-template-columns:minmax(320px,1.65fr) minmax(230px,1fr);gap:15px;align-items:start}.grafico{background:white;padding:15px;border-radius:13px;box-shadow:0 2px 10px #23334d0b}.grafico header{display:flex;align-items:center;gap:7px;margin-bottom:10px}.grafico h2{font-weight:750;font-size:14px}.filtros{background:#f8fafc;border-radius:8px;padding:9px;display:flex;align-items:end;gap:7px;flex-wrap:wrap}.filtros label{min-width:120px;flex:1}.filtros label span{font-size:10px;color:#64748b;display:flex;align-items:center;gap:3px}.filtros input{width:100%;border:1px solid #c3cedb;border-radius:4px;background:white;font-size:11px;padding:6px;height:33px}.filtros button{background:#158ee6;color:white;min-width:88px;height:33px;border-radius:4px;font-size:11px;display:flex;align-items:center;gap:6px;justify-content:center}.grafico-cuerpo{margin-top:8px;background:#f7f9fb;border-radius:10px;min-height:245px;padding:12px 13px}.titulo-grafico{font-size:11px;text-align:center;color:#536070;display:flex;align-items:center;gap:6px;justify-content:center;margin-bottom:9px}.donut{height:155px;width:155px;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center}.agujero{width:96px;height:96px;border-radius:50%;background:#f7f9fb;display:flex;flex-direction:column;align-items:center;justify-content:center}.agujero strong{font-size:23px}.agujero small{font-size:10px;color:#64748b}.leyenda{display:flex;justify-content:center;flex-wrap:wrap;gap:6px 13px}.leyenda span{font-size:10px;display:flex;gap:5px;align-items:center}.leyenda i{display:block;width:17px;height:7px}.sin-datos{font-size:12px;color:#64748b;text-align:center;padding:50px 10px}.alertas{display:flex;flex-direction:column;gap:12px}.alerta{border-radius:9px;overflow:hidden;background:white;box-shadow:0 2px 12px #222b3e0b}.alerta-titulo{display:flex;align-items:center;justify-content:space-between;gap:8px;text-align:left;background:linear-gradient(105deg,#2994d3,#30475d);color:white;padding:15px;width:100%;font-size:11px}.lista-stock{padding:8px 12px;max-height:290px;overflow:auto}.lista-stock>div{border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;gap:9px;padding:9px 1px;font-size:12px}.lista-stock>div:last-child{border:0}.lista-stock small{display:block;font-size:10px;color:#94a3b8}.lista-stock b{padding:2px 8px;border-radius:6px}.agotado{background:#ffdde1;color:#be123c}.bajo{background:#fff3ce;color:#b45309}.lista-stock p{font-size:11px;color:#64748b;padding:12px 0}.sin-autorizacion{background:white;border-radius:8px;padding:15px;font-size:12px;color:#64748b}@media(max-width:950px){.contenido{grid-template-columns:1fr}}@media(max-width:540px){.dashboard{padding:12px}.tarjeta{flex:1 1 130px}.contenido{display:block}.alertas{margin-top:12px}}
</style>