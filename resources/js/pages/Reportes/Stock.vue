<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { Search, FileText, FileSpreadsheet } from 'lucide-vue-next';

type Producto={id:number;codigo:string;nombre:string;sucursal:string|null;marca:string|null;proveedor:string|null;stock_actual:string|number;stock_minimo:string|number;precio_compra:string|number;precio_venta:string|number;estado:boolean};
type Pagina={data:Producto[];current_page:number;last_page:number;total:number;per_page:number};
type Filtros={sucursal:string;buscar:string;marca_id:string|number;proveedor_id:string|number;stock_min:string|number;stock_max:string|number;sin_stock:boolean|number|string};
const props=defineProps<{productos:Pagina;totales:{productos:number;inversion:string|number;valor_venta:string|number;utilidad:string|number};filtros:Filtros;marcas:{id:number;nombre:string}[];proveedores:{id:number;nombre:string}[];sucursales:string[]}>();
const form=reactive<Filtros>({...props.filtros});
const dinero=(n:number|string|null|undefined)=>Number(n??0).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
const cantidad=(n:number|string)=>Number(n).toLocaleString('es-BO',{maximumFractionDigits:2});
const params=()=>Object.fromEntries(Object.entries(form).filter(([,v])=>v!==''&&v!==null&&v!==false).map(([k,v])=>[k,String(v===true?1:v)])) as Record<string,string>;
function filtrar(){router.get('/reportes/stock',params(),{replace:true,preserveState:true});}
function pagina(n:number){router.get('/reportes/stock',{...params(),page:String(n)},{replace:true,preserveState:true});}
function exportar(){window.location.href='/reportes/stock/exportar?'+new URLSearchParams(params()).toString();}
function imprimir(){window.print();}
function nivel(p:Producto){const n=Number(p.stock_actual);if(n<=0)return 'agotado';if(n<=Number(p.stock_minimo))return 'bajo';return 'normal';}
</script>
<template>
<Head title="Reporte General de Stock"/>
<AppLayout>
<main class="pagina" translate="no">
<div class="miga">REPORTES <span>›</span> <b>REPORTE DE STOCK</b></div>
<h1>REPORTE GENERAL DE STOCK</h1>
<form class="filtros" @submit.prevent="filtrar">
<div class="campo"><label>SUCURSAL:</label><select v-model="form.sucursal"><option value="TODAS">TODAS</option><option v-for="s in sucursales" :key="s" :value="s">{{s}}</option></select></div>
<div class="campo ancho"><label>NOMBRE/DESCRIPCIÓN:</label><input v-model="form.buscar" placeholder="Buscar producto o código..."/></div>
<div class="campo"><label>MARCA:</label><select v-model="form.marca_id"><option value="">TODAS</option><option v-for="m in marcas" :key="m.id" :value="String(m.id)">{{m.nombre}}</option></select></div>
<div class="campo"><label>PROVEEDOR:</label><select v-model="form.proveedor_id"><option value="">TODOS</option><option v-for="p in proveedores" :key="p.id" :value="String(p.id)">{{p.nombre}}</option></select></div>
<div class="campo"><label>STOCK MÍN.:</label><input v-model="form.stock_min" type="number" min="0" step="0.01" placeholder="Mínimo"/></div>
<div class="campo"><label>STOCK MÁXIMO:</label><input v-model="form.stock_max" type="number" min="0" step="0.01" placeholder="Máximo"/></div>
<div class="campo"><label>PRODUCTOS:</label><label class="sin-stock"><span>SIN STOCK</span><input v-model="form.sin_stock" type="checkbox" true-value="1" false-value="0"/></label></div>
<div class="campo"><label>ACCIONES:</label><button class="aplicar" type="submit"><Search :size="13"/> APLICAR FILTROS</button></div>
</form>
<div class="nota">Los precios de este reporte son informativos. Los cambios de precios deben realizarse desde Catálogos → Productos y Servicios.</div>
<section class="resumen"><div>TOTAL INVERSIÓN BRUTA: <span class="azul">{{dinero(totales.inversion)}} Bs.</span></div><div>TOTAL VALOR VENTA: <span class="rojo">{{dinero(totales.valor_venta)}} Bs.</span></div><div>TOTAL UTILIDAD POTENCIAL: <span class="verde">{{dinero(totales.utilidad)}} Bs.</span></div></section>
<div class="herramientas"><button type="button" class="pdf" @click="imprimir"><FileText :size="13"/> PDF Stock / Imprimir</button><button type="button" class="excel" @click="exportar"><FileSpreadsheet :size="13"/> Exportar CSV</button></div>
<div class="tabla"><table><thead><tr><th>CÓD</th><th>MARCA</th><th>PROVEEDOR</th><th>DESCRIPCIÓN/PRODUCTO</th><th>ESTADO</th><th>STOCK</th><th>P.COMPRA</th><th>P.VENTA</th><th>T.COMPRA</th><th>T.VENTA</th><th>UTILIDAD</th></tr></thead><tbody>
<tr v-for="p in productos.data" :key="p.id"><td>{{p.codigo}}</td><td>{{p.marca||'NO DEFINIDO'}}</td><td>{{p.proveedor||'SIN PROVEEDOR'}}</td><td>{{p.nombre}}</td><td>{{p.estado?'ACTIVO':'INACTIVO'}}</td><td class="num"><span class="stock" :class="nivel(p)">{{cantidad(p.stock_actual)}}</span></td><td class="num">{{dinero(p.precio_compra)}}</td><td class="num">{{dinero(p.precio_venta)}}</td><td class="num">{{dinero(Number(p.stock_actual)*Number(p.precio_compra))}}</td><td class="num">{{dinero(Number(p.stock_actual)*Number(p.precio_venta))}}</td><td class="num">{{dinero(Number(p.stock_actual)*(Number(p.precio_venta)-Number(p.precio_compra)))}}</td></tr>
<tr v-if="!productos.data.length"><td colspan="11" class="vacio">No hay productos que coincidan con los filtros.</td></tr>
</tbody></table></div>
<footer class="pie"><span>Mostrando {{productos.data.length}} de {{productos.total}} productos</span><div><button :disabled="productos.current_page<=1" @click="pagina(productos.current_page-1)">‹ Anterior</button><span>{{productos.current_page}} / {{productos.last_page}}</span><button :disabled="productos.current_page>=productos.last_page" @click="pagina(productos.current_page+1)">Siguiente ›</button></div></footer>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{min-height:100vh;background:#fff;padding:15px;color:#26364a}.miga{font-size:10px;color:#94a3b8;margin-bottom:13px}.miga b{color:#334155}.miga span{padding:0 6px}h1{text-align:center;font-size:17px;font-weight:800;margin:5px 0 3px}.filtros{background:#80fdb0;border:1px solid #11ac70;border-radius:4px;padding:10px;display:grid;grid-template-columns:repeat(4,minmax(135px,1fr));gap:10px}.campo{min-width:0}.campo label:first-child{display:block;font-size:10px;font-weight:800;color:#145547;margin-bottom:4px}.campo input:not([type=checkbox]),.campo select{background:#fff;border:1px solid #aac5c7;border-radius:2px;width:100%;height:30px;padding:5px 7px;font-size:11px;color:#334155}.ancho{grid-column:span 2}.sin-stock{background:#fa4254;color:white;font-size:10px;height:30px;display:flex;justify-content:center;align-items:center;gap:14px;border-radius:2px;font-weight:800}.sin-stock input{accent-color:#0d6ca9}.aplicar{height:30px;width:100%;background:#078dcc;color:white;border-radius:3px;font-size:10px;display:flex;align-items:center;justify-content:center;gap:6px}.nota{font-size:10px;text-align:center;background:#fff3f3;color:#ba3b4d;padding:7px;margin-top:7px}.resumen{border:1px solid #334155;border-radius:12px;display:flex;justify-content:space-around;gap:10px;flex-wrap:wrap;margin:10px 0;padding:7px;font-size:10px;font-weight:800}.resumen span{color:#fff;border-radius:12px;padding:3px 7px;font-size:10px}.azul{background:#168cdd}.rojo{background:#e64d61}.verde{background:#79b82a}.herramientas{display:flex;justify-content:flex-end;gap:6px;margin:9px 0 5px}.herramientas button{border:1px solid;border-radius:3px;display:flex;align-items:center;gap:4px;padding:7px;font-size:10px}.pdf{color:#f0525c;border-color:#f0525c!important}.excel{color:#6baa49;border-color:#6baa49!important}.tabla{overflow-x:auto}table{border-collapse:collapse;width:100%;font-size:10px}td,th{border:1px solid #cad4de;padding:6px;text-align:left}th{background:#04283d;color:white;white-space:nowrap;font-weight:800}td.num{text-align:right}.stock{border-radius:3px;display:block;text-align:center;color:#164d26;background:#8cfa95;font-weight:800}.stock.bajo{background:#ffcb45;color:#633c00}.stock.agotado{background:#f66a79;color:#fff}.vacio{text-align:center;padding:30px;color:#64748b}.pie{display:flex;justify-content:space-between;gap:10px;font-size:11px;color:#64748b;margin-top:12px}.pie>div{display:flex;gap:10px}.pie button{color:#0287ce}.pie button:disabled{color:#cbd5e1}@media(max-width:850px){.filtros{grid-template-columns:repeat(2,minmax(130px,1fr))}.ancho{grid-column:span 1}}@media(max-width:520px){.filtros{grid-template-columns:1fr}.resumen{justify-content:flex-start}.pie{flex-wrap:wrap}}@media print{.miga,.filtros,.herramientas,.pie,.nota{display:none!important}.pagina{padding:0}.tabla{overflow:visible}}
</style>
