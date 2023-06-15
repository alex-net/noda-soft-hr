<?php

namespace Manager;

use Gateway\User as GUser;

class User
{
    /**
     * Возвращает пользователей старше заданного возраста.
     *
     * @param int $ageFrom
     *
     * @return array
     */
    function getUsers(int $ageFrom): array
    {
        return GUser::getUsers($ageFrom);
    }

    /**
     * Возвращает пользователей по списку имен.
     *
     * @param $names array Список имён пользователей ..
     *
     * @return array
     */
    public static function getByNames(array $names): array
    {
        # можно использовать вызов return GUser::getUserByNames($names) без цикла ... = уменьшение количества зпросов в базу
        $users = [];
        foreach ($names as $name) {
            $data = GUser::getUserByName($name);
            if ($data) {
                $users[] = $data;
            }
        }

        return $users;
    }

    /**
     * Добавляет пользователей в базу данных.
     *
     * @param array $users Данные добавляемых пользователей
     *
     * @return array
     */
    public function addUsers(array $users): array
    {
        $ids = [];
        foreach ($users as $user) {
            $ids[] = GUser::add($user['name'], $user['lastName'], $user['age']);
        }

        return $ids;
    }
}