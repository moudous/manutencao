<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use SoftDeletes;
    public const CREATED_AT='criado_em'; public const UPDATED_AT='atualizado_em'; public const DELETED_AT='apagado_em';
    protected $table='manut_compras';
    protected $fillable=['titulo','unidade','quantidade','quantidade_unitaria','qtde_utilizada','comprador','preco','data_compra','disponivel'];
    protected function casts(): array { return ['quantidade'=>'float','quantidade_unitaria'=>'float','qtde_utilizada'=>'float','preco'=>'float','data_compra'=>'date','disponivel'=>'boolean','criado_em'=>'datetime','atualizado_em'=>'datetime','apagado_em'=>'datetime']; }
}
