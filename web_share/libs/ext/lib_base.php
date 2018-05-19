<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class lib_base{
	public $ci;
	public $gdata;
	public $obj;
	public $mod;
	public function lib_base(){
		$this->ci =& get_instance();
		$this->ci->load->library("lib_codes");
		$this->gdata=&$this->ci->gdata;
		$this->obj=&$this->ci->obj;
		$this->mod=$this->ci->get_mod("mod_msys","",true);
	}
	public function output($data="json"){
		return $this->ci->output($data);
	}
	public function chk_rep($tb,$key,$val){
		$chk=$this->ci->mod->get_by($tb,array($key=> $val));
		if(count($chk)==0){
			return true;
		}else{
			return false;
		}
	}
	public function key_obj($data,$key){
		$raw=$this->ci->mod->conv_to_key($data,$key);
		return json_encode($raw);
	}
	public function get_view($file,$rt=false){
		$cls=get_called_class();
		if($rt){
			return $this->ci->parser->parse("ctl/".$cls."/".$this->ci->gdata["akey"]."/".$file.".html",$this->ci->gdata,true);
		}else{
			$this->ci->parser->parse("ctl/".$cls."/".$this->ci->gdata["akey"]."/".$file.".html",$this->ci->gdata);
		}
	}
}