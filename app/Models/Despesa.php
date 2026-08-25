<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Despesa extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';
    public const DELETED_AT = 'apagado_em';

    protected $table = 'manut_despesas';
    protected $fillable = ['compra_id', 'lancamentos_id', 'quantidade', 'tipos_despesa_id', 'custo', 'unidade'];
    protected function casts(): array { return ['quantidade'=>'float', 'custo'=>'float', 'criado_em'=>'datetime', 'atualizado_em'=>'datetime', 'apagado_em'=>'datetime']; }
    public function compra(): BelongsTo { return $this->belongsTo(Compra::class, 'compra_id')->withTrashed(); }
    public function lancamento(): BelongsTo { return $this->belongsTo(Lancamento::class, 'lancamentos_id'); }
}
