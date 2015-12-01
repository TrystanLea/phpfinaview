<?php
    session_start();
    
    require "EmonLogger.php";
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
   
    $query = $_GET['q'];
    $dirset = false;
    $dir = "";

    switch ($query)
    {
        case "scandir":
            $dirset = true;
            $dir = $_GET['dir'];
            $_SESSION['last_saved_dir'] = $dir;
            print json_encode(scandir($dir));
            break;
            
        case "data":
            $dir = $_GET['dir'];
            $feedid = (int) $_GET['id'];
            $start = (int) $_GET['start'];
            $end = (int) $_GET['end'];
            $interval = (int) $_GET['interval'];
            
            require "PHPFina.php";
            $phpfina = new PHPFina(array("datadir"=>$dir));
            print json_encode($phpfina->get_data($feedid,$start,$end,$interval,1,1));            
            break;
    }
