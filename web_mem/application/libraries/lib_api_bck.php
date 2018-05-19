<?php
class lib_api_bck
{
	private $ci;
	public $api = null;
	public $parm;
	public $api_path = "http://c.bcad8.com/api/index.php/api/port/";
	public function __construct()
	{
		$this->ci = &get_instance();
		$this->ci->load->library("lib_codes");
	}
	public function empolyee()
	{
		$act = Array();
		$act[] = "emps";
		$act[] = strtotime(Date("YmdHis"));
		$act[] = "";
		$resp = $this->make_req($act);
		$res = json_decode($resp, true);
		if ($res["code"] == "100") {
			$data = $this->decode($res["data"]);
			return $data;
		}
		else {
			return false;
			//echo $resp;

		}
	}
	public function login($acc, $pwd)
	{
		$act = Array();
		$act[] = "login";
		$act[] = strtotime(Date("YmdHis"));
		$act[] = $acc;
		$act[] = $pwd;
		$act[] = "";
		$resp = $this->make_req($act);
		$res = json_decode($resp, true);
		if ($res["code"] == "100") {
			$data = $this->decode($res["data"]);
			return $data;
		}
		else {
			return false;
		}
	}
	public function emp_by_dep($dep, $id_only = "Y")
	{
		$act = Array();
		$act[] = "emps_by_depart";
		$act[] = strtotime(Date("YmdHis"));
		$act[] = $dep;
		$act[] = $id_only;
		$act[] = "";
		$resp = $this->make_req($act);
		$res = json_decode($resp, true);
		if ($res["code"] == "100") {
			$data = $this->decode($res["data"]);
			return $data;
		}
		else {
			return false;
			//echo $resp;

		}
	}
	public function new_post($parm)
	{
		$act = Array();
		$act[] = "new_post";
		$act[] = strtotime(Date("YmdHis"));
		$act[] = $parm["title"];
		$act[] = $parm["content"];
		$act[] = $parm["keyw"];
		$act[] = $parm["m_class"];
		$act[] = $parm["reciver"];
		$act[] = $parm["s_class"];
		$act[] = "";
		$resp = $this->make_req($act);
		$res = json_decode($resp, true);
		if ($res["code"] == "100") {
			$data = $this->decode($res["data"]);
			return $data;
		}
		else {
			//print_r($resp);
			return false;
		}
	}
	private function make_req($act)
	{
		$code = $this->ci->lib_codes->aes_en(implode("*", $act), "kingsuede864153");
		$opts = array('http' =>
			array(
			'method' => 'POST',
			'header' => 'Content-type: application/x-www-form-urlencoded',
			'content' => http_build_query(array("data" => $code))
		));
		$context = stream_context_create($opts);
		$path = $this->api_path;
		$resp = file_get_contents($path, false, $context);
		return $resp;
	}
	private function decode($raw)
	{
		$data = $this->ci->lib_codes->aes_de($raw, "kingsuede864153");
		$data = base64_decode($data);
		$data = json_decode($data, true);
		return $data;
	}
}