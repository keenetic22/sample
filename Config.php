<?php
/**
 * Class Config - Класс конфигурации
 */
class Config {
    /**
     * Настройки подключения
     * @var array
     */
    private static $_config = [
        'db' =>[
            'dsn' => 'mysql:host=localhost;dbname=testdb',
            'username' => 'username',
            'password' => 'password',
        ]
    ];

    public static function getParam($name) {
        if (isset(self::$_config[$name])) {
            return self::$_config[$name];
        } else {
            throw new \Exception('Неверный параметр');
        }
    }
}
