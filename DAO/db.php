<?php

/**
 * Created by PhpStorm.
 * User: kwokkakit
 * Date: 12/10/15
 * Time: 5:14 PM
 */
class DBoperator
{
    function getDB()
    {
        // ucunion
        $dbhost = "";
        $dbport = "";
        $dbuser = "";
        $dbpass = "";
        $dbname = "";

        // test enviroment
        // $dbhost="";
        // $dbport="";
        // $dbuser="";
        // $dbpass="";
        // $dbname="";
        $dbConnection = new PDO("mysql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbConnection;
    }

    function getData($sql)
    {
        try {
            $db = $this->getDB();
            $stmt = $db->query($sql);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db = null;

            return $records;
        } catch (PDOException $e) {
            $records = array('error');

            return $records;
        }

    }

    function getMailbyId($id)
    {
        $sql = "SELECT count(*) AS mails FROM message_event WHERE remark = 'email.id:$id'";

        return $sql;
    }

    function getHistoryMailById($id)
    {
        $sql = "SELECT count(*) AS mails FROM message_event_history WHERE remark = 'email.id:$id'";

        return $sql;
    }

    function getHistoryMailByName($name)
    {
        $sql = "SELECT * FROM message_event_history WHERE `to` LIKE '%$name%'";

        return $sql;
    }

    function getADRequestByDate($date, $id)
    {
        $sql = "SELECT a.date, b.pid, c.title AS product_name, a.country_id, d.etitle AS country, a.frid, e.value as platform, SUM(a.impr) AS ad_request, SUM(a.click) AS click FROM count_campaign_daily a LEFT JOIN campaign b ON a.campaign_id=b.campaign_id LEFT JOIN product c ON b.pid=c.pid LEFT JOIN country_option d ON a.country_id=d.id LEFT JOIN fr_dictionary e ON a.frid=e.frid WHERE a.date>='$date' and a.date<='$date' AND b.pid='$id' GROUP BY a.date,b.pid,a.country_id,a.frid";

        return $sql;
    }

    function getMediaPublisher($startDate, $endDate)
    {
        $sql = "SELECT rIncome.pub, rIncome.account, rIncome.BD, rIncome.category, rIncome.adFormat, rIncome.income, rCost.cost, (rCost.cost - rIncome.income) AS delta, IF(rCost.cost>0, ROUND(rIncome.income/rCost.cost,4), 0) AS rate, rData.impr, rData.click, rData.install, rIncome.media_type FROM (SELECT pub,mc.`account` AS account, mc.`agent` AS BD, pc.`cid` AS category, pin.`ad_format` AS adFormat, SUM(t.`income`) AS income, pin.`media_type` FROM publisher_bill_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` JOIN member mc ON pc.`mid`=mc.`mid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$startDate' AND t.`datetime`<='$endDate' GROUP BY pub) rIncome JOIN (SELECT pub, SUM(t.`cost`) AS cost FROM advertiser_campaign_bill_detail t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$startDate' AND t.`datetime`<='$endDate' GROUP BY pub) rCost ON rIncome.pub=rCost.pub JOIN (SELECT pub, SUM(t.`impr`) AS impr, SUM(click) AS click, SUM(INSTALL) AS INSTALL FROM count_campaign_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`date`>='$startDate' AND t.`date`<='$endDate' GROUP BY pub) rData ON rIncome.pub=rData.pub";

        return $sql;
    }

    function getMediaPublisherDaily($eachDate, $condition)
    {
        $condition = rtrim($condition, "','");
        $sql = "SELECT rIncome.datetime, rIncome.pub, rIncome.income, rCost.cost, rData.impr, rData.click, rData.install FROM (SELECT t.`datetime`, pub,mc.`account` AS account, mc.`agent` AS BD, pc.`cid` AS category, pin.`ad_format` AS adFormat, SUM(t.`income`) AS income, pin.`media_type` FROM publisher_bill_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` JOIN member mc ON pc.`mid`=mc.`mid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$eachDate' AND t.`datetime`<='$eachDate' GROUP BY pub) rIncome JOIN (SELECT pub, SUM(t.`cost`) AS cost FROM advertiser_campaign_bill_detail t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`datetime`>='$eachDate' AND t.`datetime`<='$eachDate' GROUP BY pub) rCost ON rIncome.pub=rCost.pub JOIN (SELECT pub, SUM(t.`impr`) AS impr, SUM(click) AS click, SUM(INSTALL) AS INSTALL FROM count_campaign_daily t JOIN publisher_info pin ON pin.`pubid`=t.`pubid` JOIN publisher pc ON pc.`pubid`=t.`pubid` WHERE 1=1 AND pin.`cooperation_mode`=1 AND pin.`promotion_method`=2 AND t.`date`>='$eachDate' AND t.`date`<='$eachDate' GROUP BY pub) rData ON rIncome.pub=rData.pub WHERE rIncome.pub IN ('" . $condition . "')";

        return $sql;
    }

    function getBDmediabuyapk($startDate, $endDate, $arr_campaignId)
    {
        $sql = "SELECT a.date,e.agent AS BD,SUM(a.click) AS click FROM count_campaign_daily a JOIN publisher c ON a.pubid=c.pubid JOIN publisher_info d ON a.pubid=d.pubid JOIN member e ON c.mid=e.mid WHERE a.date>='" . $startDate . "' AND a.date<='" . $endDate . "' AND d.promotion_method='2' AND a.campaign_id IN ('" . implode("','", $arr_campaignId) . "')";

        return $sql;
    }

    function getAEVidmatededuction($date)
    {
        $sql = "SELECT cpqm.stat_time,cpqm.quality_product_id,cpqm.subpub,cpqm.pubid,p.pub,m.account,m.agent AS BD, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_deductions,0))+SUM(cpqm.non_standard_deductions) AS total_deduction_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_earning,0))+SUM(cpqm.non_standard_unreach_earning) AS total_unreach_earning_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_install,0))+SUM(cpqm.non_standard_unreach_install) AS total_unreach_install_noImmunity FROM count_publisher_quality_month cpqm JOIN publisher p ON cpqm.pubid = p.pubid JOIN member m ON p.mid  = m.mid WHERE cpqm.stat_time='" . $date . "' AND cpqm.quality_product_id IN('ae','vidmate') GROUP BY cpqm.quality_product_id,cpqm.subpub,cpqm.pubid";

        return $sql;
    }

    function getQualityDeduction($date, $product)
    {
        $sql = "SELECT cpqm.stat_time,cpqm.quality_product_id,cpqm.subpub,cpqm.pubid,p.pub,m.account,m.agent AS BD, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_deductions,0))+SUM(cpqm.non_standard_deductions) AS total_deduction_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_earning,0))+SUM(cpqm.non_standard_unreach_earning) AS total_unreach_earning_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_install,0))+SUM(cpqm.non_standard_unreach_install) AS total_unreach_install_noImmunity FROM count_publisher_quality_month cpqm JOIN publisher p ON cpqm.pubid = p.pubid JOIN member m ON p.mid  = m.mid WHERE cpqm.stat_time='" . $date . "' AND cpqm.quality_product_id IN('" . $product . "') GROUP BY cpqm.quality_product_id,cpqm.subpub,cpqm.pubid";

        return $sql;
    }

    function getAEinstallearning($startDate, $endDate, $product)
    {
        $sql = "SELECT m.date,m.pid,o.title AS product_name,p.code AS country,m.install,n.earning FROM (SELECT ccd.date,ca.pid,ccd.country_id,SUM(ccd.install) AS INSTALL FROM count_campaign_daily ccd LEFT JOIN campaign ca ON ca.campaign_id=ccd.campaign_id WHERE ca.pid IN ('" . $product . "') AND ccd.date>='" . $startDate . "' AND ccd.date<='" . $endDate . "' GROUP BY ccd.date,ca.pid,ccd.country_id)m LEFT JOIN (SELECT pbd.datetime,pbd.pid,pbd.country_id,SUM(pbd.income) AS earning FROM publisher_bill_detail pbd WHERE pbd.datetime>='" . $startDate . "' AND pbd.datetime<='" . $endDate . "' AND pbd.pid IN ('" . $product . "') GROUP BY pbd.datetime,pbd.pid,pbd.country_id) n ON m.date=n.datetime AND m.pid=n.pid AND m.country_id=n.country_id LEFT JOIN product o ON m.pid=o.pid LEFT JOIN country_option p ON m.country_id=p.id";

        return $sql;
    }

    function getDynamicSource($startDate, $endDate)
    {
        $sql = "SELECT a.mid,a.account,a.agent AS BD, GROUP_CONCAT(DISTINCT pu.url SEPARATOR ';') AS url_list, b.date AS max_impr_date,b.max_impr_daily,c.impr_avg FROM member a LEFT JOIN (SELECT DISTINCT m.date,m.mid,m.impr_daily AS max_impr_daily FROM (SELECT p.DATE,q.mid,SUM(p.impr) AS impr_daily FROM count_campaign_daily p JOIN publisher q ON p.pubid=q.pubid JOIN publisher_info r ON p.pubid=r.pubid WHERE r.promotion_method=2 AND r.media_type IN (2,3,100) AND r.ad_format IN (2,4,5,100) GROUP BY p.DATE,q.mid ORDER BY impr_daily DESC) m GROUP BY m.mid) b ON a.mid=b.mid LEFT JOIN (SELECT q.mid,SUM(p.impr)/COUNT(DISTINCT p.date) AS impr_avg FROM count_campaign_daily p JOIN publisher q ON p.pubid=q.pubid JOIN publisher_info r ON p.pubid=r.pubid WHERE p.date>='" . $startDate . "' AND p.date<='" . $endDate . "' AND r.promotion_method=2 AND r.media_type IN (2,3,100) AND r.ad_format IN (2,4,5,100) GROUP BY q.mid) c ON a.mid=c.mid LEFT JOIN publisher pu ON a.mid=pu.mid GROUP BY a.mid ORDER BY a.mid";

        return $sql;
    }

    function getStaticSourceUC9APPS($startDate, $endDate, $productNameValue, $productGroupValue, $arr_BD)
    {
        $sql = "SELECT '$endDate' AS month,mem.`agent` AS bd,'$productNameValue' AS product,t.`country_id` AS country,pubinfo.`promotion_method` AS promotion_method,SUM(IF(t.`frid` IN (1,4),t.`install`,0)) AS andr_ios_install,AVG(IF(t.`frid` IN (1,4),t.`impr`,0)) AS andr_ios_avg_impr,SUM(IF(t.`frid` NOT IN (1,4),t.`install`,0)) AS fp_install,AVG(IF(t.`frid` NOT IN (1,4),t.`impr`,0)) AS fp_avg_impr FROM count_campaign_daily t JOIN campaign cam ON cam.`campaign_id`=t.`campaign_id` JOIN publisher pub ON t.`pubid`=pub.`pubid` JOIN publisher_info pubinfo ON t.`pubid`=pubinfo.`pubid` JOIN member mem ON mem.`mid`=pub.`mid` JOIN product pro ON pro.`pid`=cam.`pid` WHERE t.`date`>= '$startDate' AND t.`date`<='$endDate' AND pro.product_group='$productGroupValue' AND t.`country_id` IN ('1','3','4') AND pubinfo.`promotion_method` IN ('1','2') AND mem.`agent` IN ('" . implode("','", $arr_BD) . "') GROUP BY mem.`agent`,t.`country_id`,pubinfo.`promotion_method`";

        return $sql;
    }

    function getStaticSourceUC9APPSOther($startDate, $endDate, $productNameValue, $productGroupValue, $arr_BD)
    {
        $sql = "SELECT '$endDate' AS month,mem.`agent` AS bd,'$productNameValue' AS product,'other' AS country,pubinfo.`promotion_method` AS promotion_method,SUM(IF(t.`frid` IN (1,4),t.`install`,0)) AS andr_ios_install,AVG(IF(t.`frid` IN (1,4),t.`impr`,0)) AS andr_ios_avg_impr,SUM(IF(t.`frid` NOT IN (1,4),t.`install`,0)) AS fp_install,AVG(IF(t.`frid` NOT IN (1,4),t.`impr`,0)) AS fp_avg_impr FROM count_campaign_daily t JOIN campaign cam ON cam.`campaign_id`=t.`campaign_id` JOIN publisher pub ON t.`pubid`=pub.`pubid` JOIN publisher_info pubinfo ON t.`pubid`=pubinfo.`pubid` JOIN member mem ON mem.`mid`=pub.`mid` JOIN product pro ON pro.`pid`=cam.`pid` WHERE t.`date`>= '$startDate' AND t.`date`<='$endDate' AND pro.product_group='$productGroupValue' AND t.`country_id` NOT IN ('1','3','4') AND pubinfo.`promotion_method` IN ('1','2') AND mem.`agent` IN ('" . implode("','", $arr_BD) . "') GROUP BY mem.`agent`,pubinfo.`promotion_method`";

        return $sql;
    }

    function getStaticSourceOther($startDate, $endDate, $arr_BD)
    {
        $sql = "SELECT  '$endDate' AS month, mem.`agent` AS bd,'other' AS product,'all' AS country,pubinfo.`promotion_method` AS promotion_method,SUM(IF(t.`frid` IN (1,4),t.`install`,0)) AS andr_ios_install,SUM(IF(t.`frid` NOT IN (1,4),t.`install`,0)) AS fp_install,AVG(IF(t.`frid` IN (1,4),t.`impr`,0)) AS andr_ios_avg_impr,AVG(IF(t.`frid` NOT IN (1,4),t.`impr`,0)) AS fp_avg_impr FROM count_campaign_daily t JOIN campaign cam ON cam.`campaign_id`=t.`campaign_id` JOIN publisher pub ON t.`pubid`=pub.`pubid` JOIN publisher_info pubinfo ON t.`pubid`=pubinfo.`pubid` JOIN member mem ON mem.`mid`=pub.`mid` JOIN product pro ON pro.`pid`=cam.`pid` WHERE t.`date`>= '$startDate' AND t.`date`<='$endDate' AND pro.product_group NOT IN ('1','2') AND pubinfo.`promotion_method` IN ('1','2') AND mem.`agent` IN ('" . implode("','", $arr_BD) . ".) GROUP BY mem.`agent`,pubinfo.`promotion_method`";

        return $sql;
    }

    function getStandQuality($startDate, $endDate, $arr_country)
    {
        $sql = "SELECT country, IF(CEIL(quality*20)/20 >=1, 1, CEIL(quality*20)/20) AS up_quality, SUM(INSTALL) FROM count_publisher_quality WHERE quality_product_id = 'uc' AND DATE >= '" . $startDate . "' AND DATE <= '" . $endDate . "' AND country IN ('" . implode("','", $arr_country) . "') GROUP BY country, up_quality ORDER BY up_quality DESC";

        return $sql;
    }

    function getNonStandQuality($startDate, $endDate, $arr_country)
    {
        $sql = "SELECT country, IF(CEIL(quality*20)/20 >=1, 1, CEIL(quality*20)/20) AS up_quality, SUM(INSTALL) FROM count_subpub_quality WHERE quality_product_id = 'uc' AND DATE >= '" . $startDate . "' AND DATE <= '" . $endDate . "' AND country IN ('" . implode("','", $arr_country) . "') GROUP BY country, up_quality ORDER BY up_quality DESC";

        return $sql;
    }

    function getAdjust($date)
    {
        $sql = "SELECT a.pubid AS Pubid, b.pub AS Pub, c.account AS Account, c.agent AS BD, d.impr AS `Ad Request`, d.beacon AS Impression, e.beacona AS `Impression(Adjusted)`, d.click AS Click, e.clicka AS `Click(Adjusted)`, f.income AS Earning FROM (SELECT pubid FROM publisher_info pi WHERE pi.cooperation_mode IN (1,2) AND pi.promotion_method='2' AND pi.media_type IN (2,3,100) AND pi.ad_format IN (2,4,100) AND pi.integration_method IN (1,3)) a LEFT JOIN (SELECT p.pubid, p.pub, p.mid FROM publisher p ) b ON a.pubid=b.pubid LEFT JOIN (SELECT m.account, m.agent, m.mid FROM member m ) c ON b.mid=c.mid LEFT JOIN (SELECT ccd.pubid, sum(ccd.impr) AS impr, sum(ccd.beacon) AS beacon, sum(ccd.click) AS click FROM count_campaign_daily ccd WHERE ccd.date='$date' GROUP BY ccd.pubid) d ON a.pubid=d.pubid LEFT JOIN (SELECT ccde.pubid, sum(ccde.beacon_adjusted) AS beacona, sum(ccde.click_adjusted) AS clicka FROM count_campaign_daily_ext ccde WHERE ccde.date='$date' GROUP BY ccde.pubid) e ON a.pubid=e.pubid LEFT JOIN (SELECT pbd.pubid, sum(pbd.income) AS income FROM publisher_bill_daily pbd WHERE pbd.datetime='$date' GROUP BY pbd.pubid) f ON a.pubid=f.pubid";

        return $sql;
    }
}

