<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//require_once(APPPATH.'libraries/lib_sess'.EXT);
class lib_acc extends lib_sess{
	private $n_mod="mod_msys";
	private $n_tb="tb_acc_ctl";
	private $n_acc="acc";
	private $n_pwd="pwd";
	private $n_skey;
	public $pass;
	public $mod;
	public $libc;
	public $lib_ci;
	public $mid=-1;
	public $macc=-1;
	public $CheckLoginTime = 120;
	public function __construct(){
		parent::__construct();
		$this->ci->load->library("lib_codes");
		$this->libc = $this->ci->lib_codes;
		$this->ci->load->library("lib_chkinput");
		$this->lib_ci = $this->ci->lib_chkinput;
	}
	public function init($smod,$stb,$sacc,$spwd,$skey){
		$this->n_mod = $smod;
		$this->n_tb = $stb;
		$this->n_acc = $sacc;
		$this->n_pwd = $spwd;
		$this->n_skey = $skey;
		$this->ci->load->model($this->n_mod);
		$this->mod = $this->ci->{$this->n_mod};
	}
	public function kinfo($k=null){
		if($k!=null&&$k=="id"){
			if($this->mid!=-1){
				return $this->mid;
			}
		}
		if($k!=null&&$k=="acc"){
			if($this->macc!=-1){
				return $this->macc;
			}
		}
		$rt=array();
		for($a=0;$a< count($this->n_skey);$a++){
			$rt[$this->n_skey[$a]]=$this->info[$a];
		}
		if($k!=null){
			return $rt[$k];
		}
		return $rt;
	}
	public function login_int($info){
		$lc = $this->login_rec($info["id"]);
		$info["LoginID"] = $lc;
		$this->set($this->n_skey,$info);
	}
	public function login($acc,$pwd){
		$chk=$this->mod->get_by(
			$this->n_tb,
			array($this->n_acc=> $acc,$this->n_pwd=> $this->libc->aes_en($pwd))
		);
		if(count($chk)> 0){
			$chk=$chk[0];
			$lc=$this->login_rec($chk["id"]);
			$chk["lid"]=$lc;
			$this->set($this->n_skey,$chk);
			return true;
		}else{
			return false;
		}
	}
	/*登入記錄*/
	public function login_rec($id){
		$lc=$this->mod->add_by("tb_acc_login",array(
				"tb"=> $this->n_tb,
				"tb_id"=> $id,
				"ip"=> $this->GetUserIP(),
				"ipx"=> @$_SERVER["HTTP_X_FORWARDED_FOR"],
				"agent"=>$_SERVER["HTTP_USER_AGENT"],
				"itime"=> date("Y-m-d H:i:s"),
				"chktime"=> date("Y-m-d H:i:s")
		));
		$this->SetCookie("loginID",$lc["lid"],$this->CheckLoginTime);
		return $lc["lid"];
	}
	public function logoff(){
		$this->mod->modi_by_id("tb_acc_login",$this->kinfo("lid"),array("logout"=> 1));
		$this->logout();
	}
	public function chklog(){
		$cur=$this->ci->router->fetch_method();
		if(array_key_exists($cur,$this->pass)){
			return 1;
		}
		return $this->chk();
	}
	public function chkipt($cary,$sary){
		return $this->lib_ci->chk($cary,$sary); // lib_chkinput->chk()
	}
	
	public function GetUserIP(){
		if(!empty($_SERVER['HTTP_CLIENT_IP'])){
		   $myip = $_SERVER['HTTP_CLIENT_IP'];
		}else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		   $myip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
		   $myip = $_SERVER['REMOTE_ADDR'];
		}
		return $myip;
	}
}