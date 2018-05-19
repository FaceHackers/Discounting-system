<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
@ini_set('display_errors', 1);
class dubailott extends web_mem {
	protected $isfront = true;
	private $is_pass = false;
	private $act_id = '001575';	// 活動代碼

    /* 宣告單日最高存款金額 可獲得彩票兌獎卷 */
    public $ticketNum = array(
            200000 => 1,
            1000000 => 2,
            10000000 => 5,
            50000000 => 10
    );

	function __construct(){
		parent::__construct();
		#若不在活動時間內則跳錯誤訊息
		if (!$this->chk_act_date($this->act_id) && $this->isfront) {
			echo '<script>
					alert("'.$this->gdata["msg"].'");
					document.location.href="'.$this->actInfo['os_link'].'";
			  	  </script>';
			die();
		}

		$this->gdata['act_id']           = $this->actInfo['id'];						// 活動代碼
		$this->gdata['act_name']         = $this->actInfo['name'];						// 活動名稱
		$this->gdata['back_act_id']      = $this->actInfo['back_act_id'];				// 優惠存入編號(迪拜用)
		$this->gdata['com_name']         = $this->actInfo['com_name'];					// 娛樂城名稱
		$this->gdata['com_id']           = $this->actInfo['com_id'];					// 娛樂城id
		$this->gdata['comp']             = $this->actInfo['comp'];						// 娛樂城縮寫(活動call以前的api用)
		$this->gdata['api_id']           = $this->actInfo['api_id'];					// call api用
		$this->gdata['api_code']         = $this->actInfo['api_code'];					// call api用
		$this->gdata['folder']           = $this->actInfo['folder'];					// 各娛樂城資料夾名
		$this->gdata['start_time']       = $this->actInfo['start_time'];				// 活動開始時間
		$this->gdata['end_time']         = $this->actInfo['end_time'];					// 活動結束時間
		$this->gdata["act_ctrl"]         = $this->actInfo['act_ctrl'];					// 活動controller名稱
		$this->gdata['act_title']        = $this->actInfo['act_title'];					// 活動title
		$this->gdata['meta_key']         = $this->actInfo['meta_key'];					// 活動keywords
		$this->gdata['meta_des']         = $this->actInfo['meta_des'];					// 活動description
		$this->gdata['google_analytics'] = $this->actInfo['google_analytics'];			// Google分析碼
		$this->gdata['os_link']          = $this->actInfo['os_link'];					// 官網連結
		$this->gdata['cs_link']          = $this->actInfo['cs_link'];					// 在線客服連結

		$this->gdata['burl'] = $this->burl.$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/';
		$this->gdata['furl'] = $this->furl.$this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/';

		$map = array(
			'index'                     => 1, /* 前台首頁 */
			'login'                     => 1, /* 前台登入 */
			'toView'                    => 1, /* 切換頁面 */
            'mem_deposit_day_ticket'    => 1, /* 更新兌獎卷數量 存款可獲得對應的兌獎卷 */
            'get_last_lottery'          => 1, /* 前台 倒數計時用 */
            'get_marquee_list'          => 1, /* 跑馬燈資料 */
            'use_ticket'                => 1,  /* 使用兌換卷數  */
            'use_mem_ticket'            => 1, /* 扣除使用卷數 */
            'get_periods_data'          => 1, /* 期數查詢 */
            'get_ticket_date'           => 1, /* 搜尋 兌獎卷歷史紀錄 預設日期 為 活動開始跟活動結束*/
            'get_mem_history_turn_num'  => 1, /* 轉號碼歷史紀錄 顯示有轉過的日期 */
            'get_mem_turn_history'      => 1, /* 顯示轉號碼 歷史紀錄清單 */
            'get_ticket_story'          => 1, /* 顯示兌獎卷歷史 清單 */
            'get_last_data'             => 1, /* 會員轉號碼 最新的日期 */
            'get_ticket_story_month'    => 1 /* 兌獎卷歷史紀錄 預設一個月 */
		);
		$map_class = $this->router->fetch_class();
		if ($map_class == $this->actInfo['act_ctrl']) {
			if(array_key_exists($this->router->fetch_method(), $map)){
				$this->is_pass = true;
			} else {
				$acess = $this->session->userdata('acess');
				if($acess!="" && $acess!=null){
					$chk = $this->libc->aes_de($acess);
					$chks = explode("*", $chk);
					if(count($chks)==5){
						$this->acc = $chks[3];
						$this->gdata["acc"] = $chks[3];
						$now = time();
						$ctime = intval($chks[0]);
						if(($now-$ctime) < 6000){
							$this->is_pass = true;
							$code = $now."*".$chks[1]."*".$chks[2]."*".$chks[3]."*";
							$acess = $this->libc->aes_en($code);
							$this->session->set_userdata("acess", $acess);
						}
					}
				}
			}
			if($this->is_pass==false){
				$this->output();
			}
		}
	}

	/* 首頁 */
	public function index(){
	    $this->get_periods(); /* 期數紀錄 */
        $this->gdata['ticket_start'] = substr($this->actInfo['start_time'],0,10);
        $this->gdata['ticket_end'] = substr($this->actInfo['end_time'],0,10);

		$this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/index');
	}

	/* 切換頁面 */
	public function toView($page){
		$this->obj['code'] = 100;
		$this->obj['page'] = $page;
		$this->obj['view'] = urlencode($this->get_view($this->actInfo['folder'].'/'.$this->actInfo['act_ctrl'].'/'.$page, true));
		$this->output();
	}

	/* 會員登入 */
	public function login($dubai=false){
		if(!isset($_POST['acc'])){
			$this->obj['code'] = 404;
			$this->obj['title'] = '系统错误';
			$this->obj['msg'] = '传入资料错误';
			$this->output();
		}

		$acc = trim($_POST['acc']);
		if (!preg_match('/^[A-Za-z0-9]+$/', $acc)) {
			$this->obj['code'] = 401;
            $this->obj['title'] = 'Thông báo';
            $this->obj['msg'] = 'Tên đăng nhập không chính xác';
            $this->output();
		}

		if (!$dubai) {
			$call = 'http://misc.bcad8.com/api/query/get_mem_by_acc/BBIN/'.$this->actInfo['api_code'].'/'.$acc;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $call);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$call_url = curl_exec($ch);
			curl_close($ch);

			$url = 'http://misc.bcad8.com/api/query/chk_acc_exist/'.$this->actInfo['api_code'].'/'.$acc;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			$get_url = curl_exec($ch);
			curl_close($ch);

			$chk_acc = json_decode($get_url, true);
		} else {
			$chk_acc = $this->dubai_api('getAcc', $acc);
		}

		if(!isset($chk_acc['code'])){
			$this->obj['code'] = 404;
			$this->obj['title'] = 'Thông báo';
			$this->obj['msg'] = 'Có lỗi, vui lòng thông báo cho nhân viên kỹ thuật';
			$this->output();
		}

		switch($chk_acc['code']){
			case 100:
                /* 非迪拜越南 */
                if ($chk_acc['id_country'] != 2) {
                    $this->obj['code'] = 200;
                    $this->obj['title'] = 'Thông báo';
                    $this->obj['msg'] = 'Tên đăng nhập không chính xác';
                    $this->output();
                }
                $acc = $chk_acc['acc'];
                $this->mem_login_list($acc);

				break;
			case 200:
				$this->obj['code'] = 200;
				$this->obj['title'] = '帐号错误';
				$this->obj['msg'] = '查无您填写的会员帐号，请重新确认！';
				break;
			default:
				$this->obj['code'] = 404;
				$this->obj['title'] = '系统错误';
				$this->obj['msg'] = '系統發生錯誤，請通知系統管理員！';
				break;
		}

		$this->output();
	}

	/* 會員登入顯示資訊 */
	public function mem_login_list($acc) {
        $count_ticket = $this->get_mem_tick_qry($acc);

        $this->obj['code'] = 100;
        $this->obj['acc'] = $acc;
        $this->obj['count_ticket'] = $count_ticket;
        $this->output();
    }

    /* 使用兌換卷數  */
    public function use_ticket() {
        /* 判斷 如果沒有期數 */
        $today = date("Y-m-d H:i:s"); /* 今天 */
        $start_time = $this->chang_time($today, '-1', 'hour'); /* 越南時間 台灣減去一小時 */

        $num_list = $this->get_last_lottery_qry($start_time);
        if (empty($num_list)) {
            $this->obj['code'] = 404;
            $this->obj['title'] = 'Thông báo';
            $this->obj['msg'] = 'Hiện tại chưa có mở số kỳ';
            $this->output();
        }
        /* 判斷 如果沒有期數 End*/

        /**
         * 判斷今日是否為開獎日期
         * 檢查時間 是否在 17:00~19:00 越南時間 等於 台灣時間 18:00~20:00 則無法轉號碼
         */
        $chk_date = $this->check_drawing_lottery();
        if (!empty($chk_date)) {
            $today = date("Y-m-d H:i:s", time()); /* 今天 */
            $now_time = $this->chang_time($today, '-1', 'hour'); /* 越南時間 台灣減去一小時 */

            $start_stop = date("Y-m-d 17:00:00", time()); /* 停止運轉時間 開始 */
            $end_stop = date("Y-m-d 19:00:00", time()); /* 停止運轉時間 結束 */

            if ($end_stop >= $now_time && $start_stop <= $now_time) {
                $this->obj['code'] = 404;
                $this->obj['title'] = 'Thông báo';
                $this->obj['msg'] = 'Sắp quay thưởng ! Nên vòng quay tạm ngưng đến 19:00 sẽ vận chuyển lại';
                $this->output();
            }
        }
        /* 檢查時間 是否在 17:00~19:00 越南時間 則無法轉號碼 End */

        $acc = $_POST['acc']; /* 會員帳號 */
        $ticket = (int)$_POST['ticket']; /* 兌換卷數 */
        //$number_periods = $_POST['number_periods']; /* 兌獎期數 */

        /* 判斷是否張數足夠 */
        $count_ticket = $this->get_mem_tick_qry($acc);
        if ($count_ticket <= 0) {
            $this->obj['code'] = 400;
            $this->obj['title'] = 'Thông báo';
            $this->obj['msg'] = 'Số lượng vé xổ số không đủ !!';
            $this->output();
        } else if ($count_ticket < $ticket) {
            $this->obj['code'] = 400;
            $this->obj['title'] = 'Thông báo';
            $this->obj['msg'] = 'Số lượng vé xổ số không đủ !!';
            $this->output();
        }
        /* 判斷是否張數足夠 END*/

        /* 扣掉使用票卷 */
        $this->mod->select("SELECT GET_LOCK('lock', 10)"); /* 開始鎖表 */
        $isFree = $this->mod->select("SELECT IS_FREE_LOCK('lock')"); /* 確認是否有正確鎖表 */
        if (!$isFree) $this->output();
        $this->use_mem_ticket($acc, $ticket);
        /* 扣掉使用票卷  END*/

        /* 產生號碼 */
        $lottery_ball = array();
        for ($i = 0; $i < $ticket; $i++) {
            $lottery_ball[] = $this->lottery_ball();
        }

        $num_last = !empty($num_list)?$num_list['0']['number_periods']:'';
        /* 寫入資料庫 會員兌獎號碼  */
        foreach ($lottery_ball as $k => $v) {
            $num = implode(',', $v);
            $this->mod->add_by('act_evt',
                array(
                    'act_id' => $this->actInfo['id'], /* 活動代碼 */
                    'account' => $acc, /* 會員帳號 */
                    'param1' => 'turn_number', /* 會員兌換數量 參數 */
                    'param2' => $num_last, /* 兌獎期數  */
                    'param3' => '未開獎', /* 兌獎結果 */
                    'param5' => '未開獎', /* 可獲彩金 */
                    'descr1' => $num, /* 兌獎號碼 */
                    'status1' => '1' /* 未兌獎 狀態 */
                ));
        }

        $count_ticket = $this->get_mem_tick_qry($acc);

        $this->mod->select("SELECT RELEASE_LOCK('lock')"); /* 解放鎖表 */

        $this->obj['code'] = 100;
        $this->obj['acc'] = $acc;
        $this->obj['lottery_ball'] = $lottery_ball;
        $this->obj['count_ticket'] = $count_ticket;
        $this->output();
    }

    /* 扣值已使用卷數 */
    public function use_mem_ticket($acc, $ticket) {
        $count_ticket = $this->get_mem_tick_qry($acc);
        if ($count_ticket < $ticket) {
            $this->obj['code'] = 400;
            $this->obj['title'] = 'Thông báo';
            $this->obj['msg'] = 'Số lượng vé xổ số không đủ !!';
            $this->output();

        }

        $sql = "
                 SELECT
                      `id`,
                      `param2` `no_use_ticket`, -- 未使用彩卷
                      `itime` `ticket_add_time` -- 兌獎卷新增時間
                 FROM
                      `act_evt`
                 WHERE
                      `act_id` = ? AND
                      `param1` = ? AND
                      `account` = ? AND 
                      `param2` > ?
                ";

        $sql .= 'ORDER BY `id` ASC';
        $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket', $acc, '0'));

        foreach ($mem_ticket_list as $k=>$v) {
            /* 有效日期 */
            $effective_date_time = $this->chang_time($v['ticket_add_time'], '+14', 'day');
            $effective_date = substr($effective_date_time, '0', '10');

            $today = date("Y-m-d",time()); /* 今天 */

            if($effective_date > $today) {
                $ticket -= $v['no_use_ticket'];

                if($ticket <= 0) {
                    $v['no_use_ticket'] =  abs($ticket); /* 絕對值 */
                    /* 更新 剩餘彩票卷數 */
                    $this->mod->modi_by('act_evt',
                        array(
                            'id' => $v['id'], /* 主鍵 */
                            'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                            'param1'  => 'mem_ticket', /* 免費兌換卷參數 */
                            'account' => $acc /* 帳號 */
                        ),
                        array(
                            'param2'  => $v['no_use_ticket'] /* 剩餘卷數 */
                        )
                    );
                    break;
                } else {
                    $v['no_use_ticket'] = 0;
                    /*  無彩票卷數*/
                    $this->mod->modi_by('act_evt',
                        array(
                            'id' => $v['id'], /* 主鍵 */
                            'act_id'  => $this->actInfo['id'], /* 活動代碼 */
                            'param1'  => 'mem_ticket', /* 免費兌換卷參數 */
                            'account' => $acc /* 帳號 */
                        ),
                        array(
                            'param2'  => 0 /* 剩餘卷數 */
                        )
                    );
                }
            }
        }
    }

    /** 判斷今日是否開獎時間 */
    private function check_drawing_lottery() {
        $sql = "
                SELECT
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `date1` LIKE ?
          ";

        $get_list_date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', date('Y-m-d%')));
        return $get_list_date;
    }

    /* 產生開獎號碼 */
    public function lottery_ball($count = 6, $data = array()) {
        if (count($data) == $count) return $data;

        $num = sprintf('%02d', mt_rand(1, 45)); /* 不足2位數 補0 */
        if (!in_array($num, $data)) {
            $data[] = $num;
        }
        return $this->lottery_ball($count, $data);
    }

	/**
     * 確認會員每日最高單筆存款金額 可獲的兌獎數量
     * 200,000 1張
     * 1,000,000 2張
     * 10,000,000 5張
     * 50,000,000 10張
     * 每日下午 15:00 更新
     * 每日時間計算以今天的11:00至隔天的10:59為一天 (越南時間) 台灣時間 減去一小時
     */
	public function mem_deposit_day_ticket() {
	    /* 抓取存款區間 ---------------------------------------------*/
        $deposit_day = $this->get_deposit_day(); /* 存款區間 */
        $deposit_range_start = $deposit_day[0]['deposit_start']; /* 開始時間 */
        $deposit_range_end = $deposit_day[0]['deposit_end']; /* 結束時間 */

        $deposit_range_end_add = $this->chang_time($deposit_range_end, '+1' , 'day');  /* 結束時間 再加一天 隔天會在更新最後一次 */
        /* 抓取存款區間 End ------------------------------------------*/

        /* 宣告日期 --------------------------------------------------*/
        $today = date("Y-m-d",time()); /* 今天 */

        $daily_time_start = date("Y-m-d",strtotime("-2 day")) .' '.'23:00:00'; /* 昨天 越南時間 減去一小時 */
        $daily_time_end = date("Y-m-d",strtotime("-1 day")).' '.'22:59:59';;  /* 今天 越南時間 減去一小時 */

        $deposit_day_start = substr($daily_time_start, 0, 10);
        $deposit_day_end = substr($daily_time_end, 0, 10);
        /* 宣告日期 End------------------------------------------------*/

        /* 判斷今天日期是否有在 存款區間內 */
        $list = array();
        if($deposit_range_end_add >= $today && $deposit_range_start < $today) {
            /* 搜取每日存款會員金額 API */
            $deposit = $this->dubai_api('getDeposByDate', $deposit_day_start, $deposit_day_end);
            if (isset($deposit['code']) && $deposit['code'] == 100) {
                if (!empty($deposit['list'])) {
                    $deposit_list = $deposit['list'];

                    foreach ($deposit_list as $k => $v) {
                        /* 判斷是 越南迪拜會員 根 存款次數一次以上 */
                        if ($v['id_country'] == 2 && $v['times'] > 0) {
                            /* 會員存款資料 API */
                            $deposit_mem = $this->dubai_api('getAccDepos', $v['acc'], $deposit_day_start, $deposit_day_end, true);
                            if (isset($deposit_mem['code']) && $deposit_mem['code'] == 100) {
                                if (!empty($deposit_mem['list'])) {
                                    $deposit_mem_list = $deposit_mem['list'];

                                    $balance = array();
                                    foreach ($deposit_mem_list as $value) {
                                        $deposit_day = substr($value['itime'], 0, 10); /* 存款日期 */
                                        $deposit_time = $value['itime'];
                                        if ($daily_time_end >= $value['itime'] && $daily_time_start <= $value['itime']) {
                                            $balance[] = $value['balance'];
                                        } else {
                                            /* 未達到 規定存款時間內 */
                                            $list[] = array(
                                                'code' => 300,
                                                'acc' => $deposit_mem['acc'], /* 會員帳號 */
                                                'deposit_time' => $deposit_time /* 存款時間 */
                                            );
                                        }
                                    }

                                    /* 如果有存款金額 */
                                    if(!empty($balance)) {
                                        $max_balance = (int) max($balance); /* 單日最高存款金額 */

                                        $num = $this->deposit_ticket($max_balance);
                                        /* 取得會員對應兌獎數量 */
                                        if ($num > 0) {

                                            /* 確認是否有重複新增 */
                                            $today = date("Y-m-d").' '. '00:00:00'; /* 今天 00:00:00 */
                                            $today_end = date("Y-m-d").' '. '23:59:59'; /* 今天 23:59:59 */
                                            $chk_mem = $this->mod->select("SELECT 
                                                                                  `account` 
                                                                           FROM 
                                                                                  `act_evt` 
                                                                           WHERE 
                                                                                  `act_id`=?  AND 
                                                                                  `account`=?  AND 
                                                                                  `param1`=? AND 
                                                                                  `itime` 
                                                                           BETWEEN  '".$today."' AND '".$today_end."'",
                                                array($this->actInfo['id'], $deposit_mem['acc'], 'mem_ticket')
                                            );

                                            if(empty($chk_mem)) {
                                                $this->mod->add_by('act_evt',
                                                    array(
                                                        'act_id' => $this->actInfo['id'], /* 活動代碼 */
                                                        'account' => $deposit_mem['acc'], /* 會員帳號 */
                                                        'param1' => 'mem_ticket', /* 會員兌換數量 參數 */
                                                        'param2' => $num, /* 未使用卷數  */
                                                        'descr1' => 'Nạp tiền' . '-' . $deposit_day, /* 備註 */
                                                        'amount1' => $max_balance, /* 每日最高存款金額 */
                                                        'amount3' => $num /* 兌獎卷數量 */
                                                    ));
                                                /* 成功新增會員 */
                                                $list[] = array(
                                                    'code' => 100,
                                                    'acc' => $deposit_mem['acc']
                                                );
                                            }
                                        } else if ($num == 0) {
                                            /* 未達到 單日最高金額 */
                                            $list[] = array(
                                                'code' => 200,
                                                'acc' => $deposit_mem['acc']
                                            );
                                        }
                                    }
                                }
                            } else {
                                $list[] = array(
                                    'code' => 401,
                                    'log' => '排程-會員存款資料API-錯誤',
                                    'acc' => $deposit_mem['acc']
                                );
                            }
                        }
                    }
                }
            } else {
                $this->add_error('mem_deposit_day_ticket', '400', '排程-會員存款資料區間API-錯誤');
                $this->obj['code'] = 400;
                $this->obj['title'] = '系統錯誤';
                $this->obj['msg'] = '存款API發生錯誤，請聯繫管理員!!';
                $this->output();
            }
        }
        $this->obj['code'] = 100;
        $this->obj['list'] = $list;
        $this->output();
    }

    /* 會員單日最高存款金額 可獲得數量 */
    public function deposit_ticket($max_balance) {
        $num = 0;
        foreach ($this->ticketNum as $money => $count) {
            if ($max_balance >= $money) {
                $num = $count;
            }
        }
        return $num;
    }

    /* 取得計算存款開始時間 */
    public function get_deposit_day() {
        $sql = "
                SELECT
                      `date1` `deposit_start`,	 -- 存款開始時間
                      `date2` `deposit_end`     -- 存款結束時間
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ?
                LIMIT 1
		 ";
        $deposit_range = $this->mod->select($sql, array($this->actInfo['id'], 'deposit_range'));
        return $deposit_range;
    }

    /* 前台顯示 跑馬燈資料 */
    public function get_marquee_list() {
        $sql = "
                SELECT
                      `id`,
                      `account` `winning_member`, -- 中獎會員帳號
                      `amount3` `winning_amount`	-- 中獎金額
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? 
                ";
        $marquee_list = $this->mod->select($sql, array($this->actInfo['id'], 'winning'));

        $out = array();
        foreach ($marquee_list as $k=>$v) {
            $data = array(
                'winning_member' => $this->starFill($v['winning_member']),
                'winning_amount' => $v['winning_amount']
            );
            $out[] = $data;
        }

        $this->obj['code'] = 100;
        $this->obj['marquee_mem'] = $out;
        $this->output();
    }

    /** 帳號補星號 */
    private function starFill($acc){
        $newAcc = '';
        $length = mb_strlen($acc);
        $tmepAcc = mb_substr($acc, 0, -3);

        $newAcc = str_pad($tmepAcc, $length, '*', STR_PAD_RIGHT);

        return $newAcc;
    }

    /* 前台顯示 會員兌獎卷 歷史紀錄 日期區間查詢*/
    public function get_ticket_story() {
        if(!isset($_POST['acc']) || !isset($_POST['ticket_start']) || !isset($_POST['ticket_end'])) {
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $ticket_end = $_POST['ticket_end'].' '.'23:59:59';
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
                      `param1` = ? AND 
                      `itime` 
                BETWEEN  '".$_POST['ticket_start']."' AND '".$ticket_end."'
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
                'cumulatively_effective' => $num /* 累計有效票卷數 */
            );
            $mem_list[] = $data;
        }

        $this->obj['code'] = 100;
        $this->obj['ticket_list'] = $mem_list;
        $this->output();
    }

    /* 前台顯示 會員兌獎卷 歷史紀錄 日期區間查詢 預設顯示 一個月內*/
    public function get_ticket_story_month() {
        $today=date("Y-m-d");
        $first_day = date('Y-m-01', strtotime($today)); /* 月初 */
        $last_day = date('Y-m-d', strtotime("$first_day +1 month -1 day")).' '. '23:59:59'; /* 月底 */

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
                      `param1` = ? AND 
                      `itime` 
                BETWEEN  '".$first_day."' AND '".$last_day."'
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
                'cumulatively_effective' => $num /* 累計有效票卷數 */
            );
            $mem_list[] = $data;
        }

        $this->obj['code'] = 100;
        $this->obj['ticket_list'] = $mem_list;
        $this->output();
    }

    /* 前台登入 取得可使用彩卷數量 */
    public function get_mem_tick_qry($acc) {
        $sql = "
                 SELECT
                      `param2` `no_use_ticket`, -- 未使用彩卷
                      `itime` `ticket_add_time` -- 兌獎卷新增時間
                 FROM
                      `act_evt`
                 WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `account` = ?
                ";

        $sql .= 'ORDER BY `itime` ASC';
        $mem_ticket_list = $this->mod->select($sql, array($this->actInfo['id'], 'mem_ticket', $acc));

        $today = date("Y-m-d",time()); /* 今天 */

        $num_effective = 0;
        foreach ($mem_ticket_list as $key=>$value) {
            /* 有效日期 */
            $effective_date_time = $this->chang_time($value['ticket_add_time'], '+14', 'day');
            $effective_date = substr($effective_date_time, '0', '10');

            /* 累計有效票卷數 */
            $num_effective = ($effective_date > $today) ? $num_effective += (int)$value['no_use_ticket'] : $num_effective;
        }
        return $num_effective;
    }

    /* 前台顯示會員轉出日期　*/
    public function get_mem_history_turn_num() {
        $sql = "
                SELECT
                      DISTINCT DATE_FORMAT(`itime`, '%Y-%m-%d') as `turn_time` -- 轉出時間
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `account` = ? 
                ";

        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $_POST['acc']));

        $option_turn_date = array();
        foreach ($mem_turn_list as $k=>$v) {

            $optionDate = date('d-m-Y', strtotime($v['turn_time']));
            if (!isset($option_turn_date[$optionDate])) {
                $option_turn_date[] = array('dateId' => substr($v['turn_time'], 0, 10), 'date' => $optionDate);
            }
        }
        $this->obj['code'] = 100;
        $this->obj['option_turn_date'] = $option_turn_date;
        $this->output();
    }

    /* 搜尋會員轉號碼歷史紀錄 */
    public function get_mem_turn_history() {
        if(!isset($_POST['turn_date']) || !isset($_POST['acc'])){
            $this->add_error('get_mem_turn_history', '404', '會員轉號碼歷史紀錄-錯誤');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統錯誤';
            $this->obj['msg'] = '傳入資料錯誤';
            $this->output();
        }

        $sql = "
                SELECT
                      `param2` `order_per`, -- 兌換期數
                      `param3` `result`, -- 兌獎結果
                      `param5` `receive_bonus`, -- 可獲彩金
                      `descr1` `turn_num`, -- 轉出號碼
                      `status1`, -- 是否派發狀態
                      `itime` `turn_time` -- 轉出時間
                 FROM
                      `act_evt`
                 WHERE
                       `act_id` = ? AND 
                       `param1` = ? AND 
                       `account` = ? AND 
                       `itime` LIKE '".$_POST['turn_date']."%'
          ";
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $_POST['acc']));

        $mem_turn = array();
        foreach ($mem_turn_list as $k=>$v) {
            $date = $this->get_turn_date($v['order_per']); /* 取得期數的開獎日期 */
            $turn_date = substr($date['0']['lottery_date'], 0, 10);

            $data = array(
                'turn_time' => $v['turn_time'], /* 轉彩球時間 */
                'turn_num' => $v['turn_num'], /* 轉出號碼 */
                'turn_date' => $turn_date, /* 開獎日期 */
                'receive_bonus' => $v['receive_bonus'], /* 中獎資訊 */
                'status1' => $v['status1'] /* 獎金是否存入狀態 */
            );
            $mem_turn[] = $data;
        }

        $this->obj['code'] = 100;
        $this->obj['mem_turn_list'] = $mem_turn;
        $this->output();
    }

    /* 取得跑馬燈資料 SQL */
    public function marquee_list() {
        $sql = "
                SELECT
                      `id`,
                      `account` `winning_member`, -- 中獎會員帳號
                      `amount3` `winning_amount`	-- 中獎金額
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? 
                ";
        $marquee_list = $this->mod->select($sql, array($this->actInfo['id'], 'winning'));
        return $marquee_list;
    }

    /**
     * 取得期數資料 開獎日期 大於現在的日期  取最新一筆資料
     * 前台 倒數計時用
     */
    public function get_last_lottery() {
        $today = date("Y-m-d H:i:s"); /* 今天 */
        $start_time = $this->chang_time($today , '-1', 'hour'); /* 越南時間 台灣減去一小時 */

        $num_list = $this->get_last_lottery_qry($start_time);

        if(!empty($num_list['0'])) {
            $lottery_date = $num_list['0']['lottery_date'];
            $optionDate = date('d-m-Y', strtotime($lottery_date)); /* 更改日期格式 dd/mm/yyyy */
        } else {
            $optionDate = '';
        }

        $num_last = !empty($num_list)?$num_list['0']:'';

        $this->obj['code'] = 100;
        $this->obj['list'] = $num_last; /* 回傳 最新一筆 */
        $this->obj['start_time'] = $start_time; /* 開始時間 */
        $this->obj['optionDate'] = $optionDate; /* 開獎日期 轉換格式  */
        $this->output();
    }

    /* 抓取最新一期的期數資料 */
    public function get_last_lottery_qry($start_time) {
        $sql = "
                SELECT
                      `param2` `number_periods`, -- 期數
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `status1` = ? AND 
                      `descr1` = '' AND
                      `date1` >= ?
                ORDER BY `date1` ASC
          ";
        $num_list = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', '0', $start_time));

        return $num_list;
    }

    /* 抓取期數資料 已有開獎紀錄 */
    public function get_periods() {
        $sql = "
                SELECT
                      `param2` `number_periods` -- 期數
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND
                      `param1` = ? AND
                      `status1` = ?
		        ";
        $number_periods = $this->mod->select($sql, array($this->actInfo['id'], 'number_set','1'));
        $this->gdata['number_periods'] = $number_periods;
    }

    /* 抓取 參加會員 搜尋的日期 */
    public function get_ticket_date() {
        $dateList = array();
        $timeStart = strtotime($this->actInfo['start_time']); /* 活動開始 */
        $timeEnd = strtotime($this->actInfo['end_time']); /* 活動結束 */

        /* 如果活動結束 就顯示 活動結束時間 */
        if ($timeEnd < time()) {
            $date = new DateTime($this->actInfo['end_time']);
        } else {
            $date = new DateTime();
        }

        /* 日期 大於活動開始前 到現在的時間 */
        do {
            $dateList[] = array('date' => $date->format('Y-m-d'));
            $date->modify('-1 day');
            $tmpTime = strtotime($date->format('Y-m-d 23:59:59'));
        } while ($tmpTime >= $timeStart);

        $this->gdata['dateList'] = $dateList;
    }

    /* 抓取選取的期數 號碼 */
    public function get_periods_data() {
        $sql = "
                SELECT
                      `param2` `number_periods`, -- 期數
                      `descr1` `winning_numbers`, -- 開獎號碼
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `status1` = ? 
		        ";

        /* 期數查詢 */
        if(!empty($_POST['num_periods'])) {
            $sql .= 'AND `param2` = "'.$_POST['num_periods'].'"';
        }
        $sql .= 'ORDER BY `date1` DESC';
        $periods_data = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', '1'));

        $this->obj['code'] = 100;
        $this->obj['periods_data'] = $periods_data;
        $this->output();
    }

    /* 取得期數最新一期 會員轉號碼最新日期 */
    public function get_last_data() {
        /* 取得期數最新一期  */
        $sql = "
                SELECT
                      `param2` `number_periods` -- 期數
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `status1` = ? 
		        ";

        $sql .= 'ORDER BY `date1` DESC';
        $periods_data = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', '1'));
        $periods_data_last = !empty($periods_data)?$periods_data['0']['number_periods']:'';
        /* 取得期數最新一期  End*/

        /* 會員轉號碼最新日期 */
        $sql = "
                SELECT
                      `param2` `order_per`, -- 兌換期數
                      `param3` `result`, -- 兌獎結果
                      `param5` `receive_bonus`, -- 可獲彩金
                      `descr1` `turn_num`, -- 轉出號碼
                      `status1`, -- 是否派發狀態
                      `itime` `turn_time` -- 轉出時間
                 FROM
                      `act_evt`
                 WHERE
                       `act_id` = ? AND 
                       `param1` = ? AND 
                       `account` = ? 
          ";
        $sql .= 'ORDER BY `itime` DESC';
        $mem_turn_list = $this->mod->select($sql, array($this->actInfo['id'], 'turn_number', $_POST['account']));
        $mem_turn_list_last = !empty($mem_turn_list)?$mem_turn_list['0']['turn_time']:'';
        /* 會員轉號碼最新日期 End*/

        $this->obj['code'] = 100;
        $this->obj['periods_data_last'] = $periods_data_last;
        $this->obj['mem_turn_list_last'] = $mem_turn_list_last;
        $this->output();
    }

    /* 取得轉出號碼的開獎日期 */
    public function get_turn_date($turn_date) {
        $sql = "
                SELECT
                      `date1` `lottery_date`	 -- 開獎日期
                FROM
                      `act_evt`
                WHERE
                      `act_id` = ? AND 
                      `param1` = ? AND 
                      `param2` = ? 
		        ";
        $date = $this->mod->select($sql, array($this->actInfo['id'], 'number_set', $turn_date));
        return $date;
    }

	/* 新增資料 */
    protected function insert_data($db, $data) {
        $result = $this->mod->add_by($db, $data);
        if ($result['lid'] === false) {
            return 400;
        } else {
            return 100;
        }
    }

    /* 會員基本資訊 Call API */
    public function get_acc($acc) {
        $chk_acc = trim($acc);
        if (!preg_match('/^[A-Za-z0-9]+$/', $chk_acc)) {
            return 400;
        }

        $chk_acc = $this->dubai_api('getAcc', $chk_acc);

        if(isset($chk_acc['code']) && !empty($chk_acc['code'])){
            return $chk_acc;
        } else {
            $this->add_error('get_acc', '404', '會員資訊API-getAcc');
            $this->obj['code'] = 404;
            $this->obj['title'] = '系統提示';
            $this->obj['msg'] = 'API錯誤！';
            $this->output();
        }
    }

    /* 寫入錯誤訊息 log */
    public function add_error($method, $code, $log) {
        if($method == null || $code == null || $log == null) return false;

        $data = array(
           'act_id' => $this->actInfo['id'],
           'param1' => 'error',
           'param2' => $code,
           'param3' => $method,
           'descr1' => $log
        );
        $this->insert_data('act_evt', $data);
    }

    /**
     *  轉換時間
     *  $number +- 數字
     *  $type 類型 月份 小時 日期
     */
    public function chang_time($time, $number, $type) {
        $time = new DateTime($time);
        $chang_time = $time->modify(''.$number.' '.$type.'');
        $chang_now_time = $chang_time->format('Y-m-d H:i:s');

        return $chang_now_time;
    }
}