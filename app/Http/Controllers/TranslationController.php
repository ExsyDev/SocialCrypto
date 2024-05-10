<?php

namespace App\Http\Controllers;

use Spatie\TranslationLoader\LanguageLine;

class TranslationController extends Controller
{
    /**
     * Add translation
     * @group Translations
     * @return string[]
     */
    public function __invoke(): array
    {
        request()->validate([
            'group' => 'required|string',
            'key' => 'required|string',
            'translations' => 'required|array'
        ]);

        $translation = LanguageLine::create([
            'group' => request('group'),
            'key' => request('key'),
            'text' => request('translations'),
        ]);

        if($translation) {
            return ['status' => 'Translation added successfully;'];
        }

        return ['status' => 'Error has occurred!'];
    }
}
