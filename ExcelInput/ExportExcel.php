<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/27/027
 * Time: 9:34
 */

header("content-type:text/html;charset=utf-8");
include("PHPExcel.class.php");
include("./PHPExcel/IOFactory.php");
include("./PHPExcel/Writer/Excel5.php");

$fileName = "PHPExcel";
$headArr=array("警员编号","警员姓名","设备编号","警员性别","单位名称","联系方式");

$list = array(
    array("100001","李警官","B612356","男","公安局","18988888888"),
    array("100002","王警官","B612357","男","公安局","18988888889"),
    array("100003","党警官","B612358","男","公安局","18988888988"),
    array("100004","陈警官","B612359","女","公安局","18988666696"),
    array("100005","杨警官","B612350","男","公安局","18988888788"),
    array("100006","门警官","B612351","男","公安局","18988788888"),
    array("100007","许警官","B612352","男","公安局","18988999888"),
    array("100008","徐警官","B612353","女","公安局","18988888111")
);

ExcelOut($fileName,$headArr,$list);

function ExcelOut($fileName,$headArr,$list){
    //对数据进行验证
    if(empty($list) || !is_array($list)){
        die("导入数据错误！！！");
    }
    if(empty($fileName)){
        exit;
    }
    $date = date('Y-m-d',time());
    $fileName .= '_'.$date.'.xls';

    $objExcel = new \PHPExcel();

    //设置表头
    $key = ord('A');
    foreach ($headArr as $value){
        $colum = chr($key);
        $objExcel->setActiveSheetIndex(0)->setCellValue($colum.'1',$value);
        $key += 1;
    }
    $column = 2;
    $objActSheet = $objExcel->setActiveSheetIndex();
    $objActSheet -> getDefaultStyle() -> getNumberFormat() -> setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    foreach ($list as $key => $row){
        $span = ord('A');
        foreach ($row as $k => $val){
            $j = chr($span);
            $objActSheet->setCellValue($j.$column,$val);
            $span += 1;
        }
        $column ++;
    }
    $fileName = iconv('utf-8','gb2312',$fileName);
    $objExcel->setActiveSheetIndex(0);
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename=\"$fileName\"");
    header('Cache-Control: max-age=0');
    ob_clean();
    flush();
    $objWriter = \PHPExcel_IOFactory::createWriter($objExcel,'Excel5');
    $objWriter -> save('PHP://output');
    exit;
}