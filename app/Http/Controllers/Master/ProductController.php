<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Machine;
use App\Models\MachineStandard;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::with('standards.machine')->get();
        $machines = Machine::all();
        $standards = MachineStandard::with(['product', 'machine'])->get();

        return Inertia::render('Master/Products', [
            'products' => $products,
            'machines' => $machines,
            'standards' => $standards,
        ]);
    }
}

