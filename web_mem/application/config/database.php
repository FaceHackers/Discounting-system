<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('../web_conf/config_mem.inc');
$set = get_conf_mem(); /* get_base 設定檔 */

$active_group = 'act_evt';
$active_record = TRUE;

$db = get_db_conf_mem($set); /* 資料庫設定檔 */