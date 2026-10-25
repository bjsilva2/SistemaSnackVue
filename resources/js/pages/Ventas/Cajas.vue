<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { RefreshCw, LockKeyholeOpen, MoreVertical, Search, X } from 'lucide-vue-next';
type Caja={id:number;usuario:string;sucursal:string;monto_inicial:string|number;ingresos:string|number;egresos:string|number;total_ventas:string|number;saldo:string|number;fecha_apertura:string;fecha_cierre:string|null;estado:string};
type Pagina={data:Caja[];current_page:number;last_page:number;total:number};
const props=defineProps<{cajas:Pagina;filtros:{buscar:string};cajaAbierta:boolean}>();
const buscar=ref(props.filtros.buscar||'');
const modal=ref(false);
const form=useForm({sucursal:'Sistema Snack',monto_inicial:0});
const formato=(v:string|number)=>Number(v).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
function filtrar(){router.get('/ventas/cajas',{buscar:buscar.value},{preserveState:true,replace:true})}
function pagina(n:number){router.get('/ventas/cajas',{buscar:buscar.value,page:n},{preserveState:true})}
function abrir(){form.reset();form.clearErrors();modal.value=true}
function guardar(){form.post('/ventas/cajas',{preserveScroll:true,onSuccess:()=>{modal.value=false;form.reset()}})}
function cerrar(c:Caja){if(confirm('¿Confirmas el cierre de la caja #'+c.id+'?'))router.patch('/ventas/cajas/'+c.id+'/cerrar',{}, {preserveScroll:true})}
</script>
<template>
<Head title="Administrar Cajas"/>
<AppLayout>
<main class="pagina">
<div class="miga">Cajas &gt; <strong>Listado de Cajas</strong></div>
<section class="panel">
<header class="encabezado"><h1>Administrar Cajas <span class="contador">{{cajas.total}}</span></h1><div class="botones-superiores"><button type="button" class="actualizar" title="Actualizar listado" @click="router.reload()"><RefreshCw :size="15"/></button><button type="button" class="apertura" :disabled="cajaAbierta" @click="abrir"><LockKeyholeOpen :size="15"/> APERTURAR CAJA</button></div></header>
<div class="barra"><form @submit.prevent="filtrar" class="buscador"><Search :size="13"/><input v-model="buscar" placeholder="Buscar Cajas"/><button type="submit">Buscar</button></form></div>
<div class="tabla"><table>
<thead><tr><th>CÓD. ⇅</th><th>USUARIO</th><th>S.INICIAL</th><th>INGRESOS</th><th>EGRESOS</th><th>T.VENTAS</th><th>SALDO</th><th>F.APERTURA</th><th>F.CIERRE</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
<tbody>
<tr v-for="c in cajas.data" :key="c.id"><td>{{c.id}}</td><td>{{c.usuario}}</td><td class="num">{{formato(c.monto_inicial)}}</td><td class="num"><b>{{formato(c.ingresos)}}</b></td><td class="num egreso"><b>{{formato(c.egresos)}}</b></td><td class="num"><b>{{formato(c.total_ventas)}}</b></td><td class="num">{{formato(c.saldo)}}</td><td>{{c.fecha_apertura}}</td><td>{{c.fecha_cierre||'—'}}</td><td><span class="estado" :class="{abierto:c.estado==='ABIERTO'}">{{c.estado}}</span></td><td><button type="button" class="acciones" :disabled="c.estado!=='ABIERTO'" :title="c.estado==='ABIERTO'?'Cerrar caja':'Caja cerrada'" @click="cerrar(c)"><MoreVertical :size="15"/></button></td></tr>
<tr v-if="!cajas.data.length"><td colspan="11" class="vacio">No se registraron cajas todavía.</td></tr>
</tbody></table></div>
<div class="pie"><span>Registros: {{cajas.total}}</span><div><button :disabled="cajas.current_page<=1" @click="pagina(cajas.current_page-1)">Anterior</button><span>{{cajas.current_page}} / {{cajas.last_page}}</span><button :disabled="cajas.current_page>=cajas.last_page" @click="pagina(cajas.current_page+1)">Siguiente</button></div></div>
</section>
<div v-if="modal" class="fondo" @click.self="!form.processing&&(modal=false)">
<section class="modal"><header><strong>APERTURA DE CAJA</strong><button type="button" :disabled="form.processing" @click="modal=false"><X :size="16"/></button></header>
<form @submit.prevent="guardar">
<div class="cuerpo"><label>SELECCIONE SUCURSAL:<span class="requerido">*</span></label><select v-model="form.sucursal" required><option value="Sistema Snack">Sistema Snack</option></select>
<label>MONTO INICIAL:<span class="requerido">*</span></label><input v-model.number="form.monto_inicial" type="number" min="0" step="0.01" required placeholder="0.00"/>
<p v-if="form.errors.monto_inicial" class="error">{{form.errors.monto_inicial}}</p><p v-if="form.errors.sucursal" class="error">{{form.errors.sucursal}}</p>
</div><footer><small>CAMPOS REQUERIDOS:<span class="requerido">*</span></small><div><button type="button" class="cancelar" :disabled="form.processing" @click="modal=false"><X :size="13"/> CANCELAR</button><button type="submit" class="confirmar" :disabled="form.processing"><LockKeyholeOpen :size="13"/> {{form.processing?'GUARDANDO...':'APERTURAR'}}</button></div></footer>
</form></section></div>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{background:#f6f7fb;min-height:100vh;padding:17px 20px;color:#334155}.miga{font-size:11px;color:#94a3b8;margin-bottom:15px}.miga strong{color:#334155}.panel{background:white;padding:15px 12px}.encabezado{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px}.encabezado h1{font-size:20px;font-weight:700;color:#263443}.contador{display:inline-block;background:#5f9e30;color:white;padding:3px 8px;border-radius:8px;font-size:15px}.botones-superiores{display:flex;gap:6px}.actualizar,.apertura{display:inline-flex;align-items:center;gap:5px;border:1px solid #328fe0;color:#2386d7;border-radius:3px;padding:7px 9px;font-size:11px}.apertura:disabled{opacity:.45;cursor:not-allowed}.barra{display:flex;justify-content:flex-end;margin-bottom:5px}.buscador{display:flex;align-items:center;gap:5px;border:1px solid #9eb3c5;border-radius:3px;padding:3px 5px}.buscador input{width:110px;font-size:10px;outline:0}.buscador button{font-size:10px;color:#288ad2}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:10px}th,td{padding:7px 6px;border:1px solid #cfd5dc;text-align:left}thead{background:#052b40;color:white}th{white-space:nowrap}td{background:white}.num{text-align:right}.egreso{color:#e11d48}.estado{display:inline-block;background:#f2c9cc;color:#b91c1c;font-weight:800;padding:4px 6px;font-size:9px}.estado.abierto{background:#d0efca;color:#237b37}.acciones{background:#0d88db;color:white;border-radius:3px;padding:5px}.acciones:disabled{opacity:.5;cursor:not-allowed}.vacio{text-align:center;padding:22px;color:#64748b}.pie{display:flex;justify-content:space-between;padding:12px 0;gap:9px;font-size:11px}.pie>div{display:flex;gap:10px}.pie button{color:#258cdb}.pie button:disabled{opacity:.3}.fondo{position:fixed;inset:0;background:#0008;z-index:100;display:flex;align-items:center;justify-content:center;padding:12px}.modal{width:min(400px,100%);border-radius:4px;background:white;box-shadow:0 6px 24px #0003}.modal header{display:flex;justify-content:space-between;padding:14px 17px;color:#4b5563;font-size:12px}.cuerpo{padding:18px}.cuerpo label{display:block;font-size:10px;font-weight:800;margin:7px 0 3px}.cuerpo input,.cuerpo select{width:100%;border:1px solid #bbc8d6;border-radius:3px;padding:7px;font-size:11px;background:white}.cuerpo input{text-align:right}.requerido{color:#e11d48}.error{color:#be123c;font-size:11px;margin-top:6px}.modal footer{display:flex;justify-content:space-between;align-items:end;gap:5px;flex-wrap:wrap;border-top:1px solid #e2e8f0;padding:12px 15px}.modal footer small{font-size:9px;font-weight:700}.modal footer>div{display:flex;gap:6px}.cancelar,.confirmar{display:flex;gap:4px;align-items:center;padding:8px 10px;border-radius:3px;font-size:11px;color:white}.cancelar{background:#dc2626}.confirmar{background:#5a9d37}@media(max-width:660px){.pagina{padding:9px}.encabezado{flex-wrap:wrap}.encabezado h1{font-size:17px}}
</style>
