<?php

namespace App\Http\Controllers;

use App\Models\SocialCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    /**
     * @return Collection
     */
    public function index(): Collection
    {
        return SocialCategory::all();
    }
}
