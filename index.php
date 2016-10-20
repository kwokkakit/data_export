<?php
/**
 * Created by PhpStorm.
 * User: kwokkakit
 * Date: 12/10/15
 * Time: 5:14 PM
 */

require 'vendor/autoload.php';
require 'DAO/db.php';

$VIEWSDIR = '/VIEWS/';

$app = new \Slim\Slim();

//Index introduce
$app->get('/', 'indexFunction');

//Mail status
$app->group('/mails', function () use ($app) {
    // GET /mail
    $app->map('', function () {
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
    $app->get('/:id', function ($id) {
        //remark: email.id:530
        $dbopt = new DBoperator();
        $sql = $dbopt->getMailbyId($id);
        $records = $dbopt->getData($sql);

        try {
            echo 'There are ' . $records[0]['mails'] . ' mails in queue.';
        } catch (Exception $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }

    })->conditions(array('id' => '[0-9]+'));

    // GET /mail/history/@id
    $app->get('/history/:id', function ($id) {
        //remark: email.id:530
        $dbopt = new DBoperator();
        $sql = $dbopt->getHistoryMailById($id);
        $records = $dbopt->getData($sql);

        try {
            echo 'There are ' . $records[0]['mails'] . ' mails in queue.';
        } catch (Exception $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }

    })->conditions(array('id' => '[0-9]+'));

    // GET /mail/history/@name
    $app->get('/history/:name', function ($name) {
        $dbopt = new DBoperator();
        $sql = $dbopt->getHistoryMailByName($name);
        $records = $dbopt->getData($sql);

        try {
            //Display Data
            //Table Head
            $output = "<table><tr><td>from_account</td><td>from</td><td>to</td><td>title</td><td>beg_time</td><td>end_time</td></tr>";

            //Table Body
            for ($i = 0; $i < count($records); $i++) {
                $output .= "<tr>";
                $output .= "<td>" . $records[$i]['from_account'] . "</td>";
                $output .= "<td>" . $records[$i]['from'] . "</td>";
                $output .= "<td>" . $records[$i]['to'] . "</td>";
                $output .= "<td>" . $records[$i]['title'] . "</td>";
                $output .= "<td>" . date('Y-m-d H:i:s', $records[$i]['beg_time']) . "</td>";
                $output .= "<td>" . date('Y-m-d H:i:s', $records[$i]['end_time']) . "</td>";
                $output .= "</tr>";
            }
            $output .= "</table>";
            echo $output;

        } catch (Exception $e) {
            //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    })->conditions(array('name' => '[a-zA-Z0-9_-]+[\.a-zA-Z0-9]*@[a-zA-Z0-9_-]+[\.a-zA-Z]+$'));
});

//Export pid=2029901,2028908,2177116,2177117,2396545,2396546 data.
$app->get('/:id/:date', function ($id, $date) {
    //  GET /2029901/2015-10-10
    require 'download.php';

    if ($id == '2029901' or $id == '2028908' or $id == '2177116' or $id == '2177117' or $id == '2396545' or $id == '2396546') {
        if (validateDate($date)) {
            $dbopt = new DBoperator();
            $sql = $dbopt->getADRequestByDate($date, $id);
            //echo $sql;
            $records = $dbopt->getData($sql);
            try {
                //Export CSV
                $filename = "pid_" . $id . "_adrequest_click_by_contry_fr_" . $date . ".csv";
                result($records, $filename);
            } catch (Exception $e) {
                //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } else {
            echo "Date format ERROR . ex: 2015-01-01.";
        }
    } else {
        echo "Your Product ID not allowed";
    }
})->conditions(array('id' => '[0-9]+'));

//Export MediaPublisher data.
$app->group('/mediapublisher', function () use ($app) {
    // GET /mediapublisher
    $app->get('', function () {
        require 'date_handler.php';
        require 'download.php';

        $startDate = last_monday(0, false);
        $endDate = last_sunday(0, false);

        $dbopt = new DBoperator();
        $sql = $dbopt->getMediaPublisher($startDate, $endDate);
        $records = $dbopt->getData($sql);

        try {
            //Export CSV
            $filename = "channel_weekly_breakeven_analysis" . $startDate . "_" . $endDate . ".csv";
            result($records, $filename);
        } catch (Exception $e) {
            //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    });

    // /mediapublisher/export/2015-01-01&2015-02-01
    $app->get('/export/:date', function ($date) {
        require 'date_handler.php';
        require 'download.php';

        $arr = explode("&", $date);
        $startDate = $arr[0];
        $endDate = $arr[1];

        if (validateDate($startDate) and validateDate($endDate)) {
            $dbopt = new DBoperator();
            $sql = $dbopt->getMediaPublisher($startDate, $endDate);
            $records = $dbopt->getData($sql);

            try {
                //Export CSV
                $filename = "channel_weekly_breakeven_analysis_" . $startDate . "_" . $endDate . ".csv";
                result($records, $filename);
            } catch (Exception $e) {
                //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } else {
            echo 'Media Publisher</br>';
            echo 'Date format: <font color="red">2015-01-01&2015-02-01</br></font>';
        }
    });

    //Export MediaPublisher by daily
    $app->group('/daily', function () use ($app) {
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

            if ($result == 1) {
                echo "Delete success.</br>";
            } else {
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

            if (validateDate($startDate) and validateDate($endDate)) {
                //处理时间
                $dateList1 = explode("-", $startDate);
                $dateList2 = explode("-", $endDate);
                $d1 = mktime(0, 0, 0, $dateList1[1], $dateList1[2], $dateList1[0]);
                $d2 = mktime(0, 0, 0, $dateList2[1], $dateList2[2], $dateList2[0]);

                $days = -(round(($d1 - $d2) / 3600 / 24));
                if ($days < 0) {
                    throw new Exception("Start Date must be greater than End Date.");
                }

                $pubFile = fopen($dir . $pubFileName, "r");
                $pubResult = fgetcsv($pubFile);

                $str = implode(',', $pubResult);
                $condition = str_replace(",", "','", $str);

                for ($i = 0; $i <= $days; $i++) {
                    $eachDay = date("Y-m-d", strtotime("$startDate +$i day"));
                    $dbopt = new DBoperator();
                    $sql = $dbopt->getMediaPublisherDaily($eachDay, $condition);
                    $records = $dbopt->getData($sql);

                    try {
                        //Export CSV
                        $filename = "channel_weekly_breakeven_analysis_eachDay.csv";
                        result($records, $dic, $filename);
                    } catch (Exception $e) {
                        //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
                        echo '{"error":{"text":' . $e->getMessage() . '}}';
                    }
                }

            } else {
                echo 'MediaPublisher Daily</br>';
                echo 'Date format: <font color="red">2015-01-01&2015-02-01</br></font>';
            }
        });
    });
});

//Export BD Media Buy APK click
$app->group('/BDmediabuyapk', function () use ($app) {
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

        if (intval($selectMonth) <= 10) {
            if (intval($selectMonth) == 1) {
                $startdate = (intval($selectYear) - 1) . '-12-26';
            } else {
                $startdate = $selectYear . '-0' . (intval($selectMonth) - 1) . '-26';
            }
            $enddate = $selectYear . '-' . $selectMonth . '-25';
        } else {
            $startdate = $selectYear . '-' . (intval($selectMonth) - 1) . '-26';
            $enddate = $selectYear . '-' . $selectMonth . '-25';
        }

        //To request the resutle
        $curl_url = 'http://127.0.0.1:8803/index.php/BDmediabuyapk/' . $startdate . '&' . $enddate;
        $curl_body = '<a href="' . $curl_url . '">Download</a>';

        $app->response->setBody($curl_body);
    });

    //Export data
    $app->get('/:date', function ($date) {
        require 'download.php';

        $arr = explode("&", $date);
        $startDate = $arr[0];
        $endDate = $arr[1];

        $arr_campaignId = array('1358289', '1358367', '1363758', '1363759', '1401077', '1401078', '1401079', '1401080', '1401081', '1401082', '1401083', '1401084', '1404258', '1473639', '1495323', '1512852', '1520267', '1531556', '1532107', '1532108', '1532111', '1532112', '1532114', '1549350', '1583719', '1586909', '1602104', '1602105', '1602108', '1606194', '1614780', '1621356', '1637557', '1638695', '1658520');

        if (validateDate($startDate) and validateDate($endDate)) {
            $dbopt = new DBoperator();
            $sql = $dbopt->getBDmediabuyapk($startDate, $endDate, $arr_campaignId);
            $records = $dbopt->getData($sql);

            try {
                //Export CSV
                $filename = "BDmediabuyapk_" . $startDate . "_" . $endDate . ".csv";
                result($records, $filename);
            } catch (Exception $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } else {
            echo 'BD Media Buy APK click</br>';
            echo 'Date format: <font color="red">2015-02-25&2015-03-26</font></br>';
        }
    });
});

//Export AE & Vidmate deductions.
$app->group('/AEVidmatededuction', function () use ($app) {
    //Select month & day
    $app->get('', function () {
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
        $filename = "AEVidmatededuction_" . $selectYear . "-" . $selectMonth . ".csv";
        result($records, $filename);
    });

    // /AEVidmatededuction/2016-01
    $app->get('/:date', function ($date) {
        $date = $date . '-01';

        $dbopt = new DBoperator();
        $sql = $dbopt->getAEVidmatededuction($date);
        $records = $dbopt->getData($sql);

        try {
            echo json_encode($records);
        } catch (Exception $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    });
});

//Export publisher quality deductions by bill_month.
$app->group('/QualityDeduction', function () use ($app) {
    //Select quality_product_id, month
    $app->get('', function () {
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
        $curl_url = 'http://127.0.0.1:8803/index.php/QualityDeduction/' . $selectProduct . '&' . $selectYear . '-' . $selectMonth;
        echo $curl_url;
        exit;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_URL, $curl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $data = curl_exec($ch);
        curl_close($ch);

        $records = json_decode($data, true);

        //Export CSV
        $filename = $selectProduct . "_" . $selectYear . "-" . $selectMonth . ".csv";
        result($records, $filename);
    });

    // //2016-01
    $app->get('/:productdate', function ($productdate) {
        //basedir
        //$dic = '/staticsource/';

        $arr = explode("&", $productdate);
        $product = $arr[0];
        $date = $arr[1];
        $date = $date . '-01';

        $dbopt = new DBoperator();
        $sql = $dbopt->getQualityDeduction($date, $product);
        $records = $dbopt->getData($sql);

        try {
            echo json_encode($records);
        } catch (Exception $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    });
});

//Export AE install & earning
$app->group('/AEinstallearning', function () use ($app) {
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
        $product = implode("','", $arr_product);

        if (validateDate($startDate) and validateDate($endDate)) {
            $dbopt = new DBoperator();
            $sql = $dbopt->getAEinstallearning($startDate, $endDate, $product);
            $records = $dbopt->getData($sql);

            try {
                //Export CSV
                $filename = "AEinstallearning_" . $startDate . "_" . $endDate . ".csv";
                result($records, $filename);
            } catch (Exception $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } else {
            echo 'AE install & earning Data</br>';
            echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
        }
    });
});

//Export Dynamic source
$app->group('/dynamicsource', function () use ($app) {
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

        if (validateDate($startDate) and validateDate($endDate)) {
            //To request the resutle
            $curl_url = 'http://127.0.0.1:8803/index.php/dynamicsource/' . $startDate . '&' . $endDate;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $curl_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            curl_exec($ch);
            curl_close($ch);

            //Gen file
            $genFile = $dir . "export_peak_and_avg_adrequest_byAcc_" . $startDate . "_" . $endDate . ".csv";

            if (file_exists($genFile) != true) {
                touch($genFile);
            }

            echo "File is generating. Please go back to check.";
        } else {
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

        $dbopt = new DBoperator();
        $sql = $dbopt->getDynamicSource($startDate, $endDate);
        $records = $dbopt->getData($sql);

        try {
            //Export CSV
            $filename = "export_peak_and_avg_adrequest_byAcc_" . $startDate . "_" . $endDate . ".csv";

            result($records, $dic, $filename);
        } catch (Exception $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
        }
    });

    $app->get('/delete/:filename', function ($filename) {
        //basedir
        $dic = '/dynamicsource/';
        $dir = dirname(__FILE__) . $dic;

        $file = $dir . $filename;

        $result = @unlink($file);

        if ($result == 1) {
            echo "Delete success.</br>";
        } else {
            echo "File delete fail!";
        }
    });
});

//Export Static source
$app->group('/staticsource', function () use ($app) {
    //Select year & month
    $app->get('', function () {
        require 'download.php';

        //basedir
        global $VIEWSDIR;
        $dic = '/staticsource/';
        $dir = dirname(__FILE__) . $dic;
        $viewsdir = dirname(__FILE__) . $VIEWSDIR;

        if (count($_POST) == 0) {
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

        if (intval($selectMonth) <= 10) {
            if (intval($selectMonth) == 1) {
                $startdate = (intval($selectYear) - 1) . '-12-26';
            } else {
                $startdate = $selectYear . '-0' . (intval($selectMonth) - 1) . '-26';
            }
            $enddate = $selectYear . '-' . $selectMonth . '-25';
        } else {
            $startdate = $selectYear . '-' . (intval($selectMonth) - 1) . '-26';
            $enddate = $selectYear . '-' . $selectMonth . '-25';
        }

        if (validateDate($startdate) and validateDate($enddate)) {
            //Gen file
            $genFile = $dir . "export_BD_monthBalance_pid_country_promotionmethod_" . $startdate . "_" . $enddate . ".csv";

            if (file_exists($genFile) != true) {
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
        } else {
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

        $productGroup = array("1", "2");
        $productName = array("ucbrowser", "9apps");

        $arr_BD = array('buxin', 'chenjp', 'chenzb', 'fuquan', 'gongyefq', 'Guls', 'lianghl', 'liangrui', 'limj3', 'litvinov', 'luli', 'luohy1', 'mykola', 'shipw', 'tarandeep2', 'tnj', 'wangwei8', 'wangyx', 'yekai', 'zhanglh', 'zhoushan', 'zhuangtc', 'henrykusumaputra', 'fenty1', 'purk', 'wangyun', 'minzhen', 'yanping');

        $filename = "export_BD_monthBalance_pid_country_promotionmethod_" . $startDate . "_" . $endDate . ".csv";

        if (validateDate($startDate) and validateDate($endDate)) {
            for ($i = 0; $i < count($productGroup); $i++) {
                $productGroupValue = $productGroup[$i];
                $productNameValue = $productName[$i];

                //查询uc,9apps列表国家的数据
                $dbopt = new DBoperator();
                $sql1 = $dbopt->getStaticSourceUC9APPS($startDate, $endDate, $productNameValue, $productGroupValue, $arr_BD);
                $records1 = $dbopt->getData($sql1);

                //查询uc,9apps其他国家的数据
                $sql2 = $dbopt->getStaticSourceUC9APPSOther($startDate, $endDate, $productNameValue, $productGroupValue, $arr_BD);
                $records2 = $dbopt->getData($sql2);

                try {
                    //Export CSV
                    result($records1, $dic, $filename);
                    result($records2, $dic, $filename);
                } catch (Exception $e) {
                    echo '{"error":{"text":' . $e->getMessage() . '}}';
                }
            }

            //查询其他产品所有国家数据
            $dbopt = new DBoperator();
            $sql3 = $dbopt->getStaticSourceOther($startDate, $endDate, $arr_BD);
            $records3 = $dbopt->getData($sql3);

            try {
                //Export CSV
                result($records3, $dic, $filename);
            } catch (Exception $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } else {
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

        if ($result == 1) {
            echo "Delete success.</br>";
        } else {
            echo "File delete fail!";
        }
    });
});

//Export Quality data.
$app->group('/quality', function () use ($app) {
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
        $arr_country = array('others', 'IN', 'ID', 'RU', 'VN', 'BD', 'PK', 'MY', 'BR', 'SA', 'EG', 'NP', 'SG', 'LK', 'AE', 'CO', 'TR', 'PH', 'MX', 'KZ', 'DZ', 'TH', 'AR', 'BO', 'UA', 'MM', 'SD', 'ZA', 'GT', 'KR', 'IQ', 'BY', 'US', 'UZ');

        if (validateDate($startDate) and validateDate($endDate)) {
            $dbopt = new DBoperator();
            $sql = $dbopt->getStandQuality($startDate, $endDate, $arr_country);
            $records = $dbopt->getData($sql);

            try {
                //Export CSV
                $filename = "standard_quality_" . $startDate . "_" . $endDate . ".csv";
                result($records, $filename);
            } catch (Exception $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } else {
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
        $arr_country = array('others', 'IN', 'ID', 'RU', 'VN', 'BD', 'PK', 'MY', 'BR', 'SA', 'EG', 'NP', 'SG', 'LK', 'AE', 'CO', 'TR', 'PH', 'MX', 'KZ', 'DZ', 'TH', 'AR', 'BO', 'UA', 'MM', 'SD', 'ZA', 'GT', 'KR', 'IQ', 'BY', 'US', 'UZ');

        if (validateDate($startDate) and validateDate($endDate)) {
            $dbopt = new DBoperator();
            $sql = $dbopt->getNonStandQuality($startDate, $endDate, $arr_country);
            $records = $dbopt->getData($sql);

            try {
                //Export CSV
                $filename = "nonstandard_quality_" . $startDate . "_" . $endDate . ".csv";
                result($records, $filename);
            } catch (Exception $e) {
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } else {
            echo 'Quality Data</br>';
            echo 'Date format: <font color="red">2015-01-01&2015-02-01</font></br>';
        }
    });
});

//Export adjust data.
$app->group('/adjust', function () use ($app) {
    // GET /adjust
    $app->get('', function () {
        require 'download.php';

        //basedir
        $dic = '/adjust/';
        $dir = dirname(__FILE__) . $dic;

        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $genFile = $dir . 'adrequest_click_impression_adjust_' . $yesterday . '.csv';
        if (file_exists($genFile) != true) {
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

    $app->get('/:date', function ($date) {
        require 'download.php';

        set_time_limit(0);

        //basedir
        $dic = '/adjust/';

        $dbopt = new DBoperator();
        $sql = $dbopt->getAdjust($date);
        //echo $sql;
        $records = $dbopt->getData($sql);

        try {
            try {
                //Export CSV
                $filename = "adrequest_click_impression_adjust_" . $date . ".csv";
                result($records, $dic, $filename);
            } catch (Exception $e) {
                //error_log($e->getMessage(), 3, '/var/tmp/phperror.log'); //Write error log
                echo '{"error":{"text":' . $e->getMessage() . '}}';
            }
        } catch (Exception $e) {
            echo '{"error":{"text":' . $e->getMessage() . '}}';
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
