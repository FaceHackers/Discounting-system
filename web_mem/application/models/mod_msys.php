<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('../web_share/libs/ext_mod/mod_ext'.EXT);
class mod_msys extends mod_ext {
	function __construct(){
		$this->dbw_str = 'act_evt';
		$this->dbr_str = 'act_evt';
		parent::__construct();
	}
	public function change_db($w,$r=null){
		$this->dbw_str = $w;
		$this->dbr_str = $r == null ? $w : $r;
		$this->db_con();
	}
}