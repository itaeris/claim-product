<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CatalogQuery;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function index(CatalogQuery $catalog): JsonResponse
    {
        $user = request()->user();

        return response()->json([
            ...$catalog->payload(),
            'employee' => [
                'name' => $user instanceof User ? $user->name : null,
                'team' => $user instanceof User ? $user->team : null,
                'initials' => $user instanceof User ? $user->initials : null,
                'is_admin' => $user instanceof User && $user->is_admin,
            ],
        ]);
    }
}
