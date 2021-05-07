# MultiThreading in php from  Redis + php-cli
[![Latest Stable Version](https://poser.pugx.org/sidorkinalex/multiphp/v)](//packagist.org/packages/sidorkinalex/multiphp) [![Total Downloads](https://poser.pugx.org/sidorkinalex/multiphp/downloads)](//packagist.org/packages/sidorkinalex/multiphp) [![Latest Unstable Version](https://poser.pugx.org/sidorkinalex/multiphp/v/unstable)](//packagist.org/packages/sidorkinalex/multiphp) [![License](https://poser.pugx.org/sidorkinalex/multiphp/license)](//packagist.org/packages/sidorkinalex/multiphp)

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
