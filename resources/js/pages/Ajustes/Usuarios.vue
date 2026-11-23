<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, MoreVertical, Search, X } from 'lucide-vue-next';
type Usuario={id:number;name:string;email:string;rol_id:number|null;rol_nombre:string|null;created_at:string;updated_at:string};
type Pagina={data:Usuario[];total:number;current_page:number;last_page:number};
const props=defineProps<{usuarios:Pagina;roles:{id:number;nombre:string}[];filtros:{buscar:string};usuarioActual:number}>();
const buscar=ref(props.filtros.buscar||'');
const modal=ref(false);const editar=ref<number|null>(null);
const form=useForm({name:'',email:'',rol_id:'' as string,password:'',password_confirmation:''});
const titulo=computed(()=>editar.value?'EDITAR USUARIO':'NUEVO USUARIO');
function abrir(u?:Usuario){form.reset();form.clearErrors();editar.value=u?.id??null;if(u){form.name=u.name;form.email=u.email;form.rol_id=u.rol_id?String(u.rol_id):'';}modal.value=true;}
function guardar(){const opciones={preserveScroll:true,onSuccess:()=>{modal.value=false;form.reset()}};if(editar.value)form.put('/ajustes/usuarios/'+editar.value,opciones);else form.post('/ajustes/usuarios',opciones);}
function filtrar(){router.get('/ajustes/usuarios',{buscar:buscar.value},{preserveState:true,replace:true});}
function pagina(n:number){router.get('/ajustes/usuarios',{buscar:buscar.value,page:n},{preserveState:true});}
function fecha(x:string){const m=x?.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);return m?m[3]+'/'+m[2]+'/'+m[1]+', '+m[4]+':'+m[5]:x;}
</script>
<template><Head title="Administrar Usuarios"/><AppLayout><main class="pagina" translate="no">
<div class="miga">Configuraciones › <b>Usuarios</b></div><div class="panel">
<header><h1>Administrar Usuarios <span class="contador">{{usuarios.total}}</span></h1><button class="nuevo" @click="abrir()"><Plus :size="15"/> NUEVO USUARIO</button></header>
<form class="buscador" @submit.prevent="filtrar"><Search :size="13"/><input v-model="buscar" placeholder="Buscar..."/><button type="submit">Buscar</button></form>
<div class="tabla"><table><thead><tr><th>CÓDIGO ⇅</th><th>NOMBRE</th><th>CORREO ELECTRÓNICO</th><th>ROLES DE USUARIO</th><th>FECHA DE CREACIÓN</th><th>ÚLTIMA MODIFICACIÓN</th><th>ACCIONES</th></tr></thead>
<tbody><tr v-for="u in usuarios.data" :key="u.id"><td>{{u.id}}</td><td>{{u.name}}</td><td>{{u.email}}</td><td><span class="rol">{{u.rol_nombre||'SIN ROL'}}</span></td><td>{{fecha(u.created_at)}}</td><td>{{fecha(u.updated_at)}}</td><td><button class="opciones" title="Editar usuario" @click="abrir(u)"><MoreVertical :size="15"/></button></td></tr><tr v-if="!usuarios.data.length"><td colspan="7" class="vacio">Sin usuarios para mostrar.</td></tr></tbody></table></div>
<footer class="pie"><span>Mostrando {{usuarios.data.length}} de {{usuarios.total}} usuarios</span><div><button :disabled="usuarios.current_page<=1" @click="pagina(usuarios.current_page-1)">‹</button><span>{{usuarios.current_page}}</span><button :disabled="usuarios.current_page>=usuarios.last_page" @click="pagina(usuarios.current_page+1)">›</button></div></footer>
</div>
<div v-if="modal" class="fondo" @click.self="modal=false"><section class="modal"><header><h2>{{titulo}}</h2><button @click="modal=false"><X :size="17"/></button></header><form @submit.prevent="guardar">
<div class="cuerpo">
<label>NOMBRE COMPLETO: *</label><input v-model="form.name" required placeholder="Nombre del usuario"/><small v-if="form.errors.name" class="error">{{form.errors.name}}</small>
<label>CORREO ELECTRÓNICO: *</label><input v-model="form.email" type="email" required placeholder="Correo electrónico"/><small v-if="form.errors.email" class="error">{{form.errors.email}}</small>
<label>ROL DE USUARIO: *</label><select v-model="form.rol_id" required><option value="">Seleccione rol</option><option v-for="r in roles" :key="r.id" :value="String(r.id)">{{r.nombre}}</option></select><small v-if="form.errors.rol_id" class="error">{{form.errors.rol_id}}</small>
<label>CONTRASEÑA: {{editar?'(dejar vacía para conservar)':'*'}}</label><input v-model="form.password" type="password" :required="!editar" minlength="8" autocomplete="new-password"/><small v-if="form.errors.password" class="error">{{form.errors.password}}</small>
<label>CONFIRMAR CONTRASEÑA:</label><input v-model="form.password_confirmation" type="password" :required="!editar" autocomplete="new-password"/>
</div><footer><button class="cancelar" type="button" @click="modal=false">CANCELAR</button><button class="guardar" type="submit" :disabled="form.processing">{{form.processing?'GUARDANDO...':'GUARDAR'}}</button></footer></form></section></div>
</main></AppLayout></template>
<style scoped>
.pagina{padding:16px;background:#f8fafc;min-height:100vh;color:#263648}.miga{font-size:10px;color:#94a3b8;margin-bottom:15px}.miga b{color:#334155}.panel{background:#fff;padding:15px}.panel header{display:flex;align-items:center;justify-content:space-between;margin-bottom:13px}.panel h1{font-size:19px;font-weight:700}.contador{background:#60a339;color:white;padding:4px 8px;font-size:15px;border-radius:7px}.nuevo{border:1px solid #69aaff;border-radius:3px;color:#238adc;padding:7px;display:flex;gap:5px;align-items:center;font-size:11px}.buscador{display:flex;justify-content:flex-end;gap:6px;align-items:center;margin-bottom:7px}.buscador input{border:1px solid #b6c6d7;border-radius:3px;padding:6px;font-size:11px}.buscador button{font-size:10px;color:#168fda}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:10px}td,th{border:1px solid #cbd5e1;padding:7px 5px;text-align:left}th{background:#042a3d;color:white;white-space:nowrap}.rol{background:#3499ec;color:white;font-weight:800;padding:4px 6px;font-size:9px}.opciones{background:#0c91d8;color:white;border-radius:3px;padding:5px}.vacio{text-align:center;padding:20px}.pie{display:flex;justify-content:space-between;font-size:11px;color:#64748b;margin-top:10px}.pie>div{display:flex;gap:12px}.pie button{color:#098ad3}.pie button:disabled{opacity:.3}.fondo{position:fixed;inset:0;background:#0007;z-index:100;display:flex;align-items:center;justify-content:center;padding:15px}.modal{background:#fff;border-radius:5px;width:min(440px,100%);box-shadow:0 4px 22px #0002}.modal header{display:flex;align-items:center;justify-content:space-between;padding:15px;border-bottom:1px solid #e2e8f0}.modal h2{font-size:15px;font-weight:800}.cuerpo{padding:15px;display:flex;flex-direction:column;gap:5px}.cuerpo label{font-size:10px;font-weight:800;margin-top:7px}.cuerpo input,.cuerpo select{border:1px solid #b8c5d4;padding:8px;border-radius:3px;font-size:12px}.error{color:#be123c}.modal footer{display:flex;justify-content:flex-end;gap:9px;padding:15px}.cancelar,.guardar{padding:8px 14px;font-size:11px;color:white;border-radius:3px}.cancelar{background:#dc2626}.guardar{background:#62a338}
</style>