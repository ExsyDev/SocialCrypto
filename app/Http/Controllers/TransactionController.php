<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function sendFunds(Request $request): JsonResponse
    {
        $requestData = $request->all();
        // Обработка запроса и отправка средств
        return response()->json(['message' => 'Funds sent successfully']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransactionCost(Request $request): JsonResponse
    {
        $requestData = $request->all();
        return response()->json(['transaction_cost' => 10.0]);
    }
}
