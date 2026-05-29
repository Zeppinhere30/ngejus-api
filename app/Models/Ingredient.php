<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['name','unit','stock','min_stock','cost_per_unit'];

    public function recipes()      { return $this->hasMany(ProductRecipe::class); }
    public function transactions() { return $this->hasMany(StockTransaction::class); }

    public function getIsLowStockAttribute() { return $this->stock <= $this->min_stock; }
}