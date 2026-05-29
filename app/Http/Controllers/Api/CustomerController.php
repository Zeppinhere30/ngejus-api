<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index() {
        return response()->json(Customer::orderBy('name')->get());
    }

    public function store(Request $request) {
        $request->validate(['name' => 'required']);
        return response()->json(Customer::create($request->all()), 201);
    }

    public function show(Customer $customer) {
        return response()->json($customer->load('orders'));
    }

    public function update(Request $request, Customer $customer) {
        $customer->update($request->all());
        return response()->json($customer);
    }

    public function destroy(Customer $customer) {
        $customer->delete();
        return response()->json(['message' => 'Deleted']);
    }
}