<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;

class StockTransactionController extends Controller
{
    public function index() {
        return response()->json(
            StockTransaction::with('ingredient')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get()
        );
    }
}