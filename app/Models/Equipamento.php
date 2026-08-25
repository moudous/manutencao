<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipamento extends Model
{
    use SoftDeletes;
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'alterado_em';
    public const DELETED_AT = 'apagado_em';
    protected $table = 'manut_equipamentos';
    protected $fillable = ['titulo', 'ativo'];
    protected function casts(): array { return ['ativo'=>'boolean', 'criado_em'=>'datetime', 'alterado_em'=>'datetime', 'apagado_em'=>'datetime']; }
}
