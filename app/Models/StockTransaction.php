<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    public $timestamps = false;
    protected $fillable = ['ingredient_id','type','qty','note','reference_id','created_at'];
    protected $dates    = ['created_at'];

    public function ingredient() { return $this->belongsTo(Ingredient::class); }
}