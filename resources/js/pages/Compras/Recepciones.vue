<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, Save, X, Trash2 } from 'lucide-vue-next';

type Proveedor = { id: number; nombre: string };
type Producto = { id: number; codigo: string; nombre: string; stock_actual: string | number; precio_compra: string | number };
type Recepcion = { id: number; codigo: string; fecha: string; total: string | number; proveedor: string };
defineProps<{
    recepciones: { data: Recepcion[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
    proveedores: Proveedor[];
    productos: Producto[];
}>();

const abierto = ref(false);
const fechaLocal = () => {
    const d = new Date();
    const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
};
type Item = { producto_id: number | null; cantidad: number; precio_compra: number };
const form = useForm({
    proveedor_id: null as number | null,
    fecha: fechaLocal(),
    items: [] as Item[],
});
const total = computed(() => form.items.reduce((sum, item) => sum + Number(item.cantidad || 0) * Number(item.precio_compra || 0), 0));
function nuevo() {
    form.reset(); form.clearErrors();
    form.fecha = fechaLocal();
    form.items = [{ producto_id: null, cantidad: 1, precio_compra: 0 }];
    abierto.value = true;
}
function agregar() { form.items.push({ producto_id: null, cantidad: 1, precio_compra: 0 }); }
function productoCambiado(item: Item, productos: Producto[]) {
    const p = productos.find((p) => p.id === item.producto_id);
    item.precio_compra = p ? Number(p.precio_compra) : 0;
}
function guardar() {
    form.post('/compras/recepciones', {
        preserveScroll: true,
        onSuccess: () => { abierto.value = false; form.reset(); },
    });
}
function dinero(valor: number | string) {
    return Number(valor).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<template>
    <Head title="Recepción de Compras" />
    <AppLayout>
        <div class="pagina">
            <header class="titulo">
                <div><p class="ruta">COMPRAS / RECEPCIONES</p><h1>Recepción de Compras <span class="contador">{{ recepciones.total }}</span></h1></div>
                <button class="azul" type="button" @click="nuevo"><Plus :size="17" /> Nueva recepción</button>
            </header>
            <div class="tarjeta">
                <div class="tabla"><table>
                    <thead><tr><th>CÓDIGO</th><th>FECHA</th><th>PROVEEDOR</th><th>TOTAL</th></tr></thead>
                    <tbody>
                        <tr v-for="r in recepciones.data" :key="r.id"><td>{{ r.codigo }}</td><td>{{ r.fecha }}</td><td>{{ r.proveedor }}</td><td>Bs {{ dinero(r.total) }}</td></tr>
                        <tr v-if="!recepciones.data.length"><td colspan="4" class="vacio">Todavía no hay recepciones registradas.</td></tr>
                    </tbody>
                </table></div>
                <div class="pie"><span>Total: {{ recepciones.total }} registros</span><div class="paginacion">
                    <a v-if="recepciones.prev_page_url" :href="recepciones.prev_page_url">Anterior</a>
                    <span>Página {{ recepciones.current_page }} de {{ recepciones.last_page }}</span>
                    <a v-if="recepciones.next_page_url" :href="recepciones.next_page_url">Siguiente</a>
                </div></div>
            </div>
            <div v-if="abierto" class="fondo" @click.self="!form.processing && (abierto = false)">
                <div class="modal">
                    <header class="modal-titulo"><strong>DETALLE DE RECEPCIÓN DE COMPRAS</strong><button type="button" :disabled="form.processing" @click="abierto = false"><X :size="19" /></button></header>
                    <form @submit.prevent="guardar">
                        <div class="cuerpo">
                            <div class="fila">
                                <div><label>CÓDIGO INTERNO:</label><input value="Automático al guardar" readonly /></div>
                                <div><label>FECHA: *</label><input v-model="form.fecha" type="date" required /><small>{{ form.errors.fecha }}</small></div>
                                <div class="proveedor"><label>PROVEEDOR: *</label><select v-model="form.proveedor_id" required><option :value="null">Seleccione un proveedor</option><option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.nombre }}</option></select><small>{{ form.errors.proveedor_id }}</small></div>
                            </div>
                            <section class="seccion">
                                <h3>PRODUCTOS RECIBIDOS</h3>
                                <div v-for="(item, i) in form.items" :key="i" class="producto-fila">
                                    <div class="producto"><label>PRODUCTO: *</label><select v-model="item.producto_id" required @change="productoCambiado(item, productos)"><option :value="null">Seleccionar producto</option><option v-for="p in productos" :key="p.id" :value="p.id" :disabled="form.items.some((otro, idx) => idx !== i && otro.producto_id === p.id)">{{ p.codigo }} — {{ p.nombre }}</option></select></div>
                                    <div><label>CANTIDAD: *</label><input v-model.number="item.cantidad" type="number" min="0.01" step="0.01" required /></div>
                                    <div><label>P. COMPRA (Bs): *</label><input v-model.number="item.precio_compra" type="number" min="0" step="0.01" required /></div>
                                    <div class="subtotal"><label>SUBTOTAL</label><strong>Bs {{ dinero(Number(item.cantidad || 0) * Number(item.precio_compra || 0)) }}</strong></div>
                                    <button class="eliminar" type="button" :disabled="form.items.length === 1" title="Quitar producto" @click="form.items.splice(i, 1)"><Trash2 :size="16" /></button>
                                </div>
                                <button class="anadir" type="button" @click="agregar"><Plus :size="15" /> Agregar producto</button>
                                <small>{{ form.errors.items }}</small>
                            </section>
                            <div class="total">TOTAL: Bs {{ dinero(total) }}</div>
                            <p v-if="form.hasErrors" class="error">Revisa los datos. No se guardó la recepción.</p>
                        </div>
                        <footer class="botones"><button type="button" class="cancelar" :disabled="form.processing" @click="abierto = false">CANCELAR</button><button type="submit" class="azul" :disabled="form.processing"><Save :size="16" /> {{ form.processing ? 'GUARDANDO...' : 'GUARDAR' }}</button></footer>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.pagina{padding:22px;background:#f5f7fb;min-height:100vh;color:#253245}.ruta{font-size:12px;color:#94a3b8;margin-bottom:6px}.titulo{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}.titulo h1{font-size:23px;font-weight:700}.contador{background:#67a132;color:#fff;padding:2px 8px;border-radius:7px;font-size:18px}.azul{display:inline-flex;align-items:center;gap:7px;background:#078cd0;color:#fff;padding:10px 14px;border-radius:4px;cursor:pointer}.azul:disabled{opacity:.6}.tarjeta,.seccion{background:white;border:1px solid #e9edf2;border-radius:7px;padding:13px;box-shadow:0 3px 12px #0000000e}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:12px}thead{background:#052b40;color:white}th,td{text-align:left;padding:11px;border:1px solid #dee4eb}.vacio{text-align:center;padding:30px;color:#64748b}.pie{display:flex;justify-content:space-between;align-items:center;gap:10px;padding-top:14px;font-size:12px;color:#64748b}.paginacion{display:flex;gap:12px}.paginacion a{color:#078cd0}.fondo{position:fixed;inset:0;z-index:100;background:#0009;display:flex;align-items:center;justify-content:center;padding:12px}.modal{background:#fff;width:min(1050px,100%);max-height:95dvh;border-radius:8px;overflow:hidden;display:flex;flex-direction:column}.modal-titulo{display:flex;justify-content:space-between;padding:14px 18px;color:#485361}.modal form{display:flex;flex-direction:column;min-height:0;overflow:hidden}.cuerpo{overflow-y:auto;padding:8px 15px 16px}.fila{display:grid;grid-template-columns:1fr 1fr 2fr;gap:10px;margin-bottom:12px}.producto-fila{display:grid;grid-template-columns:3fr 1fr 1.2fr 1fr auto;gap:8px;align-items:end;margin-bottom:10px}.seccion h3{font-size:12px;font-weight:800;color:#273c61;border-bottom:2px solid #9b90ff;padding-bottom:8px;margin-bottom:12px}label{display:block;margin-bottom:4px;font-size:10px;font-weight:700;color:#35465a}input,select{width:100%;min-width:0;border:1px solid #bacbd7;border-radius:4px;padding:9px;color:#334155;background:white;font-size:12px}input:read-only{background:#f1f5f9}small,.error{font-size:11px;color:#c53030}.subtotal strong{display:block;padding:9px 0;font-size:12px;white-space:nowrap}.eliminar{padding:9px;color:#b91c1c}.eliminar:disabled{opacity:.3}.anadir{display:flex;gap:5px;align-items:center;margin-top:8px;color:#078cd0;font-size:12px;font-weight:700}.total{text-align:right;padding:13px 0;font-weight:800}.botones{position:sticky;bottom:0;background:white;border-top:1px solid #e5eaf0;padding:12px 15px;display:flex;gap:8px;justify-content:flex-end}.cancelar{background:#688693;color:white;border-radius:4px;padding:9px 13px}@media(max-width:800px){.fila{grid-template-columns:1fr 1fr}.proveedor{grid-column:1/-1}.producto-fila{grid-template-columns:1fr 1fr}.producto,.subtotal{grid-column:1/-1}.pie{flex-wrap:wrap}}@media(max-width:550px){.titulo{flex-wrap:wrap}.fila{grid-template-columns:1fr}.proveedor{grid-column:auto}.producto-fila{grid-template-columns:1fr}.producto,.subtotal{grid-column:auto}}
</style>
