<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/27/027
 * Time: 10:25
 */

header("content-type:text/html;charset=utf-8");
include("PHPExcel.class.php");
include("PHPExcel/IOFactory.php");
include("PHPExcel/Reader/Excel5.php");

function ImportExcel(){
    $excelUrl = './PHPExcel_2019-11-27.xls';

//    $PHPExcel = new PHPExcel();
    //如果excel文件后缀名为.xlsx，导入这下类
    //import("Org.Util.PHPExcel.Reader.Excel2007");
    //$PHPReader=new \PHPExcel_Reader_Excel2007();
    //文件后缀.xls
    $PHPReader = new \PHPExcel_Reader_Excel5();

    $PHPExcel = $PHPReader -> load($excelUrl);
    //获取第一个页面
    $currentSheet=$PHPExcel->getSheet(0);
    //获取最大行数
    $AllRow = $currentSheet->getHighestRow();
    //获取最大列数
    $AllColumn=$currentSheet->getHighestColumn();

    for ($currentRow=1;$currentRow<=$AllRow;$currentRow++) {
        for ($currentColumn='A';$currentColumn<=$AllColumn;$currentColumn++) {
            //每一个单元格的坐标
            $address = $currentColumn.$currentRow;
            //获取当前单元格的数值
            $arr[$currentRow][$currentColumn]=$currentSheet->getCell($address)->getValue();
        }
    }

//    unlink($excelUrl);  //删除文件-错误
    return $arr;
}

$array = ImportExcel();

echo '<pre>';
var_dump($array);