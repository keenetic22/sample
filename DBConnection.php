<?php
/**
 * Class DBConnection - Класс соединения с базой данных
 */
class DBConnection {

    /**
     * Переменная подключеня к базе данных
     */
    private static $_connection;

    /**
     * Запрет создания нового экземпляра
     */
    private function __construct() {}

    /**
     * Запрет клонирования объекта
     */
    private function __clone() {}

    /*
     * Возвращает текущее соединение с бд, либо создает новое
     *
     */
    public static function getConnection(  ) {

        if(!self::$_connection){
            $config = Config::getParam('db');
            try {
                self::$_connection = new PDO($config['dsn'], $config['username'], $config['password']);
                self::$_connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            } catch (PDOException $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return self::$_connection;
    }
}