<?php

namespace App\Http\Controllers;

use App\Models\AgendaPreventiva;
use App\Models\Ativo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaPreventivaController extends Controller
{
    public function index(Request $request): View { return view('agenda.index', ['includeDeleted'=>$request->boolean('include_deleted')]); }

    public function data(Request $request): JsonResponse
    {
        $query=AgendaPreventiva::query()->with(['equipamento.local.unidade']);
        if($request->boolean('include_deleted')) $query->withTrashed();
        $total=(clone $query)->count(); $search=trim((string)$request->input('search.value'));
        if($search!=='') $query->where(fn($q)=>$q->where('obs','like',"%{$search}%")->orWhereHas('equipamento',fn($a)=>$a->where('titulo','like',"%{$search}%")->orWhere('codigo','like',"%{$search}%")));
        $filtered=(clone $query)->count(); $columns=['id','ativos_id','locais_id','ultima_agenda','periodicidade','proxima_agenda','orcamento','proximo_orcamento','ativo'];
        $column=$columns[(int)$request->input('order.0.column',0)]??'id'; $direction=$request->input('order.0.dir')==='asc'?'asc':'desc'; $length=min(max((int)$request->input('length',10),1),100);
        $rows=$query->orderBy($column,$direction)->skip(max((int)$request->input('start',0),0))->take($length)->get()->map(fn(AgendaPreventiva $agenda)=>[
            'id'=>$agenda->id, 'equipamento'=>view('agenda._equipment_link',compact('agenda'))->render(), 'local'=>e($this->locationLabel($agenda->equipamento)),
            'ultima_agenda'=>$agenda->ultima_agenda?->format('d/m/Y')??'—', 'periodicidade'=>$agenda->periodicidade??'—', 'proxima_agenda'=>$agenda->proxima_agenda?->format('d/m/Y')??'—',
            'orcamento'=>$agenda->orcamento??'—', 'proximo_orcamento'=>$agenda->proximo_orcamento?->format('d/m/Y')??'—',
            'status'=>view('agenda._status',compact('agenda'))->render(), 'acoes'=>view('agenda._actions',compact('agenda'))->render(),
        ]);
        return response()->json(['draw'=>(int)$request->input('draw'),'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$rows]);
    }

    public function create(): View { return $this->formView(new AgendaPreventiva(['ativo'=>true]),false); }
    public function store(Request $request): RedirectResponse
    {
        $data=$this->validated($request); $data['criado_por']=$request->session()->get('gi_context.usuario.id'); AgendaPreventiva::create($data);
        return redirect()->route('agenda.index')->with('status','Agenda preventiva cadastrada com sucesso.');
    }
    public function show(int $id): View { $agenda=AgendaPreventiva::withTrashed()->with(['equipamento.local.unidade','criador'])->findOrFail($id); return view('agenda.show',compact('agenda')); }
    public function edit(int $id): View { return $this->formView(AgendaPreventiva::withTrashed()->findOrFail($id),true); }
    public function update(Request $request,int $id): RedirectResponse { AgendaPreventiva::withTrashed()->findOrFail($id)->update($this->validated($request)); return redirect()->route('agenda.index')->with('status','Agenda preventiva atualizada com sucesso.'); }
    public function destroy(int $id): RedirectResponse { AgendaPreventiva::findOrFail($id)->delete(); return redirect()->route('agenda.index',['include_deleted'=>1])->with('status','Agenda movida para os registros apagados.'); }
    public function restore(int $id): RedirectResponse { AgendaPreventiva::onlyTrashed()->findOrFail($id)->restore(); return redirect()->route('agenda.index',['include_deleted'=>1])->with('status','Agenda restaurada com sucesso.'); }
    public function forceDestroy(int $id): RedirectResponse { AgendaPreventiva::onlyTrashed()->findOrFail($id)->forceDelete(); return redirect()->route('agenda.index',['include_deleted'=>1])->with('status','Agenda excluída permanentemente.'); }

    private function validated(Request $request): array
    {
        $data=$request->validate(['ativos_id'=>['required','integer','exists:manut_ativos,id'],'ultima_agenda'=>['nullable','date'],'proxima_agenda'=>['nullable','date'],'proximo_orcamento'=>['nullable','date'],'periodicidade'=>['nullable','integer','min:0'],'orcamento'=>['nullable','integer','min:0'],'ativo'=>['nullable','boolean'],'obs'=>['nullable','string','max:250']]);
        $data['locais_id']=Ativo::withTrashed()->findOrFail($data['ativos_id'])->locais_id; return $data;
    }
    private function formView(AgendaPreventiva $agenda,bool $isEdit): View
    {
        $ativos=Ativo::withTrashed()->with('local.unidade')
            ->where(fn($query)=>$query->where(fn($active)=>$active->where('ativo',true)->whereNull('apagado_em'))->when($agenda->ativos_id,fn($current,$id)=>$current->orWhere('id',$id)))
            ->orderBy('titulo')->get();
        return view('agenda.form',compact('agenda','isEdit','ativos'));
    }
    private function assetLabel(?Ativo $ativo): string { return $ativo ? ($ativo->titulo?:"Ativo #{$ativo->id}").' ('.($ativo->codigo?:'sem código').') - '.$this->locationLabel($ativo) : '—'; }
    private function locationLabel(?Ativo $ativo): string { return $ativo?->local ? ($ativo->local->titulo?:'Local sem título').' - '.($ativo->local->unidade?->titulo?:'Unidade não informada') : 'Local não informado - Unidade não informada'; }
}
