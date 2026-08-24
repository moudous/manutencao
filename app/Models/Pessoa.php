<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pessoa extends Model
{
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';

    protected $table = 'manut_pessoas';

    protected $fillable = ['id', 'nome', 'email', 'perfil', 'perfil_id', 'locais_id', 'ativo'];

    protected function casts(): array
    {
        return [
            'perfil_id' => 'integer',
            'locais_id' => 'integer',
            'ativo' => 'boolean',
            'criado_em' => 'datetime',
            'atualizado_em' => 'datetime',
        ];
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'locais_id')->withTrashed();
    }
}
