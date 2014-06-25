<?php
/**
 * Class Cache - Класс для работы с кешем
 */
class Cache {

    private static $_cache;

    public static function getCache() {
        if (!static::$_cache) {
            static::$_cache = new \Memcache();

            if(!static::$_cache->connect('localhost', 11211)) {
                throw new \Exception('Невозможно подключиться к серверу кеширования');
            }
        }
        return static::$_cache;
    }
}