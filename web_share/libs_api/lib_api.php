<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('ext/lib_api_base'.EXT);
class lib_api extends lib_api_base{
	public $api=null;
	public $parm;
	public function lib_api(){
		parent::__construct();
	}
	/*將員工資料存到SESSION*/
	public function GetAllStaffToSession(){
		if(!isset($_SESSION)){
			@session_start();
		}
		if(isset($_SESSION["BCK"]["staffMapping"])){
			session_write_close();
			return $_SESSION["BCK"]["staffMapping"];
		}else{
			$info = $this->SelectStaffAll();
			$Satff = array();
			foreach($info as $k => $v){
				$Satff[$v["Act"]] = $v;
			}
			$_SESSION["BCK"]["staffMapping"] = $Satff;
			session_write_close();
			return $Satff;
		}
	}
	/*查詢全部部門*/
	public function SelectDepartment($Enable = null){
		$path = $this->api_url."staff/SelectDepartment/";
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			$info = array();
			foreach($r["info"] as $k => $v){
				if($Enable!=null){
					if($Enable){
						if($v["ME010"]==0){
							continue;
						}
					}else{
						if($v["ME010"]==1){
							continue;
						}
					}
				}
				$Inf["code"] = $v["ME001"];
				$Inf["Name"] = $v["ME002"];
				$Inf["Enable"] = $v["ME010"];
				$info[] = $Inf;
			}
			return $info;
		}else{
			return false;
		}
	}
	/*全部員工資料*/
	public function SelectStaffAll(){
		$path = $this->api_url."staff/SelectStaffAll/";
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			$info = array();
			foreach($r["info"] as $k => $v){
				$Inf["id"] = $v["D_ID"];
				$Inf["Act"] = $v["MV001"];
				$Inf["Name"] = $v["MV002"];
				$Inf["NickName"] = $v["MV046"];
				$Inf["Dep"] = $v["MV004"];		//部門
				$Inf["level"] = $v["MV005"];  	//層級
				$Inf["Slevel"] = $v["MV006"];	//子級
				$Inf["DirectorLevelID"] = $v["DirectorLevelID"];	//部門層級
				$Inf["DirectorGroup"] = $v["DirectorGroup"];		//0 = 一般,1 = 交接選取人使用(中階主管以上及特別人物)
				$Inf["GroupID"] = $v["GroupID"];					//部門組別
				$info[] = $Inf;
			}
			return $info;
		}else{
			return false;
		}
	}
	/*在職員工資料*/
	public function SelectStaffForWork(){
		$path = $this->api_url."staff/SelectStaffForWork/";
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			$info = array();
			foreach($r["info"] as $k => $v){
				$Inf["id"] = $v["D_ID"];
				$Inf["Act"] = $v["MV001"];
				$Inf["Name"] = $v["MV002"];
				$Inf["NickName"] = $v["MV046"];
				$Inf["Dep"] = $v["MV004"];		//部門
				$Inf["level"] = $v["MV005"];  	//層級
				$Inf["Slevel"] = $v["MV006"];	//子級
				$Inf["DirectorLevelID"] = $v["DirectorLevelID"];	//部門層級
				$Inf["DirectorGroup"] = $v["DirectorGroup"];		//0 = 一般,1 = 交接選取人使用(中階主管以上及特別人物)
				$Inf["GroupID"] = $v["GroupID"];					//部門組別
				$info[] = $Inf;
			}
			return $info;
		}else{
			return false;
		}
	}
	/*交接-在職員工資料*/
	public function SelectStaffForHandover(){
		$path = $this->api_url."staff/SelectStaffForHandover/";
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			$info = array();
			$info = $r["info"];
			return $info;
		}else{
			return false;
		}
	}
	/*查詢員工資料(by Act)*/
	public function SelectStaffByAct($Act){
		$path = $this->api_url."staff/SelectStaffByAct/$Act/";
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			$info = array();
			$info["id"] = $r["info"]["D_ID"];
			$info["Act"] = $r["info"]["MV001"];
			$info["Name"] = $r["info"]["MV002"];
			$info["Dept"] = $r["info"]["MV004"];
			$info["NickName"] = $r["info"]["MV046"];
			$info["Psw_md5"] = $r["info"]["MV085"];
			$info["Psw_aes"] = $r["info"]["MV086"];
			$info["Psw_next_update_time"] = $r["info"]["CREATE_DATE"];
			return $info;
		}else{
			return false;
		}
	}
	/*修改會員密碼*/
	public function UpdateStaffForPwd($Act,$Pwd){
		$path = $this->api_url."staff/UpdateStaffForPwd/$Act/$Pwd/";
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			return $r['code'];
		}else{
			return false;
		}
	}
	/*修改會員密碼*/
	public function JumpStaffForUrl($Act){
		$path = $this->api_url."staff/JumpStaffForUrl/$Act/";
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			return $r;
		}else{
			return false;
		}
	}
	/*登入會員url*/
	public function StaffLoginToOldUrlKey(){
		$inf["url"] = $this->api_url;
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$inf["key"] = $token;
		return $inf;
	}
	/*取得打卡記錄*/
	public function ByActForCommuting($Act,$sdate,$edate){
		$sdate = date("Ymd",strtotime($sdate));
		$edate = date("Ymd",strtotime($edate));
		$path = $this->api_url."staff/ByActForCommuting/$Act/$sdate/$edate/";
		
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		//print_r($r);
		if(isset($r['code']) && $r['code'] == '100'){
			$info = $r["info"];
			foreach($info["off"] as $k => $v){
				$Inf["id"] = $v["D_ID"];
				$Inf["Act"] = $v["MC001"];
				$Inf["Date"] = $v["MC002"];
				$Inf["Time"] = date("H:i",strtotime($v["MC003"]));
				$Inf["Type"] = $v["MC011"];
				$Inf["IP"] = $v["MC012"];
				$info["off"][$k] = $Inf;
			}
			foreach($info["on"] as $k => $v){
				$Inf["id"] = $v["D_ID"];
				$Inf["Act"] = $v["MC001"];
				$Inf["Date"] = $v["MC002"];
				$Inf["Time"] = date("H:i",strtotime($v["MC003"]));
				$Inf["Type"] = $v["MC011"];
				$Inf["IP"] = $v["MC012"];
				$info["on"][$k] = $Inf;
			}
			return $info;
		}else{
			return false;
		}
	}
	/*取得休假表*/
	public function ByActForVacation($Act,$sdate){
		$sdate = date("Ymd",strtotime($sdate));
		$path = $this->api_url."staff/ByActForVacation/$Act/$sdate/";
		
		$key = $this->api_key;
		$token = $this->ci->lib_codes->aes_en(strtotime(Date("YmdHis")),$key);
		$parm["token"] = $token;
		$post = curl_init($path);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($post, CURLOPT_SSL_VERIFYHOST, 2); 
		curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($post, CURLOPT_POSTFIELDS,$parm);
		$resp = curl_exec($post);
		$de = $this->ci->lib_codes->aes_de($resp,$key);
		$de = trim($de);
		$r = json_decode($de,true);
		if(isset($r['code']) && $r['code'] == '100'){
			$info = $r["info"];
			$s = date('Y-m-01',strtotime($sdate));
			$e = date('Y-m-t',strtotime($sdate));
			$Commuting = $this->ByActForCommuting($Act,$s,$e);
			foreach($Commuting["on"] as $k => $v){
				$d = date('Ymd',strtotime($v["Date"]));
				$info[$d]["on"] = $v["Time"];
			}
			foreach($Commuting["off"] as $k => $v){
				$d = date('Ymd',strtotime($v["Date"]));
				if($info[$d]["code"]=="002"){
					$d = date('Ymd',strtotime($v["Date"] . " -1 day"));
				}
				$info[$d]["off"] = $v["Time"];
			}
			$late = 0;
			$late_num = 0;
			$early = 0;
			$early_num = 0;
			$ask_for_leave = 0;
			foreach($info as $k => $v){
				$Status = '';
				if(isset($v["code"])){
					if($v["code"]=="100"){
						$Status = $v["str"];
						$ask_for_leave++;
					}else{
						if(isset($v["on"]) || isset($v["off"])){
							if(isset($v["on"])){
								$Status .= $v["on"];
							}
							$Status .= "~";
							if(isset($v["off"])){
								$Status .= $v["off"];
							}
						}else{
							if($v["code"]=="000" || $v["code"]=="777" || $v["code"]=="888" || $v["code"]=="999" ){
								$Status = $v["str"];
							}else{
								if(strtotime($k) < strtotime(date("Ymd")) ){
									$info[$k]["code"] = "222";
									$Status = "未請假";
								}else{
									$Status = "-";
								}
							}
						}
						if($v["code"]!="000" && $v["code"]!="777" && $v["code"]!="888" && $v["code"]!="999" && $v["code"]!="100"){
							if(isset($v["on"]) && isset($v["off"])){
								$on = str_replace (":","",$v["on"]);
								if(($on - $v["on_time"]) > 0){
									$late += (strtotime($sdate." ".$on) - strtotime($sdate." ".$v["on_time"]))/ (60);//$on - $v["on_time"];
									$late_num++;
								}
								$off = str_replace (":","",$v["off"]);
								if(($v["off_time"] - $off) > 0){
									$early += (strtotime($sdate." ".$v["off_time"]) - strtotime($sdate." ".$off))/ (60);//$v["off_time"] - $off;
									$early_num++;
								}
							}
						}
					}
				}
				$info[$k]["Status"] = $Status;
			}
			$re_info["info"] = $info;
			$re_info["late"] = $late;
			$re_info["late_num"] = $late_num;
			$re_info["early"] = $early;
			$re_info["early_num"] = $early_num;
			$re_info["ask_for_leave"] = $ask_for_leave;
			return $re_info;
		}else{
			return false;
		}
	}
}