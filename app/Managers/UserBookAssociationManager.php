<?php

namespace App\Managers;

use App\Exceptions\UserBookAssociationException;
use App\Models\Book;
use App\Models\Stat;
use App\Models\UserBookAssociation;

class UserBookAssociationManager
{
    private ?UserBookAssociation $userBookAssociation;
    private StatManager $statManager;

    public function __construct(UserBookAssociation $userBookAssociation = null)
    {
        $this->userBookAssociation = $userBookAssociation;
        $this->statManager = app(StatManager::class);
    }

    /**
     * Создание записи, если такой уже не существует
     *
     * Если запись уже существует, вернётся null, т.к. ничего не произошло
     *
     * @throws UserBookAssociationException
     */
    public function createIfNotExists(array $params, Book $book): ?UserBookAssociation
    {
        $userBookAssociation = UserBookAssociation::checkExists(
            $params['association'],
            $book->id,
            $params['user_id'] ?? null,
            $params['telegram_user_id'] ?? null
        );

        if ($userBookAssociation) {
            return null;
        } else {
            return $this->create($params, $book);
        }
    }

    /**
     * @throws UserBookAssociationException
     */
    public function create(array $params, Book $book): UserBookAssociation
    {
        $userId = $params['user_id'] ?? null;
        $telegramUserId = $params['telegram_user_id'] ?? null;

        if (!$userId && !$telegramUserId) {
            throw new UserBookAssociationException();
        }

        $this->userBookAssociation = app(UserBookAssociation::class);
        $this->userBookAssociation->fill($params);
        $this->userBookAssociation->book()->associate($book);

        if ($userId) {
            $this->userBookAssociation->user()->associate($userId);
        }

        if ($telegramUserId) {
            $this->userBookAssociation->telegramUser()->associate($telegramUserId);
        }

        $this->userBookAssociation->save();

        $this->statManager->create(Stat::USER_BOOK_ASSOCIATION_MODEL, $this->userBookAssociation->id, Stat::CREATED_ACTION);

        return $this->userBookAssociation;
    }
}
