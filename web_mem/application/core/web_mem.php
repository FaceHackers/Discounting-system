<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('../web_share/libs/ext_web/web_base'.EXT);
class web_mem extends web_base {
	public $libc;
	public $lib_ac;
	public $lib_bck;

	function __construct(){
		parent::__construct();

		// load liberary
		$this->libc         = $this->get_lib("lib_codes");
		$this->lib_ac       = $this->get_lib("lib_acc_mem");
		$this->lib_bck      = $this->get_lib("lib_api_bck");
		$this->lib_ac->skey = get_class();

		// global data
		$this->gdata["inc_head"]      = $this->get_view('global/inc_head', true);
		$this->gdata["inc_back_head"] = $this->get_view('global/inc_back_head', true);
	}

	public function get_view($file, $rt = false, $fileExt = 'html'){
		$parserFile = $file.'.'.$fileExt;
		if ($rt) {
			return $this->parser->parse($parserFile, $this->gdata, true);
		} else {
			$this->parser->parse($parserFile, $this->gdata);
		}
	}

	/* 取得使用者 IP */
	public function getClientIP() {
        /**
         * $_SERVER 參數
         * REMOTE_ADDR (真實 IP 或是 Proxy IP)
         */
        foreach (array(
					'HTTP_CLIENT_IP',
					'HTTP_X_FORWARDED_FOR',
					'HTTP_X_FORWARDED',
					'HTTP_X_CLUSTER_CLIENT_IP',
					'HTTP_FORWARDED_FOR',
					'HTTP_FORWARDED',
					'REMOTE_ADDR') as $key) {
			if (array_key_exists($key, $_SERVER)) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip);

					/**
                     *  filter_var() 函數通過指定的過濾器過濾變量
                     *  FILTER_VALIDATE_IP 過濾器驗證IP地址
                     *  FILTER_FLAG_IPV4 該值必須是有效的IPv4地址
                     *  FILTER_FLAG_IPV6 該值必須是有效的IPv6地址
                     *  FILTER_FLAG_NO_PRIV_RANGE 該值不能在私有範圍內
                     *  FILTER_FLAG_NO_RES_RANGE 該值不能在保留範圍內
                     */
					if ((bool) filter_var($ip, FILTER_VALIDATE_IP,
									FILTER_FLAG_IPV4 |
									FILTER_FLAG_NO_PRIV_RANGE |
									FILTER_FLAG_NO_RES_RANGE)) {
						return $ip;
					}
				}
			}
		}
		return null;
	}
}