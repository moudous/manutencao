<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lancamento extends Model
{
    use SoftDeletes;
    public const CREATED_AT='criado_em'; public const UPDATED_AT='atualizado_em'; public const DELETED_AT='apagado_em';
    protected $table='manut_lancamentos';
    protected $guarded=[];
    protected function casts(): array { return ['data_lancamento'=>'datetime','data_orcamento'=>'datetime','data_arquivamento'=>'datetime','data_agendamento'=>'datetime','criado_em'=>'datetime','atualizado_em'=>'datetime','apagado_em'=>'datetime','ativo'=>'boolean']; }
    public function agenda(): BelongsTo { return $this->belongsTo(AgendaPreventiva::class,'agenda_id')->withTrashed(); }
    public function equipamento(): BelongsTo { return $this->belongsTo(Ativo::class,'ativos_id')->withTrashed(); }
    public function local(): BelongsTo { return $this->belongsTo(Local::class,'locais_id')->withTrashed(); }
    public function tecnico(): BelongsTo { return $this->belongsTo(Pessoa::class,'tecnicos_id'); }
}
