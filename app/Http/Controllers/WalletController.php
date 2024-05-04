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
     * @throws TronException
     */
    public function create(Request $request): JsonResponse
    {
        if($request->user()) {
            $fullNode = new HttpProvider(config('services.node.url'));
            $solidityNode = new HttpProvider(config('services.node.url'));
            $eventServer = new HttpProvider(config('services.node.url'));

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

    /**
     * @throws TronException
     */
    public function getTokenInfo($wallet)
    {
        $tron = new HttpProvider(config('services.node.url'));

        return [$wallet->wallet => [
            'type' => 'basic',
            'amount' => 1.000000,
            'risk' => false,
            'tokenInfo' => [
                'tokenId' => $wallet,
                'tokenAbbr' => $wallet->token,
                'tokenName' => $wallet->token->name,
                'tokenDecimal' => 6,
                'tokenType' => 'trc20',
                'tokenLogo' => ''
            ]
        ]];
    }
}
