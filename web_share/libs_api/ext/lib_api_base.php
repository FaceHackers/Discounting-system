<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class lib_api_base{
	public $ci;
	public $gdata;
	public $obj;
	public $api_url = "http://251.bcad8.com/Zheng_api/";
	public $api_key = "e246f6e30f938667abc6fa35ab0a76de";
	//正式站
	/*
	public $api_url = "http://www.bcad8.com/Zheng_api/";
	public $api_key = "300cf374c5438ca72877d4d08525cda0";
	*/
	public function lib_api_base(){
		$this->ci =& get_instance();
		$this->ci->load->library("lib_codes");
		$this->gdata =& $this->ci->gdata;
		$this->obj =& $this->ci->obj;
	}
	public function output($data="json"){
		return $this->ci->output($data);
	}
}