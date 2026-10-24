<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Search, MoreVertical, X, Save } from 'lucide-vue-next';
type Cliente={id:number;codigo:string;nombre_completo:string;ci_nit:string|null;telefono:string|null;domicilio:string|null;tipo_cliente:string;estado:boolean};
type Pagina={data:Cliente[];total:number;current_page:number;last_page:number};
const props=defineProps<{clientes:Pagina;filtros:{buscar:string};siguienteCodigo:string}>();
const buscar=ref(props.filtros.buscar||'');
const modal=ref(false);
const editando=ref<number|null>(null);
const codigo=ref(props.siguienteCodigo);
const form=useForm({nombre_completo:'',ci_nit:'',telefono:'',domicilio:'',tipo_cliente:'Normal',estado:true as boolean});
function nuevo(){editando.value=null;codigo.value=props.siguienteCodigo;form.reset();form.clearErrors();modal.value=true}
function editar(c:Cliente){editando.value=c.id;codigo.value=c.codigo;form.nombre_completo=c.nombre_completo;form.ci_nit=c.ci_nit||'';form.telefono=c.telefono||'';form.domicilio=c.domicilio||'';form.tipo_cliente=c.tipo_cliente;form.estado=c.estado;form.clearErrors();modal.value=true}
function guardar(){const opt={preserveScroll:true,onSuccess:()=>{modal.value=false;form.reset()}};if(editando.value)form.put('/ventas/clientes/'+editando.value,opt);else form.post('/ventas/clientes',opt)}
function filtrar(){router.get('/ventas/clientes',{buscar:buscar.value},{preserveState:true,replace:true})}
function pagina(n:number){router.get('/ventas/clientes',{buscar:buscar.value,page:n},{preserveState:true})}
</script>
<template>
<Head title="Listado de Clientes"/>
<AppLayout>
<main class="pagina">
<div class="ruta">Ventas &gt; <strong>Clientes</strong></div>
<section class="panel">
<header class="cabecera"><h1>Administrar Clientes <span class="cantidad">{{clientes.total}}</span></h1><button class="nuevo" type="button" @click="nuevo"><Plus :size="16"/> Nuevo</button></header>
<div class="barra"><form class="buscador" @submit.prevent="filtrar"><Search :size="13"/><input v-model="buscar" placeholder="Buscar cliente..." aria-label="Buscar cliente"/><button type="submit">Buscar</button></form></div>
<div class="tabla"><table>
<thead><tr><th>#</th><th>CÓDIGO ⇅</th><th>NOMBRE COMPLETO</th><th>CI/NIT</th><th>TELÉFONO</th><th>DOMICILIO</th><th>T. CLIENTE</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
<tbody>
<tr v-for="(c,i) in clientes.data" :key="c.id">
<td>{{(clientes.current_page-1)*10+i+1}}</td><td>{{c.codigo}}</td><td>{{c.nombre_completo}}</td><td>{{c.ci_nit||''}}</td><td>{{c.telefono||''}}</td><td>{{c.domicilio||''}}</td><td>{{c.tipo_cliente}}</td><td><span class="estado" :class="{inactivo:!c.estado}">{{c.estado?'ACTIVO':'INACTIVO'}}</span></td>
<td class="acciones-td"><button class="acciones" type="button" title="Editar cliente" aria-label="Editar cliente" @click="editar(c)"><MoreVertical :size="16"/></button></td>
</tr>
<tr v-if="!clientes.data.length"><td colspan="9" class="vacio">No se encontraron clientes registrados.</td></tr>
</tbody>
</table></div>
<div class="pie"><span>Mostrando {{clientes.data.length}} de {{clientes.total}} clientes</span><div class="paginas"><button :disabled="clientes.current_page<=1" @click="pagina(clientes.current_page-1)">‹</button><span class="pagina-actual">{{clientes.current_page}}</span><button :disabled="clientes.current_page>=clientes.last_page" @click="pagina(clientes.current_page+1)">›</button></div><span>10 por página</span></div>
</section>
<div v-if="modal" class="fondo" @click.self="!form.processing&&(modal=false)">
<section class="modal"><header class="cabecera-modal"><strong>{{editando?'EDITAR CLIENTE':'NUEVO CLIENTE'}}</strong><button type="button" :disabled="form.processing" @click="modal=false"><X :size="18"/></button></header>
<form @submit.prevent="guardar"><div class="cuerpo">
<div class="fila"><div><label>CÓDIGO:</label><input :value="codigo" readonly/></div><div><label>NOMBRE COMPLETO: *</label><input v-model="form.nombre_completo" maxlength="200" required/></div></div>
<div class="fila"><div><label>CI/NIT:</label><input v-model="form.ci_nit" maxlength="50"/></div><div><label>TELÉFONO:</label><input v-model="form.telefono" maxlength="50"/></div></div>
<div><label>DOMICILIO:</label><input v-model="form.domicilio" maxlength="250"/></div>
<div class="fila"><div><label>TIPO DE CLIENTE: *</label><select v-model="form.tipo_cliente"><option value="Normal">Normal</option><option value="Mayorista">Mayorista</option></select></div><div><label>ESTADO:</label><select v-model="form.estado"><option :value="true">ACTIVO</option><option :value="false">INACTIVO</option></select></div></div>
<div v-if="form.hasErrors" class="errores"><p v-for="(e,k) in form.errors" :key="k">{{e}}</p></div>
</div><footer><button type="button" class="cancelar" :disabled="form.processing" @click="modal=false">CANCELAR</button><button type="submit" class="guardar" :disabled="form.processing"><Save :size="15"/> {{form.processing?'GUARDANDO...':'GUARDAR'}}</button></footer></form>
</section></div>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{padding:18px 20px;background:#f6f7fa;min-height:100vh;color:#334155}.ruta{font-size:11px;color:#94a3b8;margin-bottom:13px}.ruta strong{color:#334155}.panel{background:white;padding:14px 13px;border-radius:3px}.cabecera{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:13px}.cabecera h1{font-size:19px;font-weight:700;color:#263238}.cantidad{background:#63a13e;border-radius:7px;color:white;font-size:16px;padding:4px 9px}.nuevo{display:flex;align-items:center;gap:4px;border:1px solid #2b9aff;color:#2b9aff;border-radius:3px;padding:7px 12px;font-size:12px}.barra{display:flex;justify-content:flex-end;margin-bottom:4px}.buscador{display:flex;align-items:center;gap:4px;border:1px solid #94a3b8;border-radius:3px;padding:3px 5px}.buscador input{font-size:11px;width:140px;outline:none;min-width:0}.buscador button{color:#078cd0;font-size:11px}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:11px}thead{background:#052b40;color:white}th,td{border:1px solid #d3d7dc;padding:7px 6px;text-align:left}th{white-space:nowrap;font-size:10px}td{background:#fff}.estado{background:#c8eacc;color:#256d30;font-size:10px;font-weight:800;padding:4px}.inactivo{background:#fee2e2;color:#a52727}.acciones-td{position:relative;width:75px}.acciones{background:#118ce5;color:white;border-radius:3px;padding:5px}.vacio{text-align:center;padding:26px;color:#64748b}.pie{display:flex;justify-content:center;align-items:center;gap:15px;padding-top:10px;font-size:11px;color:#64748b}.paginas{display:flex;align-items:center;gap:10px}.paginas button{color:#64748b}.paginas button:disabled{opacity:.3}.pagina-actual{background:#e0f2fe;padding:6px 9px;color:#0369a1}.fondo{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:#0009;z-index:100;padding:12px}.modal{background:white;border-radius:6px;width:min(680px,100%);max-height:95dvh;overflow-y:auto}.cabecera-modal{display:flex;justify-content:space-between;padding:14px 17px;border-bottom:1px solid #e2e8f0}.cuerpo{padding:16px;display:grid;gap:12px}.fila{display:grid;grid-template-columns:1fr 1fr;gap:12px}label{display:block;font-size:10px;font-weight:800;margin-bottom:5px}input:not(.buscador input),select{width:100%;border:1px solid #bdcad8;padding:9px;border-radius:4px;font-size:12px}input[readonly]{background:#f1f5f9}footer{display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #e2e8f0;padding:13px 16px}.cancelar{background:#688693;color:white;padding:9px 14px;border-radius:4px}.guardar{background:#078cd0;color:white;padding:9px 15px;border-radius:4px;display:flex;align-items:center;gap:6px}.errores{color:#b91c1c;font-size:11px}@media(max-width:580px){.pagina{padding:9px}.fila{grid-template-columns:1fr}.cabecera h1{font-size:16px}.pie{flex-wrap:wrap}}
</style>
