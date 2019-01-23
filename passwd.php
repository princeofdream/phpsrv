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

function get_user( $info )
{
	return $info[4];
}

function get_passwd( $info )
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


function show_data($mdb)
{
	$display = 1;

	/* show data */
	$sql = "SELECT * FROM `perseus_tire_base`";
	// $sql = "SELECT * FROM `fota_machine`";
	$result = mysqli_query($mdb, $sql);

	if($display === 1)
	{
		logs( "<table border='1'>");
	}
	$ret = "NULL";
	while($row = mysqli_fetch_array($result))
	{
		if($display === 1)
		{
			logs("<tr><td>". $row['idx'] . " </td><td>" . $row['sn'] . " </td><td>" . $row['vendor'] . " </td><td>" . $row['area'] . " </td><td>" . $row['timestamp'] . " </td><td>" . $row['size'].'</td></tr>');
		}
	}

	// mysqli_close($mdb);
	return $ret;
}




function update_perseus_base ( $mdb, $info)
{
	$check_sn_str = sprintf("SELECT * FROM `perseus_tire_base` WHERE `user`=\"%s\" ",get_user($info));
	logd("sql: $check_sn_str");
	$check_sn_stat = mysqli_query($mdb, $check_sn_str);
	// logd( $check_sn_stat);
	// if($check_sn_stat) {
		$row = mysqli_fetch_array($check_sn_stat);
		logd("get value: " . $row['user']);
	// }
	if( strlen($row['sn']) == 0 )
	{
		logd("----------------------------Insert info into DB------------------------------------------");
		$ret = perseus_db_insert($mdb,get_user($info),get_passwd($info),get_session($info), get_timestamp($info));
		logd("----------------------------Read Info from DB, pre stat: $ret------------------------------------------");
		$ret = show_data($mdb);
		logd("----------------------------End of DB action------------------------------------------");
	}
	else
	{
		logd("----------------------------Update DB info------------------------------------------");
		$sql = sprintf(" UPDATE `perseus_tire_base` SET `idx`=\"%d\",`user`=\"%s\", `passwd`=\"%s\", `session`=\"%s\", `timestamp` = now(), source=\"%s\" WHERE `user`=\"%s\" ",
			get_idx(idx),get_user($info), get_passwd($info),get_session($info),get_source($info), get_user($info));
		mysqli_query($mdb, $sql);
		logd("----------------------------Read Info from DB------------------------------------------");
		$ret = show_data($mdb);
		logd("----------------------------End of DB action------------------------------------------");
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
	logd("user      : " . get_user($info      )) ;
	logd("passwd    : " . get_passwd($info    )) ;
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

	if ( strcmp( get_show($info), "1") == 0 ) {
		$check_sn_str = sprintf("SELECT * FROM `perseus_users` WHERE `user`=\"%s\" ",$get_passwd);
		logd("sql: $check_sn_str");
		$check_sn_stat = mysqli_query($mdb, $check_sn_str);
		$row = mysqli_fetch_array($check_sn_stat);
		if (strlen($row['user']) == 0)
			logs("null");
		else
			logs("{\"index\":\"". $row['idx'] . "\",\"user\":\"" . $row['user'] . "\",\"passwd\":\"" . $row['passwd'] . "\",\"timestamp\":\"" . $row['timestamp'] . "\",\"session\":\"" . $row['session'] . "\",\"source\":\"". $row['source'] . "\"}");
	// } else {
	//     update_perseus_base ( $mdb, $info);
	}

	logd("----------------------------Connect End------------------------------------------");
	// logd("----------------------------Disconnect to DB------------------------------------------");
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

// $get_user   = $_GET['idx'];
$get_idx       = get_value("idx");
$get_user      = get_value("user");
$get_passwd    = get_value("passwd");
$get_session   = get_value("session");
$get_timestamp = get_value("timestamp");
$get_source    = get_value("source");

$get_show = get_value("show");

date_default_timezone_set('Asia/Shanghai');
// $current_dt = date('Y-m-d');
// $current_tm = date('H:i:s');


$base_info = array($get_serv, $get_port, $get_remoteip, $get_idx, $get_user, $get_passwd, $get_session, $get_timestamp, $get_source, $get_show );

$main_ret=update_server_main( $base_info );
if($main_ret == -1)
{
	print("{\"code\":\"500\",\"msg\":\"Your version is incorrect!\",\"data\":{\"url\":\"\",\"md5\":\"\",\"length\":\"\",\"version\":\"\"}}");
}
// update_server_main( $base_info );

?>


