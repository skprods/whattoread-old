<?php

namespace App\Managers;

use App\Exceptions\UserBookAssociationException;
use App\Models\Book;
use App\Models\TelegramUser;

class AssociationManager
{
    private UserBookAssociationManager $userBookAssociationManager;

    public function __construct()
    {
        $this->userBookAssociationManager = app(UserBookAssociationManager::class);
    }

    /**
     * Создание ассоциаций для пользователя из Телеграм и создание/увеличение
     * количества ассоциаций к переданной книге
     *
     * По каждой ассоциации сначала создаётся пользовательская ассоциация. Если
     * она уже существует, в ответ придёт null. Далее, если ассоциация была создана,
     * мы создаём или увеличиваем суммарное количество для конкретной ассоциации с
     * книгой. Если же ассоциация не была создана (вернулся null), значит, эта
     * пользовательская ассоциация уже учтена в общем количестве и ничего дополнительно
     * делать не нужно
     *
     * @param array $associations   - массив ассоциаций
     * @param Book $book            - книга
     * @param TelegramUser $user    - пользователь Телеграм
     *
     * @throws UserBookAssociationException
     */
    public function addForTelegramUser(array $associations, Book $book, TelegramUser $user)
    {
        foreach ($associations as $association) {
            $userAssociation = $this->userBookAssociationManager->createIfNotExists([
                'association' => $association,
                'telegram_user_id' => $user->id,
            ], $book);

            if ($userAssociation) {
                app(BookAssociationManager::class)->createOrIncrement($association, $book);
            }
        }
    }
}
