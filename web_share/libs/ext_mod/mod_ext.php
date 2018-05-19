<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('mod_base'.EXT);
class mod_ext extends mod_base {
	public $dow=false;
	function __construct(){
		parent::__construct(); 
	}
	public function get_by($tb, $ary=null, $ord=null, $limit=null){
		$ary_s = array();
		$ary_q = array();
		$ary_val = array();
		if ($ary==null) {
			$qstr = "SELECT * FROM ".$tb;
		} else {
			foreach($ary as $k => $v){
				$ary_s[] = $tb.".".$k."=?";
				$ary_q[] = "?";
				$ary_val[] = $v;
			}
			$qstr = "SELECT * FROM ".$tb." WHERE ".implode(" AND ",$ary_s);
		}
		if ($ord!=null) {
			$qstr .= " ORDER BY ".$ord["key"]." ".$ord["ord"];
		}
		if ($limit!=null) {
			$qstr .= " LIMIT ".$limit;
		}
		$raw = $this->select($qstr, $ary_val, $this->dow);
		$this->dow = false;
		return $raw;
	}
	public function get_any($tb, $ary=null, $ord=null, $limit=null){
		$ary_s = array();
		$ary_q = array();
		$ary_val = array();
		if ($ary==null) {
			$qstr = "SELECT * FROM ".$tb;
		} else {
			foreach($ary as $k => $v){
				$ary_s[] = $tb.".".$k."=?";
				$ary_q[] = "?";
				$ary_val[] = $v;
			}
			$qstr = "SELECT * FROM ".$tb." WHERE ".implode(" OR ",$ary_s);
		}
		if ($ord!=null) {
			$qstr .= " ORDER BY ".$ord["key"]." ".$ord["ord"];
		}
		if ($limit!=null) {
			$qstr .= " LIMIT ".$limit;
		}
		$raw = $this->select($qstr, $ary_val, $this->dow);
		$this->dow = false;
		return $raw;
	}
	public function get_by_key($tb, $tbid, $key="descr"){
		$raw = $this->get_by($tb, array("id" => $tbid));
		if (count($raw)!=0) {
			return $raw[0][$key];
		}
		return "N/A";
	}
	public function get_in($tb, $co, $ary){
		$qstr = "SELECT * FROM ".$tb." WHERE ".$co." in (".implode(",",$ary).")";
		$raw = $this->select($qstr, null, $this->dow);
		$this->dow = false;
		return $raw;
	}
	public function add_by($tb, $ary, $auto=true){
		$ary_s = array();
		$ary_q = array();
		$ary_val = array();

		if($auto === true){
            if(isset($ary["itime"])) unset($ary["itime"]);
        }

        foreach($ary as $k => $v){
			$ary_s[] = $tb.".".$k;
			$ary_q[] = "?";
			$ary_val[] = $v;
		}

        //自動寫入 itime
        if(!in_array($tb.".itime", $ary_s) && $this->fieldExists('itime', $tb)){
            $ary_s[] = $tb.".itime";
            $ary_q[] = "?";
            $ary_val[] = date("Y-m-d H:i:s");
        }

		$qstr = "INSERT INTO ".$tb." (".implode(",",$ary_s).") VALUES (".implode(",",$ary_q).")";
		$raw = $this->insert($qstr,$ary_val);
		if (method_exists($this->ci,"op_rec")) {
			$this->ci->op_rec("add",$tb,$raw["lid"],$ary);
		}
		return $raw;
	}
	public function modi_by_id($tb,$id,$ary,$ids="id"){
		$ary_s = array();
		$ary_q = array();
		$ary_val = array();

		foreach($ary as $k => $v){
			$ary_s[] = $tb.".".$k."=?";
			$ary_val[] = $v;
		}

		//自動寫入 utime
        if(!in_array($tb.".utime", $ary_s) && $this->fieldExists('utime', $tb)){
            $ary_s[]=$tb.".utime=?";
            $ary_val[]=date("Y-m-d H:i:s");
        }

		$ary_val[] = $id;
		$qstr = "UPDATE ".$tb." SET ".implode(",",$ary_s)." WHERE ".$ids."=? LIMIT 1";
		$raw = $this->update($qstr, $ary_val);
		if (method_exists($this->ci,"op_rec")) {
			$this->ci->op_rec("mod", $tb, $id, json_encode($ary));
		}
		return $raw;
	}
	public function modi_by($tb, $by, $ary){
		if (method_exists($this->ci,"op_rec")) {
			$rec = $this->get_by($tb, $by);
		}
		$ary_s = array();
		$ary_q = array();
		$ary_val = array();

		foreach($ary as $k => $v){
			$ary_s[] = $tb.".".$k."=?";
			$ary_val[] = $v;
		}

		//自動寫入 utime
        if(!in_array($tb.".utime", $ary_s) && $this->fieldExists('utime', $tb)){
            $ary_s[]=$tb.".utime=?";
            $ary_val[]=date("Y-m-d H:i:s");
        }

		$sets = $ary_s;
		$ary_s = array();
		foreach($by as $k => $v){
			$ary_s[] = $tb.".".$k."=?";
			$ary_val[] = $v;
		}
		$by = $ary_s;
		$qstr = "UPDATE ".$tb." SET ".implode(",",$sets)." WHERE ".implode(" AND ",$ary_s)." ";
		$raw = $this->update($qstr, $ary_val);
		if (method_exists($this->ci, "op_rec")) {
			for($a=0;$a< count($rec);$a++){
				$this->ci->op_rec("mod", $tb,$rec[$a]["id"], json_encode($rec[$a]));
			}
		}
	}
	public function del_by_id($tb, $id, $key="id"){
		if (method_exists($this->ci, "op_rec")) {
			$rec = $this->get_by($tb, array( $key => $id));
		}
		$raw = $this->delete("DELETE FROM ".$tb." WHERE ".$key."=? LIMIT 1", array($id));
		if (method_exists($this->ci, "op_rec")) {
			$this->ci->op_rec("del", $tb,$id, json_encode($rec[0]));
		}
		return $raw;
	}
	public function del_by($tb, $ary){
		if (method_exists($this->ci,"op_rec")) {
			$rec = $this->get_by($tb,$ary);
		}
		$ary_s = array();
		$ary_q = array();
		$ary_val = array();
		foreach($ary as $k => $v){
			$ary_s[] = $tb.".".$k."=?";
			$ary_q[] = "?";
			$ary_val[] = $v;
		}
		$qstr = "DELETE FROM ".$tb." WHERE ".implode(" AND ",$ary_s);
		$raw = $this->delete($qstr, $ary_val);
		if(method_exists($this->ci, "op_rec")){
			for($a=0;$a< count($rec);$a++){
				$this->ci->op_rec("del", $tb,$rec[$a]["id"], json_encode($rec[$a]));
			}
		}
	}
	public function conv_to_key($ary, $key){
		$nary = array();
		for($a=0;$a< count($ary);$a++){
			$nary[$ary[$a][$key]] = $ary[$a];
		}
		return $nary;
	}
}