<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class VersionConflict extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Заявка уже изменена. Обновите её и повторите действие.',
            'code' => 'version_conflict',
        ], 409);
    }
}
