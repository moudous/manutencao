<?php

namespace App\Http\Controllers;

use App\Models\AgendaPreventiva;
use App\Models\Lancamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LancamentoController extends Controller
{
    public function index(AgendaPreventiva $agenda): View
    {
        $agenda->load('equipamento.local.unidade');
        return view('lancamentos.index',compact('agenda'));
    }
    public function data(Request $request,AgendaPreventiva $agenda): JsonResponse
    {
        $query=$agenda->lancamentos()->with(['equipamento','local']); $total=(clone $query)->count(); $search=trim((string)$request->input('search.value'));
        if($search!=='')$query->where(fn($q)=>$q->where('solicitante','like',"%{$search}%")->orWhere('problema','like',"%{$search}%")->orWhere('observacao','like',"%{$search}%"));
        $filtered=(clone $query)->count(); $columns=['id','data_lancamento','solicitante','problema','data_orcamento','data_agendamento','etapa','ativo']; $column=$columns[(int)$request->input('order.0.column',0)]??'id'; $direction=$request->input('order.0.dir')==='asc'?'asc':'desc'; $length=min(max((int)$request->input('length',10),1),100);
        $rows=$query->orderBy($column,$direction)->skip(max((int)$request->input('start',0),0))->take($length)->get()->map(fn(Lancamento $l)=>['id'=>$l->id,'data_lancamento'=>$l->data_lancamento?->format('d/m/Y H:i')??'—','solicitante'=>e($l->solicitante?:'—'),'problema'=>e($l->problema?:'—'),'data_orcamento'=>$l->data_orcamento?->format('d/m/Y')??'—','data_agendamento'=>$l->data_agendamento?->format('d/m/Y')??'—','etapa'=>$l->etapa??'—','status'=>'<span class="badge '.($l->ativo?'text-bg-success':'text-bg-secondary').'">'.($l->ativo?'Ativo':'Inativo').'</span>','acoes'=>view('lancamentos._actions',['agenda'=>$agenda,'lancamento'=>$l])->render()]);
        return response()->json(['draw'=>(int)$request->input('draw'),'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$rows]);
    }
    public function show(AgendaPreventiva $agenda,Lancamento $lancamento): View
    {
        abort_unless((int)$lancamento->agenda_id===(int)$agenda->id,404); $lancamento->load(['equipamento.local.unidade','local','tecnico']);
        return view('lancamentos.show',compact('agenda','lancamento'));
    }
}
