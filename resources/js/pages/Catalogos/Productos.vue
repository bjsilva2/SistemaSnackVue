<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, onBeforeUnmount } from 'vue';
import { Plus, Search, Pencil, Power, X, Save, Package, Tags, DollarSign, FileText, ImagePlus, Trash2 } from 'lucide-vue-next';

type Catalogo = { id: number; nombre: string; abreviatura?: string };
type Producto = {
    id: number; codigo: string; nombre: string; tipo: 'PRODUCTO' | 'SERVICIO';
    grupo_id: number | null; marca_id: number | null; unidad_medida_id: number | null;
    grupo: Catalogo | null; marca: Catalogo | null; unidad_medida: Catalogo | null;
    procedencia: string | null; ubicacion: string | null; caso_uso: string | null;
    sucursal?: string | null; orden_menu?: number;
    precio_compra: string | number; precio_venta: string | number;
    stock_actual: string | number; stock_minimo: string | number;
    estado: boolean; observaciones: string | null; imagen_url?: string | null;
};
type Pagina = { data: Producto[]; current_page: number; last_page: number; total: number };

const props = defineProps<{
    productos: Pagina; grupos: Catalogo[]; marcas: Catalogo[];
    unidades: Catalogo[]; filtros: { buscar: string };
}>();

const buscar = ref(props.filtros.buscar || '');
const modal = ref(false);
const editando = ref<number | null>(null);
const codigoActual = ref('Automático al guardar');
const imagenPrevia = ref<string | null>(null);
let urlTemporal: string | null = null;

const form = useForm({
    nombre: '', tipo: 'PRODUCTO' as 'PRODUCTO' | 'SERVICIO',
    grupo_id: null as number | null, marca_id: null as number | null,
    unidad_medida_id: null as number | null, procedencia: '',
    ubicacion: '', caso_uso: '', sucursal: 'Sistema Snack', orden_menu: 999,
    precio_compra: 0, precio_venta: 0, stock_actual: 0, stock_minimo: 0,
    estado: true as boolean, observaciones: '', imagen: null as File | null,
    quitar_imagen: false as boolean,
});

function limpiarTemporal() {
    if (urlTemporal) URL.revokeObjectURL(urlTemporal);
    urlTemporal = null;
    imagenPrevia.value = null;
}

function cerrar() {
    if (form.processing) return;
    modal.value = false;
    limpiarTemporal();
    form.reset();
    form.clearErrors();
}

function nuevo() {
    form.reset();
    form.clearErrors();
    limpiarTemporal();
    editando.value = null;
    codigoActual.value = 'Automático al guardar';
    modal.value = true;
}

function editar(producto: Producto) {
    limpiarTemporal();
    form.reset();
    editando.value = producto.id;
    codigoActual.value = producto.codigo;
    form.nombre = producto.nombre;
    form.tipo = producto.tipo;
    form.grupo_id = producto.grupo_id;
    form.marca_id = producto.marca_id;
    form.unidad_medida_id = producto.unidad_medida_id;
    form.procedencia = producto.procedencia || '';
    form.ubicacion = producto.ubicacion || '';
    form.caso_uso = producto.caso_uso || '';
    form.sucursal = producto.sucursal || 'Sistema Snack';
    form.orden_menu = Number(producto.orden_menu ?? 999);
    form.precio_compra = Number(producto.precio_compra);
    form.precio_venta = Number(producto.precio_venta);
    form.stock_actual = Number(producto.stock_actual);
    form.stock_minimo = Number(producto.stock_minimo);
    form.estado = Boolean(producto.estado);
    form.observaciones = producto.observaciones || '';
    form.imagen = null;
    form.quitar_imagen = false;
    imagenPrevia.value = producto.imagen_url || null;
    form.clearErrors();
    modal.value = true;
}

function seleccionarImagen(event: Event) {
    const input = event.target as HTMLInputElement;
    const archivo = input.files?.[0];
    if (!archivo) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type) || archivo.size > 3 * 1024 * 1024) {
        alert('Selecciona una imagen JPG, PNG o WEBP de máximo 3 MB.');
        input.value = '';
        return;
    }
    limpiarTemporal();
    urlTemporal = URL.createObjectURL(archivo);
    imagenPrevia.value = urlTemporal;
    form.imagen = archivo;
    form.quitar_imagen = false;
    input.value = '';
}

function retirarImagen() {
    limpiarTemporal();
    form.imagen = null;
    form.quitar_imagen = true;
}

function guardar() {
    const opciones = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            modal.value = false;
            limpiarTemporal();
            form.reset();
            form.clearErrors();
        },
    };
    if (editando.value !== null) {
        // Laravel recibe el archivo por POST y procesa el método PUT simulado.
        form.transform((datos) => ({ ...datos, _method: 'put' })).post(`/catalogos/productos/${editando.value}`, opciones);
    } else {
        form.transform((datos) => datos).post('/catalogos/productos', opciones);
    }
}

function cambiarEstado(producto: Producto) {
    if (!confirm(`¿Deseas ${producto.estado ? 'desactivar' : 'activar'} este producto?`)) return;
    router.patch(`/catalogos/productos/${producto.id}/estado`, {}, { preserveScroll: true });
}

function buscarProductos() {
    router.get('/catalogos/productos', { buscar: buscar.value }, { preserveState: true, replace: true });
}
function cambiarPagina(numero: number) {
    router.get('/catalogos/productos', { buscar: buscar.value, page: numero }, { preserveState: true, preserveScroll: true });
}
function moneda(valor: string | number) {
    return Number(valor).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
onBeforeUnmount(limpiarTemporal);
</script>

<template>
    <Head title="Productos y Servicios" />
    <AppLayout>
        <div class="pagina">
            <div class="encabezado">
                <div>
                    <p class="ruta">Catálogos / Productos y Servicios</p>
                    <h1>Administrar Productos y Servicios <span class="contador">{{ productos.total }}</span></h1>
                </div>
                <button type="button" class="btn-azul" @click="nuevo"><Plus :size="17" /> Nuevo</button>
            </div>

            <div class="tarjeta">
                <form class="buscador" @submit.prevent="buscarProductos">
                    <input v-model="buscar" placeholder="Buscar por código o nombre..." />
                    <button class="btn-azul" type="submit"><Search :size="17" /> Buscar</button>
                </form>
                <div class="tabla-contenedor">
                    <table>
                        <thead><tr>
                            <th>CÓDIGO</th><th>DESCRIPCIÓN</th><th>IMAGEN</th><th>TIPO</th>
                            <th>MARCA</th><th>GRUPO</th><th>STOCK</th><th>P. COMPRA</th>
                            <th>P. VENTA</th><th>ESTADO</th><th>ACCIONES</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="producto in productos.data" :key="producto.id">
                                <td>{{ producto.codigo }}</td>
                                <td class="nombre-producto">{{ producto.nombre }}</td>
                                <td>
                                    <img v-if="producto.imagen_url" :src="producto.imagen_url" :alt="producto.nombre" class="miniatura" />
                                    <span v-else class="sin-foto">---</span>
                                </td>
                                <td>{{ producto.tipo }}</td>
                                <td>{{ producto.marca?.nombre || 'NO DEFINIDO' }}</td>
                                <td>{{ producto.grupo?.nombre || 'NO DEFINIDO' }}</td>
                                <td>
                                    <span v-if="producto.tipo === 'PRODUCTO'" class="stock" :class="{
                                        agotado: Number(producto.stock_actual) <= 0,
                                        minimo: Number(producto.stock_actual) > 0 && Number(producto.stock_actual) <= Number(producto.stock_minimo),
                                        disponible: Number(producto.stock_actual) > Number(producto.stock_minimo)
                                    }">{{ producto.stock_actual }}</span>
                                    <span v-else>—</span>
                                </td>
                                <td>Bs {{ moneda(producto.precio_compra) }}</td>
                                <td><strong>Bs {{ moneda(producto.precio_venta) }}</strong></td>
                                <td><span :class="producto.estado ? 'activo' : 'inactivo'">{{ producto.estado ? 'ACTIVO' : 'INACTIVO' }}</span></td>
                                <td>
                                    <div class="acciones">
                                        <button type="button" class="btn-editar" title="Editar" @click="editar(producto)"><Pencil :size="16" /></button>
                                        <button type="button" class="btn-estado" title="Cambiar estado" @click="cambiarEstado(producto)"><Power :size="16" /></button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!productos.data.length"><td colspan="11" class="sin-datos">No existen productos registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="paginacion">
                    <span>Total: {{ productos.total }} registros</span>
                    <div class="controles-pagina">
                        <button :disabled="productos.current_page <= 1" @click="cambiarPagina(productos.current_page - 1)">Anterior</button>
                        <span>{{ productos.current_page }} / {{ productos.last_page }}</span>
                        <button :disabled="productos.current_page >= productos.last_page" @click="cambiarPagina(productos.current_page + 1)">Siguiente</button>
                    </div>
                </div>
            </div>

            <div v-if="modal" class="fondo-modal" @click.self="cerrar">
                <div class="modal" role="dialog" aria-modal="true" aria-label="Detalle de productos">
                    <div class="modal-cabecera">
                        <h2>DETALLE PRODUCTOS <span v-if="editando !== null">— EDITAR</span></h2>
                        <button type="button" :disabled="form.processing" title="Cerrar" @click="cerrar"><X :size="19" /></button>
                    </div>
                    <form class="formulario-modal" @submit.prevent="guardar">
                        <div class="modal-cuerpo">
                            <div class="fila-superior">
                                <section class="seccion">
                                    <h3><Package :size="16" /> DATOS BÁSICOS</h3>
                                    <div class="campos-basicos">
                                        <div><label>TIPO: <b>*</b></label><select v-model="form.tipo"><option value="PRODUCTO">PRODUCTO</option><option value="SERVICIO">SERVICIO</option></select></div>
                                        <div class="descripcion"><label>DESCRIPCIÓN: <b>*</b></label><input v-model="form.nombre" required maxlength="200" placeholder="Nombre del producto/servicio" /><small>{{ form.errors.nombre }}</small></div>
                                        <div><label>CÓD. INTERNO:</label><input :value="codigoActual" readonly class="codigo-automatico" /></div>
                                        <div><label>ORDEN MENÚ:</label><input v-model.number="form.orden_menu" type="number" min="0" max="999999" /><small>{{ form.errors.orden_menu }}</small></div>
                                        <div><label>ESTADO:</label><select v-model="form.estado"><option :value="true">ACTIVO</option><option :value="false">INACTIVO</option></select></div>
                                    </div>
                                </section>
                                <section class="seccion">
                                    <h3><Tags :size="16" /> CLASIFICACIÓN</h3>
                                    <div class="campos-clasificacion">
                                        <div><label>MARCA:</label><select v-model="form.marca_id"><option :value="null">NO DEFINIDO</option><option v-for="marca in marcas" :key="marca.id" :value="marca.id">{{ marca.nombre }}</option></select><small>{{ form.errors.marca_id }}</small></div>
                                        <div><label>GRUPO:</label><select v-model="form.grupo_id"><option :value="null">NO DEFINIDO</option><option v-for="grupo in grupos" :key="grupo.id" :value="grupo.id">{{ grupo.nombre }}</option></select><small>{{ form.errors.grupo_id }}</small></div>
                                        <div><label>PROCEDENCIA:</label><input v-model="form.procedencia" maxlength="150" placeholder="No definido" /><small>{{ form.errors.procedencia }}</small></div>
                                        <div><label>U. MEDIDA:</label><select v-model="form.unidad_medida_id"><option :value="null">NO DEFINIDO</option><option v-for="unidad in unidades" :key="unidad.id" :value="unidad.id">{{ unidad.nombre }} ({{ unidad.abreviatura }})</option></select><small>{{ form.errors.unidad_medida_id }}</small></div>
                                    </div>
                                </section>
                            </div>
                            <section class="seccion">
                                <h3><DollarSign :size="16" /> INVENTARIO / PRECIOS</h3>
                                <div class="campos-inventario">
                                    <div><label>SUCURSAL:</label><input v-model="form.sucursal" maxlength="150" placeholder="Sistema Snack" /></div>
                                    <div><label>UBICACIÓN:</label><input v-model="form.ubicacion" maxlength="150" placeholder="Estante, pasillo..." /></div>
                                    <div><label>CASO DE USO:</label><input v-model="form.caso_uso" maxlength="200" placeholder="Descripción del uso..." /></div>
                                    <div><label>STOCK MÍNIMO:</label><input v-model.number="form.stock_minimo" type="number" min="0" step="0.01" :disabled="form.tipo === 'SERVICIO'" /><small>{{ form.errors.stock_minimo }}</small></div>
                                    <div><label>{{ editando !== null ? 'STOCK ACTUAL (SOLO LECTURA):' : 'STOCK INICIAL:' }}</label><input v-model.number="form.stock_actual" type="number" min="0" step="0.01" :readonly="editando !== null" :disabled="form.tipo === 'SERVICIO'" /><small>{{ form.errors.stock_actual }}</small></div>
                                    <div><label>P.U. COMPRA (Bs):</label><input v-model.number="form.precio_compra" type="number" min="0" step="0.01" required /><small>{{ form.errors.precio_compra }}</small></div>
                                    <div><label>P. VENTA (Bs): <b>*</b></label><input v-model.number="form.precio_venta" type="number" min="0" step="0.01" required /><small>{{ form.errors.precio_venta }}</small></div>
                                </div>
                            </section>
                            <div class="fila-inferior">
                                <section class="seccion">
                                    <h3><FileText :size="16" /> INFORMACIÓN ADICIONAL</h3>
                                    <label>OBSERVACIONES:</label>
                                    <textarea v-model="form.observaciones" rows="3" placeholder="Notas adicionales..."></textarea>
                                </section>
                                <section class="seccion">
                                    <h3><ImagePlus :size="16" /> IMAGEN DEL PRODUCTO</h3>
                                    <div class="foto-contenido">
                                        <div class="vista-foto">
                                            <img v-if="imagenPrevia" :src="imagenPrevia" alt="Vista previa" />
                                            <ImagePlus v-else :size="26" />
                                        </div>
                                        <div class="foto-acciones">
                                            <label class="btn-archivo" for="imagen-producto">Seleccionar imagen</label>
                                            <input id="imagen-producto" type="file" accept="image/png,image/jpeg,image/webp" class="input-archivo" @change="seleccionarImagen" />
                                            <button v-if="imagenPrevia" type="button" class="btn-quitar" @click="retirarImagen"><Trash2 :size="14" /> Quitar</button>
                                            <span class="ayuda">JPG, PNG o WEBP. Máximo 3 MB.</span>
                                            <small>{{ form.errors.imagen }}</small>
                                        </div>
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
.pagina { padding: 22px; background: #f5f7fb; min-height: 100vh; color: #253245; }
.encabezado { display:flex; justify-content:space-between; align-items:center; gap:15px; margin-bottom:20px; }
.ruta { color:#94a3b8; font-size:12px; margin-bottom:6px; }
h1 { font-size:23px; font-weight:700; }
.contador { display:inline-block; padding:1px 9px; background:#67a132; color:#fff; border-radius:9px; font-size:20px; }
.btn-azul { background:#078cd0; color:white; border-radius:4px; padding:9px 15px; display:inline-flex; align-items:center; justify-content:center; gap:7px; cursor:pointer; }
.btn-azul:disabled { opacity:.55; cursor:not-allowed; }
.tarjeta { background:white; padding:18px; border-radius:9px; box-shadow:0 2px 12px #0000000c; }
.buscador { display:flex; justify-content:flex-end; gap:8px; margin-bottom:16px; }
.buscador input { width:290px; }
input, select, textarea { width:100%; min-width:0; background:white; color:#334155; border:1px solid #bacbd7; border-radius:4px; padding:8px 9px; font-size:12px; }
input:read-only { background:#f1f5f9; }
.tabla-contenedor { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:12px; text-align:left; }
thead { background:#052b40; color:white; }
th, td { padding:10px 8px; border:1px solid #dee4eb; white-space:nowrap; }
.nombre-producto { font-weight:600; }
.miniatura { width:43px; height:43px; object-fit:cover; border-radius:4px; border:1px solid #dde4e8; }
.sin-foto { color:#94a3b8; }
.activo,.inactivo,.stock { display:inline-block; padding:4px 8px; border-radius:5px; font-weight:700; }
.activo,.disponible { background:#cce9ce; color:#206529; }
.inactivo,.agotado { background:#fee2e2; color:#b91c1c; }
.minimo { background:#ffcf43; color:#634400; }
.acciones { display:flex; gap:6px; }
.btn-editar,.btn-estado { color:white; background:#078cd0; padding:8px; border-radius:4px; cursor:pointer; }
.btn-estado { background:#64748b; }
.sin-datos { text-align:center; padding:30px; color:#94a3b8; }
.paginacion,.controles-pagina { display:flex; justify-content:space-between; align-items:center; gap:12px; }
.paginacion { padding-top:16px; color:#64748b; font-size:12px; }
.controles-pagina button { border:1px solid #cbd5e1; padding:7px 10px; border-radius:5px; }
.controles-pagina button:disabled { opacity:.4; }
.fondo-modal { position:fixed; inset:0; z-index:100; background:#0009; display:flex; justify-content:center; align-items:center; padding:12px; }
.modal { background:#fefefe; border-radius:8px; width:100%; max-width:1050px; max-height:95dvh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 15px 45px #0003; }
.modal-cabecera { flex:0 0 auto; display:flex; justify-content:space-between; align-items:center; padding:12px 16px 8px; }
.modal-cabecera h2 { font-size:14px; font-weight:700; color:#485361; }
.formulario-modal { display:flex; min-height:0; flex:1; flex-direction:column; }
.modal-cuerpo { overflow-y:auto; flex:1; min-height:0; padding:6px 14px 10px; }
.fila-superior { display:grid; grid-template-columns:minmax(0,1.9fr) minmax(230px,1fr); gap:12px; align-items:start; }
.fila-inferior { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.2fr); gap:12px; align-items:start; }
.seccion { border:1px solid #e9edf2; border-radius:7px; padding:11px 13px; box-shadow:0 3px 12px #0000000e; margin-bottom:11px; background:white; min-width:0; }
.seccion h3 { display:flex; align-items:center; gap:7px; color:#273c61; font-size:11px; font-weight:800; border-bottom:2px solid #9b90ff; padding-bottom:7px; margin-bottom:9px; }
.seccion h3 svg { color:#786cff; }
label { display:block; margin-bottom:3px; font-size:10px; font-weight:700; color:#35465a; }
label b,.campos-obligatorios b { color:#f03f4c; }
small,.aviso-error { color:#d72d3c; font-size:10px; }
.campos-basicos { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
.campos-basicos .descripcion { grid-column:span 2; }
.campos-clasificacion { display:grid; grid-template-columns:1fr; gap:7px; }
.campos-inventario { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:9px; }
.campos-inventario > div { grid-column:span 3; }
.campos-inventario > div:nth-child(1) { grid-column:span 4; }
.campos-inventario > div:nth-child(2) { grid-column:span 3; }
.campos-inventario > div:nth-child(3) { grid-column:span 3; }
.campos-inventario > div:nth-child(4) { grid-column:span 2; }
.campos-inventario > div:nth-child(5) { grid-column:span 4; }
.campos-inventario > div:nth-child(6) { grid-column:span 4; }
.campos-inventario > div:nth-child(7) { grid-column:span 4; }
.foto-contenido { display:flex; align-items:center; gap:12px; }
.vista-foto { display:flex; justify-content:center; align-items:center; flex:0 0 88px; width:88px; height:76px; color:#8ca0ae; background:#f3f7fa; border:1px dashed #a8b6c4; border-radius:5px; overflow:hidden; }
.vista-foto img { width:100%; height:100%; object-fit:cover; }
.foto-acciones { display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
.btn-archivo { padding:7px 9px; background:#078cd0; color:white; cursor:pointer; border-radius:4px; font-size:11px; }
.input-archivo { display:none; }
.btn-quitar { display:flex; align-items:center; gap:3px; padding:7px; color:#b91c1c; background:#fee2e2; border-radius:4px; font-size:11px; }
.ayuda { display:block; width:100%; font-size:10px; color:#64748b; }
.botones-modal { flex:0 0 auto; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 15px; background:#fff; border-top:1px solid #e5eaf0; }
.campos-obligatorios { color:#475569; font-size:10px; font-weight:700; }
.grupo-botones { display:flex; gap:7px; }
.btn-cancelar { color:white; background:#688693; border-radius:4px; padding:9px 12px; display:inline-flex; align-items:center; gap:7px; }
@media (max-width:800px) {
    .fila-superior { grid-template-columns:1fr; }
    .campos-basicos { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .campos-basicos .descripcion { grid-column:span 1; }
    .campos-inventario { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .campos-inventario > div, .campos-inventario > div:nth-child(n) { grid-column:span 1; }
}
@media (max-width:550px) {
    .pagina { padding:10px; }
    .encabezado { align-items:flex-start; }
    h1 { font-size:18px; }
    .buscador input { width:100%; }
    .modal { max-height:98dvh; }
    .modal-cuerpo { padding:5px 9px; }
    .fila-inferior { grid-template-columns:1fr; }
    .campos-basicos { grid-template-columns:1fr; }
    .campos-inventario { grid-template-columns:1fr 1fr; }
    .botones-modal { flex-wrap:wrap; }
    .paginacion { flex-wrap:wrap; }
}
</style>
