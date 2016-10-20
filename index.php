<?php
/**
 * Created by PhpStorm.
 * User: kwokkakit
 * Date: 12/10/15
 * Time: 5:14 PM
 */

require 'vendor/autoload.php';
require 'db_config.php';

$VIEWSDIR = '/views/';

$app = new \Slim\Slim();

//Index introduce
$app->get('/', 'indexFunction');

//Mail status
$app->group('/mails', function () use ($app){
  // GET /mail
  $app->map('', function() {
    $app = Slim\Slim::getInstance();
    $html = <<<HTM
<h1>Read Me</h1>
<p><font color="red">http://URL/index.php/mails/@id</font> : How many mails in the sending queue.</p>
<p><font color="red">http://URL/index.php/mails/history/@id</font> : How many mails already sent in queue.</p>
<p><font color="red">http://URL/index.php/mails/history/@name</font> : Email history List.</p>
<p></p>
<p>@id: mail id in backend. [email.id:]</p>
<p>@name: email address.</p>
HTM;
    $app->response->setBody($html);
  })->via('GET', 'POST', 'PUT');

  // GET /mail/@id
  $app->get('/:id', function($id) {
    //remark: email.id:530
    $sql = "SELECT count(*) AS mails FROM message_event WHERE remark = 'email.id:$id'";

    try {
      $db = getDB();
      $stmt = $db->query($sql);
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $db = null;
      echo 'There are ' . $records[0]['mails'] . ' mails in queue.';
    } catch(PDOException $e) {
      //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

  })->conditions(array('id' => '[0-9]+'));

  // GET /mail/history/@id
  $app->get('/history/:id', function ($id){
    //remark: email.id:530
    $sql = "SELECT count(*) AS mails FROM message_event_history WHERE remark = 'email.id:$id'";

    try {
      $db = getDB();
      $stmt = $db->query($sql);
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $db = null;
      echo 'There are ' . $records[0]['mails'] . ' mails already sent.';
    } catch(PDOException $e) {
      //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
  })->conditions(array('id' => '[0-9]+'));

  // GET /mail/history/@name
  $app->get('/history/:name', function ($name){
    $sql = "SELECT * FROM message_event_history WHERE `to` LIKE '%$name%'";

    try {
      $db = getDB();
      $stmt = $db->query($sql);
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $db = null;

      //Display Data
      //Table Head
      $output = "<table><tr><td>from_account</td><td>from</td><td>to</td><td>title</td><td>beg_time</td><td>end_time</td></tr>";

      //Table Body
      for($i=0;$i<count($records);$i++){
        $output .= "<tr>";
        $output .= "<td>".$records[$i]['from_account']."</td>";
        $output .= "<td>".$records[$i]['from']."</td>";
        $output .= "<td>".$records[$i]['to']."</td>";
        $output .= "<td>".$records[$i]['title']."</td>";
        $output .= "<td>".date('Y-m-d H:i:s', $records[$i]['beg_time'])."</td>";
        $output .= "<td>".date('Y-m-d H:i:s', $records[$i]['end_time'])."</td>";
        $output .= "</tr>";
      }
      $output .= "</table>";
      echo $output;

    } catch(PDOException $e) {
      //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
  })->conditions(array('name' => '[a-zA-Z0-9_-]+[\.a-zA-Z0-9]*@[a-zA-Z0-9_-]+[\.a-zA-Z]+$'));
});

//Export pid=2029901,2028908,2177116,2177117,2396545,2396546 data.
$app->get('/:id/:date', function ($id, $date){
  //  GET /2029901/2015-10-10
  require 'download.php';

  if ($id == '2029901' or $id == '2028908' or $id == '2177116' or $id == '2177117' or $id == '2396545' or $id == '2396546') {
    if (validateDate($date)){
      $sql = "SELECT a.date, b.pid, c.title AS product_name, a.country_id, d.etitle AS country, a.frid, e.value as platform, SUM(a.impr) AS ad_request, SUM(a.click) AS click FROM count_campaign_daily a LEFT JOIN campaign b ON a.campaign_id=b.campaign_id LEFT JOIN product c ON b.pid=c.pid LEFT JOIN country_option d ON a.country_id=d.id LEFT JOIN fr_dictionary e ON a.frid=e.frid WHERE a.date>='$date' and a.date<='$date' AND b.pid='$id' GROUP BY a.date,b.pid,a.country_id,a.frid";

//      echo $sql;
      try {
        $db = getDB();
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        //Export CSV
        $filename = "pid_".$id."_adrequest_click_by_contry_fr_".$date.".csv";
        result($records, $filename);
      } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }
    else{
      echo "Date format ERROR . ex: 2015-01-01.";
    }
  }
  else{
    echo "Your Product ID not allowed";
  }
})->conditions(array('id' => '[0-9]+'));

//Export MediaPublisher data.
$app->group('/mediapublisher', function () use ($app){
  // GET /mediapublisher
  $app->get('', function () {
    require 'date_handler.php';
    require 'download.php';

    $startDate=last_monday(0,false);
    $endDate=last_sunday(0,false);

    $sql = mp_sql($startDate, $endDate);

    try {
      $db = getDB();
      $stmt = $db->query($sql);
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $db = null;

      //Export CSV
      $filename = "channel_weekly_breakeven_analysis".$startDate."_".$endDate.".csv";
      result($records, $filename);
    } catch(PDOException $e) {
      //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
  });

  // /mediapublisher/export/2015-01-01&2015-02-01
  $app->get('/export/:date', function ($date) {
    require 'date_handler.php';
    require 'download.php';

    $arr = explode("&", $date);
    $startDate = $arr[0];
    $endDate = $arr[1];

    if(validateDate($startDate) and validateDate($endDate)){
      $sql = mp_sql($startDate, $endDate);

      try {
        $db = getDB();
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        //Export CSV
        $filename = "channel_weekly_breakeven_analysis_".$startDate."_".$endDate.".csv";
        result($records, $filename);
      } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }
    else {
      echo 'Media Publisher</br>';
      echo 'Date format: <font color="red">2015-01-01&2015-02-01</br></font>';
    }
  });

  //Export MediaPublisher by daily
  $app->group('/daily', function () use ($app){
    // GET /mediapublisher/daily
    $app->get('', function () {
      require 'download.php';

      //basedir
      $dic = '/mediapublisher/';
      $dir = dirname(__FILE__) . $dic;

      //file list
      fileList($dir, $dic, 1);
    });

    $app->get('/delete', function () {
      //basedir
      $dic = '/mediapublisher/';
      $dir = dirname(__FILE__) . $dic;

      $filename = "channel_weekly_breakeven_analysis_eachDay.csv";
      $file = $dir . $filename;

      $result = @unlink($file);

      if($result==1){
        echo "Delete success.</br>";
      }else{
        echo "File delete fail!";
      }
    });

    // GET /mediapublisher/daily/2015-01-01&2015-02-01
    $app->get('/:date', function ($date) {
      require 'date_handler.php';
      require 'download.php';

      //basedir
      $dic = '/mediapublisher/';
      $dir = dirname(__FILE__) . $dic;

      //pub File
      $pubFileName = 'pub.csv';

      $arr = explode("&", $date);
      $startDate = $arr[0];
      $endDate = $arr[1];

      if(validateDate($startDate) and validateDate($endDate)){
        //处理时间
        $dateList1=explode("-",$startDate);
        $dateList2=explode("-",$endDate);
        $d1=mktime(0,0,0,$dateList1[1],$dateList1[2],$dateList1[0]);
        $d2=mktime(0,0,0,$dateList2[1],$dateList2[2],$dateList2[0]);

        $days=-(round(($d1-$d2)/3600/24));
        if($days < 0){
          throw new Exception("Start Date must be greater than End Date.");
        }

        $pubFile=fopen($dir.$pubFileName, "r");
        $pubResult=fgetcsv($pubFile);

        $str=implode(',',$pubResult);
        $condit=str_replace(",","','",$str);

        for($i=0;$i<=$days;$i++) {
          $eachDay = date("Y-m-d",strtotime("$startDate +$i day"));
          $sql = mpd_sql($eachDay, $condit);

          try {
            $db = getDB();
            $stmt = $db->query($sql);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $db = null;

            //Export CSV
            $filename = "channel_weekly_breakeven_analysis_eachDay.csv";
            result($records, $dic, $filename);
          } catch(PDOException $e) {
            //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
            echo '{"error":{"text":'. $e->getMessage() .'}}';
          }
        }

      }else{
        echo 'MediaPublisher Daily</br>';
        echo 'Date format: <font color="red">2015-01-01&2015-02-01</br></font>';
      }
    });
  });
});

//Export BD Media Buy APK click
$app->group('/BDmediabuyapk', function () use ($app){
  //Select year & month
  $app->get('', function () {
    global $VIEWSDIR;
    $viewsdir = dirname(__FILE__) . $VIEWSDIR;
    $template = file_get_contents($viewsdir . 'BDmediabuyapk.html');
    echo $template;
  });

  //Receive select data.
  $app->post('', function () {
    $app = \Slim\Slim::getInstance();
    $selectYear = $app->request->post('selectYear');
    $selectMonth = $app->request->post('selectMonth');

    if(intval($selectMonth) <= 10){
      if(intval($selectMonth) == 1){
        $startdate = (intval($selectYear)-1) . '-12-26';
      }else{
        $startdate = $selectYear . '-0' . (intval($selectMonth)-1) . '-26';
      }
      $enddate = $selectYear . '-' .$selectMonth . '-25';
    }else{
      $startdate = $selectYear . '-' . (intval($selectMonth)-1) . '-26';
      $enddate = $selectYear . '-' .$selectMonth . '-25';
    }

    //To request the resutle
    $curl_url = 'http://127.0.0.1:8803/index.php/BDmediabuyapk/' . $startdate . '&' . $enddate;
    $curl_body = '<a href="' . $curl_url  . '">Download</a>';
    
    $app->response->setBody($curl_body);
  });

  //Export data
  $app->get('/:date', function ($date) {
    require 'download.php';

    $arr = explode("&", $date);
    $startDate = $arr[0];
    $endDate = $arr[1];

    $arr_campaignId = array('1358289','1358367','1363758','1363759','1401077','1401078','1401079','1401080','1401081','1401082','1401083','1401084','1404258','1473639','1495323','1512852','1520267','1531556','1532107','1532108','1532111','1532112','1532114','1549350','1583719','1586909','1602104','1602105','1602108','1606194','1614780','1621356','1637557','1638695','1658520');

    if(validateDate($startDate) and validateDate($endDate)){
      $sql = "SELECT a.date,e.agent AS BD,SUM(a.click) AS click FROM count_campaign_daily a JOIN publisher c ON a.pubid=c.pubid JOIN publisher_info d ON a.pubid=d.pubid JOIN member e ON c.mid=e.mid WHERE a.date>='" . $startDate . "' AND a.date<='" . $endDate . "' AND d.promotion_method='2' AND a.campaign_id IN ('". implode("','",$arr_campaignId) ."')";

      try{
        $db = getDB();
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //Export CSV
        $filename = "BDmediabuyapk_".$startDate."_".$endDate.".csv";
        result($records, $filename);
      } catch(Exception $e){
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }else{
      echo 'BD Media Buy APK click</br>';
      echo 'Date format: <font color="red">2015-02-25&2015-03-26</font></br>';
    }
  });
});

//Export AE & Vidmate deductions.
$app->group('/AEVidmatededuction', function() use ($app) {
  //Select month & day
  $app->get('', function() {
    global $VIEWSDIR;
    $viewsdir = dirname(__FILE__) . $VIEWSDIR;
    $template = file_get_contents($viewsdir . 'AEVidmatededuction.html');
    echo $template;
  });

  //Receive select data.
  $app->post('', function () {
    require 'download.php';

    $app = \Slim\Slim::getInstance();
    $selectYear = $app->request->post('selectYear');
    $selectMonth = $app->request->post('selectMonth');

    //To request the resutle
    $curl_url = 'http://127.0.0.1:8803/index.php/AEVidmatededuction/' . $selectYear . '-' . $selectMonth;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_URL, $curl_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    curl_close($ch);

    $records = json_decode($data, true);

    //Export CSV
    $filename = "AEVidmatededuction_".$selectYear."-".$selectMonth.".csv";
    result($records, $filename);
  });

  // /AEVidmatededuction/2016-01
  $app->get('/:date', function($date) {
    $date = $date . '-01';

    $sql = "SELECT cpqm.stat_time,cpqm.quality_product_id,cpqm.subpub,cpqm.pubid,p.pub,m.account,m.agent AS BD, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_deductions,0))+SUM(cpqm.non_standard_deductions) AS total_deduction_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_earning,0))+SUM(cpqm.non_standard_unreach_earning) AS total_unreach_earning_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_install,0))+SUM(cpqm.non_standard_unreach_install) AS total_unreach_install_noImmunity FROM count_publisher_quality_month cpqm JOIN publisher p ON cpqm.pubid = p.pubid JOIN member m ON p.mid  = m.mid WHERE cpqm.stat_time='" . $date . "' AND cpqm.quality_product_id IN('ae','vidmate') GROUP BY cpqm.quality_product_id,cpqm.subpub,cpqm.pubid";

    try{
      $db = getDB();
      $stmt = $db->query($sql);
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($records);
    } catch(Exception $e){
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
  });
});

//Export publisher quality deductions by bill_month.
$app->group('/QualityDeduction', function() use ($app) {
  //Select quality_product_id, month
  $app->get('', function() {
    global $VIEWSDIR;
    $viewsdir = dirname(__FILE__) . $VIEWSDIR;
    $template = file_get_contents($viewsdir . 'QualityDeduction.html');
    echo $template;
  });

  //Receive select data.
  $app->post('', function () {
    require 'download.php';

    $app = \Slim\Slim::getInstance();
    $selectProduct = $app->request->post('selectProduct');
    $selectYear = $app->request->post('selectYear');
    $selectMonth = $app->request->post('selectMonth');

    //To request the resutle
    $curl_url = 'http://127.0.0.1:8803/index.php/QualityDeduction/'. $selectProduct . '&' .$selectYear . '-' . $selectMonth;
     echo  $curl_url;exit;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_URL, $curl_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    curl_close($ch);

    $records = json_decode($data, true);

    //Export CSV
    $filename = $selectProduct."_".$selectYear."-".$selectMonth.".csv";
    result($records, $filename);
  });

  // //2016-01
  $app->get('/:productdate', function($productdate) {
    //basedir
    //$dic = '/staticsource/';

    $arr = explode("&", $productdate);
    $product = $arr[0];
    $date = $arr[1];
    $date = $date . '-01';

    $sql = "SELECT cpqm.stat_time,cpqm.quality_product_id,cpqm.subpub,cpqm.pubid,p.pub,m.account,m.agent AS BD, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_deductions,0))+SUM(cpqm.non_standard_deductions) AS total_deduction_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_earning,0))+SUM(cpqm.non_standard_unreach_earning) AS total_unreach_earning_noImmunity, SUM(IF(cpqm.is_immunity='N',cpqm.unreach_install,0))+SUM(cpqm.non_standard_unreach_install) AS total_unreach_install_noImmunity FROM count_publisher_quality_month cpqm JOIN publisher p ON cpqm.pubid = p.pubid JOIN member m ON p.mid  = m.mid WHERE cpqm.stat_time='" . $date . "' AND cpqm.quality_product_id IN('" . $product . "') GROUP BY cpqm.quality_product_id,cpqm.subpub,cpqm.pubid";

    try{
      $db = getDB();
      $stmt = $db->query($sql);
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode($records);
    } catch(Exception $e){
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
  });
});

//Export AE install & earning
$app->group('/AEinstallearning', function () use ($app){
  //Read Me
  $app->get('', function () {
    $output = <<<HTM
<h1>ReadMe</h1>
<p>1. /AEinstallearning/2016-01-01&2016-01-15</p>
HTM;
    print $output;
  });

  //pid: 2007171,2009421,2024998,2030620
  $app->get('/:date', function ($date) {
    require 'download.php';

    $arr = explode("&", $date);
    $startDate = $arr[0];
    $endDate = $arr[1];
    $arr_product = array('2007171', '2009421', '2024998', '2030620');
    $product = implode("','",$arr_product);

    if(validateDate($startDate) and validateDate($endDate)){
      $sql = "SELECT m.date,m.pid,o.title AS product_name,p.code AS country,m.install,n.earning FROM (SELECT ccd.date,ca.pid,ccd.country_id,SUM(ccd.install) AS INSTALL FROM count_campaign_daily ccd LEFT JOIN campaign ca ON ca.campaign_id=ccd.campaign_id WHERE ca.pid IN ('" . $product . "') AND ccd.date>='" . $startDate . "' AND ccd.date<='" . $endDate . "' GROUP BY ccd.date,ca.pid,ccd.country_id)m LEFT JOIN (SELECT pbd.datetime,pbd.pid,pbd.country_id,SUM(pbd.income) AS earning FROM publisher_bill_detail pbd WHERE pbd.datetime>='" . $startDate . "' AND pbd.datetime<='" . $endDate . "' AND pbd.pid IN ('" . $product . "') GROUP BY pbd.datetime,pbd.pid,pbd.country_id) n ON m.date=n.datetime AND m.pid=n.pid AND m.country_id=n.country_id LEFT JOIN product o ON m.pid=o.pid LEFT JOIN country_option p ON m.country_id=p.id";

      try{
        $db = getDB();
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //Export CSV
        $filename = "AEinstallearning_".$startDate."_".$endDate.".csv";
        result($records, $filename);
      } catch(Exception $e){
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }else{
      echo 'AE install & earning Data</br>';
      echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
    }
  });
});

//Export Dynamic source
$app->group('/dynamicsource', function () use ($app){
  $app->get('', function () {
    require 'download.php';

    //basedir
    global $VIEWSDIR;
    $dic = '/dynamicsource/';
    $dir = dirname(__FILE__) . $dic;
    $viewsdir = dirname(__FILE__) . $VIEWSDIR;

    //interface
    $template = file_get_contents($viewsdir . 'dynamicsource.html');
    echo $template;

    //file list
    fileList($dir, $dic, 2);
  });

  $app->post('', function () {
    //basedir
    $dic = '/dynamicsource/';
    $dir = dirname(__FILE__) . $dic;

    $app = \Slim\Slim::getInstance();
    $startDate = $app->request->post('startdate');
    $endDate = $app->request->post('enddate');

    if(validateDate($startDate) and validateDate($endDate)){
      //To request the resutle
      $curl_url = 'http://127.0.0.1:8803/index.php/dynamicsource/' . $startDate . '&' . $endDate;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $curl_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
      curl_setopt($ch, CURLOPT_TIMEOUT, 1);
      curl_exec($ch);
      curl_close($ch);

      //Gen file
      $genFile = $dir . "export_peak_and_avg_adrequest_byAcc_".$startDate."_".$endDate.".csv";

      if(file_exists($genFile) != true ){
        touch($genFile);
      }

      echo "File is generating. Please go back to check.";
    }else{
      echo 'Dynamicsource Data</br>';
      echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
    }
  });

  $app->get('/:date', function ($date) {
    require 'download.php';

    //basedir
    $dic = '/dynamicsource/';

    $arr = explode("&", $date);
    $startDate = $arr[0];
    $endDate = $arr[1];

    $sql = "SELECT a.mid,a.account,a.agent AS BD, GROUP_CONCAT(DISTINCT pu.url SEPARATOR ';') AS url_list, b.date AS max_impr_date,b.max_impr_daily,c.impr_avg FROM member a LEFT JOIN (SELECT DISTINCT m.date,m.mid,m.impr_daily AS max_impr_daily FROM (SELECT p.DATE,q.mid,SUM(p.impr) AS impr_daily FROM count_campaign_daily p JOIN publisher q ON p.pubid=q.pubid JOIN publisher_info r ON p.pubid=r.pubid WHERE r.promotion_method=2 AND r.media_type IN (2,3,100) AND r.ad_format IN (2,4,5,100) GROUP BY p.DATE,q.mid ORDER BY impr_daily DESC) m GROUP BY m.mid) b ON a.mid=b.mid LEFT JOIN (SELECT q.mid,SUM(p.impr)/COUNT(DISTINCT p.date) AS impr_avg FROM count_campaign_daily p JOIN publisher q ON p.pubid=q.pubid JOIN publisher_info r ON p.pubid=r.pubid WHERE p.date>='" . $startDate ."' AND p.date<='" . $endDate . "' AND r.promotion_method=2 AND r.media_type IN (2,3,100) AND r.ad_format IN (2,4,5,100) GROUP BY q.mid) c ON a.mid=c.mid LEFT JOIN publisher pu ON a.mid=pu.mid GROUP BY a.mid ORDER BY a.mid"; 

    try{
      $db = getDB();
      $stmt = $db->query($sql);
      $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

      //Export CSV
      $filename = "export_peak_and_avg_adrequest_byAcc_".$startDate."_".$endDate.".csv";

      result($records, $dic, $filename);
      } catch(Exception $e){
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
  });

  $app->get('/delete/:filename', function ($filename) {
    //basedir
    $dic = '/dynamicsource/';
    $dir = dirname(__FILE__) . $dic;

    $file = $dir . $filename;

    $result = @unlink($file);

    if($result==1){
      echo "Delete success.</br>";
    }else{
      echo "File delete fail!";
    }
  });
});

//Export Static source
$app->group('/staticsource', function() use ($app) {
  //Select year & month
  $app->get('', function () {
    require 'download.php';

    //basedir
    global $VIEWSDIR;
    $dic = '/staticsource/';
    $dir = dirname(__FILE__) . $dic;
    $viewsdir = dirname(__FILE__) . $VIEWSDIR;

    if(count($_POST) == 0){
      $template = file_get_contents($viewsdir . 'staticsource.html');
      echo $template;
    }

    //file list
    fileList($dir, $dic, 3);
  });

  //Receive select data.
  $app->post('', function () {
    //basedir
    $dic = '/staticsource/';
    $dir = dirname(__FILE__) . $dic;

    $app = \Slim\Slim::getInstance();
    $selectYear = $app->request->post('selectYear');
    $selectMonth = $app->request->post('selectMonth');

    if(intval($selectMonth) <= 10){
      if(intval($selectMonth) == 1){
        $startdate = (intval($selectYear)-1) . '-12-26';
      }else{
        $startdate = $selectYear . '-0' . (intval($selectMonth)-1) . '-26';
      }
      $enddate = $selectYear . '-' .$selectMonth . '-25';
    }else{
      $startdate = $selectYear . '-' . (intval($selectMonth)-1) . '-26';
      $enddate = $selectYear . '-' .$selectMonth . '-25';
    }

    if(validateDate($startdate) and validateDate($enddate)){
      //Gen file
      $genFile = $dir . "export_BD_monthBalance_pid_country_promotionmethod_".$startdate."_".$enddate.".csv";

      if(file_exists($genFile) != true ){
        touch($genFile);
      }

      //To request the resutle
      $curl_url = 'http://127.0.0.1:8803/index.php/staticsource/' . $startdate . '&' . $enddate;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPGET, 1);
      curl_setopt($ch, CURLOPT_URL, $curl_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
      curl_setopt($ch, CURLOPT_TIMEOUT, 1);
      curl_exec($ch);
      curl_close($ch);

      echo "File is generating. Please go back to check.";
    }else{
      echo 'Staticsource Data</br>';
      echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
    }
  });

  //Export data
  $app->get('/:date', function ($date) {
    require 'download.php';

    //basedir
    $dic = '/staticsource/';

    $arr = explode("&", $date);
    $startDate = $arr[0];
    $endDate = $arr[1];

    $productGroup=array("1", "2");
    $productName=array("ucbrowser", "9apps");

    $arr_BD=array('buxin','chenjp','chenzb','fuquan','gongyefq','Guls','lianghl','liangrui','limj3','litvinov','luli','luohy1','mykola','shipw','tarandeep2','tnj','wangwei8','wangyx','yekai','zhanglh','zhoushan','zhuangtc','henrykusumaputra','fenty1','purk','wangyun','minzhen','yanping');

    if(validateDate($startDate) and validateDate($endDate)){
      for ($i = 0; $i < count($productGroup); $i++) {
        $productGroupValue = $productGroup[$i];
        $productNameValue = $productName[$i];

        //查询uc,9apps列表国家的数据
        $sql1 = "SELECT '$endDate' AS month,mem.`agent` AS bd,'$productNameValue' AS product,t.`country_id` AS country,pubinfo.`promotion_method` AS promotion_method,SUM(IF(t.`frid` IN (1,4),t.`install`,0)) AS andr_ios_install,AVG(IF(t.`frid` IN (1,4),t.`impr`,0)) AS andr_ios_avg_impr,SUM(IF(t.`frid` NOT IN (1,4),t.`install`,0)) AS fp_install,AVG(IF(t.`frid` NOT IN (1,4),t.`impr`,0)) AS fp_avg_impr FROM count_campaign_daily t JOIN campaign cam ON cam.`campaign_id`=t.`campaign_id` JOIN publisher pub ON t.`pubid`=pub.`pubid` JOIN publisher_info pubinfo ON t.`pubid`=pubinfo.`pubid` JOIN member mem ON mem.`mid`=pub.`mid` JOIN product pro ON pro.`pid`=cam.`pid` WHERE t.`date`>= '$startDate' AND t.`date`<='$endDate' AND pro.product_group='$productGroupValue' AND t.`country_id` IN ('1','3','4') AND pubinfo.`promotion_method` IN ('1','2') AND mem.`agent` IN ('" . implode("','",$arr_BD) . "') GROUP BY mem.`agent`,t.`country_id`,pubinfo.`promotion_method`";

        //查询uc,9apps其他国家的数据
        $sql2 = "SELECT '$endDate' AS month,mem.`agent` AS bd,'$productNameValue' AS product,'other' AS country,pubinfo.`promotion_method` AS promotion_method,SUM(IF(t.`frid` IN (1,4),t.`install`,0)) AS andr_ios_install,AVG(IF(t.`frid` IN (1,4),t.`impr`,0)) AS andr_ios_avg_impr,SUM(IF(t.`frid` NOT IN (1,4),t.`install`,0)) AS fp_install,AVG(IF(t.`frid` NOT IN (1,4),t.`impr`,0)) AS fp_avg_impr FROM count_campaign_daily t JOIN campaign cam ON cam.`campaign_id`=t.`campaign_id` JOIN publisher pub ON t.`pubid`=pub.`pubid` JOIN publisher_info pubinfo ON t.`pubid`=pubinfo.`pubid` JOIN member mem ON mem.`mid`=pub.`mid` JOIN product pro ON pro.`pid`=cam.`pid` WHERE t.`date`>= '$startDate' AND t.`date`<='$endDate' AND pro.product_group='$productGroupValue' AND t.`country_id` NOT IN ('1','3','4') AND pubinfo.`promotion_method` IN ('1','2') AND mem.`agent` IN ('" . implode("','",$arr_BD) . "') GROUP BY mem.`agent`,pubinfo.`promotion_method`";

        try{
          $db1 = getDB();
          $stmt1 = $db1->query($sql1);
          $records1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
          $db1 = null;

          $db2 = getDB();
          $stmt2 = $db2->query($sql2);
          $records2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
          $db2 = null;

          //Export CSV
          $filename = "export_BD_monthBalance_pid_country_promotionmethod_".$startDate."_".$endDate.".csv";
          result($records1, $dic, $filename);
          result($records2, $dic, $filename);
        } catch(Exception $e){
          echo '{"error":{"text":'. $e->getMessage() .'}}';
        }
      }

      //查询其他产品所有国家数据
      $sql3 =  "SELECT  '$endDate' AS month, mem.`agent` AS bd,'other' AS product,'all' AS country,pubinfo.`promotion_method` AS promotion_method,SUM(IF(t.`frid` IN (1,4),t.`install`,0)) AS andr_ios_install,SUM(IF(t.`frid` NOT IN (1,4),t.`install`,0)) AS fp_install,AVG(IF(t.`frid` IN (1,4),t.`impr`,0)) AS andr_ios_avg_impr,AVG(IF(t.`frid` NOT IN (1,4),t.`impr`,0)) AS fp_avg_impr FROM count_campaign_daily t JOIN campaign cam ON cam.`campaign_id`=t.`campaign_id` JOIN publisher pub ON t.`pubid`=pub.`pubid` JOIN publisher_info pubinfo ON t.`pubid`=pubinfo.`pubid` JOIN member mem ON mem.`mid`=pub.`mid` JOIN product pro ON pro.`pid`=cam.`pid` WHERE t.`date`>= '$startDate' AND t.`date`<='$endDate' AND pro.product_group NOT IN ('1','2') AND pubinfo.`promotion_method` IN ('1','2') AND mem.`agent` IN ('". implode("','",$arr_BD) .".) GROUP BY mem.`agent`,pubinfo.`promotion_method`";

      try{
        $db3 = getDB();
        $stmt3 = $db3->query($sql3);
        $records3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        $db3 = null;

        //Export CSV
        result($records3, $dic, $filename);
      } catch(Exception $e){
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }else{
      echo 'Staticsource Data</br>';
      echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
    }
  });

  $app->get('/delete/:filename', function ($filename) {
    //basedir
    $dic = '/staticsource/';
    $dir = dirname(__FILE__) . $dic;

    $file = $dir . $filename;

    $result = @unlink($file);

    if($result==1){
      echo "Delete success.</br>";
    }else{
      echo "File delete fail!";
    }
  });
});

//Export Quality data.
$app->group('/quality', function () use ($app){
  //Read Me
  $app->get('', function () {
    $output = <<<HTM
<h1>ReadMe</h1>
<p>1. /quality/standard/2016-01-01&2016-01-15</p>
<p>2. /quality/nonstandard/2016-01-01&2016-01-15</p>
HTM;
    print $output;
  });

  // /quality/standard/2015-01-01&2015-02-01
  $app->get('/standard/:date', function ($date) {
    require 'download.php';

    $arr = explode("&", $date);
    $startDate = $arr[0];
    $endDate = $arr[1];
    $arr_country =  array('others', 'IN', 'ID', 'RU', 'VN' ,'BD', 'PK', 'MY', 'BR', 'SA', 'EG', 'NP', 'SG', 'LK', 'AE', 'CO', 'TR', 'PH', 'MX', 'KZ', 'DZ', 'TH', 'AR', 'BO', 'UA', 'MM', 'SD', 'ZA', 'GT', 'KR', 'IQ', 'BY', 'US', 'UZ');

    if(validateDate($startDate) and validateDate($endDate)){
      $sql = "SELECT country, IF(CEIL(quality*20)/20 >=1, 1, CEIL(quality*20)/20) AS up_quality, SUM(INSTALL) FROM count_publisher_quality WHERE quality_product_id = 'uc' AND DATE >= '" . $startDate . "' AND DATE <= '" . $endDate . "' AND country IN ('" . implode("','",$arr_country) ."') GROUP BY country, up_quality ORDER BY up_quality DESC";

      try{
        $db = getDB();
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //Export CSV
        $filename = "standard_quality_".$startDate."_".$endDate.".csv";
        result($records, $filename);
      } catch (Exception $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }else{
      echo 'Quality Data</br>';
      echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
    }
  });

  // /quality/nonstandard/2015-01-01&2015-02-01
  $app->get('/nonstandard/:date', function ($date) {
    require 'download.php';

    $arr = explode("&", $date);
    $startDate = $arr[0];
    $endDate = $arr[1];
    $arr_country =  array('others', 'IN', 'ID', 'RU', 'VN' ,'BD', 'PK', 'MY', 'BR', 'SA', 'EG', 'NP', 'SG', 'LK', 'AE', 'CO', 'TR', 'PH', 'MX', 'KZ', 'DZ', 'TH', 'AR', 'BO', 'UA', 'MM', 'SD', 'ZA', 'GT', 'KR', 'IQ', 'BY', 'US', 'UZ');

    if(validateDate($startDate) and validateDate($endDate)){
      $sql = "SELECT country, IF(CEIL(quality*20)/20 >=1, 1, CEIL(quality*20)/20) AS up_quality, SUM(INSTALL) FROM count_subpub_quality WHERE quality_product_id = 'uc' AND DATE >= '" . $startDate . "' AND DATE <= '" . $endDate . "' AND country IN ('" . implode("','",$arr_country) . "') GROUP BY country, up_quality ORDER BY up_quality DESC";

      try{
        $db = getDB();
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //Export CSV
        $filename = "nonstandard_quality_".$startDate."_".$endDate.".csv";
        result($records, $filename);
      } catch (Exception $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    }else{
      echo 'Quality Data</br>';
      echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
    }
  });
});

//Export adjust data.
$app->group('/adjust', function () use ($app) {
  // GET /adjust
  $app->get('', function() {
    require 'download.php';

    //basedir
    $dic = '/adjust/';
    $dir = dirname(__FILE__) . $dic;

    $yesterday = date("Y-m-d",strtotime("-1 day"));
    $genFile = $dir . 'adrequest_click_impression_adjust_' . $yesterday . '.csv';
    if(file_exists($genFile) != true ){
      //generation a file
      touch($genFile);

      //To request the resutle
      $curl_url = 'http://127.0.0.1:8803/index.php/adjust/' . $yesterday;
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPGET, 1);
      curl_setopt($ch, CURLOPT_URL, $curl_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
      curl_setopt($ch, CURLOPT_TIMEOUT, 1);
      curl_exec($ch);
      curl_close($ch);
    }

    //file list
    fileList($dir, $dic, 0);
  });

  $app->get('/:date', function($date) {
    require 'download.php';

    set_time_limit(0);

    //basedir
    $dic = '/adjust/';

    $sql = "SELECT a.pubid AS Pubid, b.pub AS Pub, c.account AS Account, c.agent AS BD, d.impr AS `Ad Request`, d.beacon AS Impression, e.beacona AS `Impression(Adjusted)`, d.click AS Click, e.clicka AS `Click(Adjusted)`, f.income AS Earning FROM (SELECT pubid FROM publisher_info pi WHERE pi.cooperation_mode IN (1,2) AND pi.promotion_method='2' AND pi.media_type IN (2,3,100) AND pi.ad_format IN (2,4,100) AND pi.integration_method IN (1,3)) a LEFT JOIN (SELECT p.pubid, p.pub, p.mid FROM publisher p ) b ON a.pubid=b.pubid LEFT JOIN (SELECT m.account, m.agent, m.mid FROM member m ) c ON b.mid=c.mid LEFT JOIN (SELECT ccd.pubid, sum(ccd.impr) AS impr, sum(ccd.beacon) AS beacon, sum(ccd.click) AS click FROM count_campaign_daily ccd WHERE ccd.date='$date' GROUP BY ccd.pubid) d ON a.pubid=d.pubid LEFT JOIN (SELECT ccde.pubid, sum(ccde.beacon_adjusted) AS beacona, sum(ccde.click_adjusted) AS clicka FROM count_campaign_daily_ext ccde WHERE ccde.date='$date' GROUP BY ccde.pubid) e ON a.pubid=e.pubid LEFT JOIN (SELECT pbd.pubid, sum(pbd.income) AS income FROM publisher_bill_daily pbd WHERE pbd.datetime='$date' GROUP BY pbd.pubid) f ON a.pubid=f.pubid";
    //echo $sql;

    try{
      try {
        $db = getDB();
        $stmt = $db->query($sql);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = null;

        //Export CSV
        $filename = "adrequest_click_impression_adjust_".$date.".csv";
        result($records, $dic, $filename);
      } catch(PDOException $e) {
        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
        echo '{"error":{"text":'. $e->getMessage() .'}}';
      }
    } catch(Exception $e){
      echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
  })->conditions(array('date' => '[0-9]{4}(\-)[0-9]{2}(\-)[0-9]{2}$'));
});

$app->get('/README', function () {
  $app = Slim\Slim::getInstance();
  $html = <<<HTM
<h1>README</h1>
<h2>Mail</h2>
<p><font color="red">http://URL/index.php/mails/@id</font> : How many mails in the sending queue.</p>
<p><font color="red">http://URL/index.php/mails/history/@id</font> : How many mails already sent in queue.</p>
<p><font color="red">http://URL/index.php/mails/history/@name</font> : Email history List.</p>
<p></p>
<p>@id: mail id in backend. [email.id:]</p>
<p>@name: email address.</p>
<p><hr/></p>
HTM;
    $app->response->setBody($html);
});

$app->run();

/**********************************/

function indexFunction()
{
  $app = \Slim\Slim::getInstance();
  $app->response->setStatus(200);

  $html = <<<HTM
<h1>Welcome</h1>
<p><<< Download Server >>>. Please contact chenyang.ycy@alibaba-inc.com</p>
<p></p>
<p><<< Data Export >>>. Please contact jiajie.gjj@alibaba-inc.com</p>
HTM;

  $app->response->setBody($html);
}

function validateDate($date, $format = 'Y-m-d')
{
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) == $date;
}
