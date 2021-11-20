<?php

namespace App\Http\Controllers\Admin;

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
        $botActivity = $dashboardManager->getBotActivity();
        $botActiveUsers = $dashboardManager->getBotActiveUsers();

        return new JsonResponse([
            'counter' => $data,
            'bot' => [
                'activity' => $botActivity,
                'users' => $botActiveUsers,
            ]
        ]);
    }

    public function botActivity(Request $request): JsonResponse
    {
        /** @var DashboardManager $dashboardManager */
        $botActivity = app(DashboardManager::class)->getBotActivity($request->date);

        return new JsonResponse($botActivity);
    }

    public function botUsers(Request $request): JsonResponse
    {
        /** @var DashboardManager $dashboardManager */
        $botActivity = app(DashboardManager::class)->getBotActiveUsers($request->date);

        return new JsonResponse($botActivity);
    }
}
