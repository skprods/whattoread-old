<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Telegram\Bot\Api;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
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
        } elseif ($e instanceof TelegramException) {
            $telegram = new Api(config('telegram.bots.whattoread.token'));
            $telegram->sendMessage([
                'chat_id' => env('ERROR_CHAT_ID'),
                'text' => "*ОШИБКА*:\nКод: {$e->getCode()}\n{$e->telegramText}",
                'parse_mode' => 'markdown',
            ]);
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
}
