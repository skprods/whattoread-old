<?php

namespace App\Http\Controllers\Admin;

use App\Http\Collections\Admin\ExceptionsCollection;
use App\Http\Controllers\Controller;
use App\Managers\DashboardManager;
use App\Models\Book;
use App\Models\Exception;
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
        $botActiveUsersLastYear = $dashboardManager->getBotActiveUsers(now()->year - 1);

        return new JsonResponse([
            'counter' => $data,
            'bot' => [
                'activity' => $botActivity,
                'users' => $botActiveUsers,
                'users_prev_year' => $botActiveUsersLastYear,
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

    public function exceptions(Request $request)
    {
        $exceptions = Exception::query()->orderByDesc('id')->limit($request->limit ?? 10)->get();

        return new ExceptionsCollection($exceptions);
    }
}
