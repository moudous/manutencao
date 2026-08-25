<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function index(Request $request): View { return view('compras.index',['includeDeleted'=>$request->boolean('include_deleted')]); }
    public function data(Request $request): JsonResponse
    {
        $query=Compra::query(); if($request->boolean('include_deleted'))$query->withTrashed(); $total=(clone $query)->count(); $search=trim((string)$request->input('search.value'));
        if($search!=='')$query->where(fn($q)=>$q->where('titulo','like',"%{$search}%")->orWhere('comprador','like',"%{$search}%")->orWhere('unidade','like',"%{$search}%"));
        $filtered=(clone $query)->count(); $columns=['id','titulo','unidade','quantidade','quantidade_unitaria','qtde_utilizada','preco','preco','comprador','data_compra','disponivel']; $column=$columns[(int)$request->input('order.0.column',0)]??'id'; $direction=$request->input('order.0.dir')==='asc'?'asc':'desc'; $length=min(max((int)$request->input('length',10),1),100);
        $rows=$query->orderBy($column,$direction)->skip(max((int)$request->input('start',0),0))->take($length)->get()->map(fn(Compra $compra)=>['id'=>$compra->id,'titulo'=>e($compra->titulo?:'—'),'unidade'=>e($compra->unidade?:'—'),'quantidade'=>$this->number($compra->quantidade),'quantidade_unitaria'=>$this->number($compra->quantidade_unitaria),'qtde_utilizada'=>$this->number($compra->qtde_utilizada),'preco'=>$compra->preco!==null?'R$ '.number_format($compra->preco,2,',','.'):'—','preco_unitario'=>$compra->preco_unitario!==null?'R$ '.number_format($compra->preco_unitario,2,',','.'):'—','comprador'=>e($compra->comprador?:'—'),'data_compra'=>$compra->data_compra?->format('d/m/Y')??'—','status'=>view('compras._status',compact('compra'))->render(),'acoes'=>view('compras._actions',compact('compra'))->render()]);
        return response()->json(['draw'=>(int)$request->input('draw'),'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$rows]);
    }
    public function create(): View { return view('compras.form',['compra'=>new Compra(['disponivel'=>true]),'isEdit'=>false]); }
    public function store(Request $request): RedirectResponse { Compra::create($this->validated($request)); return redirect()->route('compras.index')->with('status','Compra cadastrada com sucesso.'); }
    public function show(int $id): View { return view('compras.show',['compra'=>Compra::withTrashed()->findOrFail($id)]); }
    public function edit(int $id): View { return view('compras.form',['compra'=>Compra::withTrashed()->findOrFail($id),'isEdit'=>true]); }
    public function update(Request $request,int $id): RedirectResponse { Compra::withTrashed()->findOrFail($id)->update($this->validated($request)); return redirect()->route('compras.index')->with('status','Compra atualizada com sucesso.'); }
    public function destroy(int $id): RedirectResponse { Compra::findOrFail($id)->delete(); return redirect()->route('compras.index',['include_deleted'=>1])->with('status','Compra movida para os registros apagados.'); }
    public function restore(int $id): RedirectResponse { Compra::onlyTrashed()->findOrFail($id)->restore(); return redirect()->route('compras.index',['include_deleted'=>1])->with('status','Compra restaurada com sucesso.'); }
    public function forceDestroy(int $id): RedirectResponse { Compra::onlyTrashed()->findOrFail($id)->forceDelete(); return redirect()->route('compras.index',['include_deleted'=>1])->with('status','Compra excluída permanentemente.'); }
    private function validated(Request $request): array { return $request->validate(['titulo'=>['nullable','string','max:100'],'unidade'=>['nullable',Rule::in(['lts','kg','m','un'])],'quantidade'=>['nullable','numeric','min:0'],'quantidade_unitaria'=>['nullable','numeric','min:0'],'comprador'=>['nullable','string','max:50'],'preco'=>['nullable','numeric','min:0'],'data_compra'=>['nullable','date'],'disponivel'=>['nullable','boolean']]); }
    private function number(?float $value): string { return $value===null?'—':number_format($value,2,',','.'); }
}
