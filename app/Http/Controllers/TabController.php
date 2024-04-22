<?php

namespace App\Http\Controllers;

use App\Models\Tab;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class TabController extends Controller
{
    /**
     * @return Collection
     */
    public function tab(): Collection
    {
        return Tab::all();
    }
}
