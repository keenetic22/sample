<?php
require_once('Config.php');
require_once('DBConnection.php');
require_once('Cache.php');
/**
 * Class News - Класс для работы с новостями (создание, обновление, удаление данных,
 * получение списка (страницы) новостей, получение новости)
 */
class News {

    /**
     * Параметр, показывающий, сколько новостей выводить на страницу
     */
    CONST NEW_PER_PAGE = 5;

    /**
     * Префикс кеша детальной информации о новости
     */
    CONST CACHE_NEWS_DETAILS_PREFIX = 'news_';

    /**
     * Префикс кеша страницы новостей
     */
    CONST CACHE_NEWS_PAGE_PREFIX = 'news_page_';

    /**
     * Ключ кеша количества новостей
     */
    CONST CACHE_COUNT_NEWS_KEY = 'countNews';

    /**
     * Ключ кеша максимальной страницы с новостями
     */
    CONST CACHE_MAX_PAGE_KEY = 'maxCachedPage';

    /**
     * Аттрибуты модели
     */
    private  $id;
    public $createTime = null;
    public $title = '';
    public $description = null;

    /** Устанавливает заголовок новости
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /** Устанавливает текст новости
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /** Возвращает идентификатор новости
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /** Возвращает название сущности для модели
     * @return string $tableName
     */
    public function getTableName()
    {
        return 'news';
    }

    /** Возвращает массив атрибутов новости. Используется для кеширования деталей.
     * @return array
     */
    public function getCachedAttributes() {
        return [
            'id' => $this->getId(),
            'createTime' => $this->createTime,
            'title' => $this->title,
            'description' => $this->description
        ];
    }

    /** Добавляет новую новость или сохраняет текущую
     * @return bool
     * @throws Exception
     */
    public function save() {
        if (!$this->title) {
            throw new Exception('Поле "заголовок" обязательно для заполнения');
        }
        if ($this->getId()) {
            return $this->update();
        } else {
            $this->createTime = mktime();
            return $this->insert();
        }
    }

    /** Добавление новой новости в бд
     * @return bool
     * @throws Exception Если произошла ошибка во время добавления записи
     */
    protected function insert()
    {
        $data = [
            ':createTime'    => $this->createTime,
            ':title'         => $this->title,
            ':description'   => $this->description
        ];
        $sql = 'insert into ' . static::getTableName() .
            ' (createTime, title, description) values (:createTime, :title, :description)';
        try {
            $sth = DBConnection::getConnection()->prepare($sql);
            if($sth->execute($data)) {
                $this->afterSave();
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /** Обновление новости в бд
     * @return bool
     * @throws Exception Если произошла ошибка во время обновления
     */
    protected function update()
    {
        $sql = 'update ' . static::getTableName() .
            ' set title = :title, description = :description where id = :id';

        try {
            $sth = DBConnection::getConnection()->prepare($sql);
            $sth->bindParam(':id', $this->getId(), PDO::PARAM_INT);
            $sth->bindParam(':title', $this->title);
            $sth->bindParam(':description', $this->description);
            if($sth->execute()) {
                $this->afterSave();
            } else {
                return false;
            }
        }  catch (PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Выполняет действия при успешном сохранении модели
     */
    public function afterSave() {
        if (!$this->getId()) {
            // Устанавливает идентификатор новой записи
            $this->id = DBConnection::getConnection()->lastInsertId();
            //Проверяет значение количества новостей в кеше
            $this->checkCountCacheKey();
            // Увеличивает количество
            Cache::getCache()->increment(self::CACHE_COUNT_NEWS_KEY, 1);
        }
        // Очищает постраничный кеш
        static::clearPageCache();
        // Обновляет кеш детальной информации
        Cache::getCache()->set(self::CACHE_NEWS_DETAILS_PREFIX . $this->getId(), $this->getCachedAttributes());
    }

    /** Удаляет новость
     * @return bool
     * @throws Exception Возникает при возникновении ошибки при удалении
     */
    public function delete() {
        if ($this->getId()) {
            $sql = 'delete from ' . static::getTableName() . ' where id = :id';
            $sth = DBConnection::getConnection()->prepare($sql);
            $sth->bindParam('id', $this->getId(), PDO::PARAM_INT);
            if($sth->execute()) {
                $this->afterDelete();
                return true;
            } else {
                return false;
            }
        } else {
            throw new \Exception('Идентификатор записи не определен');
        }
    }

    /**
     * Выполняет действия при успешном удалении модели
     */
    public function afterDelete() {
        // Чистит кеш
        Cache::getCache()->delete(self::CACHE_NEWS_DETAILS_PREFIX . $this->getId());
        // Обнуляет аттрибуты
        $this->id = null;
        $this->createTime = null;
        $this->setTitle('');
        $this->setDescription(null);
        // Проверяет переменную количества записей в кеше
        $this->checkCountCacheKey();
        // Сокращает количество
        Cache::getCache()->decrement(self::CACHE_COUNT_NEWS_KEY, 1);
        // Чистит постраничный кеш
        static::clearPageCache();
    }

    /** Возвращает новость по идентификатору
     * @param integer $pk Идентификатор записи
     * @param int $fetchMode PDO FETCH_MODE
     * @return mixed Экземпляр News в случае успеха
     * @throws Exception При возникновении ошибки получения данных
     */

    public static function findByPk($pk, $fetchMode = PDO::FETCH_CLASS)
    {
        $sql = 'select * from ' . static::getTableName() . ' where id = :id limit 1';

        try {
            $sth = DBConnection::getConnection()->prepare($sql);
            $sth->bindParam('id', $pk, PDO::PARAM_INT);
            if (PDO::FETCH_CLASS == $fetchMode) {
                $sth->setFetchMode(PDO::FETCH_CLASS, __CLASS__);
            } else {
                $sth->setFetchMode($fetchMode);
            }

            $sth->execute();
            return $sth->fetch();
        }  catch (PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /** Возвращает Новости для страницы
     * @param int $page Номер страницы
     * @return array Атрибуты новости
     * @throws Exception В случае возникновения ошибки при передаче данных
     */
    public static function getNewsByPage($page = 1)
    {
        $cacheKey = self::CACHE_NEWS_PAGE_PREFIX . $page;

        static::checkMaxCachedPage($page);
        $news = Cache::getCache()->get($cacheKey);
        if (!$news) {
            $offset = ($page-1) * self::NEW_PER_PAGE;
            $limit = self::NEW_PER_PAGE;

            $sql = 'select * from ' . static::getTableName() . ' order by createTime desc limit :offset, :limit';
            try {
                $sth = DBConnection::getConnection()->prepare($sql);
                $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
                $sth->bindParam(':limit', $limit, PDO::PARAM_INT);
                $sth->setFetchMode(PDO::FETCH_ASSOC);
                $sth->execute();
                $news = $sth->fetchAll();
                Cache::getCache()->set($cacheKey, $news);
            } catch (PDOException $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return $news;
    }

    /** Возвращает детальную информацию по новости
     * @param $pk Идентификатор новости
     * @param bool $regenerate Если флаг установлен, кеш будет сбрасываться
     * @return mixed
     */
    public static function getNewsDetails($pk, $regenerate = false)
    {
        $cacheKey = self::CACHE_NEWS_DETAILS_PREFIX . $pk;
        if ($regenerate) {
            Cache::getCache()->delete($cacheKey);
        }
        $news = Cache::getCache()->get($cacheKey);
        if (!$news) {
            $dbRecord = static::findByPk($pk, PDO::FETCH_ASSOC);
            Cache::getCache()->set($cacheKey, $dbRecord);
        }
        return $news;
    }

    /** Проверяет количество новостей в кеше
     * @param bool $regenerate Если флаг установлен, кеш будет сбрасываться
     */
    public function checkCountCacheKey($regenerate = false) {
        if (true == $regenerate) {
            Cache::getCache()->delete(self::CACHE_COUNT_NEWS_KEY);
        }
        if (!(Cache::getCache()->get(self::CACHE_COUNT_NEWS_KEY))) {
            $sql = 'select count(1) cnt from ' . static::getTableName();
            if ($sth = DBConnection::getConnection()->query($sql)) {
                Cache::getCache()->set(self::CACHE_COUNT_NEWS_KEY, $sth->fetchColumn(0));
            }
        }
    }

    /** Вычисляет максимальный номер кешированной страницы
     * @param $page Номер страницы
     */
    public static function checkMaxCachedPage($page) {
        if ($maxPage = Cache::getCache()->get(self::CACHE_MAX_PAGE_KEY) != false) {
            if ($maxPage < $page) {
                Cache::getCache()->set(self::CACHE_MAX_PAGE_KEY, $page);
            }
        } else {
            Cache::getCache()->set(self::CACHE_MAX_PAGE_KEY, $page);
        }
    }

    /**
     * Ощищает кеш
     */
    public static function clearPageCache() {
        Cache::getCache()->delete(self::CACHE_COUNT_NEWS_KEY);
        $maxPage = Cache::getCache()->get(self::CACHE_MAX_PAGE_KEY);
        if ($maxPage) {
            for ($i = 1; $i <= $maxPage; $i++) {
                $cacheKey = self::CACHE_NEWS_PAGE_PREFIX . $i;
                if (Cache::getCache()->get($cacheKey)) {
                    Cache::getCache()->delete($cacheKey);
                }
            }
        }
        Cache::getCache()->delete(self::CACHE_MAX_PAGE_KEY);
    }
}