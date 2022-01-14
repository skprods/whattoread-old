<?php

namespace App\Telegram\Entities;

use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\ChatMember;
use Telegram\Bot\Objects\User;

class MyChatMember
{
    public Chat $chat;
    public User $from;
    public int $date;
    public ChatMember $oldChatMember;
    public ChatMember $newChatMember;

    public function __construct(array $data)
    {
        $this->chat = new Chat($data['chat']);
        $this->from = new User($data['from']);
        $this->date = $data['date'];
        $this->oldChatMember = new ChatMember($data['old_chat_member']);
        $this->newChatMember = new ChatMember($data['new_chat_member']);
    }
}