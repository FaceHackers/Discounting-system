<?php
function get_base(){
	$conf = array();
	$conf['upl_dir'] = '/home/web_gs_pb/new-bcad8com/web_file/';
	$conf['is_test'] = 1;
	$dom = explode('.', $_SERVER['HTTP_HOST']);
	switch(count($dom)){
		case 2:
			$num = 0;
			break;
		case 3:
		case 4:
			$num = 1;
			break;
		default:
			$num = 0;
			break;
	}
	$now_dom = '';
	for($i = $num; $i < count($dom); $i++){
		$now_dom = $now_dom .'.'.$dom[$i];
	}

	$now_dom = substr($now_dom, 1, strlen($now_dom));

	$conf['web_file'] = 'http://files.'.$now_dom.'/';

	if(isset($_SERVER['HTTP_ORIGINALHOST'])){
		$conf['web_url'] = 'http://'.$_SERVER['HTTP_ORIGINALHOST'].'/';
	} else {
		$conf['web_url'] = 'http://'.$_SERVER['SERVER_NAME'].'/';
	}

	$conf['dbs'] = array();
	$conf['dbs']['dbw']['acc'] = 'Owen';
	$conf['dbs']['dbw']['pwd'] = 'ka171835';
	$conf['dbs']['dbw']['adr'] = '192.168.20.203';

	$conf['dbs']['dbr']['acc'] = 'Owen';
	$conf['dbs']['dbr']['pwd'] = 'ka171835';
	$conf['dbs']['dbr']['adr'] = '192.168.20.203';
	return $conf;
}
function get_db_base($set){
	$db = array();

	$db['act_evt']['hostname'] = $set['dbs']['dbw']['adr'];
	$db['act_evt']['username'] = $set['dbs']['dbw']['acc'];
	$db['act_evt']['password'] = $set['dbs']['dbw']['pwd'];
	$db['act_evt']['database'] = 'rd_owen';
	$db['act_evt']['dbdriver'] = 'mysql';
	$db['act_evt']['dbprefix'] = '';
	$db['act_evt']['pconnect'] = FALSE;
	$db['act_evt']['db_debug'] = TRUE;
	$db['act_evt']['cache_on'] = FALSE;
	$db['act_evt']['cachedir'] = '';
	$db['act_evt']['char_set'] = 'utf8';
	$db['act_evt']['dbcollat'] = 'utf8_general_ci';
	$db['act_evt']['swap_pre'] = '';
	$db['act_evt']['autoinit'] = TRUE;
	$db['act_evt']['stricton'] = FALSE;

	/* 擎磊正式站IP */
	$bck = '103.47.61.24';
	/* 優博(測試) */
	$db['ub']['hostname'] = '10.159.4.240';
	$db['ub']['username'] = 'w_bt';
	$db['ub']['password'] = '5%b&t';
	$db['ub']['database'] = 'bt';
	$db['ub']['dbdriver'] = 'mysql';
	$db['ub']['dbprefix'] = '';
	$db['ub']['pconnect'] = FALSE;
	$db['ub']['db_debug'] = TRUE;
	$db['ub']['cache_on'] = FALSE;
	$db['ub']['cachedir'] = '';
	$db['ub']['char_set'] = 'utf8';
	$db['ub']['dbcollat'] = 'utf8_general_ci';
	/* 優博(正式) */
	$db['ub_true'] = $db['ub'];
	$db['ub_true']['hostname'] = $bck;
	/* 金贊(測試) */
	$db['jz'] = $db['ub'];
	$db['jz']['database'] = 'jzplay';
	/* 金贊(正式) */
	$db['jz_true'] = $db['jz'];
	$db['jz_true']['hostname'] = $bck;
	/* 金沙會(測試) */
	$db['jsh'] = $db['ub'];
	$db['jsh']['database'] = 'dejin';
	/* 金沙會(正式) */
	$db['jsh_true'] = $db['jsh'];
	$db['jsh_true']['hostname'] = $bck;
	/* bt_api_res(測試) */
	$db['res'] = $db['ub'];
	$db['res']['database'] = 'bt_api_res';
	/* bt_api_res(正式) */
	$db['res_true'] = $db['res'];
	$db['res_true']['hostname'] = $bck;
	/* bt_api_raw(測試) */
	$db['raw'] = $db['ub'];
	$db['raw']['database'] = 'bt_api_raw';
	/* bt_api_raw(正式) */
	$db['raw_true'] = $db['raw'];
	$db['raw_true']['hostname'] = $bck;
	/** 活動查詢系統(測試) */
	$db['center'] = $db['ub'];
	$db['center']['database'] = 'act_center';
	/** 活動查詢系統(正式) */
	$db['center_true'] = $db['center'];
	$db['center_true']['hostname'] = $bck;
	return $db;
}
?>
