<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 02.05.21
 * Time: 21:25
 */

namespace SidorkinAlex\Multiphp;

use SuperClosure\Serializer;


class Thread implements ThreadInterface
{
    public $pid;
    public $id;
    public static $php_worker_path = "/var/www/html/exec.php";
    public $function;
    public $functionParams;
    public static $redis_host = "127.0.0.1";
    public static $redis_port = 6379;
    public static $redis_timeout = 0.0;
    public static $redis_reserved = null;
    public static $redis_retry_interval = 0;
    public static $cache_timeout = "1200";
    const CHANNEL_BASE_NAME = "thread";
    const SAVE_BASE_NAME = "thread_serialize";
    const FINAL_BASE_NAME = "thread_final";
    public $result;

    /**
     * Thread constructor.
     * @param null $params
     * @param $function
     */
    public function __construct($params = null, $function)
    {
        $serializer = new Serializer();
        $this->function = $serializer->serialize($function);
        $this->functionParams = $params;
        $this->id = Guidv4::create_guidv4();
    }

    /**
     * launching a new cli script to execute a task in the created thread
     * запуск нового cli скрипта для выполнения задания в созданном потоке
     */
    public function start()
    {
        $key = $this->saveToRedis();
        shell_exec("php {$this::$php_worker_path} '$key' > /dev/null & ");
    }

    /**
     * creating a connection to Redis
     * создание подключения к Redis
     * @return \Redis
     */
    protected function redisConnect(): \Redis
    {
        $redis = new \Redis();
        $redis->connect(
            self::$redis_host,
            self::$redis_port,
            self::$redis_timeout,
            self::$redis_reserved,
            self::$redis_retry_interval);
        return $redis;
    }

    /**
     * runs an anonymous function that was passed to the class
     * запускает анонимную функцию, которая была передана классу
     */
    protected function exec()
    {
        $this->pid = getmypid();
        $serializer = new Serializer();
        $function = $serializer->unserialize($this->function);
        if ($this->functionParams !== null) {
            $result = $function($this->functionParams);
        } else {
            $result = $function();
        }
        if (!is_null($result)) {
            $this->result = $result;
            $this->saveToRedis();
        }
        $this->finalise();
        $this->publishEnd();
    }

    /**
     * a static method to be called in the cli script that will act as a thread.
     * статический метод, который нужно вызывать в cli скрипте, который будет выполнять роль потока.
     * @param $key
     * @throws \Exception
     */
    public static function shell_start($key)
    {
        $redis = new \Redis();
        $redis->connect(
            self::$redis_host,
            self::$redis_port,
            self::$redis_timeout,
            self::$redis_reserved,
            self::$redis_retry_interval);
        $var = $redis->get($key);
        $obj = unserialize($var);
        if ($obj instanceof Thread) {
            $obj->exec();
        } else {
            throw new \Exception('$obj is not Thread object, $obj->exec() not started');
        }
    }

    /**
     * saving class data in Redis
     * сохранение данных класса в Redis
     * @return string
     */
    protected function saveToRedis(): string
    {
        $redis = $this->redisConnect();
        $serializer = new Serializer();
        $par = serialize($this);
        $key = self::SAVE_BASE_NAME . $this->id;
        $redis->set($key, $par, self::$cache_timeout);
        return $key;
    }

    /**
     * getting the result of executing a stream from Redis.
     * получение результата выполнения потока из Redis.
     * @return
     */
    protected function getResultFromRedis()
    {
        $redis = $this->redisConnect();
        $key = self::SAVE_BASE_NAME . $this->id;
        $obj = $redis->get($key);
        $par = unserialize($obj);
        return $par->result;
    }

    /**
     * sending a message to the channel about the completion of the thread execution
     * отправка сообщения в канал о завершении выполнения потока
     */
    protected function publishEnd()
    {
        $redis = $this->redisConnect();
        $redis->publish(self::CHANNEL_BASE_NAME . $this->id, 'end');
    }

    /**
     * creating an entry in redis about thread shutdown
     * создание записи в Redis о завершении  работы потока
     */
    protected function finalise()
    {
        $redis = $this->redisConnect();
        $key = self::FINAL_BASE_NAME . $this->id;
        $redis->set($key, "true", self::$cache_timeout);
    }

    /**
     * getting the result of the stream cyclically (high reliability, but more resource consumption)
     * получение результата потока циклично (высокая надежность, но большее потребление ресурсов)
     * @param int $waitingTimeThreadCompletion milliseconds
     * maximum thread waiting time if you specify 0 then there is no waiting time limit
     * максимальное время ожитания потока если указать 0 то ограничение по времени ожидания отсутствует
     * @param int $cyclicalSleepTime milliseconds
     * time step in milliseconds through which the completion of the stream is checked
     *временной шаг в миллисекундах через который происходит проверка завершения потока
     * @return
     */
    public function getCyclicalResult(int $waitingTimeThreadCompletion = 0, int $cyclicalSleepTime = 100)
    {
        if ($waitingTimeThreadCompletion === 0) {
            $this->waitingCyclicalFinish($cyclicalSleepTime);
        } else {
            $cyclicalCount = ceil($waitingTimeThreadCompletion / $cyclicalSleepTime);

            $this->waitingCyclicalCountFinish($cyclicalSleepTime, $cyclicalCount);
        }
        return $this->getResultFromRedis();
    }

    /**loop check by key in radish whether the stream is complete
     * циклическая проверка по ключу в редисе завершен ли поток
     * @param int $cyclicalSleepTime milliseconds
     */
    protected function waitingCyclicalFinish(int $cyclicalSleepTime)
    {
        ini_set('max_execution_time', '300');
        $redis = $this->redisConnect();
        $key = self::FINAL_BASE_NAME . $this->id;
        while (true) {
            if ($redis->get($key) == 'true') {
                break;
            }
            usleep($cyclicalSleepTime);
        }
    }

    /**
     * loop check by key in radish whether the stream with loop limitation is completed
     * циклическая проверка по ключу в редисе завершен ли поток с ограничением циклов
     * @param int $cyclicalSleepTime milliseconds
     * @param int $cyclicalCount count
     */
    protected function waitingCyclicalCountFinish(int $cyclicalSleepTime, int $cyclicalCount)
    {
        $redis = $this->redisConnect();
        $key = self::FINAL_BASE_NAME . $this->id;
        $i = 0;

        while ($i <= $cyclicalCount) {
            if ($redis->get($key) == 'true') {
                break;
            }
            usleep($cyclicalSleepTime);
        }
    }

}
