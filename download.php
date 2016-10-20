<?php
/**
 * Created by PhpStorm.
 * User: kwokkakit
 * Date: 1/28/16
 * Time: 6:08 PM
 */

function result($results, $dic = NULL, $name = NULL)
{
  if(!$name){
    $name = md5(uniqid() . microtime(TRUE) . mt_rand()). '.csv';
  }

  if($dic == NULL){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename='. $name);
    header('Pragma: no-cache');
    header("Expires: 0");

    $outstream = fopen("php://output", "w");
  }else{
    $outstream = fopen(dirname(__FILE__) . $dic . $name, "a");
  }

  $new_line = "\r\n";
  $column_names = array_keys($results[0]);
  $column_names[] = $new_line;

  fputcsv($outstream, $column_names);
  foreach($results as $result){
    fputcsv($outstream, $result);
  }

  fclose($outstream);
}

function fileList($dir, $dic, $del_flag = 0)
{
  //file list
  echo "<h1>File List</h1>";
  if(is_dir($dir)){
    if($dh = opendir($dir)){
      while(($file = readdir($dh)) != false){
        if($file == '.' || $file == '..'){
          continue;
        }else{
          $filePath = $dir.$file;
          $fmt = filemtime($filePath);
          $text = "<span style='color:#666'>(".date("Y-m-d H:i:s",$fmt).")</span>&#9 ".$file;
          if(filesize($filePath)>0){
            $text .= "&#9<a href=http://127.0.0.1:8803". $dic . $file . ">DownLoad</a>";
          }else{
            $text .= "&#9<b>Loading...</b>";
          }
          
          //mediapublisher
          if($del_flag == 1){
            $text .= "<a href=http://127.0.0.1:8803/index.php/mediapublisher/daily/delete>Delete</a></br>";
          }elseif($del_flag == 2){ 
            $text .= "&#9<a href=http://127.0.0.1:8803/index.php/dynamicsource/delete/". $file .">Delete</a></br>";
          }elseif($del_flag == 3){
            $text .= "&#9<a href=http://127.0.0.1:8803/index.php/staticsource/delete/". $file .">Delete</a></br>";
          }

          echo $text;
        }
      }closedir($dh);
    }
  }
}
