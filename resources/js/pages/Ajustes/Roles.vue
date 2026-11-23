<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, MoreVertical, Search, X, ShieldCheck } from 'lucide-vue-next';
type Rol={id:number;nombre:string;permisos:string[]|string;created_at:string;updated_at:string};
type Pagina={data:Rol[];total:number;current_page:number;last_page:number};
const props=defineProps<{roles:Pagina;modulos:Record<string,string[]>;pendientes:Record<string,string[]>;usuariosPorRol:Record<string,number>}>();
const buscar=ref('');
const modal=ref(false);const permisosModal=ref(false);
const editando=ref<number|null>(null);const actual=ref<Rol|null>(null);
const menu=ref<number|null>(null);
const nombre=useForm({nombre:''});
const asignacion=useForm({permisos:[] as string[]});
const filtrados=computed(()=>props.roles.data.filter(r=>r.nombre.toLowerCase().includes(buscar.value.toLowerCase())));
const claves=computed(()=>{
 const nombres=[...new Set([...Object.keys(props.modulos),...Object.keys(props.pendientes)])];
 return nombres.map(nombre=>({nombre,acciones:props.modulos[nombre]||[],pendientes:props.pendientes[nombre]||[]}));
});
function fecha(x:string){const m=x?.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);return m?m[3]+'/'+m[2]+'/'+m[1]+', '+m[4]+':'+m[5]:x;}
function editar(r?:Rol){nombre.reset();nombre.clearErrors();editando.value=r?.id??null;nombre.nombre=r?.nombre??'';menu.value=null;modal.value=true;}
function guardar(){const o={preserveScroll:true,onSuccess:()=>modal.value=false};if(editando.value)nombre.put('/ajustes/roles/'+editando.value,o);else nombre.post('/ajustes/roles',o);}
function editarPermisos(r:Rol){actual.value=r;menu.value=null;asignacion.clearErrors();const permisos=typeof r.permisos==='string'?JSON.parse(r.permisos) as string[]:r.permisos;asignacion.permisos=Array.isArray(permisos)?permisos.filter(v=>v!=='*'):[];permisosModal.value=true;}
function marcar(clave:string,valor:boolean){asignacion.permisos=valor?[...new Set([...asignacion.permisos,clave])]:asignacion.permisos.filter(v=>v!==clave);}
function guardarPermisos(){if(!actual.value)return;asignacion.put('/ajustes/roles/'+actual.value.id+'/permisos',{preserveScroll:true,onSuccess:()=>permisosModal.value=false});}
function borrar(r:Rol){menu.value=null;if(confirm('¿Eliminar rol '+r.nombre+'?'))router.delete('/ajustes/roles/'+r.id,{preserveScroll:true});}
function pagina(n:number){router.get('/ajustes/roles',{page:n},{preserveState:true});}
</script>
<template>
<Head title="Administrar Roles"/><AppLayout><main class="pagina" translate="no">
<div class="miga">CONFIGURACIONES › <b>ROLES</b></div>
<div class="panel"><header><h1>Administrar Roles <span class="contador">{{roles.total}}</span></h1><button class="nuevo" @click="editar()"><Plus :size="14"/> NUEVO</button></header>
<div class="busqueda"><Search :size="13"/><input v-model="buscar" placeholder="Buscar..."/></div>
<div class="tabla"><table><thead><tr><th>CÓDIGO ⇅</th><th>NOMBRE ROL</th><th>FECHA CREACIÓN</th><th>ÚLTIMA MODIFICACIÓN</th><th>ACCIONES</th></tr></thead><tbody>
<tr v-for="r in filtrados" :key="r.id"><td>{{r.id}}</td><td>{{r.nombre}}</td><td>{{fecha(r.created_at)}}</td><td>{{fecha(r.updated_at)}}</td><td><div class="accion-wrap"><button class="opciones" @click="menu=menu===r.id?null:r.id"><MoreVertical :size="15"/></button>
<div v-if="menu===r.id" class="desplegable"><button @click="editar(r)">Editar</button><button @click="editarPermisos(r)" :disabled="r.nombre==='Administrador'">Editar Permisos de Rol</button><button @click="borrar(r)" :disabled="r.nombre==='Administrador'||Number(usuariosPorRol[String(r.id)]||0)>0">Eliminar</button></div></div></td></tr>
<tr v-if="!filtrados.length"><td colspan="5" class="vacio">No hay roles coincidentes.</td></tr>
</tbody></table></div>
<footer class="pie"><span>Mostrando {{filtrados.length}} de {{roles.total}} roles</span><div><button :disabled="roles.current_page<=1" @click="pagina(roles.current_page-1)">‹</button><span>{{roles.current_page}}</span><button :disabled="roles.current_page>=roles.last_page" @click="pagina(roles.current_page+1)">›</button></div></footer></div>
<div v-if="modal" class="fondo" @click.self="modal=false"><section class="modal"><header><h2>{{editando?'EDITAR ROL':'NUEVO ROL'}}</h2><button @click="modal=false"><X :size="16"/></button></header><form @submit.prevent="guardar"><div class="cuerpo"><label>NOMBRE DEL ROL: *</label><input v-model="nombre.nombre" required maxlength="80" placeholder="Ej.: Cajero"/><p class="error" v-if="nombre.errors.nombre">{{nombre.errors.nombre}}</p></div><footer><button class="cancelar" type="button" @click="modal=false">CANCELAR</button><button class="guardar" type="submit" :disabled="nombre.processing">GUARDAR</button></footer></form></section></div>
<div v-if="permisosModal" class="fondo permisos-fondo" @click.self="permisosModal=false"><section class="modal modal-permisos"><header><h2>Detalle Permisos</h2><button @click="permisosModal=false"><X :size="20"/></button></header>
<div class="cuerpo"><h3>Asignar Permisos a Rol: {{actual?.nombre}}</h3>
<p class="nota">Puedes activar o desactivar cualquier permiso. Las funciones todavía no desarrolladas permiten guardar su configuración, pero no podrán ejecutarse hasta que existan.</p>
<div v-for="modulo in claves" :key="modulo.nombre" class="grupo"><h4>{{modulo.nombre}}</h4><div class="checks">
<label v-for="accion in modulo.acciones" :key="modulo.nombre+'.'+accion"><input type="checkbox" :checked="asignacion.permisos.includes(modulo.nombre+'.'+accion)" @change="marcar(modulo.nombre+'.'+accion,($event.target as HTMLInputElement).checked)"/>{{accion.replaceAll('_',' ')}}</label>
<label v-for="accion in modulo.pendientes" :key="modulo.nombre+'.pendiente.'+accion" class="futuro" :title="'Permiso configurable. La función estará disponible cuando se implemente en SistemaSnackVue.'"><input type="checkbox" :checked="asignacion.permisos.includes(modulo.nombre+'.'+accion)" @change="marcar(modulo.nombre+'.'+accion,($event.target as HTMLInputElement).checked)"/>{{accion}}</label>
</div></div>
<p class="error" v-if="asignacion.errors.permisos">{{asignacion.errors.permisos}}</p>
</div><footer><button class="cancelar" @click="permisosModal=false">CANCELAR</button><button class="guardar" :disabled="asignacion.processing" @click="guardarPermisos"><ShieldCheck :size="14"/> GUARDAR PERMISOS</button></footer></section></div>
</main></AppLayout></template>
<style scoped>
.pagina{padding:17px;min-height:100vh;background:#f8fafc;color:#263749}.miga{font-size:10px;color:#94a3b8;margin-bottom:15px}.miga b{color:#334155}.panel{background:white;padding:15px}.panel header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.panel h1{font-size:19px;font-weight:700}.contador{background:#61a238;border-radius:7px;padding:4px 8px;color:#fff;font-size:14px}.nuevo{border:1px solid #7cbdff;color:#2583d8;border-radius:3px;display:flex;align-items:center;gap:4px;font-size:11px;padding:7px}.busqueda{display:flex;align-items:center;gap:5px;justify-content:flex-end;margin:5px 0}.busqueda input{border:1px solid #cbd5e1;padding:6px;border-radius:3px;font-size:11px}.tabla{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:10px}th,td{border:1px solid #cbd5e1;text-align:left;padding:8px 6px}th{background:#052a3f;color:white}.accion-wrap{position:relative}.opciones{background:#0a8fd4;border-radius:3px;color:white;padding:5px}.desplegable{position:absolute;right:0;top:100%;z-index:5;background:white;box-shadow:0 3px 14px #0002;width:170px;display:flex;flex-direction:column;border:1px solid #ddd}.desplegable button{padding:9px;text-align:left;font-size:11px}.desplegable button:hover{background:#e9f0f8}.desplegable button:disabled{color:#a5a8af;cursor:not-allowed}.pie{display:flex;justify-content:space-between;margin-top:12px;font-size:11px;color:#64748b}.pie>div{display:flex;gap:15px}.pie button{color:#0284c7}.pie button:disabled{opacity:.3}.vacio{text-align:center}.fondo{position:fixed;inset:0;z-index:100;background:#0007;display:flex;align-items:center;justify-content:center;padding:12px}.modal{background:white;border-radius:5px;width:min(440px,100%)}.modal header{display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #ddd}.modal h2{font-size:17px;font-weight:700}.modal .cuerpo{padding:15px;max-height:75vh;overflow:auto}.modal label{font-size:11px}.modal input:not([type=checkbox]){border:1px solid #cbd5e1;width:100%;padding:8px}.modal footer{display:flex;justify-content:flex-end;gap:10px;padding:15px}.modal footer button{display:flex;gap:5px;align-items:center;padding:9px;color:white;border-radius:4px;font-size:11px}.cancelar{background:#e53e45}.guardar{background:#62a33a}.error{color:#be123c;font-size:11px}.modal-permisos{width:min(950px,100%)}.modal-permisos h3{font-size:17px;font-weight:700;margin:8px 0 20px}.grupo{border-bottom:2px solid #ddd;padding:8px 0;display:grid;grid-template-columns:145px 1fr;gap:7px}.grupo h4{font-size:17px}.checks{display:grid;grid-template-columns:repeat(4,minmax(120px,1fr));gap:10px}.checks label{display:flex;align-items:center;gap:6px;text-transform:capitalize}.checks input{width:18px;height:18px;accent-color:#1688e0}.checks .futuro{color:#64748b}.checks .futuro input{opacity:1;cursor:pointer}.nota{font-size:11px;color:#64748b;background:#f1f5f9;padding:8px;margin:5px 0 12px}@media(max-width:680px){.grupo{grid-template-columns:1fr}.checks{grid-template-columns:repeat(2,minmax(100px,1fr))}}
</style>