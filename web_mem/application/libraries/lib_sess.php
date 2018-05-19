<?php
class lib_sess{
	public $ci;
	public $time;
	public $pass=array();
	public $info;
	public $libc;
	public $skey;
	public function __construct(){
		$this->ci =& get_instance();
		$this->ci->load->helper('cookie');
		$this->libc=$this->ci->get_lib("lib_codes");
		$this->time=60*60*4;
	}
	public function set($set,$parms){
		foreach($set as $k => $v){
			$this->info[]= $parms[$set[$k]];
		}
		$this->set_sess();
	}
	public function chk(){
		$sess=get_cookie($this->skey);	
		if($sess==""){return false;}	
		$chk=$this->libc->aes_de($sess);
		$chk=explode("*",$chk);	
		if($chk < 1){return false;}
		$now=strtotime(DATE("YmdHis"));
		if($now-$chk[0] > $this->time){ return false;}
		array_shift($chk);
		$this->info=$chk;
		$this->set_sess();
		return true; 
	}
	public function logout(){
		delete_cookie($this->skey);
	}
	private function set_sess(){
		$code = $this->libc->aes_en(strtotime(DATE("YmdHis"))."*".implode("*",$this->info));
		$cookie = array('name'=> $this->skey,'value'=> $code,'expire' =>(60*60*10));
		set_cookie($cookie);
	}
	public function SetCookie($k,$v,$time = 32400){
		$cookie = array('name'=> $k,'value'=> $v,'expire' => $time);
		set_cookie($cookie);
	}
	public function push($k,$v){
		$cookie = array('name'=> $this->skey."_".$k,'value'=> $v,'expire' =>(60*60*10));
		set_cookie($cookie);
	}
	public function pull($k,$d=false){
		if($d){
			$sess = get_cookie($k);
		}else{
			$sess = get_cookie($this->skey."_".$k);
		}
		return $sess;
	}
	public function destory(){
		$past = time() - 3600;
		foreach ( $_COOKIE as $k => $v ){
			if($k!="Language"){
				setcookie( $k, $v, $past, '/' );
			}
		}
	}
}