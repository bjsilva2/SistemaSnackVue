<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { Search, Package, Wallet, CalendarDays } from 'lucide-vue-next';

type Dia={fecha:string;dia:number;cantidad:number;ventas:number;promedio:number;ingresos:number;egresos:number;compras:number;saldo:number};
type Totales={productos:number;cantidad:number;ventas:number;ingresos:number;egresos:number;compras:number;saldo:number};
const props=defineProps<{dias:Dia[];totales:Totales;filtros:{mes:number;anio:number;sucursal:string};sucursales:string[]}>();
const form=reactive({...props.filtros});
const meses=['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
const anios=Array.from({length:15},(_,i)=>2020+i);
const dinero=(n:number|string)=>Number(n).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
const semanas=computed(()=>{const lista:Dia[][]=[];for(let i=0;i<props.dias.length;i+=7)lista.push(props.dias.slice(i,i+7));return lista;});
function buscar(){router.get('/reportes/mensual',{mes:form.mes,anio:form.anio,sucursal:form.sucursal},{preserveState:true,replace:true});}
function fecha(d:Dia){const p=d.fecha.split('-');return p[2]+'/'+p[1]+'/'+p[0].slice(2);}
</script>
<template>
<Head title="Informe Mensual y Gestión"/>
<AppLayout>
<main class="pagina" translate="no">
 <div class="miga">REPORTES <span>›</span> <b>INFORME MENSUAL Y GESTIÓN</b></div>
 <div class="contenido">
  <h1>INFORME POR MES Y GESTIÓN</h1>
  <form class="filtros" @submit.prevent="buscar">
   <div class="campo"><label>SELECCIONE SUCURSAL:</label><select v-model="form.sucursal"><option value="TODAS">TODAS</option><option v-for="s in sucursales" :key="s" :value="s">{{s}}</option></select></div>
   <div class="campo"><label>SELECCIONE MES:</label><select v-model.number="form.mes"><option v-for="(m,i) in meses" :key="i" :value="i+1">{{m}}</option></select></div>
   <div class="campo"><label>SELECCIONE AÑO:</label><select v-model.number="form.anio"><option v-for="a in anios" :key="a" :value="a">{{a}}</option></select></div>
   <div class="campo"><label>ACCIONES:</label><button type="submit" class="aplicar"><Search :size="13"/> APLICAR FILTROS</button></div>
  </form>
  <section class="panel"><header><Package :size="15"/><strong>Resumen Mensual de Productos y Servicios</strong></header>
   <div class="cuadricula cuatro">
    <div class="tarjeta azul-claro"><Package :size="22"/><div><small>Cant. pedidos con productos</small><b>{{totales.cantidad}}</b></div></div>
    <div class="tarjeta azul-claro"><span class="simbolo">$</span><div><small>Total productos</small><b>Bs. {{dinero(totales.ventas)}}</b></div></div>
    <div class="tarjeta naranja-claro"><Package :size="22"/><div><small>Cant. servicios</small><b>0</b></div></div>
    <div class="tarjeta naranja-claro"><span class="simbolo">$</span><div><small>Total servicios</small><b>Bs. 0,00</b></div></div>
   </div>
  </section>
  <section class="panel"><header><Wallet :size="15"/><strong>Resumen Mensual de Caja y Compras</strong></header>
   <div class="cuadricula cuatro">
    <div class="tarjeta verde-claro"><span class="simbolo">+</span><div><small>Ventas cobradas</small><b>Bs. {{dinero(totales.ventas)}}</b></div></div>
    <div class="tarjeta verde-claro"><span class="simbolo">+</span><div><small>Ingresos adicionales *</small><b>Bs. {{dinero(totales.ingresos)}}</b></div></div>
    <div class="tarjeta rojo-claro"><span class="simbolo">−</span><div><small>Egresos registrados *</small><b>Bs. {{dinero(totales.egresos)}}</b></div></div>
    <div class="tarjeta neutro"><Wallet :size="21"/><div><small>Resultado neto estimado **</small><b>Bs. {{dinero(totales.saldo)}}</b></div></div>
   </div>
   <p class="aclaracion">* Los ingresos y egresos adicionales se atribuyen al día de apertura de la caja porque el sistema aún no registra sus movimientos individuales por fecha. ** El resultado neto estimado resta compras a las ventas y movimientos; no es el saldo contable final ni una utilidad.</p>
  </section>
  <section v-for="(semana,i) in semanas" :key="i" class="semana">
   <h2>SEMANA {{i+1}}</h2>
   <div class="tabla"><table><thead>
    <tr class="grupo"><th rowspan="2">Fecha</th><th colspan="3">Productos</th><th colspan="2">Servicios</th><th colspan="4">Caja y Compras</th><th rowspan="2">Resultado</th></tr>
    <tr><th>Cant. pedidos</th><th>Total Bs.</th><th>Prom. Bs.</th><th>Cant.</th><th>Total Bs.</th><th>Ventas</th><th>Ingresos *</th><th>Egresos *</th><th>Compras</th></tr></thead>
    <tbody><tr v-for="d in semana" :key="d.fecha"><td>{{fecha(d)}}</td><td class="celeste">{{d.cantidad}}</td><td class="celeste">{{dinero(d.ventas)}}</td><td class="celeste">{{dinero(d.promedio)}}</td><td class="amarillo">0</td><td class="amarillo">0,00</td><td class="verde">{{dinero(d.ventas)}}</td><td class="verde">{{dinero(d.ingresos)}}</td><td class="rosa">{{dinero(d.egresos)}}</td><td class="rosa">{{dinero(d.compras)}}</td><td :class="d.saldo>=0?'positivo':'negativo'">{{dinero(d.saldo)}}</td></tr></tbody>
   </table></div>
  </section>
  <div class="pie"><CalendarDays :size="13"/> Informe de {{meses[form.mes-1]}} de {{form.anio}} — Solo datos registrados en SistemaSnackVue</div>
 </div>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{min-height:100vh;padding:16px;background:#f8fafc;color:#243244}.miga{font-size:10px;color:#94a3b8;margin-bottom:10px}.miga span{padding:0 5px}.miga b{color:#334155}.contenido{border:1px solid #7b8799;background:#fff;padding:14px}.contenido h1{text-align:center;font-size:17px;font-weight:800;margin:9px 0 4px}.filtros{display:grid;grid-template-columns:repeat(4,minmax(120px,1fr));gap:8px;background:#7afbaf;border:1px solid #12aa74;padding:8px;margin-bottom:15px}.campo{min-width:0}.campo label{display:block;color:#195345;font-weight:800;font-size:10px;margin-bottom:3px}.campo select{width:100%;height:30px;border:1px solid #a6c5c9;background:white;border-radius:2px;font-size:11px;color:#475569;padding:5px}.aplicar{display:flex;align-items:center;justify-content:center;gap:7px;background:#068dd0;color:white;width:100%;height:30px;border-radius:2px;font-size:10px}.panel{border:1px solid #e2e8f0;border-radius:6px;padding:9px;margin:14px 0}.panel header{display:flex;align-items:center;gap:7px;border-bottom:2px solid #0a96ce;font-size:12px;padding:3px 0 7px;margin-bottom:8px}.cuadricula{display:grid;gap:10px}.cuatro{grid-template-columns:repeat(4,1fr)}.tarjeta{display:flex;align-items:center;gap:8px;border-radius:6px;padding:12px 9px;min-width:0;border-left:3px solid transparent}.tarjeta small,.tarjeta b{display:block}.tarjeta small{font-size:10px;color:#64748b}.tarjeta b{font-size:12px;color:#1f2937;margin-top:3px}.simbolo{font-size:21px;font-weight:800}.azul-claro{background:#cee8ff;color:#3985e5;border-left-color:#3985e5}.naranja-claro{background:#ffe4cb;color:#f97316;border-left-color:#f97316}.verde-claro{background:#d7f0db;color:#20a456;border-left-color:#20a456}.rojo-claro{background:#ffdbdc;color:#ef4444;border-left-color:#ef4444}.neutro{background:#f1f2f4;color:#6b7280;border-left-color:#94a3b8}.aclaracion{margin:8px 3px 0;font-size:10px;color:#64748b}.semana{margin-top:15px}.semana h2{text-align:center;font-size:15px;font-weight:800;margin-bottom:8px}.tabla{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:10px}th,td{border:1px solid #cad5e2;text-align:center;padding:6px;white-space:nowrap}th{background:#05283b;color:white;font-weight:800}.grupo th{background:#05283b}td.celeste{background:#e3f3ff;color:#1f5fa4}td.amarillo{background:#fff1c9;color:#9c720b}td.verde{background:#e5f7ea;color:#267442}td.rosa{background:#ffe4e7;color:#92364a}.positivo{background:#e4f6e9;color:#237a3f}.negativo{background:#fce7e7;color:#af3535}.pie{display:flex;gap:6px;justify-content:center;align-items:center;margin-top:15px;color:#64748b;font-size:11px}@media(max-width:900px){.cuatro,.filtros{grid-template-columns:repeat(2,minmax(120px,1fr))}}@media(max-width:520px){.cuatro,.filtros{grid-template-columns:1fr}}
</style>
