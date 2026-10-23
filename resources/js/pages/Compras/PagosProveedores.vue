<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, X, Save, MoreVertical } from 'lucide-vue-next';
type Compra={id:number;codigo:string;recibo_factura:string|null;fecha:string;sucursal:string;tipo_pago:string;metodo_pago:string;total:string|number;a_cuenta:string|number;saldo:string|number;estado:string;proveedor:string};
type Pagina={data:Compra[];current_page:number;last_page:number;total:number};
const props=defineProps<{compras:Pagina;proveedores:{id:number;nombre:string}[];sucursales:string[];filtros:Record<string,string|number|null>}>();
const f=ref({sucursal:String(props.filtros.sucursal??''),nro_orden:String(props.filtros.nro_orden??''),nro_factura:String(props.filtros.nro_factura??''),proveedor_id:String(props.filtros.proveedor_id??''),tipo_pago:String(props.filtros.tipo_pago??''),estado_pago:String(props.filtros.estado_pago??''),fecha_inicio:String(props.filtros.fecha_inicio??''),fecha_fin:String(props.filtros.fecha_fin??''),buscar:String(props.filtros.buscar??'')});
const modal=ref(false);const seleccion=ref<Compra|null>(null);
const form=useForm({recepcion_id:0,monto:0,metodo_pago:'EFECTIVO',observaciones:''});
const dinero=(v:number|string)=>Number(v).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
const filtrar=()=>router.get('/compras/pagos',f.value,{preserveState:true,replace:true});
const pagina=(n:number)=>router.get('/compras/pagos',{...f.value,page:n},{preserveState:true});
function abrir(c:Compra){seleccion.value=c;form.reset();form.clearErrors();form.recepcion_id=c.id;form.monto=Number(c.saldo);modal.value=true}
function guardar(){form.post('/compras/pagos',{preserveScroll:true,onSuccess:()=>{modal.value=false;form.reset()}})}
</script>
<template>
<Head title="Pago a Proveedores"/>
<AppLayout>
<main class="pagina">
<p class="ruta">Proveedores &gt; <b>Pago a Proveedores</b></p>
<h1>ADMINISTRAR PAGO A PROVEEDORES</h1>
<form class="filtros" @submit.prevent="filtrar">
<div><label>SUCURSAL:</label><select v-model="f.sucursal"><option value="">TODAS</option><option v-for="s in sucursales" :key="s" :value="s">{{s}}</option></select></div>
<div><label>NRO ORDEN:</label><input v-model="f.nro_orden" placeholder="Nro Orden"/></div>
<div><label>NRO FACTURA:</label><input v-model="f.nro_factura" placeholder="Nro Factura"/></div>
<div><label>PROVEEDOR:</label><select v-model="f.proveedor_id"><option value="">Todos</option><option v-for="p in proveedores" :key="p.id" :value="String(p.id)">{{p.nombre}}</option></select></div>
<div><label>TIPO DE PAGO:</label><select v-model="f.tipo_pago"><option value="">TODOS</option><option value="CONTADO">CONTADO</option><option value="CREDITO">CRÉDITO</option></select></div>
<div><label>ESTADO DE PAGO:</label><select v-model="f.estado_pago"><option value="">TODOS</option><option value="PAGADO">PAGADO</option><option value="PENDIENTE">PENDIENTE</option></select></div>
<div><label>FECHA INICIO:</label><input v-model="f.fecha_inicio" type="date"/></div>
<div><label>FECHA FIN:</label><input v-model="f.fecha_fin" type="date"/></div>
<div><label>ACCIONES:</label><button class="aplicar" type="submit"><Search :size="14"/> APLICAR FILTROS</button></div>
</form>
<div class="buscar-libre"><div class="busqueda"><Search :size="13"/><input v-model="f.buscar" placeholder="Buscar Pago a Proveedores" @keyup.enter="filtrar"/></div></div>
<div class="tabla"><table>
<thead><tr><th>OC_ID</th><th>FACTURA ⇅</th><th>PROVEEDOR</th><th>FECHA PAGO ⇅</th><th>T.COMPRAS</th><th>EFECTIVO</th><th>TRANSFERENCIA</th><th>SALDO</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
<tbody>
<tr v-for="c in compras.data" :key="c.id">
<td>{{c.id}}</td><td>{{c.recibo_factura||'No Registrado'}}</td><td>{{c.proveedor}}</td><td>{{c.fecha}}</td>
<td class="num">{{dinero(c.total)}}</td>
<td class="num">{{dinero(c.metodo_pago==='EFECTIVO'?c.a_cuenta:0)}}</td>
<td class="num">{{dinero(c.metodo_pago==='EFECTIVO'?0:c.a_cuenta)}}</td>
<td class="num">{{dinero(c.saldo)}}</td>
<td><span class="estado" :class="{pendiente:Number(c.saldo)>0}">{{Number(c.saldo)>0?'PENDIENTE':'PAGADO'}}</span></td>
<td><button type="button" class="acciones" :disabled="Number(c.saldo)<=0" :title="Number(c.saldo)>0?'Registrar pago':'Compra pagada'" @click="abrir(c)"><MoreVertical :size="15"/></button></td>
</tr>
<tr v-if="compras.data.length===0"><td colspan="10" class="vacio">No hay pagos a proveedores registrados.</td></tr>
</tbody></table></div>
<div class="paginacion"><span>Registros: {{compras.total}}</span><div><button :disabled="compras.current_page<=1" @click="pagina(compras.current_page-1)">Anterior</button><span>{{compras.current_page}} / {{compras.last_page}}</span><button :disabled="compras.current_page>=compras.last_page" @click="pagina(compras.current_page+1)">Siguiente</button></div></div>
<div v-if="modal&&seleccion" class="fondo" @click.self="!form.processing&&(modal=false)">
<section class="modal">
<header><strong>REGISTRAR PAGO A PROVEEDOR</strong><button :disabled="form.processing" @click="modal=false"><X :size="18"/></button></header>
<form @submit.prevent="guardar">
<p>Orden: <b>{{seleccion.codigo}}</b> · Proveedor: <b>{{seleccion.proveedor}}</b></p>
<p>Total de compra: <b>Bs {{dinero(seleccion.total)}}</b> · Saldo pendiente: <b>Bs {{dinero(seleccion.saldo)}}</b></p>
<div class="modal-campos"><div><label>MONTO A PAGAR (BS): *</label><input v-model.number="form.monto" type="number" min="0.01" step="0.01" :max="Number(seleccion.saldo)" required/></div><div><label>MÉTODO DE PAGO: *</label><select v-model="form.metodo_pago"><option value="EFECTIVO">EFECTIVO</option><option value="QR">QR</option><option value="TRANSFERENCIA">TRANSFERENCIA</option></select></div></div>
<div><label>OBSERVACIONES:</label><input v-model="form.observaciones" maxlength="1000"/></div>
<div v-if="form.hasErrors" class="errores"><p v-for="(error,k) in form.errors" :key="k">{{error}}</p></div>
<footer><button class="cancelar" type="button" :disabled="form.processing" @click="modal=false">CANCELAR</button><button class="aplicar" type="submit" :disabled="form.processing"><Save :size="15"/> GUARDAR PAGO</button></footer>
</form></section></div>
</main></AppLayout>
</template>
<style scoped>
.pagina{padding:16px 21px;background:#f8fafc;min-height:100vh;color:#334155}.ruta{font-size:11px;margin:5px 0 25px;color:#64748b}h1{text-align:center;font-weight:800;font-size:17px;margin-bottom:15px}.filtros{display:grid;grid-template-columns:1.4fr .9fr .9fr 2.4fr;gap:11px;padding:15px;background:#75ffad;border-radius:9px;box-shadow:0 2px 7px #0001}label{display:block;font-size:10px;color:#1e293b;font-weight:800;margin-bottom:5px}input,select{background:white;border:1px solid #b6c8d0;border-radius:3px;width:100%;padding:7px;font-size:11px;min-width:0;color:#334155}.aplicar{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#148eea;color:white;border-radius:3px;padding:8px 12px;font-size:11px;width:100%;cursor:pointer}.buscar-libre{display:flex;justify-content:flex-end;margin:40px 0 5px}.busqueda{display:flex;align-items:center;gap:5px;border:1px solid #91a5b4;background:white;padding:3px 6px;border-radius:3px}.busqueda input{border:0;width:180px;padding:2px}.tabla{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:11px}thead{background:#052b40;color:white}th,td{padding:7px;border:1px solid #d4d8dd;text-align:left;white-space:nowrap}td{background:#fff}.num{text-align:right}.estado{background:#c8edcb;color:#246b28;font-weight:800;padding:4px}.estado.pendiente{background:#ffedbf;color:#995e00}.acciones{background:#1194e8;color:white;border-radius:3px;padding:5px 7px;cursor:pointer}.acciones:disabled{opacity:.4;cursor:not-allowed}.vacio{text-align:center;padding:20px}.paginacion{display:flex;justify-content:space-between;padding:14px 0;font-size:12px}.paginacion div{display:flex;gap:12px}.paginacion button{color:#148eea}.paginacion button:disabled{opacity:.4}.fondo{position:fixed;inset:0;background:#0009;z-index:99;display:flex;justify-content:center;align-items:center;padding:12px}.modal{background:white;max-width:620px;width:100%;border-radius:7px;padding:16px}.modal header{display:flex;justify-content:space-between;margin-bottom:17px}.modal p{font-size:12px;margin:7px 0}.modal-campos{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:17px 0}.modal footer{display:flex;justify-content:flex-end;gap:9px;margin-top:16px}.modal footer .aplicar{width:auto}.cancelar{background:#6b8795;color:white;padding:9px 15px;border-radius:3px}.errores{color:#be123c}@media(max-width:850px){.filtros{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:500px){.pagina{padding:10px}.filtros,.modal-campos{grid-template-columns:1fr}.buscar-libre{margin-top:15px}}
</style>
