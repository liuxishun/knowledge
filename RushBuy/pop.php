<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/25/025
 * Time: 19:08
 */
$redis = new Redis();
$redis->connect('127.0.0.1',6379);
$redis->select(1);

while (true){
    $arr = $redis->zRange('order:list',0,0,'WITHSCORES');
    if(!$arr){
        break;
    }

    foreach ($arr as $key => $value){
        $data = explode('|',$key);
    }

    $order_data = [
        'goods_id' => $data[1],
        'user_id' => $data[0],
        'time' => $value
    ];

    echo '<pre>';
    print_r($order_data);

    $redis->zrem('order:list',$k);
}