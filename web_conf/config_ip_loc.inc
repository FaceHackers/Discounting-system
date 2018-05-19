<?php
/**
 * 阻擋地區IP與白名單
 * 
 * 地區黑名單 (判斷 $_SERVER["GEOIP_COUNTRY_CODE"])
 * ip_loc/loc.inc
 *
 * 域名白名單，略過檢查的域名:
 * ip_loc/domain.inc  
 * 
 * IP白名單  
 * ip_loc/ip.inc         
 * ip_loc/ip_subnet.inc
 * 
 * 要啟用時，config_ip_loc.inc 變數 enable 設為 1
 * 
 */
class ip_loc{
    public static $enable=1;
    /** 檢查是否符合域名白名單 */
    public static function dom_bypass(){
        $str=file_get_contents('../web_conf/ip_loc/domain.inc');
        //$pass=explode("\n",$str);
        $pass=preg_split('/\R/', $str);
        for($a=0;$a< count($pass);$a++){
            if($pass[$a]==""){continue;}
            if(strpos($_SERVER["SERVER_NAME"],$pass[$a])!==false){
                return true;
            }
        }
        return false;
    }
    /** 檢查是否通過地區黑名單 */
    public static function loc_chk(){
        $str=file_get_contents('../web_conf/ip_loc/loc.inc');
        $ary=preg_split('/\R/', $str);$ary=array_flip($ary);
        /// 黑名單
        if(isset($_SERVER['GEOIP_COUNTRY_CODE']) && isset($ary[$_SERVER["GEOIP_COUNTRY_CODE"]])){
            return false;
        }
        return true;
        /// 白名單
        /*if(isset($ary[$_SERVER["GEOIP_COUNTRY_CODE"]])){
            return true;
        }
        return false;*/
    }
    /** 檢查是否符合IP白名單  */
    public static function ip_chk(){
        //IP白名單
        $str=file_get_contents('../web_conf/ip_loc/ip.inc');
        $ary=preg_split('/\R/', $str);$ary=array_flip($ary);
        $ip = ip_loc::get_user_ip();
        
        //HTTP_X_FORWARDED_FOR 有可能複數以上的IP，用迴圈檢查
        $xfor = explode(",",$ip);
        foreach($xfor as $k=> $v){
            if(isset($ary[trim($v)])){
                return true;
            }
        }
        //IP區間白名單
        $str=file_get_contents('../web_conf/ip_loc/ip_subnet.inc');
        $ary=preg_split('/\R/', $str);
        foreach($xfor as $k=> $v){
            for($a=0;$a< count($ary);$a++){
                $lchk=explode(".",$ary[$a]);
                if(count($lchk) < 2){
                    continue;
                }
                if( ip_loc::cidr_match(trim($v),$ary[$a])==true){
                    return true;
                }
            }
        }
        return false;
    }
    public static function get_user_ip(){
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
           $myip = $_SERVER['HTTP_CLIENT_IP'];
        }else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
           $myip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
           $myip= $_SERVER['REMOTE_ADDR'];
        }
        return $myip;
    }
    public static function cidr_match($ip, $cidr){
        list($subnet, $mask) = explode('/', $cidr);
        if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1) ) == ip2long($subnet)){
            return true;
        }
        return false;
    }
}

/* 訪問限制使用範例
public function chk_ip_loc() {
    require_once ('../web_conf/config_ip_loc.inc');
    if(ip_loc::dom_bypass()==true){
        return true;
    }
    if(ip_loc::$enable==0){
        return true;
    }
    $loc=ip_loc::loc_chk();
    $ip=true;
    if($loc!=true){
        $ip=ip_loc::ip_chk();
    }
    if(!$loc && !$ip){
        return false;
    }
    return true;
}
*/
?>
