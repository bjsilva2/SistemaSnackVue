<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, Save, X, Search, Trash2 } from 'lucide-vue-next';

type Proveedor = { id: number; nombre: string };
type Producto = { id: number; nombre: string; codigo: string; stock_actual: number | string; precio_compra: number | string; precio_venta: number | string };
type Recepcion = { id: number; codigo: string; recibo_factura: string | null; fecha: string; total: number | string; estado: string; proveedor: string; solicitado_por: string | null; sucursal: string };
type Pagina = { data: Recepcion[]; total: number; current_page: number; last_page: number };
const props = defineProps<{ recepciones: Pagina; proveedores: Proveedor[]; productos: Producto[]; filtros: Record<string, string | number | null> }>();
const modal = ref(false);
const buscadorProducto = ref('');
const filtros = ref({
    recibo_factura: String(props.filtros.recibo_factura ?? ''),
    proveedor_id: String(props.filtros.proveedor_id ?? ''),
    fecha_inicio: String(props.filtros.fecha_inicio ?? ''),
    fecha_fin: String(props.filtros.fecha_fin ?? ''),
    estado: String(props.filtros.estado ?? ''),
});
const fechaLocal = () => { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };
type Item = { producto_id: number; cantidad: number; precio_compra: number; descuento: number; pv: number; pv2: number };
const form = useForm({
    proveedor_id: null as number | null,
    sucursal: 'Sistema Snack',
    fecha: fechaLocal(),
    recibo_factura: '',
    descuento: 0,
    tipo_pago: 'CONTADO' as 'CONTADO' | 'CREDITO',
    dias_credito: 0,
    metodo_pago: 'EFECTIVO' as 'EFECTIVO' | 'QR' | 'TRANSFERENCIA',
    a_cuenta: 0,
    efectivo: 0,
    enviar_a_banco: '',
    observaciones: '',
    items: [] as Item[],
});
const productosFiltrados = computed(() => {
    const q = buscadorProducto.value.trim().toLocaleLowerCase();
    if (!q) return [];
    return props.productos.filter(p => (p.nombre + ' ' + p.codigo).toLocaleLowerCase().includes(q)).slice(0, 8);
});
const monto = (v: number | string) => Number(v).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const subtotalLinea = (i: Item) => Math.max(0, Number(i.cantidad || 0) * Number(i.precio_compra || 0) - Number(i.descuento || 0));
const subtotal = computed(() => form.items.reduce((s, i) => s + subtotalLinea(i), 0));
const total = computed(() => Math.max(0, subtotal.value - Number(form.descuento || 0)));
const saldo = computed(() => Math.max(0, total.value - Number(form.a_cuenta || 0)));
const productoDe = (id: number) => props.productos.find(p => p.id === id);
function nueva() {
    form.reset(); form.clearErrors(); form.fecha = fechaLocal(); form.items = [];
    buscadorProducto.value = ''; modal.value = true;
}
function agregarProducto(p: Producto) {
    if (form.items.some(i => i.producto_id === p.id)) return;
    form.items.push({ producto_id: p.id, cantidad: 1, precio_compra: Number(p.precio_compra), descuento: 0, pv: Number(p.precio_venta), pv2: Number(p.precio_venta) });
    buscadorProducto.value = '';
}
function buscar() { router.get('/compras/ordenes', filtros.value, { preserveState: true, replace: true }); }
function pagina(numero: number) { router.get('/compras/ordenes', { ...filtros.value, page: numero }, { preserveState: true }); }
function guardar() {
    // Dejar un pago al contado por el total; el usuario puede cambiar a crédito para pagar parcialmente.
    if (form.tipo_pago === 'CONTADO') form.a_cuenta = Number(total.value.toFixed(2));
    if (form.metodo_pago === 'EFECTIVO' && form.efectivo < form.a_cuenta) form.efectivo = Number(form.a_cuenta.toFixed(2));
    form.post('/compras/recepciones', { preserveScroll: true, onSuccess: () => { modal.value = false; form.reset(); } });
}
</script>

<template>
    <Head title="Administrar Órdenes de Compras" />
    <AppLayout>
        <div class="pagina">
            <div class="ruta">COMPRAS &gt; LISTADO DE COMPRAS</div>
            <header class="encabezado">
                <h1>Administrar Órdenes de Compras <span class="contador">{{ recepciones.total }}</span></h1>
                <div class="encabezado-acciones">
                    <button class="borde" type="button" disabled title="Solicitud de compra pendiente de implementar"><Plus :size="15"/> NUEVA SOLICITUD OC (PRÓXIMAMENTE)</button>
                    <button class="borde verde" type="button" @click="nueva"><Plus :size="15"/> ORDEN DE COMPRA</button>
                </div>
            </header>
            <form class="filtros" @submit.prevent="buscar">
                <div><label>RECIBO/FACTURA:</label><input v-model="filtros.recibo_factura" placeholder="Nro Recibo" /></div>
                <div><label>PROVEEDOR:</label><select v-model="filtros.proveedor_id"><option value="">Seleccionar...</option><option v-for="p in proveedores" :key="p.id" :value="String(p.id)">{{ p.nombre }}</option></select></div>
                <div><label>FECHA INICIO:</label><input v-model="filtros.fecha_inicio" type="date"/></div>
                <div><label>FECHA FIN:</label><input v-model="filtros.fecha_fin" type="date"/></div>
                <div><label>ESTADO:</label><select v-model="filtros.estado"><option value="">TODOS</option><option value="REC-PAGADO">REC-PAGADO</option><option value="REC-PENDIENTE">REC-PENDIENTE</option></select></div>
                <div><label>ACCIONES:</label><button type="submit" class="azul"><Search :size="15"/> BUSCAR</button></div>
            </form>
            <div class="tabla"><table>
                <thead><tr><th>ID</th><th>REF/FAC</th><th>SOLICITADO POR</th><th>PROVEEDOR</th><th>MARCA</th><th>F. SOLICITUD/RECEPCIÓN</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
                <tbody>
                    <tr v-for="r in recepciones.data" :key="r.id">
                        <td>{{ r.id }}</td><td>{{ r.recibo_factura || '—' }}</td><td>{{ r.solicitado_por || '—' }}</td>
                        <td>{{ r.proveedor }}</td><td>NO DEFINIDO</td><td>{{ r.fecha }}</td>
                        <td><span class="estado">{{ r.estado }}</span></td><td><span title="Código de recepción">{{ r.codigo }}</span></td>
                    </tr>
                    <tr v-if="!recepciones.data.length"><td colspan="8" class="vacio">No hay compras registradas.</td></tr>
                </tbody>
            </table></div>
            <div class="paginacion"><span>Total: {{ recepciones.total }}</span><div><button type="button" :disabled="recepciones.current_page <= 1" @click="pagina(recepciones.current_page-1)">Anterior</button><span>{{ recepciones.current_page }} / {{ recepciones.last_page }}</span><button type="button" :disabled="recepciones.current_page >= recepciones.last_page" @click="pagina(recepciones.current_page+1)">Siguiente</button></div></div>
            <div v-if="modal" class="fondo" @click.self="!form.processing && (modal = false)">
                <div class="modal">
                    <div class="cabecera-modal"><strong>NUEVA ORDEN DE COMPRA - RECEPCIÓN</strong><button type="button" :disabled="form.processing" @click="modal = false"><X :size="19"/></button></div>
                    <form class="formulario" @submit.prevent="guardar">
                        <div class="cuerpo">
                            <div class="barra-verde">
                                <div><label>PROVEEDOR: *</label><select v-model="form.proveedor_id" required><option :value="null">Seleccione un proveedor</option><option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.nombre }}</option></select></div>
                                <div><label>SUCURSAL: *</label><input v-model="form.sucursal" required maxlength="150"/></div>
                            </div>
                            <div class="busqueda-producto">
                                <div class="campo-busqueda"><label>BUSCAR PRODUCTO | CÓD. ORIGEN | DESCRIPCIÓN:</label><input v-model="buscadorProducto" placeholder="Buscar producto por código o nombre..."/><div v-if="productosFiltrados.length" class="resultados"><button v-for="p in productosFiltrados" :key="p.id" type="button" @click="agregarProducto(p)">{{ p.codigo }} — {{ p.nombre }}</button></div></div>
                                <div><label>RECIBO/FACTURA:</label><input v-model="form.recibo_factura" maxlength="80" placeholder="N° RECIBO"/></div>
                                <div><label>DESCUENTO:</label><input v-model.number="form.descuento" type="number" min="0" step="0.01"/></div>
                            </div>
                            <div class="tabla detalle"><table>
                                <thead><tr><th>#</th><th>DESCRIPCIÓN/PRODUCTO</th><th>CANT.</th><th>PU</th><th>PU DESC.</th><th>DESC.</th><th>SUB. DESC.</th><th>SUBTOTAL</th><th>GAN%</th><th>PV</th><th>PV2</th><th>ACCIONES</th></tr></thead>
                                <tbody>
                                    <tr v-for="(item, i) in form.items" :key="item.producto_id">
                                        <td>{{ i+1 }}</td><td>{{ productoDe(item.producto_id)?.nombre }}</td>
                                        <td><input v-model.number="item.cantidad" type="number" min="0.01" step="0.01" required/></td>
                                        <td><input v-model.number="item.precio_compra" type="number" min="0" step="0.01" required/></td>
                                        <td>{{ monto(item.cantidad ? subtotalLinea(item) / item.cantidad : 0) }}</td>
                                        <td><input v-model.number="item.descuento" type="number" min="0" step="0.01" required/></td>
                                        <td>{{ monto(subtotalLinea(item)) }}</td>
                                        <td>{{ monto(subtotalLinea(item)) }}</td>
                                        <td>{{ item.precio_compra ? monto((item.pv / item.precio_compra - 1) * 100) : '0,00' }}</td>
                                        <td><input v-model.number="item.pv" type="number" min="0" step="0.01" required/></td>
                                        <td><input v-model.number="item.pv2" type="number" min="0" step="0.01" required/></td>
                                        <td><button type="button" @click="form.items.splice(i,1)"><Trash2 :size="15"/></button></td>
                                    </tr>
                                    <tr v-if="!form.items.length"><td colspan="12" class="vacio">Busca y agrega productos arriba.</td></tr>
                                </tbody>
                            </table></div>
                            <div class="totales"><div>Subtotal: <b>{{ monto(subtotal) }}</b></div><div>Descuento: <b>{{ monto(form.descuento) }}</b></div><div>Total a pagar: <b>{{ monto(total) }}</b></div><div>A cuenta: <b>{{ monto(form.a_cuenta) }}</b></div><div>Saldo: <b>{{ monto(saldo) }}</b></div></div>
                            <div class="pagos">
                                <div><label>TIPO DE PAGO:</label><select v-model="form.tipo_pago"><option value="CONTADO">PAGO AL CONTADO</option><option value="CREDITO">PAGO AL CRÉDITO</option></select></div>
                                <div><label>DÍAS CRÉDITO:</label><input v-model.number="form.dias_credito" type="number" min="0" :disabled="form.tipo_pago === 'CONTADO'"/></div>
                                <div><label>MÉTODO DE PAGO:</label><select v-model="form.metodo_pago"><option value="EFECTIVO">EFECTIVO</option><option value="QR">QR</option><option value="TRANSFERENCIA">TRANSFERENCIA</option></select></div>
                                <div><label>EFECTIVO:</label><input v-model.number="form.efectivo" type="number" min="0" step="0.01" :disabled="form.metodo_pago !== 'EFECTIVO'"/></div>
                                <div><label>A CUENTA:</label><input v-model.number="form.a_cuenta" type="number" min="0" step="0.01" :disabled="form.tipo_pago === 'CONTADO'"/></div>
                                <div><label>FECHA:</label><input v-model="form.fecha" type="date" required/></div>
                                <div><label>ENVIAR A BANCO:</label><input v-model="form.enviar_a_banco" maxlength="150"/></div>
                                <div class="observaciones"><label>OBSERVACIONES:</label><input v-model="form.observaciones"/></div>
                            </div>
                            <p v-if="form.hasErrors" class="error">Revisa los datos antes de guardar: <span v-for="(e, k) in form.errors" :key="k">{{ e }} · </span></p>
                        </div>
                        <div class="botones"><button type="button" class="cancelar" :disabled="form.processing" @click="modal = false"><X :size="15"/> CANCELAR</button><button type="submit" class="azul" :disabled="form.processing || !form.items.length"><Save :size="15"/> {{ form.processing ? 'GUARDANDO...' : 'GUARDAR' }}</button></div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.pagina{padding:20px;background:#f5f7fb;min-height:100vh;color:#253245}.ruta{color:#94a3b8;font-size:11px;margin-bottom:16px}.encabezado{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.encabezado h1{font-size:21px;font-weight:700}.contador{background:#67a132;color:white;border-radius:8px;padding:3px 9px}.encabezado-acciones{display:flex;gap:8px}.borde{display:inline-flex;gap:4px;align-items:center;border:1px solid #078cd0;color:#078cd0;padding:8px 11px;border-radius:4px;font-size:11px}.borde.verde{color:#74a952;border-color:#74a952}.borde:disabled{opacity:.55;cursor:not-allowed}.filtros,.barra-verde{background:#80ffb1;display:grid;grid-template-columns:1.1fr 1.3fr 1fr 1fr 1.1fr 1fr;gap:7px;padding:9px;border-radius:4px;margin-bottom:12px}.filtros>div,.barra-verde>div{min-width:0}.azul{display:inline-flex;gap:5px;align-items:center;justify-content:center;background:#078cd0;color:white;border-radius:3px;padding:8px 11px;cursor:pointer;width:100%}label{display:block;font-size:10px;font-weight:800;margin-bottom:3px;color:#35465a}input,select{width:100%;min-width:0;border:1px solid #bacbd7;border-radius:3px;padding:7px;font-size:11px;color:#334155;background:white}.tabla{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:11px}thead{background:#052b40;color:white}th,td{border:1px solid #dee4eb;padding:8px;text-align:left;white-space:nowrap}.estado{background:#cce9ce;color:#206529;font-weight:700;padding:3px}.vacio{text-align:center;padding:22px;color:#64748b}.paginacion{display:flex;justify-content:space-between;gap:8px;padding-top:14px;font-size:12px}.paginacion>div{display:flex;gap:12px;align-items:center}.paginacion button{color:#078cd0}.paginacion button:disabled{opacity:.4}.fondo{position:fixed;inset:0;background:#0009;z-index:100;display:flex;justify-content:center;align-items:center;padding:10px}.modal{background:white;border-radius:5px;width:min(1250px,100%);max-height:96dvh;display:flex;flex-direction:column;overflow:hidden}.cabecera-modal{display:flex;justify-content:center;padding:12px;position:relative;color:#485361}.cabecera-modal button{position:absolute;right:12px;top:10px}.formulario{display:flex;flex-direction:column;min-height:0}.cuerpo{overflow-y:auto;min-height:0;padding:8px 12px}.barra-verde{grid-template-columns:1fr 1fr}.busqueda-producto{display:grid;grid-template-columns:2.6fr 1fr 1fr;gap:8px;margin-bottom:10px}.campo-busqueda{position:relative}.resultados{position:absolute;top:100%;left:0;right:0;z-index:5;background:white;box-shadow:0 4px 12px #0003;max-height:180px;overflow-y:auto}.resultados button{display:block;text-align:left;padding:8px;width:100%;font-size:11px}.resultados button:hover{background:#e0f2fe}.detalle table{min-width:900px}.detalle td input{width:80px}.totales{display:flex;flex-direction:column;align-items:flex-end;gap:4px;padding:13px;font-size:12px}.totales div{min-width:180px;display:flex;justify-content:space-between;gap:15px}.pagos{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:12px}.observaciones{grid-column:span 2}.botones{border-top:1px solid #e5eaf0;background:white;display:flex;justify-content:flex-end;gap:8px;padding:10px}.botones .azul{width:auto}.cancelar{display:inline-flex;align-items:center;gap:6px;background:#688693;color:white;border-radius:3px;padding:8px 15px}.error{color:#be123c;font-size:11px;padding:6px}@media(max-width:950px){.filtros{grid-template-columns:repeat(3,1fr)}.encabezado{flex-wrap:wrap}.pagos{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.pagina{padding:10px}.filtros{grid-template-columns:repeat(2,1fr)}.barra-verde,.busqueda-producto,.pagos{grid-template-columns:1fr}.observaciones{grid-column:auto}.encabezado-acciones{flex-wrap:wrap}}
</style>
