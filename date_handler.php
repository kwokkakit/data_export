<?php
/**
 * Created by PhpStorm.
 * User: kwokkakit
 * Date: 12/21/15
 * Time: 4:53 PM
 */

//这个星期的星期一
// @$timestamp ，某个星期的某一个时间戳，默认为当前时间
// @is_return_timestamp ,是否返回时间戳，否则返回时间格式
function this_monday($timestamp=0,$is_return_timestamp=true){
    static $cache ;
    $id = $timestamp.$is_return_timestamp;
    if(!isset($cache[$id])){
        if(!$timestamp) $timestamp = time();
        $monday_date = date('Y-m-d', $timestamp-86400*date('w',$timestamp)+(date('w',$timestamp)>0?86400:-/*6*86400*/518400));
        if($is_return_timestamp){
            $cache[$id] = strtotime($monday_date);
        }else{
            $cache[$id] = $monday_date;
        }
    }
    return $cache[$id];

}

//这个星期的星期天
// @$timestamp ，某个星期的某一个时间戳，默认为当前时间
// @is_return_timestamp ,是否返回时间戳，否则返回时间格式
function this_sunday($timestamp=0,$is_return_timestamp=true){
    static $cache ;
    $id = $timestamp.$is_return_timestamp;
    if(!isset($cache[$id])){
        if(!$timestamp) $timestamp = time();
        $sunday = this_monday($timestamp) + /*6*86400*/518400;
        if($is_return_timestamp){
            $cache[$id] = $sunday;
        }else{
            $cache[$id] = date('Y-m-d',$sunday);
        }
    }
    return $cache[$id];
}

//上周一
// @$timestamp ，某个星期的某一个时间戳，默认为当前时间
// @is_return_timestamp ,是否返回时间戳，否则返回时间格式
function last_monday($timestamp=0,$is_return_timestamp=true){
    static $cache ;
    $id = $timestamp.$is_return_timestamp;
    if(!isset($cache[$id])){
        if(!$timestamp) $timestamp = time();
        $thismonday = this_monday($timestamp) - /*7*86400*/604800;
        if($is_return_timestamp){
            $cache[$id] = $thismonday;
        }else{
            $cache[$id] = date('Y-m-d',$thismonday);
        }
    }
    return $cache[$id];
}

//上个星期天
// @$timestamp ，某个星期的某一个时间戳，默认为当前时间
// @is_return_timestamp ,是否返回时间戳，否则返回时间格式
function last_sunday($timestamp=0,$is_return_timestamp=true){
    static $cache ;
    $id = $timestamp.$is_return_timestamp;
    if(!isset($cache[$id])){
        if(!$timestamp) $timestamp = time();
        $thissunday = this_sunday($timestamp) - /*7*86400*/604800;
        if($is_return_timestamp){
            $cache[$id] = $thissunday;
        }else{
            $cache[$id] = date('Y-m-d',$thissunday);
        }
    }
    return $cache[$id];
}

// SQL
function mp_sql($startDate, $endDate){
    $sql = "SELECT rIncome.pub, rIncome.account, rIncome.BD, rIncome.category, rIncome.adFormat, rIncome.income, rCost.cost, (rCost.cost - rIncome.income) AS delta, IF(rCost.cost>0, ROUND(rIncome.income/rCost.cost,4), 0) AS rate, rData.impr, rData.click, rData.install, rIncome.media_type FROM (SELECT pub,mc.`account` AS account, mc.`agent` AS BD, pc.`cid` AS category, pin.`ad_format` AS adFormat, SUM(t.`income`) AS income, pin.`media_type` FROM publisher_bill_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` JOIN member mc ON pc.`mid`=mc.`mid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$startDate' AND t.`datetime`<='$endDate' GROUP BY pub) rIncome JOIN (SELECT pub, SUM(t.`cost`) AS cost FROM advertiser_campaign_bill_detail t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$startDate' AND t.`datetime`<='$endDate' GROUP BY pub) rCost ON rIncome.pub=rCost.pub JOIN (SELECT pub, SUM(t.`impr`) AS impr, SUM(click) AS click, SUM(INSTALL) AS INSTALL FROM count_campaign_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`date`>='$startDate' AND t.`date`<='$endDate' GROUP BY pub) rData ON rIncome.pub=rData.pub";

    return $sql;
}

function mpd_sql($eachDate, $condition){
  $condition = rtrim($condition,"','");
    $sql = "SELECT rIncome.datetime, rIncome.pub, rIncome.income, rCost.cost, rData.impr, rData.click, rData.install FROM (SELECT t.`datetime`, pub,mc.`account` AS account, mc.`agent` AS BD, pc.`cid` AS category, pin.`ad_format` AS adFormat, SUM(t.`income`) AS income, pin.`media_type` FROM publisher_bill_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` JOIN member mc ON pc.`mid`=mc.`mid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$eachDate' AND t.`datetime`<='$eachDate' GROUP BY pub) rIncome JOIN (SELECT pub, SUM(t.`cost`) AS cost FROM advertiser_campaign_bill_detail t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$eachDate' AND t.`datetime`<='$eachDate' GROUP BY pub) rCost ON rIncome.pub=rCost.pub JOIN (SELECT pub, SUM(t.`impr`) AS impr, SUM(click) AS click, SUM(INSTALL) AS INSTALL FROM count_campaign_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`date`>='$eachDate' AND t.`date`<='$eachDate' GROUP BY pub) rData ON rIncome.pub=rData.pub WHERE rIncome.pub IN ('" . $condition . "')";

    return $sql;
}
