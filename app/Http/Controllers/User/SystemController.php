<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Managers\DashboardManager;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function info(): JsonResponse
    {
        /** @var DashboardManager $dashboardManager */
        $dashboardManager = app(DashboardManager::class);

        $data = Cache::get('system.info');
        $messages = $dashboardManager->getMessagesCountByMonth();

        return new JsonResponse([
            'counter' => $data,
            'messages' => $messages,
        ]);
    }

    public function telegramMessages(Request $request): JsonResponse
    {
        /** @var DashboardManager $dashboardManager */
        $messages = app(DashboardManager::class)->getMessagesCountByMonth($request->date);

        return new JsonResponse($messages);
    }
}
