<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/27/027
 * Time: 14:17
 */

header("content-type:text/html;charset=utf-8");

if($_FILES['file']['type'] == 'image/jpeg' || $_FILES['file']['type'] == 'image/png' || $_FILES['file']['type'] == 'image/gif' || $_FILES['file']['type'] == 'image/pjpeg' && $_FILES['file']['sizs'] < 20000){
    if($_FILES['file']['error'] > 0){
        echo '上传失败';
    }else{
        if(file_exists("upload/".$_FILES['file']['name'])){
            echo 'upload/'.$_FILES['file']['name'];
        }else{
            move_uploaded_file($_FILES['file']['tmp_name'],"upload/".$_FILES['file']['name']);
            echo 'upload/'.$_FILES['file']['name'];
        }
    }
}elseif($_FILES['file']['type'] == 'application/vnd.ms-excel'){
    echo '可以导入';
}else{
    echo '文件错误';
}