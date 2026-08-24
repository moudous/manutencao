<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ativo extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';
    public const DELETED_AT = 'apagado_em';

    protected $table = 'manut_ativos';

    protected $fillable = [
        'codigo',
        'titulo',
        'descricao',
        'data_aquisicao',
        'locais_id',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_aquisicao' => 'date',
            'ativo' => 'boolean',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
            'apagado_em' => 'datetime',
        ];
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'locais_id')->withTrashed();
    }
}
