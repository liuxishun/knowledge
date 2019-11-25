<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/25/025
 * Time: 18:05
 */


$userid = $_GET['uid'];
$goods_id = empty($_REQUEST['goods_id'])?2:$_REQUEST['goods_id'];
$goods_id  = intval($goods_id);

$redis = new redis();
$redis->connect('127.0.0.1', 6379);
$redis->select('1');

if($redis->zrank('order:list',$userid.'|'.$goods_id) !== false ){
    echo "您已经抢到过此商品";exit;
}

if((int)$redis->get('iphone:2:num') <= 0){
    echo "抢购失败"; exit;
}

if($redis->watch("iphone:{$goods_id}:version","iphone:{$goods_id}:num") && $redis->get("iphone:{$goods_id}:num") >=1 ){
    try{
        $redis->multi();
        $redis->incr('iphone:2:version');
        $redis->decr('iphone:2:num');
        $result = $redis->exec();
        if($result){
            //数据库中生成订单(慢) 所以我先存入redis中
            //$redis->lpush("order:list:{$goods_id}",$userid.'-'.time());
            if($redis->zadd("order:list",time(),$userid.'|'.$goods_id)){
                echo '抢购成功';
            }else{
                echo "失败";
            }
        }else{
            var_dump($result);
        }
    }catch(Exception $e){
        echo "失败";
    }

}

//$arr = $redis->zrange('order:list',0,0,' WITHSCORES');
//
//var_dump($arr);