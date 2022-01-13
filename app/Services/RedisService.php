<?php

namespace App\Services;

use App\Entities\ChatInfo;
use Redis;

class RedisService
{
    private Redis $redis;

    public function __construct(string $connectionName = 'default')
    {
        $this->redis = app(Redis::class);

        $settings = config("database.redis.$connectionName");
        $password = ($settings['password'] !== '') ? $settings['password'] : null;

        $this->redis->connect($settings['host'], $settings['port']);
        $this->redis->auth($password);
        $this->redis->select($settings['database']);
    }

    public function getChatInfo(int $chatId): ChatInfo
    {
        $data = json_decode($this->redis->get($chatId), true);

        return new ChatInfo($chatId, $data ?? []);
    }

    public function setChatInfo(ChatInfo $chatInfo)
    {
        $data = $chatInfo->toJson();

        $this->redis->set($chatInfo->id, $data);
    }
}
