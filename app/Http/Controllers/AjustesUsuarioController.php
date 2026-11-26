<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AjustesUsuarioController extends Controller
{
    private function admin(Request $request): void {
        $esAdmin=DB::table('users as u')->join('roles as r','r.id','=','u.rol_id')
            ->where('u.id',$request->user()->id)->where('r.nombre','Administrador')->exists();
        abort_unless($esAdmin,403,'Solo administradores.');
    }
    public function index(Request $request) {
        $this->admin($request);
        $buscar=trim((string)$request->query('buscar',''));
        $usuarios=DB::table('users as u')->leftJoin('roles as r','r.id','=','u.rol_id')
            ->select('u.id','u.name','u.email','u.rol_id','r.nombre as rol_nombre','u.created_at','u.updated_at')
            ->when($buscar!=='',fn($q)=>$q->where(function($q)use($buscar){
                $q->where('u.name','ilike','%'.$buscar.'%')
                  ->orWhere('u.email','ilike','%'.$buscar.'%')
                  ->orWhereRaw('CAST(u.id AS TEXT) LIKE ?',['%'.$buscar.'%']);
            }))->orderBy('u.id')->paginate(10)->withQueryString();
        return Inertia::render('Ajustes/Usuarios',[
            'usuarios'=>$usuarios,'roles'=>DB::table('roles')->orderBy('nombre')->get(['id','nombre']),
            'filtros'=>['buscar'=>$buscar],'usuarioActual'=>$request->user()->id,
        ]);
    }
    public function store(Request $request) {
        $this->admin($request);
        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','max:255','unique:users,email'],
            'password'=>['required','string','min:8','confirmed'],
            'rol_id'=>['required','integer','exists:roles,id'],
        ]);
        DB::table('users')->insert([
            'name'=>$data['name'],'email'=>$data['email'],
            'password'=>Hash::make($data['password']),'rol_id'=>$data['rol_id'],
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('success','Usuario creado.');
    }
    public function update(Request $request,int $usuario) {
        $this->admin($request);
        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($usuario)],
            'password'=>['nullable','string','min:8','confirmed'],
            'rol_id'=>['required','integer','exists:roles,id'],
        ]);
        DB::transaction(function()use($request,$usuario,$data){
            $actual=DB::table('users')->where('id',$usuario)->lockForUpdate()->first();
            if(!$actual)abort(404);
            $adminId=DB::table('roles')->where('nombre','Administrador')->value('id');
            if($actual->rol_id===$adminId && (int)$data['rol_id']!==$adminId &&
                DB::table('users')->where('rol_id',$adminId)->count()<=1)
                throw ValidationException::withMessages(['rol_id'=>'No se puede quitar el último administrador.']);
            if($usuario===$request->user()->id && (int)$data['rol_id']!==$adminId)
                throw ValidationException::withMessages(['rol_id'=>'No puedes quitarte el acceso de administrador.']);
            $actualizar=['name'=>$data['name'],'email'=>$data['email'],
                'rol_id'=>$data['rol_id'],'updated_at'=>now()];
            if(!empty($data['password']))$actualizar['password']=Hash::make($data['password']);
            DB::table('users')->where('id',$usuario)->update($actualizar);
        });
        return back()->with('success','Usuario actualizado.');
    }
}
