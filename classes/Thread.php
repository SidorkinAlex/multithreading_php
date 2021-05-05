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
    public $result;

    public function __construct($function)
    {
        $this->function = $function;
        $this->id = Guidv4::create_guidv4();
    }
    public function start(){
        $key=$this->saveToRedis();
        shell_exec("php {$this::$php_worker_path} '$key' > /dev/null & ");
    }

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

    public function exec(){
        $this->pid=getmypid();
        $function =$this->function;
        $result = $function();
        if(is_null($result)){
            $this->result = $result;
            $this->publishEnd();
            $this->saveToRedis();
        }
    }

    public static function shell_start($key){
        $redis = new \Redis();
        $redis->connect(
            self::$redis_host,
            self::$redis_port,
            self::$redis_timeout,
            self::$redis_reserved,
            self::$redis_retry_interval );
        $var = $redis->get($key);
        $serializer = new Serializer();
        $obj = $serializer->unserialize($var);
        if($obj instanceof Thread){
            $obj->exec();
        } else {
            throw new \Exception('$obj is not Thread object, $obj->exec() not started');
        }
    }

    public function saveToRedis():string
    {
        $redis = $this->redisConnect();
        $serializer = new Serializer();
        $par = $serializer->serialize($this);
        $key = self::SAVE_BASE_NAME.$this->id;
        $redis->set($key,$par,self::$cache_timeout);
        return $key;
    }

    public function publishEnd()
    {
        $redis = $this->redisConnect();
        $redis->publish(self::CHANNEL_BASE_NAME.$this->id,'end');
    }

}
