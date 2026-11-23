<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { Head, router } from '@inertiajs/vue3';
const { can } = usePermissions();
import { computed, reactive, ref } from 'vue';
import { Search, FileText, FileSpreadsheet, ChevronDown, ChevronRight, Settings2 } from 'lucide-vue-next';

type Venta = {
  id:number;codigo:string;usuario:string;fecha_venta:string;nombre_cliente:string|null;
  cliente_registrado:string|null;ci_nit:string|null;modalidad:string;metodo_pago:string|null;
  total:string|number;estado:string; cobrado_at:string|null;
};
type Pagina={data:Venta[];current_page:number;last_page:number;total:number;per_page:number};
type Filtros={sucursal:string;cliente:string;cajero:string;estado:string;metodo:string;tipo:string;desde:string;hasta:string};
const props=defineProps<{ventas:Pagina;totales:{total_ventas:string|number;pendiente:string|number;registros:number|string};filtros:Filtros;cajeros:{id:number;name:string}[]}>();
const form=reactive<Filtros>({...props.filtros});
const expandidos=ref<number[]>([]);
const detallesVisibles=computed(()=>props.ventas.data.length>0);
const tipoPedido=(v:string)=>v==='LLEVAR'?'PARA LLEVAR':v==='MESA'?'MESA':v==='YANGO'?'YANGO':v;
const fmt=(v:string|number|null|undefined)=>Number(v||0).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
function texto(v:string|null|undefined):string {
  if (!v) return '—';
  // Admitir fechas PostgreSQL, ISO y formatos locales sin convertir husos horarios.
  const iso=v.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?/);
  if(iso) return `${iso[3]}/${iso[2]}/${iso[1]} ${iso[4]}:${iso[5]}:${iso[6]||'00'}`;
  const local=v.match(/^(\d{2})\/(\d{2})\/(\d{4})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
  if(local) return `${local[1]}/${local[2]}/${local[3]} ${local[4]}:${local[5]}:${local[6]||'00'}`;
  return v;
}
const query=()=>Object.fromEntries(Object.entries(form).filter(([,v])=>v!=='')) as Record<string,string>;
function buscar(){router.get('/reportes/ventas',query(),{preserveState:true,replace:true,onSuccess:()=>{expandidos.value=[]}});}
function pagina(n:number){router.get('/reportes/ventas',{...query(),page:String(n)},{preserveState:true,replace:true,onSuccess:()=>{expandidos.value=[]}});}
function limpiar(){Object.assign(form,{sucursal:'TODAS',cliente:'',cajero:'',estado:'TODOS',metodo:'TODOS',tipo:'TODOS',desde:'',hasta:''});buscar();}
function abrirTodo(){expandidos.value=props.ventas.data.map(x=>x.id);}
function plegarTodo(){expandidos.value=[];}
function alternar(id:number){expandidos.value=expandidos.value.includes(id)?expandidos.value.filter(x=>x!==id):[...expandidos.value,id];}
function csv(){window.location.href='/reportes/ventas/exportar?'+new URLSearchParams(query()).toString();}
function imprimir(){window.print();}
</script>
<template>
<Head title="Reporte General de Ventas"/>
<AppLayout>
<main class="pagina notranslate" translate="no">
  <div class="miga">REPORTES <span>›</span> <strong>VENTAS</strong></div>
  <h1>REPORTE GENERAL DE VENTAS</h1>
  <form class="filtros" @submit.prevent="buscar">
    <div class="campo sucursal"><label>SUCURSAL:</label><select v-model="form.sucursal"><option value="TODAS">TODAS</option><option value="Sistema Snack">Sistema Snack</option></select></div>
    <div class="campo cliente"><label>BUSCAR CLIENTE:</label><input v-model="form.cliente" placeholder="Buscar cliente..."/></div>
    <div class="campo"><label>CAJERO:</label><select v-model="form.cajero"><option value="">TODOS</option><option v-for="c in cajeros" :key="c.id" :value="String(c.id)">{{c.name}}</option></select></div>
    <div class="campo"><label>ESTADO VENTA:</label><select v-model="form.estado"><option value="TODOS">TODOS</option><option value="COBRADO">VÁLIDA</option><option value="PENDIENTE">PENDIENTE</option></select></div>
    <div class="campo"><label>M. DE PAGO:</label><select v-model="form.metodo"><option value="TODOS">TODOS</option><option value="EFECTIVO">EFECTIVO</option><option value="QR">QR</option></select></div>
    <div class="campo"><label>FECHA INICIO:</label><input v-model="form.desde" type="date"/></div>
    <div class="campo"><label>FECHA FIN:</label><input v-model="form.hasta" type="date"/></div>
    <div class="campo"><label>TIPO PEDIDO:</label><select v-model="form.tipo"><option value="TODOS">TODOS</option><option value="MESA">MESA</option><option value="LLEVAR">PARA LLEVAR</option><option value="YANGO">YANGO</option></select></div>
    <div class="campo"><label>ACCIONES:</label><div class="acciones-filtro"><button class="buscar" type="submit"><Search :size="13"/> BUSCAR</button><button type="button" class="limpiar" @click="limpiar">LIMPIAR</button></div></div>
  </form>
  <section class="resumen">
    <div>TOTAL VENTAS: <b class="azul">{{fmt(totales.total_ventas)}} Bs.</b></div>
    <div>VENTAS CRÉDITO: <b class="rojo">0,00 Bs.</b></div>
    <div>TOTAL DESCUENTOS: <b class="rojo">0,00 Bs.</b></div>
    <div>TOTAL GENERAL: <b class="verde">{{fmt(totales.total_ventas)}} Bs.</b></div>
    <p>Los totales corresponden a todos los resultados del filtro. Ventas a crédito y descuentos no están implementados en los pedidos actuales.</p>
  </section>
  <div class="herramientas"><button type="button" class="azul-btn" @click="plegarTodo">− Contraer Todo</button><button type="button" class="azul-btn" @click="abrirTodo">＋ Expandir Todo</button><button type="button" class="pdf" v-if="can('Reportes.exportar')" @click="imprimir"><FileText :size="13"/> PDF / Imprimir</button><button type="button" class="excel" v-if="can('Reportes.exportar')" @click="csv"><FileSpreadsheet :size="13"/> CSV Exportar</button></div>
  <div class="tabla">
    <table>
      <thead><tr><th></th><th>CÓD</th><th>USUARIO</th><th>FECHA VENTA</th><th>CLIENTE</th><th>CI/NIT</th><th>TIPO PEDIDO</th><th>EFECTIVO</th><th>QR</th><th>T.VENTA</th><th>ESTADO</th><th><Settings2 :size="14"/></th></tr></thead>
      <tbody>
        <template v-for="v in ventas.data" :key="v.id">
          <tr><td><button type="button" class="flecha" @click="alternar(v.id)"><ChevronDown v-if="expandidos.includes(v.id)" :size="13"/><ChevronRight v-else :size="13"/></button></td>
            <td>{{v.codigo}}</td><td>{{v.usuario}}</td><td>{{texto(v.fecha_venta)}}</td><td>{{v.cliente_registrado||v.nombre_cliente||'Sin nombre'}}</td>
            <td>{{v.ci_nit||'—'}}</td><td><span class="modalidad">{{tipoPedido(v.modalidad)}}</span></td>
            <td class="numero">{{v.metodo_pago==='EFECTIVO'?fmt(v.total):'0,00'}}</td><td class="numero">{{v.metodo_pago==='QR'?fmt(v.total):'0,00'}}</td>
            <td class="numero">{{fmt(v.total)}}</td>
            <td><span class="estado" :class="v.estado==='COBRADO'?'valida':'pendiente'">{{v.estado==='COBRADO'?'VÁLIDA':'PENDIENTE'}}</span></td>
            <td><button type="button" class="ver" title="Ver detalle" @click="alternar(v.id)">⋮</button></td>
          </tr>
          <tr v-if="expandidos.includes(v.id)" class="detalle"><td colspan="12"><b>Pedido:</b> {{v.codigo}} · <b>Tipo:</b> {{tipoPedido(v.modalidad)}} · <b>Pago:</b> {{v.metodo_pago||'Sin cobrar'}} · <b>Fecha cobro:</b> {{texto(v.cobrado_at)}} · <b>Total:</b> Bs {{fmt(v.total)}}</td></tr>
        </template>
        <tr v-if="!detallesVisibles"><td class="sin-resultados" colspan="12"><div class="icono">ⓘ</div>Debe aplicar filtros para ver resultados en la tabla o no existen ventas coincidentes.</td></tr>
      </tbody>
    </table>
  </div>
  <div class="pie">
    <span>Mostrando {{ventas.data.length}} de {{ventas.total}} registros</span>
    <div><button type="button" :disabled="ventas.current_page<=1" @click="pagina(ventas.current_page-1)">‹ Anterior</button><span>{{ventas.current_page}} / {{ventas.last_page}}</span><button type="button" :disabled="ventas.current_page>=ventas.last_page" @click="pagina(ventas.current_page+1)">Siguiente ›</button></div>
  </div>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{padding:15px;background:#fff;min-height:100vh;color:#263443}.miga{font-size:10px;color:#94a3b8;margin-bottom:14px}.miga span{padding:0 5px}.miga strong{color:#475569}h1{text-align:center;font-size:17px;font-weight:800;margin:6px 0 2px}.filtros{background:#7afcaa;border:1px solid #0ea66b;border-radius:4px;padding:9px;display:grid;grid-template-columns:repeat(5,minmax(105px,1fr));gap:9px}.campo{min-width:0}.campo label{font-size:10px;display:block;color:#155348;font-weight:800;margin-bottom:3px}.campo input,.campo select{width:100%;height:29px;border:1px solid #98bbc0;background:white;border-radius:2px;padding:3px 7px;color:#334155;font-size:11px}.cliente{grid-column:span 1}.acciones-filtro{display:flex;gap:5px}.acciones-filtro button{font-size:10px;height:29px;border-radius:2px}.buscar{background:#039ad9;display:flex;align-items:center;justify-content:center;gap:8px;color:white;flex:1}.limpiar{background:#fff;padding:0 7px;color:#2563eb;border:1px solid #93c5fd}.resumen{display:flex;justify-content:space-around;gap:10px;flex-wrap:wrap;align-items:center;border:1px solid #475569;border-radius:12px;margin-top:15px;padding:9px 10px 5px;font-size:10px;font-weight:800}.resumen b{color:white;border-radius:20px;padding:3px 8px;font-size:10px}.resumen .azul{background:#188bd6}.resumen .rojo{background:#ed4a55}.resumen .verde{background:#78b731}.resumen p{font-size:10px;font-weight:400;color:#64748b;text-align:center;width:100%}.herramientas{display:flex;justify-content:flex-end;gap:5px;margin:13px 0 5px;flex-wrap:wrap}.herramientas button{font-size:10px;display:inline-flex;align-items:center;gap:5px;padding:7px;border:1px solid;border-radius:2px}.azul-btn{background:#0089c7;color:white;border-color:#0089c7!important}.pdf{color:#e04c54;border-color:#e04c54!important}.excel{color:#70af49;border-color:#70af49!important}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:10px}th,td{padding:7px 5px;border:1px solid #cfd5de;text-align:left}th{color:#fff;background:#04283b;white-space:nowrap;font-weight:800;font-size:10px}td{background:#fff}.numero{text-align:right;font-weight:700}.modalidad{background:#0da2e9;border-radius:10px;padding:3px 6px;color:white;font-weight:800;font-size:9px}.estado{font-size:9px;padding:4px 6px;font-weight:800}.estado.valida{background:#caecc9;color:#14812e}.estado.pendiente{background:#ffe8b5;color:#a66e02}.ver{background:#098fcb;border-radius:3px;color:white;padding:3px 8px}.flecha{color:#64748b}.detalle td{background:#f1f5f9;padding:12px 16px}.sin-resultados{text-align:center;color:#64748b;padding:34px}.icono{font-size:22px;color:#4e80aa}.pie{display:flex;justify-content:space-between;gap:10px;font-size:11px;color:#64748b;margin:11px 0}.pie>div{display:flex;gap:12px}.pie button{color:#008bd0}.pie button:disabled{color:#cbd5e1}@media(max-width:900px){.filtros{grid-template-columns:repeat(3,minmax(100px,1fr))}}@media(max-width:580px){.filtros{grid-template-columns:repeat(2,minmax(100px,1fr))}.resumen{justify-content:flex-start}}
@media print{.miga,.filtros,.herramientas,.pie,.flecha,.ver{display:none!important}.pagina{padding:0}h1{margin:12px}.tabla{overflow:visible}.resumen{margin-bottom:16px}}
</style>
