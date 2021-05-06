<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 02.05.21
 * Time: 21:25
 */

namespace App\Classes;
use SuperClosure\Serializer;



class Thread
{
    public $pid;
    public $id;
    public static $php_worker_path="/var/www/html/exec.php";
    public $function;
    public static $redis_host = "127.0.0.1";
    public static $redis_port = 6379;
    public static $redis_timeout = 0.0;
    public static $redis_reserved = null;
    public static $redis_retry_interval = 0 ;
    public static $cache_timeout="1200";
    const CHANNEL_BASE_NAME = "thread";
    const SAVE_BASE_NAME = "thread_serialize";
    const FINAL_BASE_NAME = "thread_final";
    public $result;

    public function __construct($function)
    {
        $serializer = new Serializer();
        $this->function  = $serializer->serialize($function);
        $this->id = Guidv4::create_guidv4();
    }

    /**
     * launching a new cli script to execute a task in the created thread
     * запуск нового cli скрипта для выполнения задания в созданном потоке
     */
    public function start(){
        $key=$this->saveToRedis();
        shell_exec("php {$this::$php_worker_path} '$key' > /dev/null & ");
    }

    /**
     * creating a connection to Redis
     * создание подключения к Redis
     * @return \Redis
     */
    public function redisConnect():\Redis{
        $redis = new \Redis();
        $redis->connect(
            self::$redis_host,
            self::$redis_port,
            self::$redis_timeout,
            self::$redis_reserved,
            self::$redis_retry_interval );
        return $redis;
    }

    /**
     * runs an anonymous function that was passed to the class
     * запускает анонимную функцию, которая была передана классу
     */
    public function exec(){
        $this->pid=getmypid();
        $serializer = new Serializer();
        $function = $serializer->unserialize($this->function);
        $result = $function();
        if(!is_null($result)){
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
    public static function shell_start($key){
        $redis = new \Redis();
        $redis->connect(
            self::$redis_host,
            self::$redis_port,
            self::$redis_timeout,
            self::$redis_reserved,
            self::$redis_retry_interval );
        $var = $redis->get($key);
        $obj = unserialize($var);
        if($obj instanceof Thread){
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
    public function saveToRedis():string
    {
        $redis = $this->redisConnect();
        $serializer = new Serializer();
        $par = serialize($this);
        $key = self::SAVE_BASE_NAME.$this->id;
        $redis->set($key,$par,self::$cache_timeout);
        return $key;
    }

    /**
     * getting the result of executing a stream from Redis.
     * получение результата выполнения потока из Redis.
     * @return string
     */
    public function getResultFromRedis()
    {
        $redis = $this->redisConnect();
        $serializer = new Serializer();
        $key = self::SAVE_BASE_NAME.$this->id;
        $obj=$redis->get($key);
        $par = $serializer->unserialize($obj);
        return $par->result;
    }

    /**
     * waiting for the thread to finish and getting its result
     * ожидание завершения работы потока и получение его результата
     * @return result
     */
    public function getResult(){
        if(!$this->checkFinalise()){
            $this->waitingFinish();
        }
        return $this->getResultFromRedis();
    }

    /**
     * sending a message to the channel about the completion of the thread execution
     * отправка сообщения в канал о завершении выполнения потока
     */
    public function publishEnd()
    {
        $redis = $this->redisConnect();
        $redis->publish(self::CHANNEL_BASE_NAME.$this->id,'end');
    }

    /**
     * creating an entry in redis about thread shutdown
     * создание записи в Redis о завершении  работы потока
     */
    public function finalise()
    {
        $redis = $this->redisConnect();
        $key = self::FINAL_BASE_NAME.$this->id;
        $redis->set($key,"true",self::$cache_timeout);
    }

    /**
     * checking for a mark in Redis about the end of the stream
     * проверка на наличие отметки в Redis о завершении потока
     * @return bool
     */
    public function checkFinalise():bool
    {
        $redis = $this->redisConnect();
        $key = self::FINAL_BASE_NAME.$this->id;
        if($redis->get($key) == 'true'){
            return true;
        }
        return false;
    }

    /**
     * waiting for the thread to finish
     * ожидание завершения потока
     */
    public function waitingFinish()
    {

        $redis = $this->redisConnect();
        try {
            $redis->subscribe([self::CHANNEL_BASE_NAME.$this->id], function ($redis, $channel, $stdout) {
                $redis->close();
            });
        } catch (Exception $e){

        }
    }

}
