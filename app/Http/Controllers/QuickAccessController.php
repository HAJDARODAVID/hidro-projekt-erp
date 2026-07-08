<?php

namespace App\Http\Controllers;

use App\Services\Application\QuickAccessShortcutsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickAccessController extends Controller
{
    public function execute(Request $request, QuickAccessShortcutsService $shortcutsService): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:2', 'max:20'],
        ]);

        $result = $shortcutsService->executeActionByCode(Str::upper(trim($validated['code'])));

        return response()->json($result);
    }
}
