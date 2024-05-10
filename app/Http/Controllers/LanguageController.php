<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Add new locale
     * @group Translations
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'locale' => 'required|string|unique:languages',
            'name' => 'required|string',
            'active' => 'boolean',
        ]);

        $language = Language::create([
            'locale' => $validatedData['locale'],
            'name' => $validatedData['name'],
            'active' => $request->input('active', true),
        ]);

        return response()->json(['message' => 'Language added successfully', 'data' => $language], 201);
    }
}
