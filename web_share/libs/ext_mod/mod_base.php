<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class mod_base {
	public $ci;
	public $dbw_str;
	public $dbr_str;
	public $dbw;
	public $dbr;
	public $affects = 0;
	public $last_qstr = "";

	function __construct(){
		$this->ci =& get_instance();
		$this->db_con();
	}
	public function db_con(){
		$this->ci->dbp[$this->dbw_str]=$this->ci->load->database($this->dbw_str,true);
		$this->ci->dbp[$this->dbr_str]=$this->ci->load->database($this->dbr_str,true);
	}
  
	public function select($qstr,$qary=array(),$w=false){
		$rt = array();$db;
		if($w) {
			$db=$this->ci->dbp[$this->dbw_str];
		} else {
			$db = $this->ci->dbp[$this->dbr_str];
		}
		$query = $db->query($qstr,$qary);
		$this->last_qstr = $db->last_query();
		if(count($query)!=0){
			foreach ($query->result_array() as $row){
				$rt[]=$row;
			}
		}
		return $rt;
	}
	public function insert($qstr,$qary=array()){
		$rt = array();
		$this->dbw = $this->ci->dbp[$this->dbw_str];
		$query = $this->dbw->query($qstr,$qary);
		$this->last_qstr = $this->dbw->last_query();
		$this->affects = $this->dbw->affected_rows();
		$rt["lid"] = $this->dbw->insert_id();
		return $rt;
	}
	public function update($qstr,$qary=array()){
		$this->dbw = $this->ci->dbp[$this->dbw_str];
		$query = $this->dbw->query($qstr,$qary);
		$this->last_qstr = $this->dbw->last_query();
		$this->affects = $this->dbw->affected_rows();
	}
	public function delete($qstr,$qary=array()){
		$this->dbw = $this->ci->dbp[$this->dbw_str];
		$query = $this->dbw->query($qstr,$qary);
		$this->last_qstr = $this->dbw->last_query();
		$this->affects = $this->dbw->affected_rows();
	}
	public function delete_by_id($id,$tb_name,$id_name="id"){
		$this->dbw = $this->ci->dbp[$this->dbw_str];
		$query = $this->dbw->query("DELETE FROM $tb_name WHERE $id_name=?", array($id));
		$this->last_qstr = $this->dbw->last_query();
		$this->affects = $this->dbw->affected_rows();
	}

	public function fieldExists($fdname, $tbname){
        //readonly
        $db = $this->ci->dbp[$this->dbr_str];
        return $db->field_exists($fdname, $tbname);
    }
}