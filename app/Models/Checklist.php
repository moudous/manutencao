<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    public $timestamps = false;
    protected $table = 'manut_checklist';
    protected $fillable = ['ambiente_id', 'responsavel', 'turno', 'inicio', 'fim'];
    protected function casts(): array { return ['inicio'=>'datetime', 'fim'=>'datetime']; }
    public function clinica(): BelongsTo { return $this->belongsTo(Clinica::class, 'ambiente_id')->withTrashed(); }
    public function pessoaResponsavel(): BelongsTo { return $this->belongsTo(Pessoa::class, 'responsavel'); }
    public function itens(): HasMany { return $this->hasMany(ChecklistItem::class, 'checklist'); }
}
