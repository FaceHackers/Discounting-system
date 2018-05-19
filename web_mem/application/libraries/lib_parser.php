<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class lib_parser extends lib_sess{
	public $ci;
	function __construct(){
		$this->ci =& get_instance();
	}
	public function GetView($view=null,$data=array(),$return = false){
		$lang = $this->GetLang();
		if($view==null) {return;}
		return $this->ci->parser->parse($lang ."/".$view,$data,$return);
	}
}