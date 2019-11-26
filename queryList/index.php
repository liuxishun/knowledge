<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/26/026
 * Time: 18:00
 */
header("content-type:text/html;charset=utf-8");
include ('./QL/QueryList.php');
include ('./QL/phpQuery.php');

$url="http://dianying.114la.com/?kz";
$rule=array(
    "name"=>array("ul li a div.td h2","text"),
    "desc"=>array("ul li a div.td p","text"),
    "url"=>array(".wang2 ul li a","href")
);

$data = \QL\QueryList::query($url,$rule)->getData();
//echo '<pre>';
//var_dump($data);
//die;
$array = array();
foreach ($data as $key => $value){
    $urla = $value['url'];

    include ('common.php');
    preg_match($pregs,$result,$arr);
    if(empty($arr)){
        continue;
    }

    $array[$key]['name']=$arr[1];
    $array[$key]['fen']=$arr[2];
    $array[$key]['use']=$arr[3];
    $array[$key]['user']=$arr[4];
    $array[$key]['start']=$arr[5];
    $array[$key]['addr']=$arr[6];
    $array[$key]['time']=$arr[7];
    $array[$key]['timed']=$arr[8];
    $array[$key]['desc']=$arr[9];
    $array[$key]['move_url']=$arr[10];
}

echo '<pre>';
var_dump($array);