<?php
// By JamesL 20170508 version 1.0.0
session_start();

require_once("debug_util.php");
require_once("db.php");
require("platform.php");

function get_serv( $info )
{
	return $info[0];
}

function get_port( $info )
{
	return $info[1];
}

function get_remoteip( $info )
{
	return $info[2];
}

function get_idx( $info )
{
	return $info[3];
}

function get_version( $info )
{
	return $info[4];
}

function get_fp( $info )
{
	return $info[5];
}

function get_session( $info )
{
	return $info[6];
}

function get_timestamp( $info )
{
	return $info[7];
}

function get_source( $info )
{
	return $info[8];
}

function get_show( $info )
{
	return $info[9];
}




function check_perseus_passwd ( $mdb, $info)
{
	$check_sn_str = sprintf("SELECT * FROM `perseus_version` WHERE `user`=\"%s\" ",get_version($info));
	logd("sql: $check_sn_str");
	$check_sn_stat = mysqli_query($mdb, $check_sn_str);
	$row = mysqli_fetch_array($check_sn_stat);

	// decrypt to md5
	$decrypt_passwd_str = sprintf("/home/james/Environment/web_base/bin/enc md5 %s", $row['passwd']);
	exec($decrypt_passwd_str, $output);
	$passwd_from_db=sprintf("%s", $output[0]);
	logd("exec..... $passwd_from_db");
	logd("get user from db: " . $row['user'] . " passwd: " . $passwd_from_db);
	logd("get user from get: " . get_version($info). " passwd: " . get_fp($info));

	if( strcmp($row['user'], get_version($info)) == 0 && strcmp($passwd_from_db, get_fp($info)) == 0)
	{
		logd("----------------------------Insert info into DB------------------------------------------");
		$encrypt_ret_str = sprintf("/home/james/Environment/web_base/bin/enc common_enc zok_%s",get_version($info));
		exec($encrypt_ret_str, $output_ret);
		$ret_str=sprintf("%s", $output_ret[0]);
		logd("ret: $ret_str");
		logd("time: " .  $_SERVER['REQUEST_TIME']);
		print("{\"code\":\"100\",\"msg\":\"ok\",\"data\":{\"url\":\"\",\"ret\":\"$ret_str\",\"timestamp\":\"".$_SERVER['REQUEST_TIME']."\"}}");
		logd("----------------------------End of DB action------------------------------------------");
	} else {
		print("{\"code\":\"200\",\"msg\":\"fail\",\"data\":{\"url\":\"\",\"ret\":\"$ret_str\",\"timestamp\":\"".$_SERVER['REQUEST_TIME']."\"}}");
	}
}



function update_server_main( $info)
{
	/* tranform ip to dec */
	// $get_remoteip_dec = $get_remoteip[3] + $get_remoteip[2]*256 + $get_remoteip[1]*256*256 + $get_remoteip[0] *256*256*256;
	$current_tm = date('H:i:s');

	logd("----------------------------Basic Info Start------------------------------------------");
	logd("serv      : " . get_serv($info) . " :-port- : ".get_port($info). " : -rtip- : ".get_remoteip($info));
	logd("idx       : " . get_idx($info       )) ;
	logd("user      : " . get_version($info      )) ;
	logd("passwd    : " . get_fp($info    )) ;
	logd("session   : " . get_session($info   )) ;
	logd("timestamp : " . get_timestamp($info )) ;
	logd("time      : $current_tm"            ) ;
	logd("----------------------------Basic Info End------------------------------------------");

	logd("----------------------------Connect to DB------------------------------------------");
	$db_server = get_db_server();
	$db_user = get_db_user();
	$db_pwd = get_db_pwd();

	logd("DB server : $db_server");
	$mdb = connect_to_mysqli_server($db_server,$db_user,$db_pwd);
	select_database('perseus',$mdb);

	$check_idx_str  = sprintf("SELECT max(idx) FROM `perseus_version` WHERE 1");
	$check_idx_stat = mysqli_query($mdb, $check_idx_str);
	$idx_ret = mysqli_fetch_array($check_idx_stat);

	logd("idx max: " . $idx_ret['max(idx)']);
	$check_sn_str = sprintf("SELECT * FROM `perseus_version` WHERE idx=%s", $idx_ret['max(idx)']);
	logd("sql: $check_sn_str");
	$check_sn_stat = mysqli_query($mdb, $check_sn_str);
	$row = mysqli_fetch_array($check_sn_stat);
	// if (strlen($row['version']) == 0)
	//     logs("null");
	// else
		logs("{\"index\":\"". $row['idx'] . "\",\"version\":\"" . $row['version'] . "\",\"fp\":\"" . $row['fp'] . "\",\"timestamp\":\"" . $row['timestamp'] . "\",\"session\":\"" . $row['session'] . "\",\"source\":\"". $row['source'] . "\"}");

	logd("----------------------------Connect End------------------------------------------");
	// // logd("----------------------------Disconnect to DB------------------------------------------");
	// disconnect_from_mysqli_server($mdb);
}

function get_value($value)
{
	if (isset($_GET[$value])) {
		$get_value = $_GET[$value];
		return $get_value;
	} elseif (isset($_POST[$value])) {
		$get_value = $_POST[$value];
		return $get_value;
	} else {
		return "";
	}
}


/* Main */

$get_serv = $_SERVER['HTTP_HOST'];
$get_port = $_SERVER["SERVER_PORT"];
$get_remoteip = $_SERVER["REMOTE_ADDR"];

// $get_version = $_GET['idx'];
$get_idx        = get_value("idx");
$get_version    = get_value("ver");
$get_fp         = get_value("fp");
$get_session    = get_value("session");
$get_timestamp  = get_value("timestamp");
$get_source     = get_value("source");

$get_show = get_value("show");

date_default_timezone_set('Asia/Shanghai');
// $current_dt = date('Y-m-d');
// $current_tm = date('H:i:s');


$base_info = array($get_serv, $get_port, $get_remoteip, $get_idx, $get_version, $get_fp, $get_session, $get_timestamp, $get_source, $get_show );

$main_ret=update_server_main( $base_info );
if($main_ret == -1)
{
	print("{\"code\":\"500\",\"msg\":\"Your version is incorrect!\",\"data\":{\"url\":\"\",\"md5\":\"\",\"length\":\"\",\"version\":\"\"}}");
}
// update_server_main( $base_info );

?>


