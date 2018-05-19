<?php
class lib_global{
	var $ci;
	var $per;
	var $invisi="style='display:none'";
	var $timeout=600;
	public $mod;
	function __construct(){
		$this->ci =& get_instance();
		$this->ci->load->library("parser");
	}
	function load_mod($mod){
		$this->ci->load->model($mod);
		return $this->ci-> $mod;
	}
	function curPageURL() {
		$pageURL = 'http';
		//if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		}else{
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		if(count($_REQUEST)> 0){
			$pageURL.="?";
			$parms=array();
			foreach($_REQUEST AS $k => $v){
				if($v!=""){
					$parms[]=$k."=".$v;
				}
			}
			$pageURL.=implode($parms,"&");
		}
		return $pageURL;
	}
	function make_page($ref,$cpage,$total,$getstr="",$isdir=true,$burl=""){
			$rt="";
			$per_page=$this->per;//$this->ci->config->item('per_page');
			if($total > $per_page){
				if($isdir==true){
					$ref.="/";
				}
				$page_data=array();
				if($burl!=""){
					$page_data["burl"]=$burl;
				}
				$page_data["fst"]=$ref."1".$getstr;
				$page_data["next"]=$ref.($cpage+1).$getstr;
				$page_data["prev"]=$ref.($cpage-1).$getstr;
				$page_list=array();
				$total_page=$total/$per_page;
				$total_page=(int)$total_page;
				
				if($total%$per_page!=0){
					$total_page++;
				}
				
				
				if($cpage==1){
					$page_data["fst_class"]="style='display:none'";
				}
				if($cpage==$total_page){
					$page_data["lst_class"]="style='display:none'";
				}
				$page_data["lst"]=$ref.$total_page.$getstr;//$total_page;
				for($a=1;$a< $total_page+1;$a++){
					$page_list[$a]["p_ref"]=$ref.$a.$getstr;
					$page_list[$a]["p_num"]=$a;
					if($a!=$cpage){
						$page_list[$a]["p_class"]="pagenumber";
						$page_list[$a]["html"]='<a href="'.$ref.$a.$getstr.'">'.$a.'</a>';
					}else{
						$page_list[$a]["p_class"]="currentnumber";
						$page_list[$a]["html"]='<span>'.$a.'</span>';
					}
					
				}
				$oder_page=array();
				for($a=0;$a< 6;$a++){
					if(array_key_exists($cpage-$a,$page_list)){
						$oder_page[]=$page_list[$cpage-$a];
					}
				}
				
				$oder_page=array_reverse($oder_page);
				for($a=1;$a< 6;$a++){
					if(array_key_exists($cpage+$a,$page_list)){
						$oder_page[]=$page_list[$cpage+$a];
					}
				}
				$page_data["page_list"]=$oder_page;
				$rt=$this->ci->parser->parse("global/pages.html",$page_data,true);
			}
			return $rt;
	}
	function str_rep($str,$limit){
		$str=strip_tags($str);
		$str=preg_replace('#\s+#', ' ', trim($str));
		if(mb_strlen($str)> $limit){
			return mb_substr($str,0,$limit,'UTF-8')."...";
		}else{
			return $str;
		}
	}
	function upload($vars,$path='assets/ul/product'){
		$rt;
		$config['upload_path'] =$path;
		$config['allowed_types'] = 'gif|jpg|png';
		$config['encrypt_name']=true;
		$this->ci->load->library('upload', $config);
		if (!$this->ci->upload->do_upload($vars)){
			$rt = array('error' => $this->ci->upload->display_errors());
			$rt["ul_ok"]="false";
		}else{
			$raw = array('upload_data' => $this->ci->upload->data());
			$rt=$raw["upload_data"];
			$rt["ul_ok"]="true";
		}
		return $rt;
	}
	function pic_client_path(){
		return "assets/ul/product/";
	}
	function pic_evt_path(){
		return "assets/ul/evt/";
	}
	function get_user_ip(){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
    	$ip=$_SERVER['HTTP_CLIENT_IP'];
    }else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
    	$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
    	$ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }
  function time_out_js(){
  	echo "<script>alert('連線逾時,您已被登出!');top.location.href='".$this->ci->burl."admin"."';</script>";
  	exit();
  }
  function log_out_js(){
  	echo "<script>alert('已將您登出,請按確定回首頁!');top.location.href='".$this->ci->burl."adm2"."';</script>";
  	exit();
  }
  function str_indexof($string,$find,$start=0){
    $index=strpos($string,$find,$start);
    if(gettype($index)!="integer")    $index=-1;
  	return $index;
  }
  function date_USEast($str,$ymd=false){
  	//$str=date("Y-m-d H:i:s",strtotime($str)+60*60);
  	$UTC = new DateTimeZone('Asia/Taipei');
		$newTZ = new DateTimeZone("America/New_York");
		$date = new DateTime($str, $UTC );
		$date->setTimezone( $newTZ );
		//echo $date->format('Y-m-d H:i:s');
		if($ymd){
			return $date->format('Y-m-d');
		}else{
			return $date->format('Y-m-d H:i:s');
		}
  }
  function date_Ac($str,$ymd=false){
		$str=date("Y-m-d H:i:s",strtotime($str)+(60*60));
  	$UTC = new DateTimeZone("America/New_York");
		$newTZ = new DateTimeZone('Asia/Taipei');
		$date = new DateTime($str, $UTC );
		$date->setTimezone( $newTZ );
		//echo $date->format('Y-m-d H:i:s');
		if($ymd){
			return $date->format('Y-m-d');
		}else{
			return $date->format('Y-m-d H:i:s');
		}
  }
   public function conv_to_key($ary,$key){ 
		$nary=array();
		for($a=0;$a< count($ary);$a++){
			$nary[$ary[$a][$key]]=$ary[$a];
		}
		return $nary;
	}
}