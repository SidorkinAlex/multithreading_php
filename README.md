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

### Include

To connect in your project, you must create a class that inherits from the Thread class and override the following variables:

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


### Examples install from Symfony

https://github.com/SidorkinAlex/symfony-website-skeleton-multithreading_php/pull/1/files


## Example code

### Example 1 (parallel execution)

```

  $paramsFromThread = 3;
        $test = new CThread($paramsFromThread,function ($n){
            for ($i = 0; $i<$n; $i++){
                $pid=getmypid();
                file_put_contents('test1.log', $i." my pid is {$pid} \n", FILE_APPEND);
                sleep(3);
            }
            return 'test1';
        });
        $test->start();

        $test2 = new CThread($paramsFromThread,function ($n){
            for ($i = 0; $i<$n; $i++){
                $pid=getmypid();
                file_put_contents('test2.log', $i." my pid is {$pid} \n", FILE_APPEND);
                sleep(3);
            }
            return 'test2';
        });
        $test2->start();
        $result1 = $test->getCyclicalResult();
        $result2 = $test2->getCyclicalResult();
```

In the example, we see the creation of two threads, which are passed the function of iterating through arrays with a son at the end of each step for 3 seconds.
if we would execute them sequentially, the script execution time would be 18 seconds, when running them in parallel threads, the script execution time is 9 seconds.

### Example 2 (background thread)

```
public function testpars(Request $request): Response
    {
        $userIds=$request->request->get('users');
        if(!is_array($userIds)){
            throw new \Exception('post[users] is not array');
        }
        $EmailSendlerThread = new CThread($users,function ($users){
            foreach ($users as $user_id){

                $transport = \Symfony\Component\Mailer\Transport::fromDsn('smtp://localhost');
                $mailer = new \Symfony\Component\Mailer\Mailer($transport);

                $userObj = new \App\Service\User();
                $userObj->retrieve($user_id);
                if(!empty($userObj->email)){
                    $email = (new Email())
                        ->from(\App\Service\Email::getSelfEmail())
                        ->to($userObj->email)
                        //->cc('cc@example.com')
                        //->bcc('bcc@example.com')
                        //->replyTo('fabien@example.com')
                        //->priority(Email::PRIORITY_HIGH)
                        ->subject(\App\Service\Email::get_first_email_subject())
                        ->text(\App\Service\Email::get_first_email_text())
                        ->html(\App\Service\Email::get_first_email_html());

                    $mailer->send($email);
                }
            }
        });
        $EmailSendlerThread->start();

        return new JsonResponse(['status' =>"ok"]);
    }
```

Example 2 shows the code that implements the start of sending emails. the execution of the testpars method is completed by running the $EmailSendlerThread thread and does not wait for its execution.
  
 Therefore, the response to such a request will be very fast, the main thread will not wait for the $EmailSendlerThread thread to finish, but will simply return {"status" : "ok"}  to the initiator.


# Многопоточность на PHP с помошбю Redis + php-cli

Этот пакет предназначен для быстрого запуска фоновых php-cli скриптов с возможностью ожидания результата их выполнения в основном потоке.

## Установка и включение в проект
 
### Установить
 Вы можете установить пакет через компоновщик, выполнив команду:
 ```
 composer require sidorkinalex/multiphp
 ```
 
 Или вы можете загрузить текущую версию с github и подключить 3 файла к ядру вашего проекта
 
 ```
 require_once 'src/Guidv4.php';
 require_once 'src/Thread.php';
 require_once 'src/ThreadInterface.php';
 ```
 
 ### Подключение к проекту
 
 Чтобы включить его в свой проект, необходимо создать класс, который наследуется от класса Thread, и переопределить следующие переменные:
 
 
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
вам также необходимо создать точку входа в приложение для запуска через консоль(php-cli)
в текущем примере это /var/www/html/exec.php


в файл для выполнения из консоли нужно поместить код для запуска выполнения потока, так как параметр передаст ключ для получения данных потока.

```
include 'vendor/autoload.php'; 
$key=$argv[1]; //
SidorkinAlex\Multiphp\Thread::shell_start($key);
```

include 'vendor/autoload.php'; Если пакет установлен через composer. Eсли загружен с github, подключите 3 файла, перечисленные выше.

$argv[1] уникальный ключ потока, который генерируется при запуске потока и передается в качестве параметра скрипту php-cli. В зависимости от структуры эта переменная может изменяться.


SidorkinAlex\Multi php\Thread::she'will_start($key); вызов статического метода, который запускает выполнение функции, переданной потоку.

### Примеры установки для Symfony

https://github.com/SidorkinAlex/symfony-website-skeleton-multithreading_php/pull/1/files


## Примеры кода


### Пример 1 (паралельное выполнение)

```

  $paramsFromThread = 3;
        $test = new CThread($paramsFromThread,function ($n){
            for ($i = 0; $i<$n; $i++){
                $pid=getmypid();
                file_put_contents('test1.log', $i." my pid is {$pid} \n", FILE_APPEND);
                sleep(3);
            }
            return 'test1';
        });
        $test->start();

        $test2 = new CThread($paramsFromThread,function ($n){
            for ($i = 0; $i<$n; $i++){
                $pid=getmypid();
                file_put_contents('test2.log', $i." my pid is {$pid} \n", FILE_APPEND);
                sleep(3);
            }
            return 'test2';
        });
        $test2->start();
        $result1 = $test->getCyclicalResult();
        $result2 = $test2->getCyclicalResult();
```

В примере мы видим создание двух потоков, в котрые передается вункция перебора массивов с сном вконце каждого шага на 3 секунды.
если мы бы выполняли их последовательно, то время выполнения скрипта было бы 18 секунд, при запуске их паралельными потоками время выполнения скрипта составляет 9 секунд.

### Пример 2 (фоновый поток)
```
public function testpars(Request $request): Response
    {
        $userIds=$request->request->get('users');
        if(!is_array($userIds)){
            throw new \Exception('post[users] is not array');
        }
        $EmailSendlerThread = new CThread($users,function ($users){
            foreach ($users as $user_id){

                $transport = \Symfony\Component\Mailer\Transport::fromDsn('smtp://localhost');
                $mailer = new \Symfony\Component\Mailer\Mailer($transport);

                $userObj = new \App\Service\User();
                $userObj->retrieve($user_id);
                if(!empty($userObj->email)){
                    $email = (new Email())
                        ->from(\App\Service\Email::getSelfEmail())
                        ->to($userObj->email)
                        //->cc('cc@example.com')
                        //->bcc('bcc@example.com')
                        //->replyTo('fabien@example.com')
                        //->priority(Email::PRIORITY_HIGH)
                        ->subject(\App\Service\Email::get_first_email_subject())
                        ->text(\App\Service\Email::get_first_email_text())
                        ->html(\App\Service\Email::get_first_email_html());

                    $mailer->send($email);
                }
            }
        });
        $EmailSendlerThread->start();

        return new JsonResponse(['status' =>"ok"]);
    }
```
В примере 2 представлен код который реализует запуск отправки писем. выполнение метода testpars завершается запуском потока $EmailSendlerThread и не ждет его выполнения.
  
 Поэтому ответ на подобный запрос будет очень быстрым основной поток не будет ждать завершения работы потока $EmailSendlerThread а просто вернет {"status" : "ok"}  инициатору.
 
 