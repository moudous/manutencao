<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinica extends Model
{
    use SoftDeletes;
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'alterado_em';
    public const DELETED_AT = 'apagado_em';
    protected $table = 'manut_ambientes';
    protected $fillable = ['titulo', 'ativo', 'consultorios'];
    protected function casts(): array { return ['ativo'=>'boolean', 'consultorios'=>'integer', 'criado_em'=>'datetime', 'alterado_em'=>'datetime', 'apagado_em'=>'datetime']; }
    public function checklists(): HasMany { return $this->hasMany(Checklist::class, 'ambiente_id'); }
}
