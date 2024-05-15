<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    /**
     * @param Request $request
     * @param $walletId
     * @return JsonResponse
     */
    public function getBalancesByWallet(Request $request, $walletId): JsonResponse
    {
        $balances = Balance::where('wallet_id', $walletId)->get();
        return response()->json($balances);
    }

    /**
     * @param Request $request
     * @param $walletId
     * @return JsonResponse
     */
    public function getMainAndSecondaryBalances(Request $request, $walletId): JsonResponse
    {
        $mainBalances = Balance::where('wallet_id', $walletId)->where('type', 'basic')->get();
        $secondaryBalances = Balance::where('wallet_id', $walletId)->where('type', 'secondary')->get();

        return response()->json([
            'main_balances' => $mainBalances,
            'secondary_balances' => $secondaryBalances
        ]);
    }
}
