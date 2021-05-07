# MultiThreading in php from  Redis + php-cli

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