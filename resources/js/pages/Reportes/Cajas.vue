<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { Head, router } from '@inertiajs/vue3';
const { can } = usePermissions();
import { reactive } from 'vue';
import { Search, FileText, FileSpreadsheet } from 'lucide-vue-next';

type Caja={id:number;usuario:string;sucursal:string;fecha_apertura:string;fecha_cierre:string|null;monto_inicial:string|number;total_ventas:string|number;ingresos:string|number;egresos:string|number;saldo:string|number;pago_qr:string|number;estado:string};
type Pagina={data:Caja[];current_page:number;last_page:number;total:number};
type Filtros={sucursal:string;codigo:string|number;usuario_id:string|number;estado:string;desde:string;hasta:string};
const props=defineProps<{cajas:Pagina;filtros:Filtros;usuarios:{id:number;name:string}[];sucursales:string[]}>();
const form=reactive<Filtros>({...props.filtros});
const dinero=(v:string|number)=>Number(v).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
function fecha(v:string|null){if(!v)return '—';const m=v.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);return m?m[3]+'/'+m[2]+'/'+m[1]+(m[4]?' '+m[4]+':'+m[5]:''):v;}
const params=()=>Object.fromEntries(Object.entries(form).filter(([,v])=>v!==''&&v!==null).map(([k,v])=>[k,String(v)])) as Record<string,string>;
function filtrar(){router.get('/reportes/cajas',params(),{preserveState:true,replace:true});}
function pagina(n:number){router.get('/reportes/cajas',{...params(),page:String(n)},{preserveState:true,replace:true});}
function exportar(){window.location.href='/reportes/cajas/exportar?'+new URLSearchParams(params()).toString();}
function imprimir(){window.print();}
</script>
<template>
<Head title="Reporte General de Cajas"/>
<AppLayout>
<main class="pagina" translate="no">
<div class="miga">REPORTES <span>›</span> <b>REPORTE DE CAJAS</b></div>
<h1>REPORTE GENERAL DE CAJAS</h1>
<form class="filtros" @submit.prevent="filtrar">
  <div class="campo"><label>SUCURSAL:</label><select v-model="form.sucursal"><option value="TODAS">TODAS</option><option v-for="s in sucursales" :key="s" :value="s">{{s}}</option></select></div>
  <div class="campo"><label>CÓDIGO CAJA:</label><input v-model="form.codigo" type="number" min="1" placeholder="Código Caja"/></div>
  <div class="campo"><label>USUARIOS/CAJEROS:</label><select v-model="form.usuario_id"><option value="">TODOS</option><option v-for="u in usuarios" :key="u.id" :value="String(u.id)">{{u.name}}</option></select></div>
  <div class="campo"><label>FECHA APERTURA:</label><input v-model="form.desde" type="date"/></div>
  <div class="campo"><label>FECHA CIERRE:</label><input v-model="form.hasta" type="date"/></div>
  <div class="campo"><label>ESTADO DE CAJA:</label><select v-model="form.estado"><option value="TODOS">TODOS</option><option value="ABIERTO">ABIERTA</option><option value="CERRADO">CERRADA</option></select></div>
  <div class="campo"><label>ACCIONES:</label><button type="submit" class="aplicar"><Search :size="13"/> APLICAR FILTROS</button></div>
</form>
<div class="herramientas"><button class="pdf" type="button" @click="imprimir"><FileText :size="13"/> PDF Cajas / Imprimir</button><button class="excel" v-if="can('Reportes.exportar')" type="button" @click="exportar"><FileSpreadsheet :size="13"/> Exportar CSV</button></div>
<div class="tabla"><table><thead><tr><th>⚙</th><th>CÓD.</th><th>USUARIO</th><th>F.APERTURA</th><th>F.CIERRE</th><th>S.INICIAL</th><th>T.VENTAS</th><th class="ingreso">INGRESOS</th><th class="egreso">EGRESOS</th><th>S.CAJA</th><th>ESTADO</th><th>PAGO QR</th></tr></thead>
<tbody>
<tr v-for="c in cajas.data" :key="c.id"><td>●</td><td>{{c.id}}</td><td>{{c.usuario}}</td><td>{{fecha(c.fecha_apertura)}}</td><td>{{fecha(c.fecha_cierre)}}</td><td class="num">{{dinero(c.monto_inicial)}}</td><td class="num">{{dinero(c.total_ventas)}}</td><td class="num verde">{{dinero(c.ingresos)}}</td><td class="num rojo">{{dinero(c.egresos)}}</td><td class="num">{{dinero(c.saldo)}}</td><td><span class="estado" :class="{abierta:c.estado==='ABIERTO'}">{{c.estado==='ABIERTO'?'ABIERTA':'CERRADA'}}</span></td><td class="num">{{dinero(c.pago_qr)}}</td></tr>
<tr v-if="!cajas.data.length"><td colspan="12" class="vacio">No hay cajas que coincidan con los filtros.</td></tr>
</tbody></table></div>
<p class="aviso">Los importes reflejan los movimientos registrados en cada caja. Los pagos QR se calculan a partir de los pedidos cobrados y vinculados a cada caja.</p>
<footer class="pie"><span>Mostrando {{cajas.data.length}} de {{cajas.total}} cajas</span><div><button :disabled="cajas.current_page<=1" @click="pagina(cajas.current_page-1)">‹ Anterior</button><span>{{cajas.current_page}} / {{cajas.last_page}}</span><button :disabled="cajas.current_page>=cajas.last_page" @click="pagina(cajas.current_page+1)">Siguiente ›</button></div></footer>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{min-height:100vh;padding:16px 18px;background:white;color:#25364a}.miga{font-size:10px;color:#94a3b8;margin-bottom:13px}.miga b{color:#334155}.miga span{padding:0 5px}h1{text-align:center;font-weight:800;font-size:17px;margin:8px 0 3px}.filtros{background:#7dfcaa;border:1px solid #12a96b;border-radius:4px;padding:9px;display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px}.campo{min-width:0}.campo label{display:block;font-size:10px;color:#155848;font-weight:800;margin-bottom:3px}.campo select,.campo input{width:100%;height:30px;border:1px solid #a9c5c6;border-radius:3px;background:white;padding:5px 7px;color:#334155;font-size:11px}.aplicar{display:flex;align-items:center;justify-content:center;gap:6px;background:#078ed3;color:white;width:100%;height:30px;border-radius:3px;font-size:10px}.herramientas{display:flex;justify-content:flex-end;gap:6px;margin:12px 0 5px}.herramientas button{display:flex;align-items:center;gap:5px;font-size:10px;padding:7px;border:1px solid;border-radius:3px}.pdf{color:#e55158;border-color:#e55158!important}.excel{color:#82b251;border-color:#82b251!important}.tabla{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:10px}th,td{border:1px solid #cbd5e1;padding:8px 5px;text-align:left}th{background:#04283a;color:white;white-space:nowrap;font-weight:800}th.ingreso,.verde{color:#0a9f32}th.egreso,.rojo{color:#e31539}.num{text-align:right}.estado{font-size:9px;font-weight:900;padding:4px;background:#f8cdd1;color:#bb223b}.estado.abierta{background:#c5eacb;color:#217039}.vacio{text-align:center;padding:30px;color:#64748b}.aviso{color:#64748b;font-size:10px;text-align:center;margin-top:9px}.pie{display:flex;justify-content:space-between;gap:10px;color:#64748b;font-size:11px;margin-top:12px}.pie>div{display:flex;gap:11px}.pie button{color:#0284c7}.pie button:disabled{color:#cbd5e1}@media(max-width:850px){.filtros{grid-template-columns:repeat(2,minmax(130px,1fr))}}@media(max-width:500px){.filtros{grid-template-columns:1fr}.pie{flex-wrap:wrap}}@media print{.miga,.filtros,.herramientas,.pie,.aviso{display:none!important}.pagina{padding:0}.tabla{overflow:visible}}
</style>
