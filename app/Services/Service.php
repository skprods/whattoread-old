<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Facades\Console;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class Service
{
    /** Нужно ли выводить процесс в консоль */
    protected bool $debug;

    /** В какой канал логировать процесс */
    protected string $logChannel;

    /** Название сервиса для логирования */
    protected string $serviceName;

    /** Бар для визуализации процесса */
    protected ProgressBar $bar;

    /**
     * @param bool $debug               - нужно ли выводить процесс в консоль
     * @param string|null $logChannel   - в какой канал логировать процесс
     */
    public function __construct(bool $debug = false, string $logChannel = null)
    {
        $this->debug = $debug;
        $this->logChannel = $logChannel ?? config('logging.default');
        $this->serviceName = $this->serviceName ?? static::class;

        $this->bar = Console::bar();
    }

    public function setDebug(bool $debug = true): Service
    {
        $this->debug = $debug;
        return $this;
    }

    /** Логирование ошибки */
    protected function error(string $message, string $identifier = null): void
    {
        $prefix = $identifier ? "[$this->serviceName #$identifier]: " : "[$this->serviceName]: ";

        Log::channel($this->logChannel)->error($prefix . $message);
        $this->console($message, $identifier, 'error');
    }

    /** Логирование предупреждения */
    protected function warning(string $message, string $identifier = null): void
    {
        $prefix = $identifier ? "[$this->serviceName #$identifier]: " : "[$this->serviceName]: ";

        Log::channel($this->logChannel)->warning($prefix . $message);
        $this->console($message, $identifier, 'comment');
    }

    /** Логирование произвольного сообщения */
    protected function log(string $message, string $identifier = null): void
    {
        $prefix = $identifier ? "[$this->serviceName #$identifier]: " : "[$this->serviceName]: ";

        Log::channel($this->logChannel)->info($prefix . $message);
        $this->console($message, $identifier, 'info');
    }

    /** Вывод информации в консоль */
    protected function console(string $message, string $identifier = null, string $type = 'info'): void
    {
        if ($this->debug) {
            $prefix = $identifier ? "[$this->serviceName #$identifier]: " : "[$this->serviceName]: ";

            Console::$type($prefix . $message);
        }
    }

    protected function consoleNewLine(): void
    {
        if ($this->debug) {
            Console::info('');
        }
    }
}
