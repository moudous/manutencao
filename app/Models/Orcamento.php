<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orcamento extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';
    public const DELETED_AT = 'apagado_em';

    protected $table = 'manut_orcamentos';
    protected $fillable = ['descricao', 'link', 'centro_custo', 'lancamentos_id', 'situacao_id'];

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'lancamentos_id');
    }
}
