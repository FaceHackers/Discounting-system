<?php
class lib_chkinput{
	public $ci;
	function __construct(){
		$this->ci =& get_instance();
	}
	public function chk($cary,$sary){
		$rt=true;
		for($a=0;$a< count($cary);$a++){
			if(!isset($sary[$cary[$a]])){
				return false;
			}
		}
		return true;
	}
}