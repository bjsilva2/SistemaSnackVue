<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, ArrowLeft, RefreshCw, UtensilsCrossed, ShoppingBag, Car, Search, CreditCard } from 'lucide-vue-next';

type Mesa={id:number;numero:number;capacidad:number;piso:string;estado:string};
type Producto={id:number;codigo:string;nombre:string;precio_venta:string|number;stock_actual:string|number;grupo_id:number|null;imagen_url:string|null};
type Grupo={id:number;nombre:string};
type Cliente={id:number;nombre_completo:string};
const props=defineProps<{mesas:Mesa[];productos:Producto[];grupos:Grupo[];clientes:Cliente[];cajaAbierta:boolean}>();
const vista=ref<'plano'|'pedido'>('plano');
const tipo=ref<'MESA'|'LLEVAR'|'YANGO'>('MESA');
const filtro=ref('');
const grupo=ref<number|null>(null);
const mesaElegida=ref<Mesa|null>(null);
type Item={producto_id:number;cantidad:number};
const form=useForm({modalidad:'MESA' as 'MESA'|'LLEVAR'|'YANGO',mesa_id:null as number|null,cliente_id:null as number|null,nombre_cliente:'',nota_cocina:'',items:[] as Item[]});
const encontrados=computed(()=>props.productos.filter(p=>(!grupo.value||p.grupo_id===grupo.value)&&(!filtro.value||(p.nombre+' '+p.codigo).toLowerCase().includes(filtro.value.toLowerCase()))));
const moneda=(n:number|string)=>Number(n).toLocaleString('es-BO',{minimumFractionDigits:2,maximumFractionDigits:2});
const itemsVista=computed(()=>form.items.map(i=>({ ...i,producto:props.productos.find(p=>p.id===i.producto_id)})));
const total=computed(()=>itemsVista.value.reduce((s,i)=>s+(i.producto?Number(i.producto.precio_venta)*i.cantidad:0),0));
function crear(modalidad:'MESA'|'LLEVAR'|'YANGO',mesa:Mesa|null=null){
 form.reset();form.clearErrors();tipo.value=modalidad;mesaElegida.value=mesa;form.modalidad=modalidad;form.mesa_id=mesa?.id??null;vista.value='pedido';
}
function agregar(p:Producto){const i=form.items.find(i=>i.producto_id===p.id);if(i)i.cantidad++;else form.items.push({producto_id:p.id,cantidad:1});}
function quitar(id:number){form.items=form.items.filter(i=>i.producto_id!==id);}
function guardar(){form.post('/ventas/pedidos',{preserveScroll:true,onSuccess:()=>{vista.value='plano';form.reset();router.reload()}});}
function limpiar(m:Mesa){if(confirm('¿Marcar la mesa '+m.numero+' como libre?'))router.patch('/ventas/mesas/'+m.id+'/liberar',{}, {preserveScroll:true});}
</script>
<template>
<Head title="Nuevo Pedido Mesa"/>
<AppLayout>
<main class="pagina">
 <template v-if="vista==='plano'">
  <header class="titulo">
   <div><h1>Plano Visual de Mesas</h1><div class="leyenda"><span class="libre">● Libres</span> · <span class="ocupada">● Ocupadas</span> · <span class="limpiar">● Para llevar a limpiar</span> · <span class="yango">● Yango</span></div></div>
   <div class="acciones"><button class="amarillo" type="button" title="Edición del layout pendiente" disabled>Editar Layout</button><button class="actualizar" type="button" @click="router.reload()"><RefreshCw :size="15"/></button><button class="lista" type="button" @click="crear('LLEVAR')"><ShoppingBag :size="14"/> Para Llevar</button><button class="yango-boton" type="button" @click="crear('YANGO')"><Car :size="14"/> Yango</button></div>
  </header>
  <div class="plano"><div class="piso">PISO 1</div><div class="mesas">
   <button v-for="m in mesas" :key="m.id" type="button" class="mesa" :class="{ocupada:m.estado==='OCUPADA',sucia:m.estado==='POR_LIMPIAR'}" @click="m.estado==='LIBRE'?crear('MESA',m):m.estado==='POR_LIMPIAR'?limpiar(m):undefined"><span>{{m.numero}}</span><small><UtensilsCrossed :size="11"/> {{m.capacidad}}p</small><em v-if="m.estado==='OCUPADA'">OCUPADA</em><em v-if="m.estado==='POR_LIMPIAR'">POR LIMPIAR</em></button>
  </div></div>
  <p class="aviso">Las mesas ocupadas permanecen bloqueadas hasta el cobro. Al cobrar pasan a «Por limpiar».</p>
 </template>
 <template v-else>
  <div class="pedido-grid">
   <section class="catalogo">
    <div class="busqueda"><Search :size="15"/><input v-model="filtro" placeholder="Buscar platillo o código..."/><button type="button" :class="{sel:tipo==='MESA'}" @click="crear('MESA')"><UtensilsCrossed :size="13"/> MESA</button><button type="button" :class="{sel:tipo==='LLEVAR'}" @click="crear('LLEVAR')"><ShoppingBag :size="13"/> LLEVAR</button><button type="button" :class="{sel:tipo==='YANGO'}" @click="crear('YANGO')"><Car :size="13"/> YANGO</button></div>
    <div class="grupos"><button :class="{activo:grupo===null}" @click="grupo=null">🔥 Todas</button><button v-for="g in grupos" :key="g.id" :class="{activo:grupo===g.id}" @click="grupo=g.id">{{g.nombre}}</button></div>
    <div class="productos"><div v-for="p in encontrados" :key="p.id" class="producto"><div class="foto"><img v-if="p.imagen_url" :src="p.imagen_url" :alt="p.nombre"/><UtensilsCrossed v-else :size="38" color="#94a3b8"/></div><strong>{{p.nombre}}</strong><div class="precio">Bs. <b>{{moneda(p.precio_venta)}}</b><button type="button" aria-label="Agregar producto" @click="agregar(p)"><Plus :size="18"/></button></div></div><p v-if="!encontrados.length" class="sin-productos">No hay productos para mostrar.</p></div>
   </section>
   <section class="resumen"><div class="encabezado-resumen"><button type="button" @click="vista='plano'"><ArrowLeft :size="21"/></button><strong>{{tipo==='MESA'?'MESA '+(mesaElegida?.numero??''):tipo==='LLEVAR'?'PARA LLEVAR':'YANGO'}} - NUEVO</strong></div>
    <div class="cliente"><select v-model="form.cliente_id"><option :value="null">Seleccionar cliente (opcional)</option><option v-for="c in clientes" :key="c.id" :value="c.id">{{c.nombre_completo}}</option></select><input v-model="form.nombre_cliente" placeholder="Nombre del cliente"/></div>
    <div class="cabecera-items"><span>PRODUCTO</span><span>CANT.</span><span>TOTAL</span></div>
    <div class="items"><div v-for="i in itemsVista" :key="i.producto_id" class="linea"><div><strong>{{i.producto?.nombre}}</strong><button class="quitar" type="button" @click="quitar(i.producto_id)">Quitar</button></div><input v-model.number="form.items.find(x=>x.producto_id===i.producto_id)!.cantidad" type="number" min="0.01" step="0.01"/><b>{{moneda((Number(i.producto?.precio_venta)||0)*i.cantidad)}}</b></div><p v-if="!itemsVista.length" class="sin-items">Sin productos</p></div>
    <label class="nota">NOTA COCINA:<textarea v-model="form.nota_cocina" rows="2" placeholder="Observaciones..."/></label>
    <div class="total"><strong>TOTAL</strong><strong>Bs. {{moneda(total)}}</strong></div>
    <p v-if="!cajaAbierta" class="advertencia">No tienes caja abierta. Puedes registrar el pedido, pero para cobrar necesitarás abrir una caja en Administrar Cajas.</p>
    <p v-if="form.hasErrors" class="errores"><span v-for="(e,k) in form.errors" :key="k">{{e}} </span></p>
    <button class="guardar" type="button" :disabled="form.processing||form.items.length===0||(tipo==='MESA'&&!mesaElegida)" @click="guardar">REGISTRAR PEDIDO</button>
    <p class="aclaracion">El cobro se realiza después de registrar el pedido. No se descontará stock hasta cobrar.</p>
   </section>
  </div>
 </template>
</main>
</AppLayout>
</template>
<style scoped>
.pagina{min-height:100vh;padding:16px;background:#f7f9fc;color:#1e293b}.titulo{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:13px}.titulo h1{font-size:19px;font-weight:700}.leyenda{font-size:10px;color:#64748b}.libre{color:#19a968}.ocupada{color:#e11d48}.limpiar{color:#fb923c}.yango{color:#e11d48}.acciones{display:flex;gap:6px;flex-wrap:wrap}.acciones button{border-radius:5px;display:inline-flex;align-items:center;gap:5px;font-size:11px;padding:8px;color:white}.acciones .amarillo{background:#f9bc17;color:#1e293b}.acciones .amarillo:disabled{opacity:.5}.acciones .actualizar{background:#1393e4}.acciones .lista{background:#f9b414}.acciones .yango-boton{background:#d51b2b}.plano{background:#101b30;border:12px solid #172038;border-radius:5px;min-height:420px;padding:18px}.piso{background:#303d56;color:white;padding:4px 12px;border-radius:3px;font-size:11px;font-weight:800}.mesas{display:grid;grid-template-columns:repeat(4,minmax(90px,1fr));gap:32px 30px;padding:24px 5px;max-width:650px}.mesa{min-height:70px;border:2px solid #9ef4ba;border-radius:8px;background:#eaffef;color:#12233b;position:relative;font-weight:800;display:flex;justify-content:center;align-items:center;cursor:pointer}.mesa span{font-size:19px}.mesa small{position:absolute;bottom:-19px;display:flex;align-items:center;font-size:9px;color:#cbd5e1;font-weight:400}.mesa.ocupada{background:#ffe1e3;border-color:#e11d48;cursor:not-allowed}.mesa.sucia{background:#ffedd5;border-color:#f59e0b}.mesa em{position:absolute;top:-15px;font-size:9px;color:#f8fafc;font-style:normal}.aviso{color:#64748b;font-size:11px;margin-top:10px}.pedido-grid{display:grid;grid-template-columns:1.8fr 1fr;gap:12px}.catalogo,.resumen{background:white;border:1px solid #e2e8f0;border-radius:9px;padding:10px}.busqueda{display:flex;gap:6px;align-items:center}.busqueda input{flex:1;min-width:0;border:1px solid #cbd5e1;border-radius:7px;padding:8px;font-size:12px}.busqueda button{display:flex;gap:4px;align-items:center;padding:8px;border-radius:6px;background:#f1f5f9;font-size:10px}.busqueda button.sel{background:#f97316;color:white}.grupos{display:flex;gap:5px;overflow-x:auto;padding:10px 0}.grupos button{font-size:10px;border:1px solid #eee;border-radius:20px;padding:5px 9px;white-space:nowrap}.grupos button.activo{background:#14213a;color:white}.productos{display:grid;grid-template-columns:repeat(5,minmax(100px,1fr));gap:9px}.producto{border:1px solid #e2e8f0;border-radius:10px;padding:7px;min-width:0;display:flex;flex-direction:column;gap:6px}.foto{height:100px;display:flex;align-items:center;justify-content:center;overflow:hidden}.foto img{width:100%;height:100%;object-fit:contain}.producto strong{font-size:11px;min-height:28px}.precio{display:flex;justify-content:space-between;align-items:center;font-size:10px}.precio b{font-size:12px}.precio button{border-radius:50%;background:#f97316;color:white;padding:4px}.sin-productos{grid-column:1/-1;padding:30px;text-align:center}.resumen{display:flex;flex-direction:column;min-height:490px}.encabezado-resumen{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}.encabezado-resumen strong{font-size:13px}.cliente{display:flex;flex-direction:column;gap:4px}.cliente select,.cliente input,.nota textarea{border:1px solid #cbd5e1;border-radius:4px;padding:7px;font-size:11px;width:100%}.cabecera-items{display:grid;grid-template-columns:1fr 50px 55px;gap:7px;border-bottom:1px solid #cbd5e1;margin-top:12px;font-size:10px;font-weight:800}.items{flex:1;min-height:170px;max-height:290px;overflow-y:auto}.linea{display:grid;grid-template-columns:1fr 50px 55px;gap:5px;padding:9px 0;border-bottom:1px solid #eee;align-items:center}.linea strong,.linea b{font-size:11px}.linea input{width:48px;border:1px solid #ccc;padding:3px;font-size:11px}.quitar{display:block;color:#dc2626;font-size:10px}.sin-items{text-align:center;color:#cbd5e1;padding:50px;font-size:12px}.nota{font-size:10px;font-weight:700}.total{display:flex;justify-content:space-between;align-items:center;border-top:2px dotted #aaa;padding:8px 0;font-size:15px}.total strong:last-child{color:#1d4ed8}.advertencia,.errores{background:#fff7ed;color:#9a3412;font-size:11px;padding:8px;border-radius:4px}.guardar{background:#334155;color:white;border-radius:5px;padding:10px;font-weight:800;font-size:11px}.guardar:disabled{opacity:.5}.aclaracion{font-size:10px;color:#64748b;margin-top:6px}@media(max-width:1100px){.productos{grid-template-columns:repeat(3,minmax(100px,1fr))}}@media(max-width:800px){.pedido-grid{grid-template-columns:1fr}.productos{grid-template-columns:repeat(3,minmax(90px,1fr))}.mesas{grid-template-columns:repeat(3,minmax(70px,1fr))}}@media(max-width:500px){.productos{grid-template-columns:repeat(2,minmax(90px,1fr))}.mesas{grid-template-columns:repeat(2,minmax(90px,1fr))}.busqueda{flex-wrap:wrap}.titulo{flex-wrap:wrap}}
</style>
