<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id','name','slug','description',
        'image_url','price','cost_price','is_active'
    ];

    public function category()   { return $this->belongsTo(Category::class); }
    public function recipes()    { return $this->hasMany(ProductRecipe::class); }
    public function orderItems() { return $this->hasMany(OrderItem::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}