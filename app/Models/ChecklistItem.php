<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChecklistItem extends Model
{
    public $timestamps = false;
    protected $table = 'manut_checklist_itens';
    protected $fillable = ['checklist', 'ambiente_id', 'equipamento_id', 'ok'];
    protected function casts(): array { return ['ok'=>'integer']; }
    public function checklistRegistro(): BelongsTo { return $this->belongsTo(Checklist::class, 'checklist'); }
    public function equipamento(): BelongsTo { return $this->belongsTo(Equipamento::class, 'equipamento_id')->withTrashed(); }
    public function problema(): HasOne { return $this->hasOne(ChecklistProblema::class, 'checklistitemid'); }
}
