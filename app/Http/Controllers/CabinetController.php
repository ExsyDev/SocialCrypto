<?php

namespace App\Http\Controllers;

use App\Models\Cabinet;
use Illuminate\Http\Request;

class CabinetController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $data = $request->all();

        $cabinet = Cabinet::createCabinet($data);

        return response()->json($cabinet, 201); // Отправляем ответ с созданным кабинетом
    }

    /**
     * @param Request $request
     * @param Cabinet $cabinet
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Cabinet $cabinet)
    {
        $data = $request->all();

        $cabinet->updateCabinet($data);

        return response()->json($cabinet);
    }

    /**
     * @param Request $request
     * @param $id
     * @return void
     */
    public function switch(Request $request, $id)
    {
        // Логика переключения между кабинетами
    }
}
