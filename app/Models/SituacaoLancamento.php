<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SituacaoLancamento extends Model
{
    use SoftDeletes;
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';
    public const DELETED_AT = 'apagado_em';
    protected $table = 'manut_situacao_lancamento';
    protected $fillable = ['titulo', 'ativo'];
    protected function casts(): array { return ['ativo'=>'boolean', 'criado_em'=>'datetime', 'atualizado_em'=>'datetime', 'apagado_em'=>'datetime']; }
    public function lancamentos(): HasMany { return $this->hasMany(Lancamento::class, 'situacao_id'); }
}
