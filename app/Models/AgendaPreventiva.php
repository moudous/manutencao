<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgendaPreventiva extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';
    public const DELETED_AT = 'apagado_em';

    protected $table = 'manut_agenda_preventiva';
    protected $fillable = ['obs', 'ultima_agenda', 'proxima_agenda', 'proximo_orcamento', 'ativos_id', 'periodicidade', 'orcamento', 'criado_por', 'ativo', 'locais_id'];

    protected function casts(): array
    {
        return ['ultima_agenda'=>'datetime', 'proxima_agenda'=>'datetime', 'proximo_orcamento'=>'datetime', 'ativo'=>'boolean', 'criado_em'=>'datetime', 'atualizado_em'=>'datetime', 'apagado_em'=>'datetime'];
    }

    public function equipamento(): BelongsTo { return $this->belongsTo(Ativo::class, 'ativos_id')->withTrashed(); }
    public function local(): BelongsTo { return $this->belongsTo(Local::class, 'locais_id')->withTrashed(); }
    public function criador(): BelongsTo { return $this->belongsTo(Pessoa::class, 'criado_por'); }
    public function lancamentos(): HasMany { return $this->hasMany(Lancamento::class,'agenda_id'); }
}
