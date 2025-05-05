<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Category;
use App\Models\MaterialRequest;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function dashboard()
    {
        $totalProducts = Product::count();
        $totalStockins = DB::table('stock_ins')->count();
        $totalStockouts = DB::table('stock_outs')->count();
        $totalMaterialRequests = DB::table('material_request')->count();
        $products = Product::with('category')->get();
        $categories = Category::all();
        $lowStockProducts = Product::with('category')->where('stock', '<', 100)->get();
      
        return view('user.dashboard', compact('totalProducts', 'totalStockins', 'totalStockouts', 'totalMaterialRequests', 'products', 'categories', 'lowStockProducts'));
    }
}


