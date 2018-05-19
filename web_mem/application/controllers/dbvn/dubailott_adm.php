<?php
require('dubailott.php');
class dubailott_adm extends dubailott {
	private $is_pass = false;

	function __construct(){
		$this->isfront = false;
		parent::__construct();
		$this->gdata['burl'] = $this->burl.$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'_adm/';

		$map = array('index'=> 1, 'adm_login'=> 1, 'lottery_ticket_bingo' =>1);
		if(array_key_exists($this->router->fetch_method(), $map)){
			$this->is_pass = true;
		} else {
			$acess = $this->session->userdata('acess_adm');
			if($acess!="" && $acess!=null){
				$chk = $this->libc->aes_de($acess);
				$chks = explode("_", $chk);
				if(count($chks)==2){
					$this->acc = $chks[0];
					$this->gdata["acc"] = $chks[1];
					$now = time();
					$ctime = intval($chks[1]);
					if(($now-$ctime) < 6000){
						$this->is_pass = true;
						$acess = $this->libc->aes_en($chks[0]."_".time());
						$this->session->set_userdata("acess_adm", $acess);
					}
				}
			}
		}
		if($this->is_pass==false){
			$this->obj['title'] = '帳號已登出';
			$this->obj['msg'] = '您的登入效期已過，請重新登入。';
			$this->output();
		}
	}

	public function index(){
		$this->gdata["acc"] = '';
		$this->gdata["pwd"] = '';

		$this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/login');
	}

	public function adm_login(){
		if (!$this->lib_ac->chkipt(array('acc', 'pwd'), $_POST)) {
			$this->obj['code'] = 500;
			$this->obj['title'] = '系統錯誤';
			$this->obj['msg'] = '傳入資料錯誤';
			$this->output();
		}

		$login = $this->lib_bck->login($_POST["acc"], $_POST["pwd"]);
		if(!$login){
			$this->obj['code'] = 501;
			$this->obj['title'] = '輸入錯誤';
			$this->obj['msg'] = '帳號/密碼錯誤';
			$this->output();
		}

		/* 登入員編 */
		$acess = $this->libc->aes_en($_POST['acc'].'_'.time());
		$this->session->set_userdata('acess_adm', $acess);

		$this->obj['code'] = 100;
		$this->obj['view'] = $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/header', true);
		$this->output();
	}

    /** 登入人員 員編 */
    private function getEmployee(){
        $acess_adm = $this->session->userdata('acess_adm');
        $editor = $this->lib_codes->aes_de($acess_adm);
        $emp_1 = explode('_', $editor);
        $emp1 = $emp_1[0];

        return $emp1;
    }

	/* 切換頁面 */
	public function toView($page){
		$this->obj['code'] = 100;
		$this->obj['page'] = $page;
		$this->obj['view'] = $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/'.$page.'/header', true);
		$this->output();
	}

	/* 子頁面 */
	public function childView(){
		if(!isset($_POST['send'])){
			$this->obj['code'] = 404;
			$this->obj['title'] = '系統錯誤';
			$this->obj['msg'] = '傳入資料錯誤';
			$this->output();
		}

		$send = $_POST['send'];

		$unit = $send['unit'];
		$page = $send['page'];

		$url = $unit.'/'.$page;

		$this->get_ticket_date(); /* 參加會員 選擇日期 */

		$this->obj['code'] = 100;
		$this->obj['view'] = $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/'.$url, true);
		$this->output();
	}

    /** 上傳期數Excel */
    public function excel_periods_upload(){
        session_start();
        if(!isset($_FILES['excelFile'])){
            $this->add_error('excel_periods_upload', '404', '上傳期數設定-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        /* 讀取 套件 */
        $this->load->library('lib_excel');

        $fileAry = $_FILES['excelFile']; /* 取得上傳檔案 */
        $arr = array(
            'upl_dir' => $this->gdata['uploadfolder'].$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/', /*  上傳路徑*/
            'folder' => 'num_set', /* 上傳檔案名稱 */
            'file_name' => 'number_periods', /* 檔案名稱 */
            'start_row' => 2, /* 起始列 */
            'start_col' => 'A' /* 起始行 */
        );
        $ExcelValue = $this->lib_excel->read_excel($fileAry, $arr); /* 取得檔案內容 */

        $_SESSION['updata'] =  urlencode(json_encode($ExcelValue)); /* 把內容存到 session */
        $this->obj['code'] = 100;
        $this->output();
    }

    /** 上傳Excel 顯示期數清單 */
    public function upload_view_num(){
        session_start();
        $updata = json_decode(urldecode($_SESSION['updata']), true); /* 取得 檔案內容 資料 */
        session_write_close(); /* 避免等前一個頁面執行完畢，才能執行下一個頁面的情況 */

        $list = array();
        foreach ($updata as $key => $val) {
            $temp = array();

            $temp['number_periods'] = trim($val['A']); /* 期數 */
            $temp['lottery_date'] = trim($val['B']. ' '.'18:00:00'); /* 開獎日期 */
            $temp['winning_numbers'] = trim($val['C']); /* 開獎號碼*/
            $temp['status'] = 0; /* 可上傳 */

            /* 驗證是否有重複的期數 */
            $chk_num = $this->chk_number_periods($val['A']);
            if (empty($val['A'])) {
                $temp['status'] = 1;
            } else if (!empty($chk_num)) {
                $temp['status'] = 2;
            }

            /* 日期是否合法 */
            $chk_lottery_date = $this->isDate($val['B']);
            if (empty($val['B'])) {
                $temp['status'] = 3;
            } else if (!$chk_lottery_date) {
                $temp['status'] = 4;
            }

            /* 驗證是否有重複的開獎日期 */
            $lottery_date = $val['B']. ' '.'18:00:00';
            $chk_date = $this->chk_lottery_date($lottery_date);
            if (!empty($chk_date)) {
                $temp['status'] = 5;
            }

            /* 驗證 開獎號碼是否重複 格式 */
            if(!empty($val['C'])) {
                /* 有非數字根逗號 */
                if (!preg_match('/^\d{2},\d{2},\d{2},\d{2},\d{2},\d{2}$/', $val['C'])) {
                    $temp['status'] = 6;
                }

                $winning_numbers = explode(',', $val['C']); /* 開獎號碼 */
                $winning_numbers_len = count($winning_numbers); /* 開獎號碼長度 */

                $num_re = array_flip(array_flip($winning_numbers)); /* 反轉陣列 */
                $num_re_len = count($num_re); /* 長度 */

                /**
                 * 逗號之間有空白
                 * 數字大小 01~45 之間
                 */
                foreach ($winning_numbers as $v) {
                    $num = (int) $v;
                    if($v === '') {
                        $temp['status'] = 7;
                    } else if ($num > 45 || $num <= 0) {
                        $temp['status'] = 8;
                    }
                }

                /**
                 * 驗證是否超過六個號碼 或是 低於六個號碼
                 * 號碼是否有重複
                 */
                if ($winning_numbers_len > 6 || $winning_numbers_len < 6) {
                    $temp['status'] = 9;
                } else if ($winning_numbers_len != $num_re_len) {
                    $temp['status'] = 10;
                }

                /* 如果有開獎日期大於今天 則不能輸入開獎號碼 */
                $today = date("Y-m-d",time()); /* 今天 */
                if($val['B'] > $today) {
                    $temp['status'] = 12;
                }
            }

            /* 新增的開獎日期 不能小於今天 */
            if(strtotime($lottery_date) <= time()) {
                $temp['status'] = 11;
            }

            $list[] = $temp;
        }

        $this->gdata['list'] = json_encode($list);
        $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/number_set/upload_view_num');
    }

    /* 上傳期數 寫入資料庫 */
    public function excel_periods_success() {
        if(!isset($_POST['data'])){
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '請確認資料是否正確，再上傳!!';
            $this->output();
        }

        /** 如有已上傳資料 或 格式錯誤 會清空陣列空值 */
        $num_data = array_filter($_POST['data']);

        $editor = $this->getEmployee(); /* 取得登入資訊 */

        /* 新增期數 */
        $editor_data = array(
            'add_adm' => $editor, /* 新增人員 */
            'mod_adm' => '' /* 最後編輯人員  預設是空的 */
        );
        foreach ($num_data as $key => $val) {
            $number_open = !empty($val['winning_numbers']) ? '1' : '0'; /* 是否有key 號碼 */

            /* 檢查是否有重複寫入 期數 */
            $chk_per = $this->mod->get_by('act_evt', array(
                'act_id'  => $this->actInfo['id'],
                'param1'  => 'number_set', /* 期數設定 參數 */
                'param2' => $val['number_periods'] /* 期數 */
            ),null,'1');

            /* 檢查是否有重複寫入 開獎日期 */
            $chk_date = $this->mod->get_by('act_evt', array(
                'act_id'  => $this->actInfo['id'],
                'param1'  => 'number_set', /* 期數設定 參數 */
                'date1' => $val['lottery_date'], /* 開獎日期 */
            ),null,'1');

            if(!empty($chk_per) || !empty($chk_date)) {
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '請確認期數、開獎日期是否有重複!!';
                $this->output();
            }

            if(empty($chk_per) && empty($chk_date)) {
                $this->mod->add_by('act_evt',
                    array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'param1' => 'number_set', /* 期數設定 參數 */
                        'param2' => $val['number_periods'], /* 期數 */
                        'date1' => $val['lottery_date'], /* 開獎日期 */
                        'descr1' => $val['winning_numbers'], /* 開獎號碼 */
                        'status1' => $number_open,  /* 是否有key 號碼 1=已key 0=未key*/
                        'descr2' => json_encode($editor_data) /* 新增人員 */
                    ));
            }
        }

        $this->obj['code'] = 100;
        $this->obj['title'] = '上傳成功';
        $this->obj['msg'] = '期數已成功上傳';
        $this->output();
    }

    /** 上傳會員兌換獎Excel */
    public function excel_ticket_upload() {
        session_start();
        if(!isset($_FILES['excelFile'])){
            $this->add_error('excel_ticket_upload', '404', '上傳會員兌換獎設定-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        /* 讀取 套件 */
        $this->load->library('lib_excel');

        $fileAry = $_FILES['excelFile']; /* 取得上傳檔案 */
        $arr = array(
            'upl_dir' => $this->gdata['uploadfolder'].$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/', /*  上傳路徑*/
            'folder' => 'mem_ticket_set', /* 上傳檔案名稱 */
            'file_name' => 'mem_ticket', /* 檔案名稱 */
            'start_row' => 2, /* 起始列 */
            'start_col' => 'A' /* 起始行 */
        );
        $ExcelValue = $this->lib_excel->read_excel($fileAry, $arr); /* 取得檔案內容 */

        $_SESSION['updata'] =  urlencode(json_encode($ExcelValue)); /* 把內容存到 session */
        $this->obj['code'] = 100;
        $this->output();
    }

    /** 上傳Excel 顯示會員兌換獎清單 */
    public function upload_view_ticket() {
        session_start();
        $ticket_data = json_decode(urldecode($_SESSION['updata']), true); /* 取得 檔案內容 資料 */
        session_write_close(); /* 避免等前一個頁面執行完畢，才能執行下一個頁面的情況 */

        $list = array();
        foreach ($ticket_data as $key => $val) {
            $temp = array();

            $temp['account'] = trim($val['A']); /* 會員帳號 */
            $temp['ticket_num'] = trim($val['B']); /* 兌獎卷數量 */
            $temp['remark'] = trim($val['C']); /* 備註*/
            $temp['status'] = 0; /* 可上傳 */

            /**
             * 判斷是否有輸入
             * 是否有此會員
             * 兌獎卷數量 是否為數字
             */
            $chk_acc = $this->get_acc($val['A']);
            if(empty($val['A']) || empty($val['B']) || empty($val['C'])) {
                $temp['status'] = 1;
            } else if($chk_acc['code'] == 200) {
                $temp['status'] = 2;
            } else if($chk_acc == 400) {
                $temp['status'] = 2;
            } else if( (int) $val['B'] <=0) {
                $temp['status'] = 3;
            } else  if(!preg_match("/^\d*$/", $val['B'])) {
                $temp['status'] = 3;
            }

            /* 判斷是否 非越南迪拜的會員 */
            if(isset($chk_acc['code']) && $chk_acc['code'] == 100) {
                if($chk_acc['id_country'] != 2) {
                    $temp['status'] = 2;
                }
            }
            $list[] = $temp;
        }

        $this->gdata['list'] = json_encode($list);
        $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/mem_ticket_set/upload_view_ticket');
    }

    /* 上傳會員兌換獎 寫入資料庫 */
    public function excel_ticket_success() {
        if(!isset($_POST['data'])){
            $this->add_error('excel_ticket_success', '404', '上傳會員兌換獎資料-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '請確認資料是否正確，再上傳!!';
            $this->output();
        }

        /** 如有已上傳資料 或 格式錯誤 會清空陣列空值 */
        $ticket_data = array_filter($_POST['data']);

        $editor = $this->getEmployee(); /* 取得登入資訊 */
        $editor_data = array(
            'add_adm' => $editor, /* 新增人員 */
            'mod_adm' => '' /* 最後編輯人員  預設是空的 */
        );

        foreach ($ticket_data as $key => $val) {
            /* 新增會員兌獎卷 */
            $chk_acc = $this->get_acc($val['account']);
            $this->mod->add_by('act_evt',
                array(
                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                    'account' => $chk_acc['acc'], /* 會員帳號 */
                    'param1' => 'mem_ticket', /* 會員兌換數量 參數 */
                    'param2' => $val['ticket_num'], /* 未使用兌獎卷  */
                    'descr1' => $val['remark'], /* 備註 */
                    'descr2' => json_encode($editor_data), /* 新增人員 */
                    'amount1' => '', /* 每日最高存款金額 */
                    'amount3' => $val['ticket_num'], /* 兌獎卷數量 */
                    'status1' => '1' /* 手動新增 */
             ));
        }
        $this->obj['code'] = 100;
        $this->obj['title'] = '上傳成功';
        $this->obj['msg'] = '會員兌換數量已成功上傳';
        $this->output();
    }

    /** 上傳跑馬燈Excel */
    public function excel_marque_upload() {
        session_start();
        if(!isset($_FILES['excelFile'])){
            $this->add_error('excel_marque_upload', '404', '上傳跑馬燈設定-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        /* 讀取 套件 */
        $this->load->library('lib_excel');

        $fileAry = $_FILES['excelFile']; /* 取得上傳檔案 */
        $arr = array(
            'upl_dir' => $this->gdata['uploadfolder'].$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/', /*  上傳路徑*/
            'folder' => 'marquee_set', /* 上傳檔案名稱 */
            'file_name' => 'marquee_mem', /* 檔案名稱 */
            'start_row' => 2, /* 起始列 */
            'start_col' => 'A' /* 起始行 */
        );
        $ExcelValue = $this->lib_excel->read_excel($fileAry, $arr); /* 取得檔案內容 */

        $_SESSION['updata'] =  urlencode(json_encode($ExcelValue)); /* 把內容存到 session */
        $this->obj['code'] = 100;
        $this->output();
    }

    /** 上傳Excel 顯示跑馬燈清單 */
    public function upload_view_marquee() {
        session_start();
        $marquee_data = json_decode(urldecode($_SESSION['updata']), true); /* 取得 檔案內容 資料 */
        session_write_close(); /* 避免等前一個頁面執行完畢，才能執行下一個頁面的情況 */

        $list = array();
        foreach ($marquee_data as $key => $val) {
            $temp = array();

            $temp['winning_member'] = trim($val['A']); /* 中獎會員帳號 */
            $temp['winning_amount'] = trim(str_replace(",", "", $val['B'])); /* 中獎金額 */
            $temp['status'] = 0; /* 可上傳 */

            /**
             * 判斷是否有輸入
             * 是否有此會員
             * 中獎金額 是否為數字
             */
            //$chk_acc = $this->get_acc($val['A']);
            if(empty($val['A']) || empty($val['B'])) {
                $temp['status'] = 1;
            } else if( (int) $temp['winning_amount'] <=0) {
                $temp['status'] = 2;
            } else  if(!preg_match("/^\d*$/", $temp['winning_amount'])) {
                $temp['status'] = 2;
            }

            /* 判斷是否 非越南迪拜的會員 */
            /*if(isset($chk_acc['code']) && $chk_acc['code'] == 100) {
                if($chk_acc['id_country'] != 2) {
                    $temp['status'] = 2;
                }
            }*/
            $list[] = $temp;
        }

        $this->gdata['list'] = json_encode($list);
        $this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/adm/marquee/upload_view_marquee');
    }

    /* 上傳跑馬燈資料 寫入資料庫 */
    public function excel_marquee_success() {
        if(!isset($_POST['data'])){
            $this->add_error('excel_marquee_success', '404', '上傳跑馬燈資料-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '請確認資料是否正確，再上傳!!';
            $this->output();
        }

        /** 如有已上傳資料 或 格式錯誤 會清空陣列空值 */
        $marquee_data = array_filter($_POST['data']);

        foreach ($marquee_data as $key => $val) {
            /* 新增會員兌獎卷 */
            //$chk_acc = $this->get_acc($val['winning_member']);
            $this->mod->add_by('act_evt',
                array(
                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                    'account' => $val['winning_member'], /* 會員帳號 */
                    'param1' => 'winning', /* 會員中獎金額 參數 */
                    'amount3' => $val['winning_amount'] /* 中獎金額 */
                ));
        }
        $this->obj['code'] = 100;
        $this->obj['title'] = '上傳成功';
        $this->obj['msg'] = '跑馬燈資料已成功上傳';
        $this->output();
    }

    /** 上傳中獎會員Excel */
    public function excel_mem_bingo_upload() {
        session_start();
        if(!isset($_FILES['excelFile'])){
            $this->add_error('excel_mem_bingo_upload', '404', '上傳中獎會員-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        /* 讀取 套件 */
        $this->load->library('lib_excel');

        $fileAry = $_FILES['excelFile']; /* 取得上傳檔案 */
        $arr = array(
            'upl_dir' => $this->gdata['uploadfolder'].$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/', /*  上傳路徑*/
            'folder' => 'mem_bingo', /* 上傳檔案名稱 */
            'file_name' => 'mem_bingo_list', /* 檔案名稱 */
            'start_row' => 2, /* 起始列 */
            'start_col' => 'A' /* 起始行 */
        );
        $ExcelValue = $this->lib_excel->read_excel($fileAry, $arr); /* 取得檔案內容 */

        $_SESSION['updata'] =  urlencode(json_encode($ExcelValue)); /* 把內容存到 session */
        $this->obj['code'] = 100;
        $this->output();
    }

    /** 上傳Excel 顯示中獎會員清單 */
    public function upload_view_mem_bingo() {
        session_start();
        $mem_bingo_data = json_decode(urldecode($_SESSION['updata']), true); /* 取得 檔案內容 資料 */
        session_write_close(); /* 避免等前一個頁面執行完畢，才能執行下一個頁面的情況 */

        $list = array();
        foreach ($mem_bingo_data as $key => $val) {
            $temp = array();

            $temp['mem_bingo'] = trim($val['A']); /* 中獎會員帳號 */
            $temp['mem_num'] = trim($val['B']); /* 兌獎期數 */
            $temp['receive_bonus'] = trim(str_replace(",", "", $val['C']));  /* 可獲彩金 */
            $temp['bingo_number'] = trim($val['D']);  /* 中獎號碼 */
            $temp['distribute_time'] = trim($val['E']);  /* 派獎時間 */
            $temp['status'] = 0;  /* 可派獎 */

            /**
             * 判斷是否有輸入
             * 是否有此會員
             * 是否有輸入對獎期數 彩金
             * 中獎金額 是否為數字
             * 派發時間格式 是否正確
             * 上傳名單是否正確
             */
            $chk_acc = $this->get_acc($val['A']);
            if (empty($val['A']) || empty($val['B']) || empty($val['C']) || empty($val['D']) || empty($val['E'])) {
                $temp['status'] = 1; /* 不能為空 */
            } else if ($chk_acc['code'] == 200 || $chk_acc == 400) {
                $temp['status'] = 2; /* 是否有此會員 */
            } else if ( (int) $temp['receive_bonus'] <= 0) {
                $temp['status'] = 3; /* 是否是數字 */
            } else if(!preg_match("/^\d*$/", $temp['receive_bonus'])) {
                $temp['status'] = 3; /* 是否是數字 */
            } else if ($temp['distribute_time']) {
                if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $temp['distribute_time'])) {
                    $temp['status'] = 4; /* 派發時間 格式錯誤 */
                }
            }

            $chk_bingo = $this->chk_ticket_bingo_money($val['A'], $val['B'], $temp['receive_bonus'], $val['D']); /* 確認中獎資訊是否正確 */
            if ($chk_bingo['code'] == 400) {
                $temp['status'] = 5; /* 找不到符合的資料 */
            }

            if ($chk_bingo['code'] == 100) {
                $temp['turn_num'] = $chk_bingo['turn_num']; /* 轉出號碼 */
                $temp['turn_time'] = $chk_bingo['turn_time']; /* 轉出時間 */

                /* 檢查是否派獎 */
                $chk_bingo = $this->mod->get_by('act_evt', array(
                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                    'account' => $chk_acc['acc'], /* 會員帳號 */
                    'param1' => 'turn_number', /* 會員中獎 參數 */
                    'param2' => $val['B'], /* 兌獎期數 */
                    'param5' => $temp['receive_bonus'], /* 可獲彩金 */
                    'itime' => $chk_bingo['turn_time'], /* 轉出時間 */
                    'descr1' => $chk_bingo['turn_num'], /* 轉出號碼 */
                    'status1' => '4' /* 4 = 已派發獎金 */
                ));

                if (!empty($chk_bingo)) {
                    $temp['status'] = 6; /* 已派發獎金 */
                }
            }
            $list[] = $temp;
        }
        $this->gdata['list'] = json_encode($list);
        $this->get_view($this->actInfo['folder'] . '/' . $this->actInfo['act_ctrl'] . '/adm/mem_list/upload_view_mem_bingo');
    }

    /* 上傳中獎會員 寫入資料庫 */
    public function excel_bingo_mem_success() {
        if(!isset($_POST['data'])){
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '請確認資料是否正確，再上傳!!';
            $this->output();
        }
        /** 如有已上傳資料 或 格式錯誤 會清空陣列空值 */
        $bingo_mem = array_filter($_POST['data']);

        $editor = $this->getEmployee(); /* 取得登入資訊 */

        /* 新增期數 */
        $editor_data = array(
            'add_adm' => $editor /* 新增人員 */
        );
        foreach ($bingo_mem as $key => $val) {
            $chk_acc = $this->get_acc($val['mem_bingo']);

            /* 檢查是否有重複資料*/
            $chk_bingo = $this->mod->get_by('act_evt', array(
                'act_id' => $this->actInfo['id'], /* 活動代碼 */
                'account' => $chk_acc['acc'], /* 會員帳號 */
                'param1' => 'bingo_mem', /* 會員中獎 參數 */
                'param2' => $val['mem_num'], /* 兌獎期數 */
                'amount3' => $val['receive_bonus'], /* 可獲彩金 */
                'date2' => $val['turn_time'], /* 轉出時間 */
                'descr1' => $val['turn_num'], /* 轉出號碼 */
                'status1' => '1' /* 0 = 未派發 1= 已派發 */
            ));

            if(empty($chk_bingo)) {
                /* 新增中獎會員 */
                $this->mod->add_by('act_evt',
                    array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $chk_acc['acc'], /* 會員帳號 */
                        'param1' => 'bingo_mem', /* 會員中獎 參數 */
                        'param2' => $val['mem_num'], /* 兌獎期數 */
                        'amount3' => $val['receive_bonus'], /* 可獲彩金 */
                        'date1' => $val['distribute_time'], /* 派獎時間 */
                        'date2' => $val['turn_time'], /* 轉出時間 */
                        'descr1' => $val['turn_num'], /* 轉出號碼 */
                        'descr2' => json_encode($editor_data), /* 新增人員 */
                        'status1' => '1' /* 0 = 未派發 1= 已派發 */
                    ));

                $this->mod->modi_by('act_evt',
                    array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $chk_acc['acc'], /* 會員帳號 */
                        'param1' => 'turn_number', /* 會員中獎 參數 */
                        'param2' => $val['mem_num'], /* 兌獎期數 */
                        'param5' => $val['receive_bonus'], /* 可獲彩金 */
                        'itime' => $val['turn_time'], /* 轉出時間 */
                        'descr1' => $val['turn_num'], /* 轉出號碼 */
                        'status1' => '3' /* 3 = 已中獎 */
                     ),
                     array(
                          'status1' => '4' /*  4 = 已派發獎金 */
                     )
                );
            }
        }
        $this->obj['code'] = 100;
        $this->obj['title'] = '上傳成功';
        $this->obj['msg'] = '中獎會員資料已成功上傳';
        $this->output();
    }

    /* 確認上傳中獎名單是否正確 */
    public function chk_ticket_bingo_money($acc, $num, $money, $bingo_number) {
        $sql = "
                SELECT
                      `account` `turn_acc`, -- 會員帳號
                      `param2` `order_per`, -- 兌換期數
                      `param3` `result`, -- 兌獎結果
                      `param5` `receive_bonus`, -- 可獲彩金
                      `descr1` `turn_num`, -- 轉出號碼
                      `itime` `turn_time` -- 轉出時間
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND
                      `account` = ? AND 
                      `param2` = ? AND 
                      `param5` = ? AND 
                      `descr1` = ?
                ";

        $mem_bingo_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $acc, $num, $money, $bingo_number));

        if (empty($mem_bingo_list)) {
            return array('code' => 400); /* 找不到符合的資料 */
        } else {
            foreach ($mem_bingo_list as $key=>$item) {
                $turn_num = $item['turn_num']; /* 號碼 */
                $turn_time = $item['turn_time']; /* 轉出時間 */

                $bingo_list = array(
                    'code' => 100,
                    'turn_num' => $turn_num,
                    'turn_time' => $turn_time
                );
               return $bingo_list;
            }
        }
    }

    /* 下載資料型態 */
    public function downloadExcel_type() {
        if(!isset($_POST['get_data'])) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '目前無會員資料';
            $this->output();
        }

        /* 資料 */
        $get_data = $_POST['get_data'];

        /* 判斷檔案名稱 */
        $get_type= $_POST['type'];

        if(!isset($get_type)) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '檔案名稱錯誤';
            $this->output();
        }

        $this->downloadExcel($get_data, $get_type);
    }

    /* 下載檔案 兌獎卷 兌獎號碼*/
    public function downloadExcel($get_data, $get_type) {
        if(empty($get_data) || empty($get_type)) return false;

        $this->load->library('lib_excel');
        switch ($get_type) {
            case 'voucher_inquiry':
                $name = '兌獎卷';

                /* 標題 */
                $title = array(
                    'ticket_add_time' => '兌獎卷新增時間',
                    'acc_deposit' => '賬號',
                    'max_deposit' => '每日最高存款金額',
                    'ticket_num' => '獲得兌獎卷數量',
                    'no_use_ticket' => '未使用彩卷數量',
                    'cumulatively_effective' => '累计有效卷数',
                    'effective_date' => '有效日期',
                    'remark' => '備註'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();

                break;
            case 'duijiang_number':
                $name = '兌獎號碼';

                /* 標題 */
                $title = array(
                    'turn_time' => '轉號日期',
                    'turn_acc' => '賬號',
                    'turn_num' => '轉出號碼',
                    'order_per' => '兌獎期數',
                    'result' => '兌獎結果',
                    'receive_bonus' => '可獲得彩金'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();

                break;
            case 'yet':
                $name = '已派獎';

                /* 標題 */
                $title = array(
                    'turn_time' => '轉號日期',
                    'bingo_acc' => '賬號',
                    'mem_num' => '兌獎期數',
                    'receive_bonus' => '可獲彩金',
                    'turn_num' => '轉出號碼',
                    'distribute_time' => '派獎時間'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();

                break;
            case 'not_yet':
                $name = '未派獎';

                /* 標題 */
                $title = array(
                    'turn_time' => '轉號日期',
                    'bingo_acc' => '賬號',
                    'mem_num' => '兌獎期數',
                    'receive_bonus' => '可獲彩金',
                    'turn_num' => '轉出號碼',
                    'distribute_time' => '派獎時間'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();

                break;
            case 'no_use_ticket':
                $name = '未使用獎卷';

                /* 標題 */
                $title = array(
                    'acc_deposit' => '會員帳號',
                    'no_use_ticket' => '未使用彩卷數量',
                    'effective_date' => '有效日期'
                );

                $file = $this->lib_excel->download(2, $get_data, $title, 'Excel5', null, true);
                $this->obj['file'] = $file;
                $this->obj['fileName'] = ''.$name.'會員名單';
                $this->obj['code'] = 100;
                $this->output();
                break;
        }
    }

    /* 下載未使用彩卷 */
    public function down_no_use_ticket() {
        /* 判斷檔案名稱 */
        $get_type= $_POST['type'];

        $sql = "
                        SELECT
                              `account` `acc_deposit`, -- 會員帳號
                              `descr1` `remark`, -- 備註
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `amount1` `max_deposit`, -- 每日最高存款金額
                              `amount3` `ticket_num`,	 -- 獲得兌獎卷數量
                              `itime` `ticket_add_time`, -- 兌獎卷新增時間
                              `status1` -- 判斷是手動新增還是排程新增
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND
                              `param1` = ?
                ";

        $sql .= 'ORDER BY `itime` ASC';
        $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket'));

        $today = date("Y-m-d",time()); /* 今天 */
        $expired = $this->chang_time($today, '-14', 'day');
        $mem_list = array();
        foreach ($mem_ticket_list as $key=>$value) {

            /* 每日最高存款金額  */
            $max_deposit = ($value['max_deposit'] != '')? $max_deposit = number_format($value['max_deposit']):'';

            /* 有效日期 */
            $effective_date_time = $this->chang_time($value['ticket_add_time'], '+14' , 'day');
            $effective_date = substr($effective_date_time, '0', '10');
            if ($effective_date >= $_POST['no_use_ticket_date']) {
                $sql_2 = "
                              SELECT
                                    SUM(`param2`) AS `no_use_ticket` -- 未使用彩卷
                              FROM
                                    `act_evt`
                              WHERE
                                    `act_id`= ? AND
                                    `param1`= ? AND
                                    `itime` <= '".$value['ticket_add_time']."' AND
                                    `itime` > '".$expired."' AND
                                    `account` = '".$value['acc_deposit']."'
                              GROUP BY 
                                    `account`";
                $effective_list = $this->mod->select($sql_2, array($this->actInfo['id'], 'mem_ticket'));
                $num_effective = !empty($effective_list)?$effective_list['0']['no_use_ticket']:0; /* 累計有效票卷數 */

                if($value['no_use_ticket'] > 0) {
                    $data = array(
                        'acc_deposit' => $value['acc_deposit'], /* 會員帳號 */
                        'no_use_ticket' => $value['no_use_ticket'], /* 未使用彩卷數量 */
                        'effective_date' => $effective_date, /* 有效日期 */
                    );
                    $mem_list[] = $data;
                }
            }
        }
        if(empty($mem_list)) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統提示';
            $this->obj['msg'] = '無資料可下載';
            $this->output();
        } else {
            $this->downloadExcel($mem_list, $get_type);
        }
    }

	/** 期數設定 */
	public function number_set() {
        if(!isset($_POST['type'])){
            $this->add_error('number_set', '404', '期數設定-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $editor = $this->getEmployee(); /* 取得登入資訊 */
        switch ($_POST['type']) {
            case 'qry':
                $sql = "
                    SELECT
                          `id`,
                          `param2` `number_periods`, -- 期數
                          `descr1` `winning_numbers`, -- 開獎號碼
                          `date1` `lottery_date`,	 -- 開獎日期
                          `status1` `chk_open` -- 確認是否有key 号碼
                    FROM
                          `act_evt`
                    WHERE
                          `act_id` = ? AND 
                          `param1` = ?
                ";
                $num_list = $this->mod->select($sql, array($this->actInfo['id'], 'number_set'));

                $this->obj['code'] = 100;
                $this->obj['num_list'] = $num_list;
                $this->output();
                break;
            case 'add':
                /* 後端驗證 */
                /* 未輸入 */
                if(empty($_POST['send']['number_periods']) || empty($_POST['send']['lottery_date'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入期數或開獎日期';
                    $this->output();
                }

                /* 驗證日期合法性 */
                $chk_lottery_date = $this->isDate($_POST['send']['lottery_date']);
                if(!$chk_lottery_date) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期格式，錯誤!!';
                    $this->output();
                }

                /* 驗證開獎號碼 有輸入才驗證 */
                if($_POST['send']['winning_numbers']) {
                    /* 有非數字根逗號 */
                    if (!preg_match('/^\d{2},\d{2},\d{2},\d{2},\d{2},\d{2}$/', $_POST['send']['winning_numbers'])) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = '開獎号碼，請確認輸入格式是否正確!！';
                        $this->output();
                    }

                    $winning_numbers = explode(',', $_POST['send']['winning_numbers']); /* 開獎號碼 */
                    $winning_numbers_len = count($winning_numbers); /* 開獎號碼長度 */

                    $num_re = array_flip(array_flip($winning_numbers)); /* 反轉陣列 */
                    $num_re_len = count($num_re); /* 長度 */

                    /**
                     * 逗號之間有空白
                     * 數字大小 01~45 之間
                     */
                    foreach ($winning_numbers as $v) {
                        $num = (int) $v;
                        if($v === '') {
                            $this->obj['code'] = 404;
                            $this->obj['title'] = '系統提示';
                            $this->obj['msg'] = '開獎号碼，請確認輸入格式是否正確!！!';
                            $this->output();
                        } else if ($num > 45 || $num <= 0) {
                            $this->obj['code'] = 404;
                            $this->obj['title'] = '系統提示';
                            $this->obj['msg'] = '開獎号碼，請輸入介於01~45号';
                            $this->output();
                        }
                    }

                    /**
                     * 驗證是否超過六個號碼 或是 低於六個號碼
                     * 號碼是否有重複
                     */
                    if ($winning_numbers_len > 6 || $winning_numbers_len < 6) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = '開獎號碼 輸入超過六個号碼，或不足六個号碼';
                        $this->output();
                    } else if ($winning_numbers_len != $num_re_len) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = '開獎号碼，不能重複';
                        $this->output();
                    }
                }
                /** 後端驗證 End */

                /* 驗證期數 是否有重複 */
                $chk_num = $this->chk_number_periods($_POST['send']['number_periods']);
                if(!empty($chk_num)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '期數不能重複!!';
                    $this->output();
                }
                /* 驗證期數 End ------*/

                /* 驗證開獎日期 是否有重複 */
                $lottery_date = $_POST['send']['lottery_date']. ' '.'18:00:00';
                $chk_date = $this->chk_lottery_date($lottery_date);
                if(!empty($chk_date)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期不能重複!!';
                    $this->output();
                } else if(strtotime($lottery_date) <= time()) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期 須大於現在時間！!';
                    $this->output();
                }
                /* 驗證開獎日期 End ------*/

                /* 新增期數 */
                $editor_data = array(
                    'add_adm' => $editor, /* 新增人員 */
                    'mod_adm' => '' /* 最後編輯人員  預設是空的 */
                );

                $number_open = !empty($_POST['send']['winning_numbers']) ? '1' : '0'; /* 是否有key 號碼 */
                $data = array(
                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                        'param1' => 'number_set', /* 期數設定 參數 */
                        'param2' => $_POST['send']['number_periods'], /* 期數 */
                        'date1' => $lottery_date, /* 開獎日期 */
                        'descr1' => $_POST['send']['winning_numbers'], /* 開獎號碼 */
                        'status1' => $number_open,  /* 是否有key 號碼 1=已key 0=未key*/
                        'descr2' => json_encode($editor_data) /* 新增人員 */
                );

                $code = $this->insert_data('act_evt', $data);
                if($code == 100) {
                    $this->obj['code'] = 100;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增期數成功';
                    $this->output();
                } else if($code == 400) {
                    $this->obj['code'] = 400;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增期數失敗';
                    $this->output();
                }
                break;
            case 'mod':
                if(empty($_POST['send']['number_periods']) || empty($_POST['send']['lottery_date'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入期數或開獎日期';
                    $this->output();
                }

                /* 驗證 開獎期數 是否有重複 */
                $sql = "
                        SELECT
                              `param2` `number_periods` -- 期數
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `param2` = ? AND NOT
                              `id` = ? 
                        LIMIT 1
		        ";
                $number_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $_POST['send']['number_periods'], $_POST['id']));

                if(!empty($number_periods)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '期數不能重複!!';
                    $this->output();
                }

                /* 驗證 開獎日期 是否有重複 */
                $lottery_date = $_POST['send']['lottery_date']. ' '.'18:00:00';
                $sql = "
                        SELECT
                              `date1` `lottery_date` -- 日期
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `date1` = ? AND NOT
                              `id` = ? 
                        LIMIT 1
		        ";
                $number_date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $lottery_date, $_POST['id']));

                if(!empty($number_date)) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '開獎日期不能重複!!';
                    $this->output();
                }

                /**
                 * 假如當下開獎日期 一樣可以更新 只要不去更改開獎日期的話
                 * 如果有更動日期的話 就不能更改之前的日期
                 */
                $sql = "
                        SELECT
                              `date1` `lottery_date` -- 日期
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `id` = ? 
                        LIMIT 1
		        ";
                $edit_date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $_POST['id']));

                $originData = $edit_date['0']['lottery_date'];
                /* 判斷如果修改開獎日期的話 */
                if($originData != $lottery_date) {
                    if(strtotime($lottery_date) <= time()) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = '開獎日期 須大於現在時間！!';
                        $this->output();
                    }
                }

                /* 如果修改的期數 會員有轉修改的期數 並一併修改期數 */
                $sql = "
                        SELECT
                              `param2` `number_periods` -- 期數
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `id` = ? 
                        LIMIT 1
		        ";
                $edit_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $_POST['id']));

                $origi_periods = $edit_periods['0']['number_periods'];
                if($origi_periods != $_POST['send']['number_periods']) {
                    $this->mod->modi_by('act_evt',
                        array(
                            'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                            'param1'  => 'turn_number', /* 轉號碼參數 */
                            'param2'  =>  $origi_periods, /* 兌獎期數 */
                            'status1' => '1' /* 未開獎參數 */
                        ),
                        array(
                            'param2'  => $_POST['send']['number_periods'], /* 期數 */
                        )
                    );
                }
                /* 如果修改的期數 會員有轉修改的期數 並一併修改期數  END*/

                $edit_num_data = $this->edit_num_data($_POST['id']);
                foreach ($edit_num_data as $k=>$v) {
                    $add_adm = $v['add_adm']; /* 新增人員 */
                    $v['mod_adm'] = $editor;

                    $edit_data = array(
                        'add_adm' => $add_adm,
                        'mod_adm' => $editor
                    );
                }

                /* 修改期數  */
                $number_open = !empty($_POST['send']['winning_numbers']) ? '1' : '0'; /* 是否有key 號碼 */
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $_POST['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'number_set' /* 期數參數 */
                    ),
                    array(
                        'param2'  => $_POST['send']['number_periods'], /* 期數 */
                        'descr1'  => $_POST['send']['winning_numbers'], /* 開獎號碼 */
                        'date1'   => $lottery_date, /* 開獎日期 */
                        'status1' => $number_open,
                        'descr2'  => json_encode($edit_data)
                    )
                );
                /* 修改期數 END  */

                /* 如果有key 號碼 就開始比對 */
                if($_POST['send']['winning_numbers']) {
                    $this->lottery_ticket_bingo($_POST['send']['number_periods'], $_POST['send']['winning_numbers']);
                }

                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '已更新成功';
                $this->output();
                break;
            default:
                $this->add_error('number_set', '404', '期數設定-型態錯誤');
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

    /* 最後編輯期數的人員 */
    public function edit_num_data($id) {
        $sql = "
                SELECT
                      `descr2` `edit_num` -- 編輯人員
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `id` = ? 
                LIMIT 1
		        ";
        $edit_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $id));

        $out_data = array();
        foreach ($edit_periods as $k=>$v) {
            $tmp = json_decode($v['edit_num'], true); /* 解析 編輯人員 字串 */

            $data['add_adm'] = $tmp['add_adm']; /* 新增人員 */
            $data['mod_adm'] = $tmp['mod_adm']; /* 編輯人員 */

            $out_data[] = $data;
        }
        return $out_data;
    }

    /* 抓取要比對的期數 寫入開獎結果 */
    public function lottery_ticket_bingo($number_periods, $winning_numbers){
        if(empty($number_periods) || empty($winning_numbers)) return false;

        $sql = "
                SELECT
                      `id`,
                      `account` `turn_acc`, -- 會員帳號
                      `param2` `order_per`, -- 兌換期數
                      `descr1` `turn_num` -- 轉出號碼
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `param2` = ?
                ";

        $sql .= 'ORDER BY `itime` ASC';
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $number_periods));

        foreach ($mem_turn_list as $k=>$v) {
            $money = $this->get_bingo_money($winning_numbers, $v['turn_num']);
            if($money > 0) {
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $v['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $v['turn_acc'], /* 會員帳號 */
                        'param1'  => 'turn_number', /* 轉出號碼參數 */
                        'param2'  => $number_periods /* 期數 */
                    ),
                    array(
                        'param3'  => '中獎', /* 兌獎結果 */
                        'param5'  => $money, /* 可獲彩金 */
                        'status1' => '3' /* 3 = 已中獎 */
                    )
                );
            } else if($money == 0){
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $v['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'account' => $v['turn_acc'], /* 會員帳號 */
                        'param1'  => 'turn_number', /* 轉出號碼參數 */
                        'param2'  => $number_periods /* 期數 */
                    ),
                    array(
                        'param3'  => '不中獎', /* 兌獎結果 */
                        'param5'  => '不中獎', /* 可獲彩金 */
                        'status1' => '2' /* 2 = 未中獎 */
                    )
                );
            }
        }
    }

    /* 會員 兌換獎 設定 */
    public function mem_ticket_set() {
        if(!isset($_POST['type'])){
            $this->add_error('mem_ticket_set', '404', '會員兌換獎-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $editor = $this->getEmployee(); /* 取得登入資訊 */
        switch ($_POST['type']) {
            case 'qry':
                $sql = "
                        SELECT
                              `id`,
                              `account`, -- 會員帳號
                              `descr1` `remark`, -- 備註
                              `amount3` `ticket_num`,	 -- 獲得兌獎卷數量
                              `itime` `ticket_add_time` -- 兌獎卷新增時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `status1` = ?
                ";
                $ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket', '1'));

                $this->obj['code'] = 100;
                $this->obj['ticket_list'] = $ticket_list;
                $this->output();
                break;
            case 'add':
                /* 未輸入 */
                if(empty($_POST['send']['account']) || empty($_POST['send']['ticket_num']) || empty($_POST['send']['remark'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入會員賬號或兌獎卷數量或備註';
                    $this->output();
                }

                /* 判斷是否 有此會員 */
                $chk_acc = $this->get_acc($_POST['send']['account']);
                if($chk_acc == 400 || $chk_acc['code'] == 200) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '查无您填写的会员帐号，请重新确认！';
                    $this->output();
                }

                /* 判斷是否 非越南迪拜的會員 */
                if(isset($chk_acc['code']) && $chk_acc['code'] == 100) {
                    if($chk_acc['id_country'] != 2) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = '查无您填写的会员帐号，请重新确认！';
                        $this->output();
                    }
                }

                /* 兌獎卷數量 有非數字 */
                if(!preg_match("/^\d*$/", $_POST['send']['ticket_num'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '兌換卷數量，請輸入數字';
                    $this->output();
                } else if($_POST['send']['ticket_num'] <= 0) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '兌換卷數量，不能低於0';
                    $this->output();
                }

                $editor_data = array(
                    'add_adm' => $editor, /* 新增人員 */
                    'mod_adm' => '' /* 最後編輯人員  預設是空的 */
                );
                $data =  array(
                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                    'account' => $chk_acc['acc'], /* 會員帳號 */
                    'param1' => 'mem_ticket', /* 會員兌換數量 參數 */
                    'param2' => intval($_POST['send']['ticket_num']), /* 未使用彩卷  */
                    'descr1' => $_POST['send']['remark'], /* 備註 */
                    'descr2' => json_encode($editor_data), /* 新增人員 */
                    'amount1' => '', /* 每日最高存款金額 */
                    'amount3' => $_POST['send']['ticket_num'], /* 兌獎卷數量 */
                    'status1' => '1'
                );

                $code = $this->insert_data('act_evt', $data);
                if($code == 100) {
                    $this->obj['code'] = 100;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增兌獎卷成功';
                    $this->output();
                } else if($code == 400) {
                    $this->obj['code'] = 400;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增兌獎卷失敗';
                    $this->output();
                }

                break;
            case 'mod':
                /* 未輸入 */
                if(empty($_POST['send']['ticket_num']) || empty($_POST['send']['remark'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入會員賬號或兌獎卷數量或備註';
                    $this->output();
                }

                /* 兌獎卷數量 有非數字 */
                if(!preg_match("/^\d*$/", $_POST['send']['ticket_num'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '兌換卷數量，請輸入數字';
                    $this->output();
                } else if($_POST['send']['ticket_num'] <= 0) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '兌換卷數量，不能低於0';
                    $this->output();
                }

                /* 判斷 如果 已過期 則無法修改 */
                $today = date("Y-m-d",time()); /* 今天 */
                $ticket_add_time = $this->chang_time($_POST['ticket_add_time'] , '+14', 'day');
                $ticket_add = substr($ticket_add_time, 0,10);

                if($ticket_add < $today) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '兌換卷已過期，無法修改';
                    $this->output();
                }

                /* 判斷不能低於原本的數量 兌獎卷數量 */
                $sql = "
                        SELECT
                              `param2` `no_use_ticket`, -- 未使用兌獎卷數量
                              `amount3` `ticket_num`	 -- 獲得兌獎卷數量
                        FROM
                              `act_evt`
                        WHERE
                              `id` = ? AND
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `status1` = ?
                        LIMIT 1
                ";
                $ticket_list = $this->mod->select($sql, array($_POST['id'], $this->actInfo['id'], 'mem_ticket', '1'));
                $ticket_num = $ticket_list['0']['ticket_num'];
                if($ticket_num > $_POST['send']['ticket_num'] ) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '不能低於原本兌獎卷數量';
                    $this->output();
                }
                /* 判斷不能低於原本的數量 兌獎卷數量 End*/

                $edit_ticket_data = $this->edit_ticket_data($_POST['id']);
                foreach ($edit_ticket_data as $k=>$v) {
                    $add_adm = $v['add_adm']; /* 新增人員 */
                    $v['mod_adm'] = $editor;

                    $edit_data = array(
                        'add_adm' => $add_adm,
                        'mod_adm' => $editor /* 最後編輯人員 */
                    );
                }

                $no_use_ticket = $ticket_list['0']['no_use_ticket']; /* 未使用卷數 */
                $num = $_POST['send']['ticket_num'] - $ticket_num; /* 更新數量 減去原本數量 */
                $new_ticket = $no_use_ticket + $num; /* 最新的未使用兌獎卷樹數量 */
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $_POST['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'mem_ticket' /* 免費兌換卷參數 */
                    ),
                    array(
                        'descr1'  => $_POST['send']['remark'], /* 備註 */
                        'descr2'  => json_encode($edit_data), /* 最後編輯人員 */
                        'param2'  => $new_ticket, /* 未使用兌獎卷數量 */
                        'amount3'   => $_POST['send']['ticket_num'] /* 兌換獎數量 */
                    )
                );

                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '已更新成功';
                $this->output();
                break;
            default:
                $this->add_error('mem_ticket_set', '404', '會員兌換獎-型態錯誤');
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

    /* 最後編輯會員兌獎卷的人員 */
    public function edit_ticket_data($id) {
        $sql = "
                SELECT
                      `descr2` `edit_ticket` -- 編輯人員
                FROM
                      `act_evt`
                WHERE
                      `id` = ? AND
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `status1` = ?
                ";
        $ticket_list = $this->mod->select($sql, array($id, $this->actInfo['id'], 'mem_ticket', '1'));

        $out_data = array();
        foreach ($ticket_list as $k=>$v) {
            $tmp = json_decode($v['edit_ticket'], true); /* 解析 編輯人員 字串 */

            $data['add_adm'] = $tmp['add_adm']; /* 新增人員 */
            $data['mod_adm'] = $tmp['mod_adm']; /* 編輯人員 */

            $out_data[] = $data;
        }
        return $out_data;
    }

    /* 跑馬燈設定 */
    public function marquee_set() {
        if(!isset($_POST['type'])){
            $this->add_error('marquee_set', '404', '跑馬燈設定-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            case 'qry':
                $marquee_list = $this->marquee_list(); /* 取得跑馬燈資料 */

                $this->obj['code'] = 100;
                $this->obj['marquee_list'] = $marquee_list;
                $this->output();
                break;
            case 'add':
                /* 未輸入 */
                if(empty($_POST['winning_member']) || empty($_POST['winning_num'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入會員帳號或中獎金額';
                    $this->output();
                }

                /* 2017/12/21  行銷要求 新增跑馬燈 不需要驗證 真實會員帳號 */
                /* 判斷是否 有此會員 */
                /*$chk_acc = $this->get_acc($_POST['winning_member']);
                if($chk_acc == 400 || $chk_acc['code'] == 200) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '查无您填写的会员帐号，请重新确认！';
                    $this->output();
                }
                if(isset($chk_acc['code']) && $chk_acc['code'] == 100) {
                    if($chk_acc['id_country'] != 2) {
                        $this->obj['code'] = 404;
                        $this->obj['title'] = '系統提示';
                        $this->obj['msg'] = '查无您填写的会员帐号，请重新确认！';
                        $this->output();
                    }
                }*/
                /* 判斷是否 非越南迪拜的會員 */

                /* 新增會員跑馬燈 */
                $data =  array(
                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                    'account' => $_POST['winning_member'], /* 會員帳號 */
                    'param1' => 'winning', /* 會員中獎金額 參數 */
                    'amount3' => $_POST['winning_num'] /* 中獎金額 */
                );

                $code = $this->insert_data('act_evt', $data);
                if($code == 100) {
                    $this->obj['code'] = 100;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增成功';
                    $this->output();
                } else if($code == 400) {
                    $this->obj['code'] = 400;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '新增失敗';
                    $this->output();
                }
                break;
            case 'mod':
                /* 未輸入 */
                if(empty($_POST['winning_member']) || empty($_POST['winning_num'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '妳未輸入會員帳號或中獎金額';
                    $this->output();
                }

                /* 修改 */
                $this->mod->modi_by('act_evt',
                    array(
                        'id' => $_POST['id'], /* 主鍵 */
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'winning' /* 跑馬燈參數 */
                    ),
                    array(
                        'amount3'   => $_POST['winning_num'] /* 中獎金額 */
                    )
                );

                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '已更新成功';
                $this->output();
                break;
            case 'del':
                $id = $_POST['id'];

                $this->mod->del_by_id('act_evt', $id, 'id');
                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '刪除成功';
                $this->output();
                break;
            default :
                $this->add_error('marquee_set', '404', '跑馬燈設定-型態錯誤');
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

    /**
     * 會員查詢 兌獎卷 兌獎號碼 單個會員查詢
     * 參加會員 兌獎卷 兌獎號碼 用日期查詢
     */
    public function get_mem_list() {
        if(!isset($_POST['type'])){
            $this->add_error('get_mem_list', '404', '會員資料型態-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            /* 用帳號查詢 兌獎卷 */
            case 'voucher_inquiry':
                $sql = "
                        SELECT
                              `account` `acc_deposit`, -- 會員帳號
                              `descr1` `remark`, -- 備註
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `amount1` `max_deposit`, -- 每日最高存款金額
                              `amount3` `ticket_num`,	 -- 獲得兌獎卷數量
                              `itime` `ticket_add_time`, -- 兌獎卷新增時間
                              `status1` -- 判斷是手動新增還是排程新增
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? 
                ";

                /* 帳號查詢 */
                if(isset($_POST['acc'])) {
                    $sql .= 'AND `account` = "'.$_POST['acc'].'"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket'));

                $today = date("Y-m-d",time()); /* 今天 */

                $mem_list = array();
                $num_effective = 0;
                foreach ($mem_ticket_list as $key=>$value) {

                    /* 如果是手動新增  每日最高存款金額 就顯示空值 */
                    if($value['status1'] == '1') {
                        $value['max_deposit'] = '';
                    }

                    /* 每日最高存款金額  */
                    $max_deposit = ($value['max_deposit'] != '')? $max_deposit = number_format($value['max_deposit']):'';

                    /* 有效日期 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+14' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    /* 累計有效票卷數 */
                    $num = ($effective_date > $today)?$num_effective += (int) $value['no_use_ticket']:$num_effective;

                    $data = array(
                        'acc_deposit' => $value['acc_deposit'], /* 會員帳號 */
                        'remark' => $value['remark'], /* 備註 */
                        'max_deposit' => $max_deposit, /* 每日最高存款金額 */
                        'ticket_num' => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'ticket_add_time' => $value['ticket_add_time'], /* 兌獎卷新增時間 */
                        'effective_date' => $effective_date, /* 有效日期 */
                        'no_use_ticket' => $value['no_use_ticket'], /* 未使用彩卷數量 */
                        'cumulatively_effective' => $num /* 累計有效票卷數 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            /* 用帳號查詢兌獎號碼 */
            case 'duijiang_number':
                $sql = "
                        SELECT
                              `account` `turn_acc`, -- 會員帳號
                              `param2` `order_per`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `param5` `receive_bonus`, -- 可獲彩金
                              `descr1` `turn_num`, -- 轉出號碼
                              `itime` `turn_time` -- 轉出時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? 
                ";

                /* 帳號查詢 */
                if(isset($_POST['acc'])) {
                    $sql .= 'AND `account` = "'.$_POST['acc'].'"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number'));

                $turn_num = array();
                foreach ($mem_turn_list as $key=>$value) {
                    /* 如果是數字 就顯示 數字分位符號 */
                    if(is_numeric($value['receive_bonus'])) {
                        $value['receive_bonus'] = number_format($value['receive_bonus']);
                    }
                    $data = array(
                        'turn_acc' => $value['turn_acc'], /* 會員帳號 */
                        'order_per' => $value['order_per'], /* 兌換期數 */
                        'result' => $value['result'], /* 兌獎結果 */
                        'receive_bonus' => $value['receive_bonus'], /* 可獲彩金 */
                        'turn_num' => $value['turn_num'], /* 轉出號碼 */
                        'turn_time' => $value['turn_time'] /* 轉出時間 */
                    );
                    $turn_num[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $turn_num;
                $this->output();
                break;
            /* 用日期搜尋 兌獎卷 */
            case 'voucher_inquiry_date':
                $sql = "
                        SELECT
                              `account` `acc_deposit`, -- 會員帳號
                              `descr1` `remark`, -- 備註
                              `param2` `no_use_ticket`, -- 未使用彩卷
                              `amount1` `max_deposit`, -- 每日最高存款金額
                              `amount3` `ticket_num`,	 -- 獲得兌獎卷數量
                              `itime` `ticket_add_time`, -- 兌獎卷新增時間
                              `status1` -- 判斷是手動新增還是排程新增
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND
                              `param1` = ?
                ";

                /* 日期查詢 */
                if(isset($_POST['ticket_date'])) {
                    $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket'));

                $today = date("Y-m-d",time()); /* 今天 */
                $expired = $this->chang_time($today, '-14', 'day');
                $mem_list = array();
                foreach ($mem_ticket_list as $key=>$value) {
                    /* 如果是手動新增  每日最高存款金額 就顯示空值 */
                    if($value['status1'] == '1') {
                        $value['max_deposit'] = '';
                    }

                    /* 每日最高存款金額  */
                    $max_deposit = ($value['max_deposit'] != '')? $max_deposit = number_format($value['max_deposit']):'';

                    /* 有效日期 */
                    $effective_date_time = $this->chang_time($value['ticket_add_time'], '+14' , 'day');
                    $effective_date = substr($effective_date_time, '0', '10');

                    $sql_2 = "
                              SELECT
                                    SUM(`param2`) AS `no_use_ticket` -- 未使用彩卷
                              FROM
                                    `act_evt`
                              WHERE
                                    `act_id`= ? AND
                                    `param1`= ? AND
                                    `itime` <= '".$value['ticket_add_time']."' AND
                                    `itime` > '".$expired."' AND
                                    `account` = '".$value['acc_deposit']."'
                              GROUP BY 
                                    `account`";
                    $effective_list = $this->mod->select($sql_2, array($this->actInfo['id'], 'mem_ticket'));
                    $num_effective = !empty($effective_list)?$effective_list['0']['no_use_ticket']:0; /* 累計有效票卷數 */

                    $data = array(
                        'acc_deposit' => $value['acc_deposit'], /* 會員帳號 */
                        'remark' => $value['remark'], /* 備註 */
                        'max_deposit' => $max_deposit, /* 每日最高存款金額 */
                        'ticket_num' => $value['ticket_num'], /* 獲得兌獎卷數量 */
                        'ticket_add_time' => $value['ticket_add_time'], /* 兌獎卷新增時間 */
                        'no_use_ticket' => $value['no_use_ticket'], /* 未使用彩卷數量 */
                        'effective_date' => $effective_date, /* 有效日期 */
                        'cumulatively_effective' => $num_effective /* 累計有效票卷數 */
                    );
                    $mem_list[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $mem_list;
                $this->output();
                break;
            /* 用日期搜尋 兌獎號碼 */
            case 'duijiang_number_date':
                $sql = "
                        SELECT
                              `account` `turn_acc`, -- 會員帳號
                              `param2` `order_per`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `param5` `receive_bonus`, -- 可獲彩金
                              `descr1` `turn_num`, -- 轉出號碼
                              `itime` `turn_time` -- 轉出時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? 
                ";

                /* 日期查詢 */
                if(isset($_POST['ticket_date'])) {
                    $sql .= 'AND `itime` LIKE "'.$_POST['ticket_date'].'%"';
                }

                $sql .= 'ORDER BY `itime` ASC';
                $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number'));

                $turn_num = array();
                foreach ($mem_turn_list as $key=>$value) {
                    /* 如果是數字 就顯示 數字分位符號 */
                    if(is_numeric($value['receive_bonus'])) {
                        $value['receive_bonus'] = number_format($value['receive_bonus']);
                    }
                    $data = array(
                        'turn_acc' => $value['turn_acc'], /* 會員帳號 */
                        'order_per' => $value['order_per'], /* 兌換期數 */
                        'result' => $value['result'], /* 兌獎結果 */
                        'receive_bonus' => $value['receive_bonus'], /* 可獲彩金 */
                        'turn_num' => $value['turn_num'], /* 轉出號碼 */
                        'turn_time' => $value['turn_time'] /* 轉出時間 */
                    );
                    $turn_num[] = $data;
                }

                $this->obj['code'] = 100;
                $this->obj['list'] = $turn_num;
                $this->output();
                break;
        }
    }

    /* 抓取會員轉過的所有期數  未兌獎*/
    public function get_mem_turn_periods() {
        $sql = "
                SELECT
                      `account` `turn_acc`, -- 會員帳號
                      `param2` `order_per`, -- 兌換期數
                 FROM
                       `act_evt`
                 WHERE
                       `act_id` = ? AND 
                       `param1` = ? AND 
                       `status1` = ?
                ";
        $mem_turn_periods = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', '1'));

        $order_per = array();
        foreach ($mem_turn_periods as $k=>$v) {
            $pass_mem[$v['order_per']] = $v;
        }
        return $order_per;
    }

    /* 存款區間 */
    public function get_deposit_range() {
        if(!isset($_POST['type'])){
            $this->add_error('get_deposit_range', '404', '存款區間-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            case 'qry':
                /* 判斷 有沒有存款區間 資料 */
                $chk_deposit = $this->mod->get_by('act_evt',
                    array(
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'deposit_range' /* 期數設定 參數 */
                    ), null, '1');

                if(empty($chk_deposit)) {
                    $this->mod->add_by('act_evt',
                        array(
                            'act_id' => $this->actInfo['id'], /* 活動代碼 */
                            'param1' => 'deposit_range', /* 存款區間 參數 */
                            'date1' => $this->actInfo['start_time'], /* 開始時間 */
                            'date2' =>  $this->actInfo['end_time']/* 結束時間 */
                        ));
                }

                /* 取得計算存款開始時間 */
                $deposit_range = $this->get_deposit_day();

                $this->obj['code'] = 100;
                $this->obj['list'] = $deposit_range;
                $this->output();
                break;
            case 'mod':
                if(empty($_POST['send']['deposit_end'])) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統錯誤';
                    $this->obj['msg'] = '傳入資料錯誤';
                    $this->output();
                }

                /* 驗證日期合法性 */
                $chk_deposit_end = $this->isDate($_POST['send']['deposit_end']);
                if(!$chk_deposit_end) {
                    $this->obj['code'] = 404;
                    $this->obj['title'] = '系統提示';
                    $this->obj['msg'] = '結束時間格式，錯誤!!';
                    $this->output();
                }

                $this->mod->modi_by('act_evt',
                    array(
                        'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                        'param1'  => 'deposit_range' /* 存款參數 */
                    ),
                    array(
                        'date2'   => $_POST['send']['deposit_end'], /* 存款結束日期 */
                    )
                );

                $this->obj['code'] = 100;
                $this->obj['title'] = '系統提示';
                $this->obj['msg'] = '已更新成功';
                $this->output();
                break;
            default:
                $this->add_error('get_deposit_range', '404', '存款區間-型態錯誤');
                $this->obj['code'] = 404;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '傳入資料錯誤';
                $this->output();
                break;
        }
    }

    /* 中獎會員資料 */
    public function get_bingo_mem_data() {
        if(!isset($_POST['type'])) {
            $this->add_error('get_bingo_mem_data', '404', '中獎會員資料型態-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        switch ($_POST['type']) {
            /* 未派獎 */
            case 'not_yet':
                $sql = "
                        SELECT
                              `account` `bingo_acc`, -- 會員帳號
                              `param2` `mem_num`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `param5` `receive_bonus`, -- 可獲彩金
                              `descr1` `turn_num`, -- 轉出號碼
                              `itime` `turn_time` -- 轉出時間
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND 
                              `param1` = ? AND 
                              `status1` = ? 
                ";
                $not_yet_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', '3'));

                $this->obj['code'] = 100;
                $this->obj['bingo_list'] = $not_yet_list;
                $this->output();
                break;
            /* 已派獎 */
            case 'yet':
                $sql = "
                        SELECT
                              `account` `bingo_acc`, -- 中獎會員帳號
                              `param2` `mem_num`, -- 兌換期數
                              `param3` `result`, -- 兌獎結果
                              `amount3` `receive_bonus`, -- 可獲彩金
                              `date1` `distribute_time`, -- 派獎時間
                              `date2` `turn_time`, -- 轉出時間
                              `descr1` `turn_num` -- 轉出號碼
                        FROM
                              `act_evt`
                        WHERE
                              `act_id` = ? AND
                              `param1` = ? AND
                              `status1` = ?
                ";
                $yet_list = $this->mod->select($sql, array($this->actInfo['id'], 'bingo_mem', '1'));

                $this->obj['code'] = 100;
                $this->obj['bingo_list'] = $yet_list;
                $this->output();
                break;
        }
    }

    /* 驗證期數 是否有重複 */
    public function chk_number_periods($number_periods) {
      if(empty($number_periods)) return false;

        $chk_num = $this->mod->get_by('act_evt',
            array(
                'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                'param1'  => 'number_set', /* 期數設定 參數 */
                'param2' => $number_periods /* 期數 */
            ), null, '1');
        return $chk_num;
    }

    /* 驗證開獎日期 是否有重複 */
    public function chk_lottery_date($lottery_date) {
        if(empty($lottery_date)) return false;

        $chk_date = $this->mod->get_by('act_evt',
            array(
                'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                'param1'  => 'number_set', /* 期數設定 參數 */
                'date1' => $lottery_date /* 開獎日期 */
            ), null, '1');
        return $chk_date;
    }

    /* 驗證日期合法性 */
    public function isDate($str) {
        if(!preg_match("/^(\d{4})[-](\d{1,2})[-](\d{1,2})$/", $str)) return false;

        $__y = substr($str, 0, 4);
        $__m = substr($str, 5, 2);
        $__d = substr($str, 8, 2);

        return checkdate($__m, $__d, $__y);
    }

    /**
     * 計算會員得獎金額
     * 特別獎 六個號碼 30,000,000
     * 頭獎 五個號碼 8,000,000
     * 二獎 四個號碼 600,000
     * 三獎 三個號碼 100,000
     */
    private function get_bingo_money($winning_numbers, $user_winning_numbers) {
        $money = 0;

        /* 解析開獎號碼 */
        $normal = explode(',', $winning_numbers);
        if (count($normal) != 6) {
            return $money;
        }

        /* 解析會員轉出號碼 */
        $user_normal = explode(',', $user_winning_numbers);
        if (count($user_normal) != 6) {
            return $money;
        }

        $bingo_normal = array_intersect($normal, $user_normal);

        /* 無中獎 */
        if (count($bingo_normal) < 3) {
            return $money;
        }

        if (count($bingo_normal) == 6) {
            $money = 30000000; /* 特別獎 */

        } else if (count($bingo_normal) == 5) {
            $money = 6800000; /* 頭獎 */

        } else if (count($bingo_normal) == 4) {
            $money = 388000; /* 二獎 */

        } else if(count($bingo_normal) == 3) {
            $money = 58000; /* 三獎 */

        }
        return $money;
    }
}
