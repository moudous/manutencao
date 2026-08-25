<?php

namespace App\Http\Controllers;

use App\Models\AgendaPreventiva;
use App\Models\Ativo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AgendaPreventivaController extends Controller
{
    public function index(Request $request): View { return view('agenda.index', ['includeDeleted'=>$request->boolean('include_deleted')]); }

    public function data(Request $request): JsonResponse
    {
        $query=AgendaPreventiva::query()->with(['equipamento.local.unidade'])->withCount('lancamentos');
        if($request->boolean('include_deleted')) $query->withTrashed();
        $total=(clone $query)->count(); $search=trim((string)$request->input('search.value'));
        if($search!=='') $query->where(fn($q)=>$q->where('obs','like',"%{$search}%")->orWhereHas('equipamento',fn($a)=>$a->where('titulo','like',"%{$search}%")->orWhere('codigo','like',"%{$search}%")));
        $filtered=(clone $query)->count(); $columns=['id','ativos_id','locais_id','proxima_agenda','proximo_orcamento','lancamentos_count','ativo'];
        $orders=$request->input('order', [['column'=>3, 'dir'=>'desc'], ['column'=>4, 'dir'=>'desc']]);
        foreach ($orders as $order) {
            $column=$columns[(int)($order['column']??0)]??'id';
            $query->orderBy($column,($order['dir']??'desc')==='asc'?'asc':'desc');
        }
        $length=min(max((int)$request->input('length',10),1),100);
        $rows=$query->skip(max((int)$request->input('start',0),0))->take($length)->get()->map(fn(AgendaPreventiva $agenda)=>[
            'id'=>$agenda->id, 'equipamento'=>view('agenda._equipment_link',compact('agenda'))->render(), 'local'=>e($this->locationLabel($agenda->equipamento)),
            'prazo_manutencao'=>$this->deadlineLabel($agenda->proxima_agenda),
            'prazo_manutencao_classe'=>$this->deadlineClass($agenda->proxima_agenda),
            'periodicidade'=>$agenda->periodicidade??'—',
            'orcamento'=>$agenda->orcamento??'—',
            'prazo_orcamento'=>$this->deadlineLabel($agenda->proximo_orcamento),
            'prazo_orcamento_classe'=>$this->deadlineClass($agenda->proximo_orcamento),
            'ultima_agenda'=>$agenda->ultima_agenda?->format('d/m/Y')??'—',
            'proxima_agenda'=>$agenda->proxima_agenda?->format('d/m/Y')??'—',
            'proximo_orcamento'=>$agenda->proximo_orcamento?->format('d/m/Y')??'—',
            'quantidade_lancamentos'=>$agenda->lancamentos_count,
            'agendamento_status'=>'<span class="badge rounded-pill '.($agenda->ativo?'text-bg-success':'text-bg-secondary').'">'.($agenda->ativo?'Automático ativado':'Automático desativado').'</span>',
            'acoes'=>view('agenda._actions',compact('agenda'))->render(),
        ]);
        return response()->json(['draw'=>(int)$request->input('draw'),'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$rows]);
    }

    public function create(): View { return $this->formView(new AgendaPreventiva(['ativo'=>true]),false); }
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $agora = now();
        $proximaAgenda = $agora->copy()->addDays(max(0, (int) ($data['periodicidade'] ?? 0)));
        $proximoOrcamento = $proximaAgenda->copy()->subDays(max(0, (int) ($data['orcamento'] ?? 0)));
        $data['criado_por'] = $request->session()->get('gi_context.usuario.id');
        $data['proxima_agenda'] = $proximaAgenda;
        $data['proximo_orcamento'] = $proximoOrcamento;

        DB::transaction(function () use ($request, $data, $agora, $proximaAgenda, $proximoOrcamento): void {
            $agenda = AgendaPreventiva::create($data);
            $agenda->lancamentos()->create([
                'ativos_id' => $agenda->ativos_id,
                'locais_id' => $agenda->locais_id,
                'solicitante' => 'Sistema',
                'data_lancamento' => $agora,
                'data_orcamento' => $proximoOrcamento,
                'data_agendamento' => $proximaAgenda,
                'data_inicio' => null,
                'etapa' => 1,
                'ativo' => true,
            ]);
        });

        return redirect()->route('agenda.index')->with('status','Agenda preventiva cadastrada com sucesso.');
    }
    public function show(int $id): View { $agenda=AgendaPreventiva::withTrashed()->with(['equipamento.local.unidade','criador'])->findOrFail($id); return view('agenda.show',compact('agenda')); }
    public function edit(int $id): View { return $this->formView(AgendaPreventiva::withTrashed()->findOrFail($id),true); }
    public function update(Request $request,int $id): RedirectResponse
    {
        $agenda=AgendaPreventiva::withTrashed()->findOrFail($id); $data=$this->validated($request);
        DB::transaction(function () use ($request,$agenda,$data): void {
            $deveCriar=!$agenda->ativo && (bool)($data['ativo']??false) && $request->boolean('criar_proximo_lancamento') && !$agenda->lancamentos()->whereNull('data_arquivamento')->exists();
            $agenda->update($data);
            if($deveCriar){$agora=now();$proxima=$agora->copy()->addDays(max(0,(int)$agenda->periodicidade));$orcamento=$proxima->copy()->subDays(max(0,(int)$agenda->orcamento));$agenda->update(['proxima_agenda'=>$proxima,'proximo_orcamento'=>$orcamento]);$agenda->lancamentos()->create(['ativos_id'=>$agenda->ativos_id,'locais_id'=>$agenda->locais_id,'solicitante'=>'Sistema','data_lancamento'=>$agora,'data_orcamento'=>$orcamento,'data_agendamento'=>$proxima,'etapa'=>1,'ativo'=>true]);}
        });
        return redirect()->route('agenda.index')->with('status','Agenda preventiva atualizada com sucesso.');
    }
    public function destroy(int $id): RedirectResponse { AgendaPreventiva::findOrFail($id)->delete(); return redirect()->route('agenda.index',['include_deleted'=>1])->with('status','Agenda movida para os registros apagados.'); }
    public function restore(int $id): RedirectResponse { AgendaPreventiva::onlyTrashed()->findOrFail($id)->restore(); return redirect()->route('agenda.index',['include_deleted'=>1])->with('status','Agenda restaurada com sucesso.'); }
    public function forceDestroy(int $id): RedirectResponse { AgendaPreventiva::onlyTrashed()->findOrFail($id)->forceDelete(); return redirect()->route('agenda.index',['include_deleted'=>1])->with('status','Agenda excluída permanentemente.'); }

    private function validated(Request $request): array
    {
        $data=$request->validate(['ativos_id'=>['required','integer','exists:manut_ativos,id'],'ultima_agenda'=>['required','date'],'proxima_agenda'=>['required','date'],'proximo_orcamento'=>['required','date'],'periodicidade'=>['required','integer','min:0'],'orcamento'=>['required','integer','min:0'],'ativo'=>['nullable','boolean'],'obs'=>['nullable','string','max:250']]);
        $data['locais_id']=Ativo::withTrashed()->findOrFail($data['ativos_id'])->locais_id; return $data;
    }
    private function formView(AgendaPreventiva $agenda,bool $isEdit): View
    {
        $ativos=Ativo::withTrashed()->with('local.unidade')
            ->where(fn($query)=>$query->where(fn($active)=>$active->where('ativo',true)->whereNull('apagado_em'))->when($agenda->ativos_id,fn($current,$id)=>$current->orWhere('id',$id)))
            ->orderBy('titulo')->get();
        $possuiLancamentoAberto=$agenda->exists && $agenda->lancamentos()->whereNull('data_arquivamento')->exists();
        return view('agenda.form',compact('agenda','isEdit','ativos','possuiLancamentoAberto'));
    }
    private function assetLabel(?Ativo $ativo): string { return $ativo ? ($ativo->titulo?:"Ativo #{$ativo->id}").' ('.($ativo->codigo?:'sem código').') - '.$this->locationLabel($ativo) : '—'; }
    private function locationLabel(?Ativo $ativo): string { return $ativo?->local ? ($ativo->local->titulo?:'Local sem título').' - '.($ativo->local->unidade?->titulo?:'Unidade não informada') : 'Local não informado - Unidade não informada'; }
    private function deadlineLabel($date): string
    {
        if (! $date) return '—';
        $days = now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);

        return $days >= 0 ? "falta {$days} dia(s)" : 'atrasado '.abs($days).' dia(s)';
    }
    private function deadlineClass($date): string
    {
        if (! $date) return '';
        $overdueDays = -now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);
        if ($overdueDays >= 4) return 'prazo-danger';
        if ($overdueDays >= 1) return 'prazo-warning';
        return '';
    }
}
