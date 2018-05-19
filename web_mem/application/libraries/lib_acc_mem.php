<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class lib_acc_mem extends lib_acc{
	private $tb="api_ERP_CMSMV";
	private $acc=null;
	public function __construct(){
		parent::__construct();
		$this->init("mod_msys",$this->tb,"acc","pwd",array(
			"id","Act","NickName","Psw_next_update_time","Dept","OutSide","LoginID"
		));
	}
	public function get_info_byid($id,$force=false){
		if($this->acc!=null&&$force==false){
			return $this->acc;
		}
		$acc=$this->mod->get_by($this->tb,array("id"=> $id));
		$this->acc=$acc[0];
		return $this->acc;
	}
}