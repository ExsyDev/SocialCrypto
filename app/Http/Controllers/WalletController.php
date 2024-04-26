<?php

namespace App\Http\Controllers;

use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Get user wallets
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
            $fullNode = new HttpProvider('http://65.108.233.218:8090');
            $solidityNode = new HttpProvider('http://65.108.233.218:8091');
            $eventServer = new HttpProvider('http://65.108.233.218:8090');

            try {
                $tron = new Tron($fullNode, $solidityNode, $eventServer);

                $account = $tron->createAccount();
                $wallet = $account->getAddress();
                $privateKey = $account->getPrivateKey();
            } catch (TronException $e) {
                exit($e->getMessage());
            }

            $wallet = $request->user()->wallets()->create([
                'wallet' => $wallet,
                'private_key' => $privateKey
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
