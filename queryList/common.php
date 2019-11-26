<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/26/026
 * Time: 18:30
 */

header("content-type:text/html;charset=utf-8");
//$urla="http://v.114la.com/movie/14190241.html";
$cp=curl_init();
curl_setopt($cp,CURLOPT_URL,$urla);
curl_setopt($cp,CURLOPT_RETURNTRANSFER,1);
$result=curl_exec($cp);

//$preg='# <div class="_box relative">
//                <div class="movie_minHeight">
//                <div class="_ti">
//                    <h2>(.*)</h2>
//                </div>
//                    <ul class="list">
//						<li class="sin">评分：
//                            <span class="sort">
//                            (.*)                            </span>分
//                        </li>
//                        <li class="sin">导演：(.*)</li>
//                        <li class="norrow_zy">主演：(.*)</li>
//                        <li class="sin">类型：(.*)</li>
//                        <li>国家/地区：(.*)</li>
//                        <li class="sin">(.*)</li>
//                        <li>(.*)</li>
//                </ul>
//
//                <p class="dt detail_dt" id="intro">(.*) </p>
//            </div>
//#';


$pregs = '#<div class="_box relative">
                <div class="movie_minHeight">
                <div class="_ti">
                    <h2>(.*) </h2>
                </div>
                    <ul class="list">
						<li class="sin">评分：
                            <span class="sort">
                            (.*)                            </span>分
                        </li>
                        <li class="sin">导演：(.*)</li>
                        <li class="norrow_zy">主演：(.*)</li>
                        <li class="sin">类型：(.*)</li>
                        <li>国家/地区：(.*)</li>
                        <li class="sin">时长：(.*)</li>
                        <li>年代：(.*)</li>
                </ul>

                <p class="dt detail_dt" id="intro">(.*)</p>
            </div>
                <div class="mv">
                    <a href="(.*)" class=".*">
                    .*<s></s></a>
                    <div id="tvData">

                    </div>
                </div>
#';