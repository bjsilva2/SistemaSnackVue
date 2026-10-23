<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Search, Pencil, Power, X, Save, Truck } from 'lucide-vue-next';

type Proveedor = {
    id: number;
    codigo: string;
    nombre: string;
    tipo_proveedor: 'Natural' | 'Juridico';
    procedencia: 'NACIONAL' | 'EXTRANJERO';
    nit: string | null;
    telefono: string | null;
    contacto: string | null;
    celular_contacto: string | null;
    nombre_cheque: string | null;
    nombre_lcv: string | null;
    estado: boolean;
};
type Pagina = { data: Proveedor[]; current_page: number; last_page: number; total: number };
const props = defineProps<{ proveedores: Pagina; filtros: { buscar: string } }>();

const buscar = ref(props.filtros.buscar || '');
const modal = ref(false);
const editando = ref<number | null>(null);
const codigoActual = ref('Automático al guardar');
const form = useForm({
    nombre: '',
    tipo_proveedor: 'Natural' as 'Natural' | 'Juridico',
    procedencia: 'NACIONAL' as 'NACIONAL' | 'EXTRANJERO',
    nit: '',
    telefono: '',
    contacto: '',
    celular_contacto: '',
    nombre_cheque: '',
    nombre_lcv: '',
    estado: true as boolean,
});

function nuevo() {
    editando.value = null;
    codigoActual.value = 'Automático al guardar';
    form.reset();
    form.clearErrors();
    modal.value = true;
}
function editar(p: Proveedor) {
    form.reset();
    editando.value = p.id;
    codigoActual.value = p.codigo;
    form.nombre = p.nombre;
    form.tipo_proveedor = p.tipo_proveedor;
    form.procedencia = p.procedencia;
    form.nit = p.nit ?? '';
    form.telefono = p.telefono ?? '';
    form.contacto = p.contacto ?? '';
    form.celular_contacto = p.celular_contacto ?? '';
    form.nombre_cheque = p.nombre_cheque ?? '';
    form.nombre_lcv = p.nombre_lcv ?? '';
    form.estado = Boolean(p.estado);
    form.clearErrors();
    modal.value = true;
}
function cerrar() {
    if (form.processing) return;
    modal.value = false;
    form.clearErrors();
}
function guardar() {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => {
            modal.value = false;
            editando.value = null;
            form.reset();
            form.clearErrors();
        },
    };
    if (editando.value !== null) {
        form.put(`/compras/proveedores/${editando.value}`, opciones);
    } else {
        form.post('/compras/proveedores', opciones);
    }
}
function cambiarEstado(p: Proveedor) {
    if (!confirm(`¿Deseas ${p.estado ? 'desactivar' : 'activar'} el proveedor ${p.nombre}?`)) return;
    router.patch(`/compras/proveedores/${p.id}/estado`, {}, { preserveScroll: true });
}
function buscarProveedores() {
    router.get('/compras/proveedores', { buscar: buscar.value }, { preserveState: true, replace: true });
}
function cambiarPagina(numero: number) {
    router.get('/compras/proveedores', { buscar: buscar.value, page: numero }, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Proveedores" />
    <AppLayout>
        <div class="pagina">
            <div class="encabezado">
                <div>
                    <p class="ruta">Compras / Proveedores</p>
                    <h1>Administrar Proveedores <span class="contador">{{ proveedores.total }}</span></h1>
                </div>
                <button type="button" class="btn-azul" @click="nuevo"><Plus :size="17" /> Nuevo</button>
            </div>
            <div class="tarjeta">
                <form class="buscador" @submit.prevent="buscarProveedores">
                    <input v-model="buscar" placeholder="Buscar por código, nombre o CI/NIT..." />
                    <button type="submit" class="btn-azul"><Search :size="17" /> Buscar</button>
                </form>
                <div class="tabla-contenedor">
                    <table>
                        <thead><tr>
                            <th>CÓD.</th><th>NOMBRE COMPLETO</th><th>TIPO PROVEEDOR</th>
                            <th>PROCEDENCIA</th><th>CI/NIT</th><th>TELÉFONO</th><th>ESTADO</th><th>ACCIONES</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="proveedor in proveedores.data" :key="proveedor.id">
                                <td>{{ proveedor.codigo }}</td>
                                <td class="nombre">{{ proveedor.nombre }}</td>
                                <td>{{ proveedor.tipo_proveedor === 'Juridico' ? 'Jurídico' : 'Natural' }}</td>
                                <td>{{ proveedor.procedencia }}</td>
                                <td>{{ proveedor.nit || '—' }}</td>
                                <td>{{ proveedor.telefono || '—' }}</td>
                                <td><span :class="proveedor.estado ? 'activo' : 'inactivo'">{{ proveedor.estado ? 'ACTIVO' : 'INACTIVO' }}</span></td>
                                <td><div class="acciones">
                                    <button type="button" class="btn-editar" title="Editar" @click="editar(proveedor)"><Pencil :size="16" /></button>
                                    <button type="button" class="btn-estado" title="Cambiar estado" @click="cambiarEstado(proveedor)"><Power :size="16" /></button>
                                </div></td>
                            </tr>
                            <tr v-if="proveedores.data.length === 0"><td colspan="8" class="sin-datos">No existen proveedores registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="paginacion">
                    <span>Total: {{ proveedores.total }} registros</span>
                    <div class="controles-pagina">
                        <button type="button" :disabled="proveedores.current_page <= 1" @click="cambiarPagina(proveedores.current_page - 1)">Anterior</button>
                        <span>{{ proveedores.current_page }} / {{ proveedores.last_page }}</span>
                        <button type="button" :disabled="proveedores.current_page >= proveedores.last_page" @click="cambiarPagina(proveedores.current_page + 1)">Siguiente</button>
                    </div>
                </div>
            </div>

            <div v-if="modal" class="fondo-modal" @click.self="cerrar">
                <div class="modal" role="dialog" aria-modal="true" aria-label="Detalle proveedor">
                    <div class="modal-cabecera">
                        <h2>PROVEEDOR DETALLE <span v-if="editando !== null">— EDITAR</span></h2>
                        <button type="button" :disabled="form.processing" title="Cerrar" @click="cerrar"><X :size="19" /></button>
                    </div>
                    <form class="formulario-modal" @submit.prevent="guardar">
                        <div class="modal-cuerpo">
                            <div class="fila-superior">
                                <section class="seccion">
                                    <h3><Truck :size="16" /> PROVEEDOR DETALLE</h3>
                                    <div class="campos">
                                        <div><label>CÓDIGO:</label><input :value="codigoActual" readonly /></div>
                                        <div><label>NOMBRE COMPLETO: <b>*</b></label><input v-model="form.nombre" maxlength="200" required /><small>{{ form.errors.nombre }}</small></div>
                                        <div><label>TIPO PROVEEDOR: <b>*</b></label><select v-model="form.tipo_proveedor"><option value="Natural">Natural</option><option value="Juridico">Jurídico</option></select><small>{{ form.errors.tipo_proveedor }}</small></div>
                                        <div><label>PROCEDENCIA: <b>*</b></label><select v-model="form.procedencia"><option value="NACIONAL">Nacional</option><option value="EXTRANJERO">Extranjero</option></select><small>{{ form.errors.procedencia }}</small></div>
                                        <div><label>CI/NIT:</label><input v-model="form.nit" maxlength="30" /><small>{{ form.errors.nit }}</small></div>
                                        <div><label>TELÉFONO:</label><input v-model="form.telefono" maxlength="30" /><small>{{ form.errors.telefono }}</small></div>
                                        <div><label>CONTACTO:</label><input v-model="form.contacto" maxlength="150" /><small>{{ form.errors.contacto }}</small></div>
                                        <div><label>CEL. DEL CONTACTO:</label><input v-model="form.celular_contacto" maxlength="30" /><small>{{ form.errors.celular_contacto }}</small></div>
                                        <div><label>NOMBRE CHEQUE:</label><input v-model="form.nombre_cheque" maxlength="200" /><small>{{ form.errors.nombre_cheque }}</small></div>
                                        <div><label>NOMBRE LCV:</label><input v-model="form.nombre_lcv" maxlength="200" /><small>{{ form.errors.nombre_lcv }}</small></div>
                                        <div><label>ESTADO:</label><select v-model="form.estado"><option :value="true">ACTIVO</option><option :value="false">INACTIVO</option></select><small>{{ form.errors.estado }}</small></div>
                                    </div>
                                </section>
                            </div>
                            <p v-if="form.hasErrors" class="aviso-error">Revisa los campos marcados antes de guardar.</p>
                        </div>
                        <div class="botones-modal">
                            <span class="campos-obligatorios">CAMPOS REQUERIDOS: <b>*</b></span>
                            <div class="grupo-botones">
                                <button type="button" class="btn-cancelar" :disabled="form.processing" @click="cerrar"><X :size="16" /> CANCELAR</button>
                                <button type="submit" class="btn-azul" :disabled="form.processing"><Save :size="16" /> {{ form.processing ? 'GUARDANDO...' : 'GUARDAR' }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.pagina { padding:22px; background:#f5f7fb; min-height:100vh; color:#253245; }
.encabezado { display:flex; justify-content:space-between; align-items:center; gap:15px; margin-bottom:20px; }
.ruta { color:#94a3b8; font-size:12px; margin-bottom:6px; }
h1 { font-size:23px; font-weight:700; }
.contador { display:inline-block; padding:1px 9px; background:#67a132; color:#fff; border-radius:9px; font-size:20px; }
.btn-azul { background:#078cd0; color:white; border-radius:4px; padding:9px 15px; display:inline-flex; align-items:center; justify-content:center; gap:7px; cursor:pointer; }
.btn-azul:disabled { opacity:.55; cursor:not-allowed; }
.tarjeta { background:white; padding:18px; border-radius:9px; box-shadow:0 2px 12px #0000000c; }
.buscador { display:flex; justify-content:flex-end; gap:8px; margin-bottom:16px; }
.buscador input { width:310px; }
input, select, textarea { width:100%; min-width:0; background:white; color:#334155; border:1px solid #bacbd7; border-radius:4px; padding:8px 9px; font-size:12px; }
input:read-only { background:#f1f5f9; }
.tabla-contenedor { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:12px; text-align:left; }
thead { background:#052b40; color:white; }
th,td { padding:10px 8px; border:1px solid #dee4eb; white-space:nowrap; }
.nombre { font-weight:600; }
.direccion { max-width:230px; overflow:hidden; text-overflow:ellipsis; }
.activo,.inactivo { display:inline-block; padding:4px 8px; border-radius:5px; font-weight:700; }
.activo { background:#cce9ce; color:#206529; }
.inactivo { background:#fee2e2; color:#b91c1c; }
.acciones { display:flex; gap:6px; }
.btn-editar,.btn-estado { color:white; background:#078cd0; padding:8px; border-radius:4px; cursor:pointer; }
.btn-estado { background:#64748b; }
.sin-datos { text-align:center; padding:30px; color:#94a3b8; }
.paginacion,.controles-pagina { display:flex; justify-content:space-between; align-items:center; gap:12px; }
.paginacion { padding-top:16px; color:#64748b; font-size:12px; }
.controles-pagina button { border:1px solid #cbd5e1; padding:7px 10px; border-radius:5px; }
.controles-pagina button:disabled { opacity:.4; }
.fondo-modal { position:fixed; inset:0; z-index:100; background:#0009; display:flex; justify-content:center; align-items:center; padding:12px; }
.modal { background:#fefefe; border-radius:8px; width:100%; max-width:940px; max-height:95dvh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 15px 45px #0003; }
.modal-cabecera { flex:0 0 auto; display:flex; justify-content:space-between; align-items:center; padding:12px 16px 8px; }
.modal-cabecera h2 { font-size:14px; font-weight:700; color:#485361; }
.formulario-modal { display:flex; min-height:0; flex:1; flex-direction:column; }
.modal-cuerpo { overflow-y:auto; flex:1; min-height:0; padding:6px 14px 10px; }
.fila-superior { display:grid; grid-template-columns:1fr; gap:12px; align-items:start; }
.seccion { border:1px solid #e9edf2; border-radius:7px; padding:11px 13px; box-shadow:0 3px 12px #0000000e; margin-bottom:11px; background:white; min-width:0; }
.seccion h3 { display:flex; align-items:center; gap:7px; color:#273c61; font-size:11px; font-weight:800; border-bottom:2px solid #9b90ff; padding-bottom:7px; margin-bottom:9px; }
label { display:block; margin-bottom:3px; font-size:10px; font-weight:700; color:#35465a; }
label b,.campos-obligatorios b { color:#f03f4c; }
small,.aviso-error { color:#d72d3c; font-size:10px; }
.campos { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
.campo-ancho { grid-column:1/-1; }
.separacion { margin-top:14px; }
.botones-modal { flex:0 0 auto; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 15px; background:#fff; border-top:1px solid #e5eaf0; }
.campos-obligatorios { color:#475569; font-size:10px; font-weight:700; }
.grupo-botones { display:flex; gap:7px; }
.btn-cancelar { color:white; background:#688693; border-radius:4px; padding:9px 12px; display:inline-flex; align-items:center; gap:7px; }
@media(max-width:750px) { .campos { grid-template-columns:repeat(2,minmax(0,1fr)); } .encabezado { align-items:flex-start; } }
@media(max-width:550px) { .pagina { padding:10px; } h1 { font-size:18px; } .buscador input { width:100%; } .campos { grid-template-columns:1fr; } .modal { max-height:98dvh; } .botones-modal { flex-wrap:wrap; } .paginacion { flex-wrap:wrap; } }
</style>
