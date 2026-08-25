<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Clinica;
use App\Models\Equipamento;
use App\Models\Pessoa;
use App\Services\GiPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChecklistController extends Controller
{
    public function index(Request $request): View
    {
        $usuarioId = (int) $request->session()->get('gi_context.usuario.id');
        $checklist = Checklist::query()->where('responsavel', $usuarioId)->whereNull('fim')->with(['clinica', 'itens.problema'])->latest('id')->first();
        return view('checklist.index', [
            'checklist'=>$checklist, 'clinicas'=>Clinica::query()->where('ativo', true)->orderBy('titulo')->get(),
            'equipamentos'=>Equipamento::query()->where('ativo', true)->orderBy('titulo')->get(),
            'responsavel'=>Pessoa::query()->find($usuarioId)?->nome ?: $request->session()->get('gi_context.usuario.nome'),
            'itens'=>$checklist ? $this->itemsPayload($checklist) : [],
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $usuarioId = (int) $request->session()->get('gi_context.usuario.id');
        $data = $request->validate([
            'ambiente_id'=>['required','integer',Rule::exists('manut_ambientes','id')->where(fn ($q) => $q->where('ativo', true)->whereNull('apagado_em'))],
            'turno'=>['required',Rule::in(['m','t','n'])],
        ]);
        DB::transaction(function () use ($usuarioId, $data): void {
            $existente = Checklist::query()->where('responsavel', $usuarioId)->whereNull('fim')->lockForUpdate()->exists();
            abort_if($existente, 422, 'Já existe uma verificação em andamento para este responsável.');
            Checklist::create($data + ['responsavel'=>$usuarioId, 'inicio'=>now(), 'fim'=>null]);
        });
        return redirect()->route('checklist.index')->with('status', 'Verificação iniciada com sucesso.');
    }

    public function updateItem(Request $request, Checklist $checklist): JsonResponse
    {
        $this->ensureEditable($request, $checklist);
        $data = $request->validate(['consultorio'=>['required','integer','min:1'], 'equipamento_id'=>['required','integer',Rule::exists('manut_equipamentos','id')->where(fn($q)=>$q->where('ativo',true)->whereNull('apagado_em'))], 'estado'=>['nullable','integer',Rule::in([0,1])], 'problema'=>['nullable','string','max:50']]);
        abort_if($data['consultorio'] > (int) $checklist->clinica?->consultorios, 422, 'Consultório inválido para esta clínica.');
        DB::transaction(function () use ($checklist, $data): void {
            $item = ChecklistItem::query()->where(['checklist'=>$checklist->id, 'ambiente_id'=>$data['consultorio'], 'equipamento_id'=>$data['equipamento_id']])->first();
            if ($data['estado'] === null) { if ($item) { $item->problema()->delete(); $item->delete(); } return; }
            $item ??= new ChecklistItem(['checklist'=>$checklist->id, 'ambiente_id'=>$data['consultorio'], 'equipamento_id'=>$data['equipamento_id']]);
            $item->ok = (int) $data['estado']; $item->save();
            if ((int) $data['estado'] === 0) $item->problema()->updateOrCreate([], ['ambiente_id'=>$data['consultorio'], 'problema'=>$data['problema'] ?? null]);
            else $item->problema()->delete();
        });
        $checklist->load('itens.problema');
        return response()->json(['message'=>'Verificação salva.', 'itens'=>$this->itemsPayload($checklist), 'status'=>$this->roomStatuses($checklist)]);
    }

    public function finish(Request $request, Checklist $checklist): JsonResponse
    {
        $this->ensureEditable($request, $checklist);
        $equipamentos = Equipamento::query()->where('ativo', true)->orderBy('id')->pluck('id');
        if ((int) $checklist->clinica?->consultorios < 1 || $equipamentos->isEmpty()) return response()->json(['message'=>'Cadastre consultórios e equipamentos ativos antes de finalizar.'], 422);
        $marcados = $checklist->itens()->get()->keyBy(fn ($item) => $item->ambiente_id.'-'.$item->equipamento_id);
        for ($consultorio=1; $consultorio <= (int) $checklist->clinica->consultorios; $consultorio++) {
            foreach ($equipamentos as $equipamentoId) if (! $marcados->has($consultorio.'-'.$equipamentoId)) return response()->json(['message'=>'Marque todas as verificações antes de finalizar.', 'next'=>['consultorio'=>$consultorio, 'equipamento_id'=>$equipamentoId]], 422);
        }
        $checklist->update(['fim'=>now()]);
        $request->session()->flash('status', 'Verificação finalizada com sucesso.');
        return response()->json(['message'=>'Verificação finalizada com sucesso.', 'redirect'=>route('checklist_terminados.index')]);
    }

    public function completedIndex(): View { return view('checklist.completed-index'); }
    public function completedData(Request $request): JsonResponse
    {
        $query = Checklist::query()->whereNotNull('fim')->with(['clinica','pessoaResponsavel','itens']);
        $total=(clone $query)->count(); $search=trim((string)$request->input('search.value'));
        if($search!=='')$query->where(fn($q)=>$q->whereHas('clinica',fn($c)=>$c->where('titulo','like',"%{$search}%"))->orWhereHas('pessoaResponsavel',fn($p)=>$p->where('nome','like',"%{$search}%")));
        $filtered=(clone $query)->count(); $columns=['id','ambiente_id','responsavel','turno',null,'inicio','fim']; $column=$columns[(int)$request->input('order.0.column',0)]??'id';$direction=$request->input('order.0.dir')==='asc'?'asc':'desc';$length=min(max((int)$request->input('length',10),1),100);
        if ($column === null) $column = 'id';
        $podeVisualizar=app(GiPermissionService::class)->permite('checklist.visualizar',$request);
        $rows=$query->orderBy($column,$direction)->skip(max((int)$request->input('start',0),0))->take($length)->get()->map(fn(Checklist $item)=>['id'=>$item->id,'clinica'=>e($item->clinica?->titulo?:'—'),'responsavel'=>e($item->pessoaResponsavel?->nome?:'—'),'turno'=>$this->turno($item->turno),'problemas'=>$this->problemRooms($item),'inicio'=>$item->inicio?->format('d/m/Y H:i')??'—','fim'=>$item->fim?->format('d/m/Y H:i')??'—','duracao'=>$item->inicio&&$item->fim?$item->inicio->diff($item->fim)->format('%H:%I:%S'):'—','acoes'=>$podeVisualizar?'<a href="'.e(route('checklist_terminados.show',$item,false)).'" class="btn btn-sm btn-outline-dark listagem-acao" title="Visualizar"><i class="bi bi-eye-fill"></i></a>':'']);
        return response()->json(['draw'=>(int)$request->input('draw'),'recordsTotal'=>$total,'recordsFiltered'=>$filtered,'data'=>$rows]);
    }

    public function completedShow(Checklist $checklist): View
    {
        abort_if($checklist->fim === null, 404); $checklist->load(['clinica','pessoaResponsavel','itens.problema']);
        $statusConsultorios = [];
        for ($room=1; $room <= (int) $checklist->clinica?->consultorios; $room++) {
            $statusConsultorios[$room] = $checklist->itens->where('ambiente_id', $room)->contains(fn ($item) => ! $item->ok) ? 'warning' : 'complete';
        }
        return view('checklist.completed-show',['checklist'=>$checklist,'equipamentos'=>Equipamento::withTrashed()->whereIn('id',$checklist->itens->pluck('equipamento_id'))->orderBy('titulo')->get(),'itens'=>$this->itemsPayload($checklist),'statusConsultorios'=>$statusConsultorios]);
    }

    private function ensureEditable(Request $request, Checklist $checklist): void { abort_unless((int)$checklist->responsavel===(int)$request->session()->get('gi_context.usuario.id'),403);abort_if($checklist->fim!==null,422,'Esta verificação já foi finalizada.');$checklist->loadMissing('clinica'); }
    private function itemsPayload(Checklist $checklist): array { return $checklist->itens->mapWithKeys(fn($i)=>[$i->ambiente_id.'-'.$i->equipamento_id=>['id'=>$i->id,'estado'=>$i->ok?1:0,'problema'=>$i->problema?->problema]])->all(); }
    private function roomStatuses(Checklist $checklist): array
    {
        $total=Equipamento::query()->where('ativo',true)->count();$result=[];
        for($room=1;$room<=(int)$checklist->clinica?->consultorios;$room++){ $items=$checklist->itens->where('ambiente_id',$room);$result[$room]=$items->isEmpty()?'empty':($items->count()<$total?'partial':($items->contains(fn($i)=>!$i->ok)?'warning':'complete')); }
        return $result;
    }
    private function problemRooms(Checklist $checklist): string
    {
        $rooms = $checklist->itens
            ->where('ok', 0)
            ->pluck('ambiente_id')
            ->unique()
            ->sort()
            ->map(fn ($room) => str_pad((string) $room, 2, '0', STR_PAD_LEFT))
            ->values();

        return $rooms->isNotEmpty() ? $rooms->implode(', ') : '—';
    }
    private function turno(?string $turno): string { return ['m'=>'Manhã','t'=>'Tarde','n'=>'Noite'][$turno]??'—'; }
}
