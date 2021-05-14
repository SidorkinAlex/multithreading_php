<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 10.05.21
 * Time: 11:37
 */

namespace SidorkinAlex\Multiphp;


interface ThreadInterface
{
    /**
     * Thread constructor.
     * @param null $params
     * @param $function
     */
    function __construct($params=null,$function);

    /**
     * launching a new cli script to execute a task in the created thread
     * запуск нового cli скрипта для выполнения задания в созданном потоке
     */
    public function start();

    /**
     * a static method to be called in the cli script that will act as a thread.
     * статический метод, который нужно вызывать в cli скрипте, который будет выполнять роль потока.
     * @param $key
     * @throws \Exception
     */
    public static function shell_start($key);

    /**
     * getting the result of the stream cyclically (high reliability, but more resource consumption)
     * получение результата потока циклично (высокая надежность, но большее потребление ресурсов)
     *
     * @param int $waitingTimeThreadCompletion milliseconds
     * maximum thread waiting time if you specify 0 then there is no waiting time limit
     * максимальное время ожитания потока если указать 0 то ограничение по времени ожидания отсутствует
     *
     * @param int $cyclicalSleepTime milliseconds
     * time step in milliseconds through which the completion of the stream is checked
     *временной шаг в миллисекундах через который происходит проверка завершения потока
     * @return
     */
    public function getCyclicalResult(int $waitingTimeThreadCompletion=0, int $cyclicalSleepTime=100);
}