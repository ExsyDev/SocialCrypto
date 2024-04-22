<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * @return Collection
     */
    public function index(): Collection
    {
        return Wallet::all();
    }
}
