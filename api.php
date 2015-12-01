<?php
    session_start();
    
    require "EmonLogger.php";
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
   
    require "PHPFina.php";
    $phpfina = new PHPFina();
   
    $query = $_GET['q'];
    $dirset = false;
    $dir = "";

    switch ($query)
    {
        case "scandir":
            $dirset = true;
            $dir = $_GET['dir'];
            $_SESSION['last_saved_dir'] = $dir;
            
            $scan = scandir($dir);
            $phpfina->dir = $dir;
            
            $feeds = array();
            foreach  ($scan as $item) {
                if (strpos($item,".dat")!==false) {
                    $parts = explode(".",$item);
                    $feedid = (int) $parts[0];
                    $timeval = $phpfina->lastvalue($feedid);
                    $feeds[] = array(
                        "feedid"=>$feedid, 
                        "lastvalue"=>$timeval["value"],
                        "size"=>$phpfina->get_feed_size($feedid)
                    );
                }
            }
            
            print json_encode($feeds);
            break;
            
        case "data":
            $dir = $_GET['dir'];
            $feedid = (int) $_GET['id'];
            $start = (int) $_GET['start'];
            $end = (int) $_GET['end'];
            $interval = (int) $_GET['interval'];
            
            $phpfina->dir = $dir;
            print json_encode($phpfina->get_data($feedid,$start,$end,$interval,1,1));            
            break;
    }
