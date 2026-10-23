<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Search, ShoppingCart, PackageCheck } from 'lucide-vue-next';

type Recepcion = {
    id: number; codigo: string; recibo_factura: string | null;
    fecha: string; total: number | string; estado: string; proveedor: string;
    solicitado_por: string | null; sucursal: string;
};
type Pagina = { data: Recepcion[]; total: number; current_page: number; last_page: number };
const props = defineProps<{
    recepciones: Pagina;
    filtros: Record<string, string | number | null>;
    proveedores: { id: number; nombre: string }[];
}>();
const filtros = ref({
    recibo_factura: String(props.filtros.recibo_factura ?? ''),
    proveedor_id: String(props.filtros.proveedor_id ?? ''),
    fecha_inicio: String(props.filtros.fecha_inicio ?? ''),
    fecha_fin: String(props.filtros.fecha_fin ?? ''),
    estado: String(props.filtros.estado ?? ''),
});
const dinero = (valor: number | string) =>
    Number(valor).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function buscar() {
    router.get('/compras/recepciones', filtros.value, { preserveState: true, replace: true });
}
function pagina(numero: number) {
    router.get('/compras/recepciones', { ...filtros.value, page: numero }, { preserveState: true });
}
</script>

<template>
    <Head title="Recepción de Compras" />
    <AppLayout>
        <main class="pagina">
            <p class="miga">COMPRAS &gt; RECEPCIÓN DE COMPRAS</p>
            <div class="cabecera">
                <h1><PackageCheck :size="23" /> Recepción de Compras <span class="contador">{{ recepciones.total }}</span></h1>
                <Link class="nueva" href="/compras/ordenes"><ShoppingCart :size="16"/> IR A ÓRDENES DE COMPRA</Link>
            </div>
            <p class="descripcion">Aquí puedes consultar las compras recibidas que ya ingresaron al stock.</p>
            <form class="filtros" @submit.prevent="buscar">
                <div><label>RECIBO/FACTURA:</label><input v-model="filtros.recibo_factura" placeholder="Nro Recibo"/></div>
                <div><label>PROVEEDOR:</label><select v-model="filtros.proveedor_id"><option value="">Todos</option><option v-for="p in proveedores" :key="p.id" :value="String(p.id)">{{ p.nombre }}</option></select></div>
                <div><label>FECHA INICIO:</label><input v-model="filtros.fecha_inicio" type="date"/></div>
                <div><label>FECHA FIN:</label><input v-model="filtros.fecha_fin" type="date"/></div>
                <div><label>ESTADO:</label><select v-model="filtros.estado"><option value="">TODOS</option><option value="REC-PAGADO">REC-PAGADO</option><option value="REC-PENDIENTE">REC-PENDIENTE</option></select></div>
                <div><label>ACCIONES:</label><button class="buscar" type="submit"><Search :size="15"/> BUSCAR</button></div>
            </form>
            <div class="tabla"><table>
                <thead><tr><th>ID</th><th>CÓD. RECEPCIÓN</th><th>REF/FAC</th><th>FECHA RECEPCIÓN</th><th>PROVEEDOR</th><th>SUCURSAL</th><th>TOTAL</th><th>ESTADO</th></tr></thead>
                <tbody>
                    <tr v-for="r in recepciones.data" :key="r.id">
                        <td>{{ r.id }}</td><td><strong>{{ r.codigo }}</strong></td><td>{{ r.recibo_factura || '—' }}</td>
                        <td>{{ r.fecha }}</td><td>{{ r.proveedor }}</td><td>{{ r.sucursal }}</td>
                        <td>Bs {{ dinero(r.total) }}</td><td><span class="estado">{{ r.estado }}</span></td>
                    </tr>
                    <tr v-if="!recepciones.data.length"><td colspan="8" class="vacio">No se han recibido compras todavía.</td></tr>
                </tbody>
            </table></div>
            <div class="pie"><span>Total: {{ recepciones.total }} recepciones</span><div>
                <button type="button" :disabled="recepciones.current_page <= 1" @click="pagina(recepciones.current_page - 1)">Anterior</button>
                <span>{{ recepciones.current_page }} / {{ recepciones.last_page }}</span>
                <button type="button" :disabled="recepciones.current_page >= recepciones.last_page" @click="pagina(recepciones.current_page + 1)">Siguiente</button>
            </div></div>
        </main>
    </AppLayout>
</template>

<style scoped>
.pagina{min-height:100vh;background:#f5f7fb;padding:20px;color:#253245}.miga{font-size:11px;color:#94a3b8;margin-bottom:17px}.cabecera{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}.cabecera h1{font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px}.contador{background:#67a132;color:white;padding:2px 9px;border-radius:8px;font-size:18px}.nueva{border:1px solid #078cd0;border-radius:4px;padding:9px 11px;color:#078cd0;font-size:11px;display:inline-flex;align-items:center;gap:7px}.descripcion{font-size:12px;color:#64748b;margin:10px 0 16px}.filtros{display:grid;grid-template-columns:1.1fr 1.3fr 1fr 1fr 1.1fr 1fr;gap:7px;padding:9px;background:#80ffb1;border-radius:4px;margin-bottom:12px}.filtros>div{min-width:0}label{display:block;margin-bottom:3px;font-size:10px;font-weight:800;color:#35465a}input,select{width:100%;min-width:0;background:white;border:1px solid #bacbd7;border-radius:3px;padding:7px;color:#334155;font-size:11px}.buscar{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;background:#078cd0;color:white;border-radius:3px;padding:8px;font-size:12px}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:11px}thead{background:#052b40;color:white}th,td{white-space:nowrap;border:1px solid #dee4eb;padding:9px;text-align:left}.estado{background:#cce9ce;color:#206529;padding:3px;font-weight:700}.vacio{text-align:center;padding:24px;color:#64748b}.pie{display:flex;justify-content:space-between;gap:15px;padding-top:14px;font-size:12px}.pie>div{display:flex;gap:12px}.pie button{color:#078cd0}.pie button:disabled{opacity:.35}@media(max-width:950px){.filtros{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:540px){.pagina{padding:10px}.filtros{grid-template-columns:repeat(2,minmax(0,1fr))}.cabecera h1{font-size:18px}}
</style>
