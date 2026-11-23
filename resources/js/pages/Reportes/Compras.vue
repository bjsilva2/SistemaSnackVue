<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { Head, router } from '@inertiajs/vue3';
const { can } = usePermissions();
import { reactive, computed } from 'vue';
function imprimir(){window.print();}
import { Search, FileText, FileSpreadsheet } from 'lucide-vue-next';

type Compra={id:number;compra_id:number;compra_codigo:string;recibo_factura:string|null;fecha:string;fecha_recepcion:string;proveedor:string;usuario:string|null;producto:string;producto_codigo:string;cantidad:string|number;precio_compra:string|number;descuento:string|number;subtotal:string|number;sucursal:string};
type Pagina={data:Compra[];current_page:number;last_page:number;total:number};
type Filtros={sucursal:string;factura:string;producto_id:string|number;proveedor_id:string|number;usuario_id:string|number;desde:string;hasta:string};
type Opcion={id:number;nombre?:string;name?:string};
const props=defineProps<{compras:Pagina;resumen:{cantidad_compras:number;total_compras:string|number;total_descuentos:string|number};filtros:Filtros;sucursales:string[];productos:Opcion[];proveedores:Opcion[];usuarios:Opcion[]}>();
const form=reactive<Filtros>({...props.filtros});
const dinero=(x:number|string|null|undefined)=>Number(x??0).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
const fecha=(s:string|null|undefined)=>{if(!s)return '—';const m=s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2}))?)?/);return m?`${m[3]}/${m[2]}/${m[1]}${m[4]?' '+m[4]+':'+m[5]+':'+(m[6]||'00'):''}`:s;};
const params=()=>Object.fromEntries(Object.entries(form).filter(([,v])=>v!==''&&v!==null).map(([k,v])=>[k,String(v)])) as Record<string,string>;
function filtrar(){router.get('/reportes/compras',params(),{preserveState:true,replace:true});}
function pagina(n:number){router.get('/reportes/compras',{...params(),page:String(n)},{preserveState:true,replace:true});}
function csv(){window.location.href='/reportes/compras/exportar?'+new URLSearchParams(params()).toString();}
const hay=computed(()=>props.compras.data.length>0);
</script>
<template>
<Head title="Reporte General de Compras"/>
<AppLayout>
<main class="pagina" translate="no">
<div class="miga">REPORTES <span>›</span> <b>COMPRAS</b></div>
<h1>REPORTE GENERAL DE COMPRAS</h1>
<form class="filtros" @submit.prevent="filtrar">
  <div class="campo"><label>SUCURSAL:</label><select v-model="form.sucursal"><option value="TODAS">TODAS</option><option v-for="s in sucursales" :key="s" :value="s">{{s}}</option></select></div>
  <div class="campo"><label>RECIBO/FACTURA:</label><input v-model="form.factura" placeholder="Nro Recibo"/></div>
  <div class="campo"><label>NOMBRE PRODUCTO:</label><select v-model="form.producto_id"><option value="">TODOS LOS PRODUCTOS</option><option v-for="p in productos" :key="p.id" :value="String(p.id)">{{p.nombre}}</option></select></div>
  <div class="campo"><label>PROVEEDOR:</label><select v-model="form.proveedor_id"><option value="">TODOS</option><option v-for="p in proveedores" :key="p.id" :value="String(p.id)">{{p.nombre}}</option></select></div>
  <div class="campo"><label>USUARIOS:</label><select v-model="form.usuario_id"><option value="">TODOS</option><option v-for="u in usuarios" :key="u.id" :value="String(u.id)">{{u.name}}</option></select></div>
  <div class="campo"><label>FECHA COMPRA DESDE:</label><input v-model="form.desde" type="date"/></div>
  <div class="campo"><label>FECHA COMPRA HASTA:</label><input v-model="form.hasta" type="date"/></div>
  <div class="campo"><label>ACCIONES:</label><button class="aplicar" type="submit"><Search :size="14"/> APLICAR FILTROS</button></div>
</form>
<section class="resumen">
  <div>TOTAL COMPRAS: <span class="azul">{{dinero(resumen.total_compras)}} Bs.</span></div>
  <div>TOTAL DESCUENTOS: <span class="rojo">{{dinero(resumen.total_descuentos)}} Bs.</span></div>
  <div>TOTAL GENERAL: <span class="verde">{{dinero(resumen.total_compras)}} Bs.</span></div>
</section>
<p class="nota">Los totales corresponden a las compras del filtro. Si filtras un producto, se incluyen los totales de las compras que lo contienen; la tabla muestra solo las líneas del producto seleccionado.</p>
<div class="herramientas">
  <button type="button" class="pdf" @click="imprimir"><FileText :size="13"/> Imprimir / PDF</button>
  <button type="button" class="excel" v-if="can('Reportes.exportar')" @click="csv"><FileSpreadsheet :size="13"/> Exportar CSV</button>
</div>
<div class="tabla">
<table>
<thead><tr><th>#</th><th>REC/FACT</th><th>USUARIO</th><th>PROVEEDOR</th><th>F.SOLICITUD</th><th>F.RECEPCIÓN</th><th>DESCRIPCIÓN/PRODUCTOS</th><th>CANT</th><th>PU</th><th>DESC.</th><th>TOTAL</th></tr></thead>
<tbody>
<tr v-for="(r,i) in compras.data" :key="r.id">
 <td>{{(compras.current_page-1)*20+i+1}}</td>
 <td>{{r.recibo_factura||r.compra_codigo}}</td>
 <td>{{r.usuario||'Sin usuario'}}</td>
 <td>{{r.proveedor}}</td>
 <td>{{fecha(r.fecha)}}</td>
 <td>{{fecha(r.fecha_recepcion)}}</td>
 <td>{{r.producto}}</td>
 <td class="numero">{{Number(r.cantidad).toLocaleString('es-BO',{maximumFractionDigits:2})}}</td>
 <td class="numero">{{dinero(r.precio_compra)}}</td>
 <td class="numero">{{dinero(r.descuento)}}</td>
 <td class="numero">{{dinero(r.subtotal)}}</td>
</tr>
<tr v-if="!hay"><td colspan="11" class="vacio">No hay compras que coincidan con los filtros.</td></tr>
</tbody></table>
</div>
<footer class="pie"><span>Mostrando {{compras.data.length}} de {{compras.total}} líneas de compra</span><div><button :disabled="compras.current_page<=1" @click="pagina(compras.current_page-1)">‹ Anterior</button><span>{{compras.current_page}} / {{compras.last_page}}</span><button :disabled="compras.current_page>=compras.last_page" @click="pagina(compras.current_page+1)">Siguiente ›</button></div></footer>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{min-height:100vh;padding:16px 18px;background:#fff;color:#25364a}.miga{font-size:10px;color:#94a3b8;margin-bottom:14px}.miga b{color:#334155}.miga span{padding:0 5px}h1{text-align:center;font-size:17px;font-weight:800;margin:7px 0 3px}.filtros{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:10px;background:#7dfcab;border:1px solid #19aa70;border-radius:4px;padding:10px}.campo{min-width:0}.campo label{display:block;font-size:10px;font-weight:800;color:#175348;margin-bottom:4px}.campo input,.campo select{width:100%;background:#fff;border:1px solid #9abec1;border-radius:3px;height:30px;padding:4px 7px;color:#334155;font-size:11px}.aplicar{width:100%;height:30px;background:#048fcf;color:#fff;display:flex;align-items:center;justify-content:center;gap:8px;border-radius:2px;font-size:10px}.resumen{border:1px solid #475569;border-radius:14px;display:flex;justify-content:space-around;gap:10px;flex-wrap:wrap;padding:8px 12px;margin:14px 4px 2px;font-size:11px;font-weight:800}.resumen span{display:inline-block;border-radius:13px;color:#fff;padding:3px 8px;font-size:10px}.azul{background:#138bd8}.rojo{background:#ed515e}.verde{background:#78b235}.nota{font-size:10px;color:#64748b;text-align:center;margin:6px 0 12px}.herramientas{display:flex;justify-content:flex-end;gap:7px;margin:10px 0 6px}.herramientas button{display:inline-flex;align-items:center;gap:4px;font-size:10px;border:1px solid;padding:7px;border-radius:2px}.pdf{color:#e94b58;border-color:#e94b58!important}.excel{color:#75ac4b;border-color:#75ac4b!important}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:10px}th,td{border:1px solid #d4dae1;padding:7px 5px;text-align:left}th{white-space:nowrap;background:#042a3d;color:#fff;font-size:10px;font-weight:800}.numero{text-align:right}.vacio{text-align:center;padding:32px;color:#64748b}.pie{display:flex;justify-content:space-between;gap:10px;color:#64748b;font-size:11px;margin-top:12px}.pie>div{display:flex;gap:10px}.pie button{color:#038bd2}.pie button:disabled{color:#cbd5e1}@media(max-width:900px){.filtros{grid-template-columns:repeat(2,minmax(120px,1fr))}}@media(max-width:530px){.filtros{grid-template-columns:1fr}.resumen{justify-content:start}.pie{flex-wrap:wrap}}
@media print{.miga,.filtros,.herramientas,.pie{display:none!important}.pagina{padding:0}.tabla{overflow:visible}.nota{font-size:9px}}
</style>
