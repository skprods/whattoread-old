<?php

namespace App\Exceptions;

use App\Managers\ExceptionManager;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    private array $notNotifiable = [
        BaseException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        ValidationException::class,
        UnauthorizedException::class,
        TelegramSDKException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            try {
                app(ExceptionManager::class)->create([
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                Log::error(json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
            } catch (Exception $exception) {
                Log::error(json_encode($exception, JSON_UNESCAPED_UNICODE));
            }

            $notify = true;

            foreach ($this->notNotifiable as $exception) {
                if ($e instanceof $exception) {
                    $notify = false;
                    break;
                }
            }

            if ($e->getMessage() === 'Token has expired') {
                $notify = false;
            }

            if ($notify && env('APP_ENV') === 'production') {
                $this->sendNotificationToTelegram($e);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        $trace = env("APP_DEBUG") ? ['trace' => $e->getTrace()] : [];

        if ($e instanceof BaseException) {
            return $e->render();
        } elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return $this->sendNotFoundError($e, $trace);
        } elseif ($e instanceof ValidationException) {
            return $this->sendValidationError($e, $trace);
        } elseif ($e->getMessage() === 'Token has expired') {
            $error = array_merge([
                'code' => 401,
                'message' => 'Время действия токена истекло.',
            ], $trace);

            return response()->json([
                'success' => false,
                'error' => $error,
            ], 401);
        } elseif ($e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
            $e = new ForbiddenException();
            return $e->render();
        } elseif ($e instanceof TelegramSDKException) {
            return 'ok';
        } else {
            $errorDetail = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            $error = $this->prepareResponseStructure($errorDetail, $trace);

            return response()->json($error, 500);
        }
    }

    private function sendNotFoundError(Exception $e, array $trace): JsonResponse
    {
        $message = ($e->getMessage() !== '') ? $e->getMessage() : 'Ничего не найдено';

        $errorDetail = [
            'code' => 404,
            'message' => $message,
        ];

        $error = $this->prepareResponseStructure($errorDetail, $trace);

        return response()->json($error, 404);
    }

    private function sendValidationError(ValidationException $e, array $trace): JsonResponse
    {
        $message = "Переданные данные невалидны.";
        $code = $e->getCode();
        $validator = [];

        foreach ($e->validator->errors()->messages() as $field => $error) {
            $validator[$field] = array_shift($error);
        }

        $errorDetail = [
            'code' => $code,
            'message' => $message,
            'validator' => $validator,
        ];

        $error = $this->prepareResponseStructure($errorDetail, $trace);

        return response()->json($error, 400);
    }

    private function prepareResponseStructure(array $errorDetail, array $trace): array
    {
        return array_merge([
            'success' => false,
            'error' => $errorDetail,
        ], $trace);
    }

    private function sendNotificationToTelegram(Throwable $e)
    {
        $token = config('telegram.bots.whattoread.token');

        /** @var NotificationService $notificationService */
        $notificationService = app(NotificationService::class, ['telegram' => new Api($token)]);
        $notificationService->notifyForException($e);
    }
}
