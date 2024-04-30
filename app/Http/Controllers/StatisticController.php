<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StatisticController extends Controller
{
    /**
     * @return mixed
     */
    public function index()
    {
        return auth()->user()->wallets->only('statistic');
    }
}
