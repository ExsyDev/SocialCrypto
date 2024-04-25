<?php

namespace App\Http\Controllers;

use App\Models\Tab;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TabController extends Controller
{
    /**
     * Get tabs of the user
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if($request->user()) {
            return $request->user()->tabs;
        }

        return response()->json([
            'message' => 'Unauthorized'
        ],401);
    }

    /**
     * Create tab user
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        if(!$request->name) {
            return response()->json([
                'message' => 'Please fill the name of the tab'
            ],412);
        }

        if($request->user()) {
            return $request->user()->tabs()->create([
                'name' => $request->name //TODO: IMPLEMENT TRON WALLET GENERATOR
            ]);
        }

        return response()->json([
            'message' => 'Unauthorized'
        ],401);
    }
}
