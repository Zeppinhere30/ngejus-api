<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number','customer_id','cashier_id','status',
        'subtotal','discount','tax','total',
        'payment_method','amount_paid','change_amount','notes','paid_at'
    ];
    protected $dates = ['paid_at'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function cashier()  { return $this->belongsTo(User::class, 'cashier_id'); }
    public function items()    { return $this->hasMany(OrderItem::class); }

    public function scopePaid($q)    { return $q->where('status', 'paid'); }
    public function scopePending($q) { return $q->where('status', 'pending'); }
}