<?php
include_once('config_base.inc');
function get_conf_mem(){
	$conf = get_base();
	//$conf["path_onldp"]="http://119.81.169.3:30000/";
	return $conf;
}
function get_db_conf_mem($set){
	$conf = get_db_base($set);
	return $conf;
}
?>