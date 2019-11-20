<?php
header("content-type:text/html;charset=utf-8");
//redis 解决秒杀的基础原理
$redis = new Redis();
$redis->connect('127.0.0.1',6379);
$redis->select(1);
$redia_name = 'miaosha';
$num = 10;//模拟有十件商品
$i = 20;//模拟有20个人同时抢购
$n = 0;//验证数据是否正确
while ($i--){
    $n++;
    $nil = mt_rand(1,50);
    if($redis->lLen($redis_name) < $num){
        $redis->rPush($redis_name,$nil);
        echo $nil.'秒杀成功,编号：'.$n.'<br>';
    }else{
        echo $nil.'秒杀结束,编号：'.$n.'<br>';
    }
}


echo "<br>";
echo "=================================";
echo "<br>";

//验证秒杀是否成功
while ($redis->lLen($redis_name) > 0){
    $str = $redis->lPop($redis_name);
    echo "秒杀成功用户".$str.'<br>';
}

$redis->close();