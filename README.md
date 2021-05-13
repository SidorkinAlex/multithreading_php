# MultiThreading in php from  Redis + php-cli
[![Latest Stable Version](https://poser.pugx.org/sidorkin-alex/multithreadingphp/v)](//packagist.org/packages/sidorkin-alex/multithreadingphp) [![Total Downloads](https://poser.pugx.org/sidorkin-alex/multithreadingphp/downloads)](//packagist.org/packages/sidorkin-alex/multithreadingphp) [![Latest Unstable Version](https://poser.pugx.org/sidorkin-alex/multithreadingphp/v/unstable)](//packagist.org/packages/sidorkin-alex/multithreadingphp) [![License](https://poser.pugx.org/sidorkin-alex/multithreadingphp/license)](//packagist.org/packages/sidorkin-alex/multithreadingphp)

this package is designed to quickly run background php-cli scripts with the ability to wait for the result of their execution in the main thread.

## Inslall and including in project

### Install
You can install the package via the compositor by running the command:

```
composer require sidorkinalex/multiphp
```

Or you can download the current version from github and connect 3 files to the core of your project

```
require_once 'src/Guidv4.php';
require_once 'src/Thread.php';
require_once 'src/ThreadInterface.php';
```

### Including

To connect in your project, you must create a class that inherits from the "name" class and override the following variables:

```
namespace App;

use SidorkinAlex\Multiphp\Thread;

class CustomThread extends Thread
{
    public static $php_worker_path = "/var/www/html/exec.php"; //path to the script that starts the thread(with the initialized core of your project)
    public static $redis_host = "127.0.0.1";// can be a host, or the path to a unix domain socket from Redis
    public static $redis_port = 6379; // Redis port
    public static $redis_timeout = 0.0; // timeout from Redis value in seconds (optional, default is 0.0 meaning unlimited)
    public static $redis_reserved = null; //should be null if $retry_interval is specified from Redis
    public static $redis_retry_interval = 0; //retry interval in milliseconds from Redis.
    public static $cache_timeout = "1200"; // seconds lifetime of stream data in the Redis database

}
```
you also need to create an entry point to the application to run through the console(php-cli)
in the current example, this is /var/www/html/exec.php


in the file for execution from the console, you need to put the code to start the execution of the stream, as the parameter will pass the key to get the stream data.

```
include 'vendor/autoload.php'; 
$key=$argv[1]; //
SidorkinAlex\Multiphp\Thread::shell_start($key);
```
include 'vendor/autoload.php';  If the package is installed via the composer or if downloaded from github, connect the 3 files listed above.

$argv[1] a unique thread key that is generated when the thread is started and passed as a parameter to the php-cli script. Depending on the framework, this variable may change.


SidorkinAlex\Multiphp\Thread::shell_start($key); calling a static method that starts the execution of the function passed to the thread.


## Examples install from Symfony





## Examples code

```

$n = 15;
$test = new Thread($n,function ($n){
   for ($i = 0; $i<$n; $i++){
       $pid=getmypid();
       file_put_contents('test.log', $i." my pid is {$pid} \n", FILE_APPEND);
       sleep(10);
   }
});
$test->start();
```

# Многопоточность на PHP с помошбю Redis + php-cli

этот пакет предназначен для быстрого запуска фоновых php-cli скриптов с возможностью ожидания результата их выполнения в основном потоке. 