<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('gender')->orderBy('age_min')->get();
        return response()->json($categories);
    }

    /**
     * Initialize FFA categories from seeder
     */
    public function initFFA(): JsonResponse
    {
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        return response()->json([
            'message' => '36 catégories FFA officielles 2025 initialisées avec succès',
            'categories' => Category::all()
        ]);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'gender' => 'required|in:M,F',
            'age_min' => 'required|integer|min:0',
            'age_max' => 'required|integer|gte:age_min',
            'color' => 'nullable|string|max:20',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Display the specified category
     */
    public function show(Category $category): JsonResponse
    {
        $category->load('entrants');
        return response()->json($category);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'gender' => 'sometimes|in:M,F',
            'age_min' => 'sometimes|integer|min:0',
            'age_max' => 'sometimes|integer|gte:age_min',
            'color' => 'nullable|string|max:20',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
