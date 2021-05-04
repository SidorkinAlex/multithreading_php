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
    public $php_worker_path;
    public $function;

    public function __construct($function)
    {
        $this->function = $function;
        $this->id=Guidv4::create_guidv4();
        $this->init_path();
    }
    public function start(){
        $serializer = new Serializer();
        $par = $serializer->serialize($this);

        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $key=guidv4();
        $redis->set($key,$par);
        shell_exec("php {$this->php_worker_path} '$key' > /dev/null & ");
    }

    public function exec(){
        $function =$this->function;
        $function();
    }

    public function init_path()
    {
        $this->php_worker_path="/var/www/html/exec.php";
    }

}