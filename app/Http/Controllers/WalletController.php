<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if($request->user()) {
            return response()->json($request->user()->wallets);
        }

        return response()->json([
            'message' => 'Unauthorized'
        ],401);
    }

    /**
     * Create wallet user
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        if($request->user()) {
            $wallet = $request->user()->wallets()->create([
                'wallet' => \Str::random() //TODO: IMPLEMENT TRON WALLET GENERATOR
            ]);

            if ($wallet) {
                return response()->json(
                    ['message' => 'Wallet created successfully!', 'wallet' => $wallet->wallet]
                );
            }
        }

        return response()->json([
            'message' => 'Unauthorized'
        ],401);
    }
}
