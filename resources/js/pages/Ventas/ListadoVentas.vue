<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { Search, Eye, X, Receipt } from 'lucide-vue-next';

type Detalle={producto:string;cantidad:string|number;precio_unitario:string|number;subtotal:string|number};
type Venta={id:number;codigo:string;fecha:string;cobrado_at:string|null;modalidad:string;mesa_numero:number|null;nombre_cliente:string|null;cliente_registrado:string|null;usuario:string;total:string|number;estado:string;metodo_pago:string|null;detalles:Detalle[]};
type Pagina={data:Venta[];total:number;current_page:number;last_page:number};
type Filtros={buscar:string;estado:string;tipo:string;desde:string;hasta:string};
const props=defineProps<{ventas:Pagina;totalRegistros:number;filtros:Filtros}>();
const form=reactive<Filtros>({...props.filtros});
const abierto=ref<Venta|null>(null);
const dinero=(n:string|number)=>Number(n).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
function fecha(x:string|null){if(!x)return '—';const m=x.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?/);return m?m[3]+'/'+m[2]+'/'+m[1]+' '+m[4]+':'+m[5]+':'+(m[6]||'00'):x;}
const tipo=(v:Venta)=>v.modalidad==='MESA'?'MESA '+(v.mesa_numero??'—'):v.modalidad==='LLEVAR'?'PARA LLEVAR':v.modalidad;
const cliente=(v:Venta)=>v.cliente_registrado||v.nombre_cliente||'Sin nombre';
function params(){return Object.fromEntries(Object.entries(form).filter(([,v])=>v!=='').map(([k,v])=>[k,v]));}
function buscar(){router.get('/ventas/listado',params(),{replace:true,preserveState:true});}
function limpiar(){Object.assign(form,{buscar:'',estado:'TODOS',tipo:'TODOS',desde:'',hasta:''});buscar();}
function pagina(n:number){router.get('/ventas/listado',{...params(),page:n},{replace:true,preserveState:true});}
</script>

<template>
<Head title="Listado de Ventas"/>
<AppLayout>
<main class="pagina" translate="no">
  <div class="ruta">Ventas &gt; <strong>Listado de Ventas</strong></div>
  <section class="panel">
    <header class="cabecera"><h1>Listado de Ventas <span class="cantidad">{{totalRegistros}}</span></h1></header>
    <form class="filtros" @submit.prevent="buscar">
      <div class="campo"><label>BUSCAR VENTA:</label><input v-model="form.buscar" placeholder="Código, cliente o usuario..."/></div>
      <div class="campo"><label>TIPO DE PEDIDO:</label><select v-model="form.tipo"><option value="TODOS">TODOS</option><option value="MESA">MESA</option><option value="LLEVAR">PARA LLEVAR</option><option value="YANGO">YANGO (HISTÓRICO)</option></select></div>
      <div class="campo"><label>ESTADO:</label><select v-model="form.estado"><option value="TODOS">TODOS</option><option value="COBRADO">COBRADO</option><option value="PENDIENTE">PENDIENTE</option></select></div>
      <div class="campo"><label>FECHA INICIO:</label><input v-model="form.desde" type="date"/></div>
      <div class="campo"><label>FECHA FIN:</label><input v-model="form.hasta" type="date" :min="form.desde"/></div>
      <div class="campo"><label>ACCIONES:</label><div class="botones"><button type="submit" class="buscar"><Search :size="14"/> BUSCAR</button><button type="button" class="limpiar" @click="limpiar">LIMPIAR</button></div></div>
    </form>
    <div class="tabla"><table><thead><tr><th>#</th><th>CÓDIGO</th><th>FECHA</th><th>CLIENTE</th><th>USUARIO</th><th>TIPO PEDIDO</th><th>MÉTODO DE PAGO</th><th>TOTAL</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
    <tbody>
      <tr v-for="(v,i) in ventas.data" :key="v.id"><td>{{(ventas.current_page-1)*10+i+1}}</td><td>{{v.codigo}}</td><td>{{fecha(v.fecha)}}</td><td>{{cliente(v)}}</td><td>{{v.usuario}}</td><td>{{tipo(v)}}</td><td>{{v.metodo_pago||'—'}}</td><td class="num">Bs {{dinero(v.total)}}</td><td><span class="estado" :class="{pendiente:v.estado!=='COBRADO'}">{{v.estado==='COBRADO'?'COBRADO':'PENDIENTE'}}</span></td><td><button class="ver" type="button" title="Ver detalle de venta" @click="abierto=v"><Eye :size="16"/></button></td></tr>
      <tr v-if="!ventas.data.length"><td colspan="10" class="vacio">No se encontraron ventas con esos filtros.</td></tr>
    </tbody></table></div>
    <footer class="pie"><span>Mostrando {{ventas.data.length}} de {{ventas.total}} registros</span><div class="paginas"><button type="button" :disabled="ventas.current_page<=1" @click="pagina(ventas.current_page-1)">‹ Anterior</button><span>{{ventas.current_page}} / {{ventas.last_page}}</span><button type="button" :disabled="ventas.current_page>=ventas.last_page" @click="pagina(ventas.current_page+1)">Siguiente ›</button></div></footer>
  </section>
  <div v-if="abierto" class="fondo" @click.self="abierto=null"><section class="modal"><header><h2><Receipt :size="17"/> Detalle de Venta — {{abierto.codigo}}</h2><button type="button" title="Cerrar" @click="abierto=null"><X :size="19"/></button></header>
    <div class="cuerpo"><div class="resumen"><p><b>Cliente:</b> {{cliente(abierto)}}</p><p><b>Tipo:</b> {{tipo(abierto)}}</p><p><b>Fecha de pedido:</b> {{fecha(abierto.fecha)}}</p><p><b>Fecha de cobro:</b> {{fecha(abierto.cobrado_at)}}</p><p><b>Usuario:</b> {{abierto.usuario}}</p><p><b>Pago:</b> {{abierto.metodo_pago||'Pendiente'}}</p></div>
      <div class="tabla"><table><thead><tr><th>PRODUCTO</th><th>CANT.</th><th>P. UNITARIO</th><th>SUBTOTAL</th></tr></thead><tbody><tr v-for="(d,i) in abierto.detalles" :key="i"><td>{{d.producto}}</td><td class="num">{{Number(d.cantidad).toLocaleString('es-BO',{maximumFractionDigits:2})}}</td><td class="num">Bs {{dinero(d.precio_unitario)}}</td><td class="num">Bs {{dinero(d.subtotal)}}</td></tr></tbody></table></div><div class="total">TOTAL <strong>Bs {{dinero(abierto.total)}}</strong></div></div>
    <footer><button type="button" @click="abierto=null">CERRAR</button></footer>
  </section></div>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{background:#f6f7fa;padding:18px 20px;min-height:100vh;color:#334155}.ruta{color:#94a3b8;font-size:11px;margin-bottom:15px}.ruta strong{color:#334155}.panel{background:#fff;padding:16px;border-radius:4px}.cabecera{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px}.cabecera h1{font-weight:700;font-size:20px}.cantidad{padding:4px 9px;border-radius:7px;color:white;background:#63a13e;font-size:16px}.filtros{display:grid;grid-template-columns:repeat(3,minmax(150px,1fr));background:#7efaad;border:1px solid #19ac6a;padding:10px;gap:9px;border-radius:4px;margin-bottom:15px}.campo label{font-size:10px;font-weight:800;display:block;margin-bottom:4px}.campo input,.campo select{background:white;width:100%;height:32px;border:1px solid #a8bfd2;border-radius:3px;padding:5px 8px;font-size:11px}.botones{display:flex;gap:6px}.botones button{height:32px;padding:0 12px;font-size:11px;border-radius:3px}.buscar{display:flex;align-items:center;gap:6px;background:#078ddd;color:white;flex:1;justify-content:center}.limpiar{background:white;border:1px solid #8fb3ca;color:#0369a1}.tabla{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:11px}th,td{border:1px solid #d1d8e2;padding:9px 6px;text-align:left}th{background:#052b40;color:white;font-size:10px;white-space:nowrap}.num{text-align:right;white-space:nowrap}.estado{font-size:10px;font-weight:800;color:#256d30;background:#c8eacc;padding:5px}.estado.pendiente{background:#ffedc4;color:#925c08}.ver{background:#078ddd;border-radius:3px;color:white;padding:6px}.vacio{text-align:center;padding:28px;color:#94a3b8}.pie{display:flex;align-items:center;justify-content:center;gap:20px;margin-top:13px;font-size:11px;color:#64748b}.paginas{display:flex;gap:10px;align-items:center}.paginas button{color:#0284c7}.paginas button:disabled{opacity:.35}.fondo{position:fixed;inset:0;background:#0008;display:flex;justify-content:center;align-items:center;padding:14px;z-index:100}.modal{max-height:90dvh;overflow:auto;background:white;border-radius:6px;width:min(800px,100%)}.modal header{display:flex;align-items:center;justify-content:space-between;padding:15px;border-bottom:1px solid #e2e8f0}.modal h2{display:flex;gap:6px;align-items:center;font-weight:800;font-size:15px}.cuerpo{padding:15px}.resumen{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-bottom:16px;font-size:12px}.total{text-align:right;font-size:15px;font-weight:800;padding:13px 0}.total strong{color:#1d4ed8}.modal footer{border-top:1px solid #e2e8f0;display:flex;justify-content:end;padding:12px}.modal footer button{background:#078ddd;color:white;border-radius:4px;padding:8px 16px;font-size:12px}@media(max-width:800px){.filtros{grid-template-columns:repeat(2,minmax(125px,1fr))}}@media(max-width:500px){.pagina{padding:8px}.filtros,.resumen{grid-template-columns:1fr}.pie{flex-wrap:wrap}}
</style>