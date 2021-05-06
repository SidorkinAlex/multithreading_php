# MultiThreading in php from  Redis + php-cli

```

$test = new Thread(function (){
   for ($i = 0; $i<10; $i++){
       $pid=getmypid();
       file_put_contents('test.log', $i." my pid is {$pid} \n", FILE_APPEND);
       sleep(10);
   }
});
$test->start();
```

# Многопоточность на PHP с помошбю Redis + php-cli