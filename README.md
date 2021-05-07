# MultiThreading in php from  Redis + php-cli
[![Latest Stable Version](https://poser.pugx.org/sidorkin-alex/multithreadingphp/v)](//packagist.org/packages/sidorkin-alex/multithreadingphp) [![Total Downloads](https://poser.pugx.org/sidorkin-alex/multithreadingphp/downloads)](//packagist.org/packages/sidorkin-alex/multithreadingphp) [![Latest Unstable Version](https://poser.pugx.org/sidorkin-alex/multithreadingphp/v/unstable)](//packagist.org/packages/sidorkin-alex/multithreadingphp) [![License](https://poser.pugx.org/sidorkin-alex/multithreadingphp/license)](//packagist.org/packages/sidorkin-alex/multithreadingphp)

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