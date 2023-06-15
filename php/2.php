<?php

namespace Gateway;

use PDO;

class User
{
    # константа используется только в этом классе ...
    const LIMIT = 10;
    # вынесли настройки доступа к базе в константы, .. чтобы было проще менять значения
    const DB_DSN = 'mysql:dbname=db;host=127.0.0.1';
    const DB_USER = 'dbuser';
    const DB_PASSWORD = 'dbpass';
    # сделали поле приватным .. чтобы закрыть доступ на прямое изменение значения ..
    /**
     * @var PDO
     */
    private static $instance;

    /**
     * Реализация singleton
     *
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (!self::$instance) {
            self::$instance = new PDO(static::DB_DSN, static::DB_USER, static::DB_PASSWORD);
        }

        return self::$instance;
    }

    /**
     * Возвращает список пользователей старше заданного возраста.
     *
     * @param int $ageFrom Возраст
     *
     * @return array
     */
    public static function getUsers(int $ageFrom): array
    {
        # Исправили запрос ..- была уязвимость в виде sql-иньекции .. также проэкранированы запрашиваемые поля ..
        $stmt = self::getInstance()->prepare("SELECT `id`, `name`, `lastName`, `from`, `age`, `settings` FROM Users WHERE `age` > :age LIMIT " . static::LIMIT);
        $stmt->execute([':age' => $ageFrom]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $users[] = static::fillUserData($row);
        }

        return $users;
    }

    /**
     * реализация метода getUserByName для массива имен .. по аналогии с методом .. getUsers
     * Если использовать этот метод вместо getUserByName .. то в метод fillUserData можно воткнуть обход строк ответа от базы  .. из двух методов getUsers и getUserByNames
     *
     * @param      array  $names  The names
     *
     * @return     array  The user by names.
     */
    public static function getUserByNames(array $names): array
    {
        if (!$names) {
            return [];
        }
        $args = [];
        for ($i = 0; $i < count($names); $i++) {
            $args[":nam$i"] = $names[$i];
        }
        $stmt = self::getInstance()->prepare("SELECT `id`, `name`, `lastName`, `from`, `age`, `settings` FROM Users WHERE `name` in (" . implode(', ', array_keys($args)) . ")");
        $stmt->execute($args);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $users[] = static::fillUserData($row);
        }
        return $users
    }

    /**
     * Возвращает пользователя по имени.
     *
     * @param string $name
     *
     * @return array
     */
    public static function getUserByName(string $name): array
    {
        $stmt = self::getInstance()->prepare("SELECT `id`, `name`, `lastName`, `from`, `age`, `settings` FROM Users WHERE `name` = :name");

        $stmt->execute([':name' => $name]);
        $userByName = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userByName) {
            return [];
        }
        return static::fillUserData($userByName);
    }

    /**
     * генераия данных пользователя из строки таблицы ..
     *
     * @param      array  $dbRowData   Данные пользователя из таблицы (сырые даннные)
     *
     * @return     array
     */
    private static function fillUserData(array $dbRowData)
    {
        $settings = json_decode($dbRowData['settings'], true);
        return [
            'id' => $dbRowData['id'],
            'name' => $dbRowData['name'],
            'lastName' => $dbRowData['lastName'],
            'from' => $dbRowData['from'],
            'age' => $dbRowData['age'],
            'key' => $dbRowData['key'],
        ];
    }

    /**
     * Добавляет пользователя в базу данных.
     *
     * @param string $name  Имя
     * @param string $lastName  Фамилия
     * @param int $age  Возраст
     *
     * @return string Ключ вставки нового пользователя .
     */
    public static function add(string $name, string $lastName, int $age): string
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare("INSERT INTO Users (name, age, lastName) VALUES (:name, :age, :lastName)");
        $stmt->execute([':name' => $name, ':age' => $age, ':lastName' => $lastName]);

        return $pdo->lastInsertId();
    }
}