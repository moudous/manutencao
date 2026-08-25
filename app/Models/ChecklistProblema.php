<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistProblema extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'checklistitemid';
    protected $table = 'checklist_problemas';
    protected $fillable = ['checklistitemid', 'ambiente_id', 'problema'];
    public function item(): BelongsTo { return $this->belongsTo(ChecklistItem::class, 'checklistitemid'); }
}
