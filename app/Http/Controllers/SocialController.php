<?php

namespace App\Http\Controllers;

use App\Models\SocialCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    /**
     * Get the social categories
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if($request->user()) {
            return response()->json(SocialCategory::all()->toArray());
        }

        return response()->json([
            'message' => 'Unauthorized'
        ],401);
    }

    /**
     * Create social category
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        if(!$request->name) {
            return response()->json([
                'message' => 'Please fill the name of the social category'
            ],412);
        }

        if($request->user()) {
            return SocialCategory::create([
                'name' => $request->name
            ]);
        }

        return response()->json([
            'message' => 'Unauthorized'
        ],401);
    }
}
