<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\RolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PedidoMesaController extends Controller {
 public function index(Request $request) {
  $productos=Producto::where('estado',true)->where('tipo','PRODUCTO')->orderBy('nombre')->get(['id','codigo','nombre','imagen','grupo_id','precio_venta','stock_actual']);
  $productos->each(function($p){$p->imagen_url=$p->imagen?Storage::disk('public')->url($p->imagen):null;});
  return Inertia::render('Ventas/PedidosMesa',[
   'mesas'=>DB::table('mesas')->orderBy('numero')->get(),
   'pedidosPendientes'=>DB::table('pedidos as p')->leftJoin('mesas as m','m.id','=','p.mesa_id')->where('p.estado','!=','COBRADO')->orderByDesc('p.id')->get(['p.id','p.codigo','p.modalidad','p.mesa_id','m.numero as mesa_numero','p.nombre_cliente','p.total','p.estado']),
   'productos'=>$productos,
   'grupos'=>DB::table('grupo_productos')->where('estado',true)->orderBy('nombre')->get(['id','nombre']),
   'clientes'=>DB::table('clientes')->where('estado',true)->orderBy('nombre_completo')->get(['id','nombre_completo']),
   'cajaAbierta'=>DB::table('cajas')->where('usuario_id',$request->user()->id)->where('estado','ABIERTO')->exists(),
  ]);
 }
 public function store(Request $request) {
  $d=$request->validate([
   'modalidad'=>['required',Rule::in(['MESA','LLEVAR','YANGO'])],
   'mesa_id'=>['nullable','integer',Rule::exists('mesas','id')],
   'cliente_id'=>['nullable','integer',Rule::exists('clientes','id')->where('estado',true)],
   'nombre_cliente'=>['nullable','string','max:160'],'nota_cocina'=>['nullable','string','max:1000'],
   'items'=>['required','array','min:1'],'items.*.producto_id'=>['required','integer','distinct',Rule::exists('productos','id')->where('estado',true)->where('tipo','PRODUCTO')],
   'items.*.cantidad'=>['required','numeric','gt:0','decimal:0,2'],
  ]);
  DB::transaction(function()use($d,$request){
   $mesa=null;
   if($d['modalidad']==='MESA'){
    if(empty($d['mesa_id']))throw ValidationException::withMessages(['mesa_id'=>'Selecciona una mesa.']);
    $mesa=DB::table('mesas')->where('id',$d['mesa_id'])->lockForUpdate()->first();
    if(!$mesa||$mesa->estado!=='LIBRE')throw ValidationException::withMessages(['mesa_id'=>'Esta mesa no está libre.']);
   }
   $productos=Producto::whereIn('id',collect($d['items'])->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
   $total=0;$detalle=[];
   foreach($d['items'] as $item){
    $p=$productos->get($item['producto_id']);
    if(!$p||!$p->estado||$p->tipo!=='PRODUCTO')throw ValidationException::withMessages(['items'=>'Producto no disponible.']);
    $qty=(int)round((float)$item['cantidad']*100);$precio=(int)round((float)$p->precio_venta*100);
    $subtotal=(int)round($qty*$precio/100);$total+=$subtotal;$detalle[]=[$p,$item['cantidad'],$precio,$subtotal];
   }
   $id=(int)DB::selectOne("SELECT nextval(pg_get_serial_sequence('pedidos','id')) AS numero")->numero;
   DB::table('pedidos')->insert([
    'id'=>$id,'codigo'=>'PED-'.str_pad((string)$id,4,'0',STR_PAD_LEFT),'modalidad'=>$d['modalidad'],'mesa_id'=>$mesa?->id,
    'cliente_id'=>$d['cliente_id']??null,'usuario_id'=>$request->user()->id,'nombre_cliente'=>$d['nombre_cliente']??null,
    'nota_cocina'=>$d['nota_cocina']??null,'estado'=>'EN_COCINA','total'=>number_format($total/100,2,'.',''),
    'created_at'=>now(),'updated_at'=>now(),
   ]);
   foreach($detalle as [$p,$cantidad,$precio,$subtotal]){
    DB::table('pedido_items')->insert(['pedido_id'=>$id,'producto_id'=>$p->id,'cantidad'=>$cantidad,
     'precio_unitario'=>number_format($precio/100,2,'.',''),'subtotal'=>number_format($subtotal/100,2,'.',''),
     'created_at'=>now(),'updated_at'=>now()]);
   }
   if($mesa)DB::table('mesas')->where('id',$mesa->id)->update(['estado'=>'OCUPADA','updated_at'=>now()]);
  });
  return back()->with('success','Pedido registrado. Puedes cobrarlo cuando tengas una caja abierta.');
 }
 public function registrarYCobrar(Request $request) {
  abort_unless(RolePermissions::allows(RolePermissions::forUser($request->user()), 'Venta.crear'),403,'Sin permiso para registrar pedidos.');
  // Cobro directo exclusivo de PARA LLEVAR. MESA mantiene el cobro posterior.
  $d=$request->validate([
   'modalidad'=>['required',Rule::in(['LLEVAR'])],
   'cliente_id'=>['nullable','integer',Rule::exists('clientes','id')->where('estado',true)],
   'nombre_cliente'=>['nullable','string','max:160'],
   'nota_cocina'=>['nullable','string','max:1000'],
   'metodo_pago'=>['required',Rule::in(['EFECTIVO','QR'])],
   'items'=>['required','array','min:1'],
   'items.*.producto_id'=>['required','integer','distinct',
    Rule::exists('productos','id')->where('estado',true)->where('tipo','PRODUCTO')],
   'items.*.cantidad'=>['required','numeric','gt:0','decimal:0,2'],
  ]);
  DB::transaction(function() use ($d,$request) {
   // El cierre de caja y el cobro compiten por el mismo bloqueo.
   $caja=DB::table('cajas')->where('usuario_id',$request->user()->id)
    ->where('estado','ABIERTO')->lockForUpdate()->first();
   if(!$caja)throw ValidationException::withMessages([
    'caja'=>'Debes tener una caja abierta para cobrar directamente.',
   ]);
   $productos=Producto::whereIn('id',collect($d['items'])->pluck('producto_id'))
    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
   $totalCentavos=0;
   $detalle=[];
   foreach($d['items'] as $item) {
    $producto=$productos->get($item['producto_id']);
    if(!$producto||!$producto->estado||$producto->tipo!=='PRODUCTO')
     throw ValidationException::withMessages(['items'=>'Producto no disponible.']);
    $cantidadCent=(int) round((float)$item['cantidad']*100);
    $existenciasCent=(int) round((float)$producto->stock_actual*100);
    if($existenciasCent<$cantidadCent)
     throw ValidationException::withMessages(['stock'=>'Stock insuficiente para '.$producto->nombre.'.']);
    $precioCent=(int) round((float)$producto->precio_venta*100);
    $subtotalCent=(int) round($cantidadCent*$precioCent/100);
    $totalCentavos+=$subtotalCent;
    $detalle[]=[$producto,$cantidadCent,$precioCent,$subtotalCent];
   }
   $pedidoId=(int)DB::selectOne("SELECT nextval(pg_get_serial_sequence('pedidos','id')) AS numero")->numero;
   $ahora=now();
   DB::table('pedidos')->insert([
    'id'=>$pedidoId,
    'codigo'=>'PED-'.str_pad((string)$pedidoId,4,'0',STR_PAD_LEFT),
    'modalidad'=>'LLEVAR','mesa_id'=>null,
    'cliente_id'=>$d['cliente_id']??null,'usuario_id'=>$request->user()->id,
    'caja_id'=>$caja->id,'nombre_cliente'=>$d['nombre_cliente']??null,
    'nota_cocina'=>$d['nota_cocina']??null,
    'estado'=>'COBRADO','metodo_pago'=>$d['metodo_pago'],
    'total'=>number_format($totalCentavos/100,2,'.',''),
    'cobrado_at'=>$ahora,'created_at'=>$ahora,'updated_at'=>$ahora,
   ]);
   foreach($detalle as [$producto,$cantidadCent,$precioCent,$subtotalCent]) {
    DB::table('pedido_items')->insert([
     'pedido_id'=>$pedidoId,'producto_id'=>$producto->id,
     'cantidad'=>number_format($cantidadCent/100,2,'.',''),
     'precio_unitario'=>number_format($precioCent/100,2,'.',''),
     'subtotal'=>number_format($subtotalCent/100,2,'.',''),
     'created_at'=>$ahora,'updated_at'=>$ahora,
    ]);
    $producto->stock_actual=number_format(
     ((int)round((float)$producto->stock_actual*100)-$cantidadCent)/100,2,'.',''
    );
    $producto->save();
   }
   $total=number_format($totalCentavos/100,2,'.','');
   DB::table('cajas')->where('id',$caja->id)->update([
    'total_ventas'=>DB::raw('total_ventas + '.$total),
    'saldo'=>DB::raw('saldo + '.$total),
    'updated_at'=>$ahora,
   ]);
  });
  return back()->with('success','Pedido para llevar registrado y cobrado correctamente.');
 }
 public function cobrar(Request $request,int $pedido) {
  $d=$request->validate(['metodo_pago'=>['required',Rule::in(['EFECTIVO','QR'])]]);
  DB::transaction(function()use($request,$pedido,$d){
   $caja=DB::table('cajas')->where('usuario_id',$request->user()->id)->where('estado','ABIERTO')->lockForUpdate()->first();
   if(!$caja)throw ValidationException::withMessages(['caja'=>'Debes abrir tu caja antes de cobrar.']);
   $p=DB::table('pedidos')->where('id',$pedido)->lockForUpdate()->first();
   if(!$p||$p->estado==='COBRADO')throw ValidationException::withMessages(['pedido'=>'Pedido inexistente o ya cobrado.']);
   $items=DB::table('pedido_items')->where('pedido_id',$pedido)->orderBy('producto_id')->get();
   $productos=Producto::whereIn('id',$items->pluck('producto_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
   foreach($items as $item){
    $producto=$productos->get($item->producto_id);
    if(!$producto||round((float)$producto->stock_actual,2)<round((float)$item->cantidad,2))
     throw ValidationException::withMessages(['stock'=>'Stock insuficiente para '.$producto?->nombre.'.']);
   }
   foreach($items as $item){
    $producto=$productos->get($item->producto_id);
    $producto->stock_actual=round((float)$producto->stock_actual-(float)$item->cantidad,2);$producto->save();
   }
   DB::table('pedidos')->where('id',$pedido)->update(['estado'=>'COBRADO','metodo_pago'=>$d['metodo_pago'],
    'caja_id'=>$caja->id,'cobrado_at'=>now(),'updated_at'=>now()]);
   $total=(float)$p->total;
   DB::table('cajas')->where('id',$caja->id)->update([
    'total_ventas'=>DB::raw('total_ventas + '.number_format($total,2,'.','')),
    'saldo'=>DB::raw('saldo + '.number_format($total,2,'.','')),'updated_at'=>now(),
   ]);
   if($p->mesa_id)DB::table('mesas')->where('id',$p->mesa_id)->update(['estado'=>'POR_LIMPIAR','updated_at'=>now()]);
  });
  return back()->with('success','Cobro realizado y stock descontado.');
 }
 public function liberarMesa(int $mesa) {
  DB::transaction(function()use($mesa){
   $m=DB::table('mesas')->where('id',$mesa)->lockForUpdate()->first();
   if(!$m||$m->estado!=='POR_LIMPIAR')throw ValidationException::withMessages(['mesa'=>'La mesa debe estar por limpiar.']);
   DB::table('mesas')->where('id',$mesa)->update(['estado'=>'LIBRE','updated_at'=>now()]);
  });
  return back()->with('success','Mesa disponible.');
 }
}
