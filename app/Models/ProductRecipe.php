<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipe extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id','ingredient_id','qty_used'];

    public function ingredient() { return $this->belongsTo(Ingredient::class); }
    public function product()    { return $this->belongsTo(Product::class); }
}