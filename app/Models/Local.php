<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Local extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';
    public const DELETED_AT = 'apagado_em';

    protected $table = 'manut_locais';

    protected $fillable = ['titulo', 'unidades_id', 'ativo'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
            'apagado_em' => 'datetime',
        ];
    }

    public function ativos(): HasMany
    {
        return $this->hasMany(Ativo::class, 'locais_id');
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidades_id')->withTrashed();
    }

    public function pessoas(): HasMany
    {
        return $this->hasMany(Pessoa::class, 'locais_id');
    }
}
