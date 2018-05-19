<?php
class lib_codes{
	var $ci;
	var $key="kingsuede864153";
	var $min=25;
	function __construct(){
		$this->ci =& get_instance();
	}
	public function aes_en($str,$keys="kingsuede864153",$iv="8105547186756005",$cipher_alg=MCRYPT_RIJNDAEL_128){
		$encrypted_string = bin2hex(mcrypt_encrypt($cipher_alg, $keys, $str, MCRYPT_MODE_CBC,$iv));
		return $encrypted_string;
	}
	public function aes_de($str,$keys="kingsuede864153",$iv="8105547186756005",$cipher_alg=MCRYPT_RIJNDAEL_128){
		error_reporting(0);
		$res="";
		try{
			$decrypted_string = mcrypt_decrypt($cipher_alg, $keys, pack("H*",$str),MCRYPT_MODE_CBC, $iv);
		}catch(Exception $e){
		}
     $res=$decrypted_string;
    error_reporting(E_ALL);
    $res=urlencode($res);
    $res=str_replace("%00","",$res);
    $res=urldecode($res);
		return $res;
	}
}