<?php
/**
 * 预存款管理
 **by 好商城V3 www.33hao.com 运营版*/

defined('InShopNC') or exit('Access Invalid!');
class predepositControl extends SystemControl{
	const EXPORT_SIZE = 1000;
	public function __construct(){
		parent::__construct();
		Language::read('predeposit');
	}

	/**
	 * 充值列表
	 */
	public function predepositOp(){
        $condition = array();
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_start_date']);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_end_date']);
        $start_unixtime = $if_start_date ? strtotime($_GET['query_start_date']) : null;
        $end_unixtime = $if_end_date ? strtotime($_GET['query_end_date']): null;
        if ($start_unixtime || $end_unixtime) {
            $condition['pdr_add_time'] = array('time',array($start_unixtime,$end_unixtime));
        }
        if (!empty($_GET['mname'])){
        	$condition['pdr_member_name'] = $_GET['mname'];
        }
		if ($_GET['paystate_search'] != ''){
			$condition['pdr_payment_state'] = $_GET['paystate_search'];
		}
		$model_pd = Model('predeposit');
		$recharge_list = $model_pd->getPdRechargeList($condition,20,'*','pdr_id desc');
		//信息输出
		Tpl::output('list',$recharge_list);
		Tpl::output('show_page',$model_pd->showpage());
		Tpl::showpage('pd.list');
	}

	/**
	 * 充值编辑(更改成收到款)
	 */
	public function recharge_editOp(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showMessage(Language::get('admin_predeposit_parameter_error'),'index.php?act=predeposit&op=predeposit','','error');
		}
		//查询充值信息
		$model_pd = Model('predeposit');
		$condition = array();
		$condition['pdr_id'] = $id;
		$condition['pdr_payment_state'] = 0;
		$info = $model_pd->getPdRechargeInfo($condition);
		if (empty($info)){
			showMessage(Language::get('admin_predeposit_record_error'),'index.php?act=predeposit&op=predeposit','','error');
		}
		if (!chksubmit()) {
		    //显示支付接口列表
		    $payment_list = Model('payment')->getPaymentOpenList();
		    //去掉预存款和货到付款
		    foreach ($payment_list as $key => $value){
		        if ($value['payment_code'] == 'predeposit' || $value['payment_code'] == 'offline') {
		            unset($payment_list[$key]);
		        }
		    }
		    Tpl::output('payment_list',$payment_list);
		    Tpl::output('info',$info);
		    Tpl::showpage('pd.edit');
		    exit();
		}

		//取支付方式信息
		$model_payment = Model('payment');
		$condition = array();
		$condition['payment_code'] = $_POST['payment_code'];
		$payment_info = $model_payment->getPaymentOpenInfo($condition);
		if(!$payment_info || $payment_info['payment_code'] == 'offline' || $payment_info['payment_code'] == 'offline') {
		    showMessage(L('payment_index_sys_not_support'),'','html','error');
		}

		$condition = array();
		$condition['pdr_sn'] = $info['pdr_sn'];
		$condition['pdr_payment_state'] = 0;
		$update = array();
		$update['pdr_payment_state'] = 1;
		$update['pdr_payment_time'] = strtotime($_POST['payment_time']);
		$update['pdr_payment_code'] = $payment_info['payment_code'];
		$update['pdr_payment_name'] = $payment_info['payment_name'];
		$update['pdr_trade_sn'] = $_POST['trade_no'];
		$update['pdr_admin'] = $this->admin_info['name'];
        $log_msg = L('admin_predeposit_recharge_edit_state').','.L('admin_predeposit_sn').':'.$info['pdr_sn'];

		try {
		    $model_pd->beginTransaction();
		    //更改充值状态
		    $state = $model_pd->editPdRecharge($update,$condition);
		    if (!$state) {
		        throw Exception(Language::get('predeposit_payment_pay_fail'));
		    }
		    //变更会员预存款
		    $data = array();
		    $data['member_id'] = $info['pdr_member_id'];
		    $data['member_name'] = $info['pdr_member_name'];
		    $data['amount'] = $info['pdr_amount'];
		    $data['pdr_sn'] = $info['pdr_sn'];
		    $data['admin_name'] = $this->admin_info['name'];
		    $model_pd->changePd('recharge',$data);
		    $model_pd->commit();
		    $this->log($log_msg,1);
		    showMessage(Language::get('admin_predeposit_recharge_edit_success'),'index.php?act=predeposit&op=predeposit');
		} catch (Exception $e) {
		    $model_pd->rollback();
		    $this->log($log_msg,0);
		    showMessage($e->getMessage(),'index.php?act=predeposit&op=predeposit','html','error');
		}
	}

	/**
	 * 充值查看
	 */
	public function recharge_infoOp(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showMessage(Language::get('admin_predeposit_parameter_error'),'index.php?act=predeposit&op=predeposit','','error');
		}
		//查询充值信息
		$model_pd = Model('predeposit');
		$condition = array();
		$condition['pdr_id'] = $id;
		$info = $model_pd->getPdRechargeInfo($condition);
		if (empty($info)){
			showMessage(Language::get('admin_predeposit_record_error'),'index.php?act=predeposit&op=predeposit','','error');
		}
		Tpl::output('info',$info);
		Tpl::showpage('pd.info');

	}

	/**
	 * 充值删除
	 */
	public function recharge_delOp(){
		$pdr_id = intval($_GET["id"]);
		if ($pdr_id <= 0){
			showMessage(Language::get('admin_predeposit_parameter_error'),'index.php?act=predeposit&op=predeposit','','error');
		}
		$model_pd = Model('predeposit');
		$condition = array();
		$condition['pdr_id'] = "$pdr_id";
		$condition['pdr_payment_state'] = 0;
		$result = $model_pd->delPdRecharge($condition);
		if ($result){
			showMessage(Language::get('admin_predeposit_recharge_del_success'),'index.php?act=predeposit&op=predeposit');
		}else {
			showMessage(Language::get('admin_predeposit_recharge_del_fail'),'index.php?act=predeposit&op=predeposit','','error');
		}
	}

	/**
	 * 预存款日志
	 */
	public function pd_log_listOp(){
	    $condition = array();
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['stime']);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['etime']);
        $start_unixtime = $if_start_date ? strtotime($_GET['stime']) : null;
        $end_unixtime = $if_end_date ? strtotime($_GET['etime']): null;
        if ($start_unixtime || $end_unixtime) {
            $condition['lg_add_time'] = array('time',array($start_unixtime,$end_unixtime));
        }
        if (!empty($_GET['mname'])){
        	$condition['lg_member_name'] = $_GET['mname'];
        }
        if (!empty($_GET['aname'])){
            $condition['lg_admin_name'] = $_GET['aname'];
        }
		$model_pd = Model('predeposit');
		$list_log = $model_pd->getPdLogList($condition,20,'*','lg_id desc');
		Tpl::output('show_page',$model_pd->showpage());
		Tpl::output('list_log',$list_log);
		Tpl::showpage('pd_log.list');
	}

	/**
	 * 提现列表
	 */
	public function pd_cash_listOp(){
	    $condition = array();
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['stime']);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['etime']);
        $start_unixtime = $if_start_date ? strtotime($_GET['stime']) : null;
        $end_unixtime = $if_end_date ? strtotime($_GET['etime']): null;
        if ($start_unixtime || $end_unixtime) {
            $condition['pdc_add_time'] = array('time',array($start_unixtime,$end_unixtime));
        }
        if (!empty($_GET['mname'])){
            $condition['pdc_member_name'] = $_GET['mname'];
        }
        if (!empty($_GET['pdc_bank_user'])){
        	$condition['pdc_bank_user'] = $_GET['pdc_bank_user'];
        }
		if ($_GET['paystate_search'] != ''){
			$condition['pdc_payment_state'] = $_GET['paystate_search'];
		}

		$model_pd = Model('predeposit');
		$cash_list = $model_pd->getPdCashList($condition,20,'*','pdc_payment_state asc,pdc_id asc');
		Tpl::output('list',$cash_list);
		Tpl::output('show_page',$model_pd->showpage());
		Tpl::showpage('pd_cash.list');
	}

	/**
	 * 删除提现记录
	 */
	public function pd_cash_delOp(){
		$pdc_id = intval($_GET["id"]);
		if ($pdc_id <= 0){
			showMessage(Language::get('admin_predeposit_parameter_error'),'index.php?act=predeposit&op=pd_cash_list','','error');
		}
		$model_pd = Model('predeposit');
		$condition = array();
		$condition['pdc_id'] = $pdc_id;
		$condition['pdc_payment_state'] = 0;
		$info = $model_pd->getPdCashInfo($condition);
		if (!$info) {
		    showMessage(Language::get('admin_predeposit_parameter_error'),'index.php?act=predeposit&op=pd_cash_list','','error');
		}
		try {
		    $result = $model_pd->delPdCash($condition);
		    if (!$result) {
		        throw new Exception(Language::get('admin_predeposit_cash_del_fail'));
		    }
		    //退还冻结的预存款
		    $model_member = Model('member');
		    $member_info = $model_member->getMemberInfo(array('member_id'=>$info['pdc_member_id']));
		    //扣除冻结的预存款
		    $admininfo = $this->getAdminInfo();
		    $data = array();
		    $data['member_id'] = $member_info['member_id'];
		    $data['member_name'] = $member_info['member_name'];
		    $data['amount'] = $info['pdc_amount'];
		    $data['order_sn'] = $info['pdc_sn'];
		    $data['admin_name'] = $admininfo['name'];
		    $model_pd->changePd('cash_del',$data);
		    $model_pd->commit();
			showMessage(Language::get('admin_predeposit_cash_del_success'),'index.php?act=predeposit&op=pd_cash_list');

		} catch (Exception $e) {
		    $model_pd->commit();
		    showMessage($e->getMessage(),'index.php?act=predeposit&op=pd_cash_list','html','error');
		}
	}

	/**
	 * 更改提现为支付状态
	 */
	public function pd_cash_payOp(){
	    $id = intval($_GET['id']);
	    if ($id <= 0){
	        showMessage(Language::get('admin_predeposit_parameter_error'),'index.php?act=predeposit&op=pd_cash_list','','error');
	    }
	    $model_pd = Model('predeposit');
	    $condition = array();
	    $condition['pdc_id'] = $id;
	    $condition['pdc_payment_state'] = 0;
	    $info = $model_pd->getPdCashInfo($condition);
	    if (!is_array($info) || count($info)<0){
	        showMessage(Language::get('admin_predeposit_record_error'),'index.php?act=predeposit&op=pd_cash_list','','error');
	    }

	    //查询用户信息
	    $model_member = Model('member');
	    $member_info = $model_member->getMemberInfo(array('member_id'=>$info['pdc_member_id']));

        $update = array();
        $admininfo = $this->getAdminInfo();
        $update['pdc_payment_state'] = 1;
        $update['pdc_payment_admin'] = $admininfo['name'];
        $update['pdc_payment_time'] = TIMESTAMP;
        $log_msg = L('admin_predeposit_cash_edit_state').','.L('admin_predeposit_cs_sn').':'.$info['pdc_sn'];

        try {
            $model_pd->beginTransaction();
            $result = $model_pd->editPdCash($update,$condition);
            if (!$result) {
                throw new Exception(Language::get('admin_predeposit_cash_edit_fail'));
            }
            //扣除冻结的预存款
            $data = array();
            $data['member_id'] = $member_info['member_id'];
            $data['member_name'] = $member_info['member_name'];
            $data['amount'] = $info['pdc_amount'];
            $data['order_sn'] = $info['pdc_sn'];
            $data['admin_name'] = $admininfo['name'];
            $model_pd->changePd('cash_pay',$data);
            $model_pd->commit();
            $this->log($log_msg,1);
            showMessage(Language::get('admin_predeposit_cash_edit_success'),'index.php?act=predeposit&op=pd_cash_list');
        } catch (Exception $e) {
            $model_pd->rollback();
            $this->log($log_msg,0);
            showMessage($e->getMessage(),'index.php?act=predeposit&op=pd_cash_list','html','error');
        }
	}

	/**
	 * 查看提现信息
	 */
	public function pd_cash_viewOp(){
	    $id = intval($_GET['id']);
	    if ($id <= 0){
	        showMessage(Language::get('admin_predeposit_parameter_error'),'index.php?act=predeposit&op=pd_cash_list','','error');
	    }
	    $model_pd = Model('predeposit');
	    $condition = array();
	    $condition['pdc_id'] = $id;
	    $info = $model_pd->getPdCashInfo($condition);
	    if (!is_array($info) || count($info)<0){
	        showMessage(Language::get('admin_predeposit_record_error'),'index.php?act=predeposit&op=pd_cash_list','','error');
	    }
	    Tpl::output('info',$info);
	    Tpl::showpage('pd_cash.view');
	}


	/**
	 * 导出预存款充值记录
	 *
	 */
	public function export_step1Op(){
	    $condition = array();
	    $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_start_date']);
	    $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_end_date']);
	    $start_unixtime = $if_start_date ? strtotime($_GET['query_start_date']) : null;
	    $end_unixtime = $if_end_date ? strtotime($_GET['query_end_date']): null;
	    if ($start_unixtime || $end_unixtime) {
	        $condition['pdr_add_time'] = array('time',array($start_unixtime,$end_unixtime));
	    }
	    if (!empty($_GET['mname'])){
	        $condition['pdr_member_name'] = $_GET['mname'];
	    }
	    if ($_GET['paystate_search'] != ''){
	        $condition['pdr_payment_state'] = $_GET['paystate_search'];
	    }
	    $model_pd = Model('predeposit');
		if (!is_numeric($_GET['curpage'])){
			$count = $model_pd->getPdRechargeCount($condition);
			$array = array();
			if ($count > self::EXPORT_SIZE ){	//显示下载链接
				$page = ceil($count/self::EXPORT_SIZE);
				for ($i=1;$i<=$page;$i++){
					$limit1 = ($i-1)*self::EXPORT_SIZE + 1;
					$limit2 = $i*self::EXPORT_SIZE > $count ? $count : $i*self::EXPORT_SIZE;
					$array[$i] = $limit1.' ~ '.$limit2 ;
				}
				Tpl::output('list',$array);
				Tpl::output('murl','index.php?act=predeposit&op=predeposit');
				Tpl::showpage('export.excel');
			}else{	//如果数量小，直接下载
				$data = $model_pd->getPdRechargeList($condition,'','*','pdr_id desc',self::EXPORT_SIZE);
				$rechargepaystate = array(0=>'未支付',1=>'已支付');
				foreach ($data as $k=>$v) {
					$data[$k]['pdr_payment_state'] = $rechargepaystate[$v['pdr_payment_state']];
				}
				$this->createExcel($data);
			}
		}else{	//下载
			$limit1 = ($_GET['curpage']-1) * self::EXPORT_SIZE;
			$limit2 = self::EXPORT_SIZE;
			$data = $model_pd->getPdRechargeList($condition,'','*','pdr_id desc',"{$limit1},{$limit2}");
			$rechargepaystate = array(0=>'未支付',1=>'已支付');
			foreach ($data as $k=>$v) {
				$data[$k]['pdr_payment_state'] = $rechargepaystate[$v['pdr_payment_state']];
			}
			$this->createExcel($data);
		}
	}

	/**
	 * 生成导出预存款充值excel
	 *
	 * @param array $data
	 */
	private function createExcel($data = array()){
		Language::read('export');
		import('libraries.excel');
		$excel_obj = new Excel();
		$excel_data = array();
		//设置样式
		$excel_obj->setStyle(array('id'=>'s_title','Font'=>array('FontName'=>'宋体','Size'=>'12','Bold'=>'1')));
		//header
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_no'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_member'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_ctime'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_ptime'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_pay'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_money'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_paystate'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_yc_memberid'));
		foreach ((array)$data as $k=>$v){
			$tmp = array();
			$tmp[] = array('data'=>'NC'.$v['pdr_sn']);
			$tmp[] = array('data'=>$v['pdr_member_name']);
			$tmp[] = array('data'=>date('Y-m-d H:i:s',$v['pdr_add_time']));
			if (intval($v['pdr_payment_time'])) {
	            if (date('His',$v['pdr_payment_time']) == 0) {
	               $tmp[] = array('data'=>date('Y-m-d',$v['pdr_payment_time']));
	            } else {
	               $tmp[] = array('data'=>date('Y-m-d H:i:s',$v['pdr_payment_time']));
	            }
			} else {
			    $tmp[] = array('data'=>'');
			}
			$tmp[] = array('data'=>$v['pdr_payment_name']);
			$tmp[] = array('format'=>'Number','data'=>ncPriceFormat($v['pdr_amount']));
			$tmp[] = array('data'=>$v['pdr_payment_state']);
			$tmp[] = array('data'=>$v['pdr_member_id']);
			$excel_data[] = $tmp;
		}
		$excel_data = $excel_obj->charset($excel_data,CHARSET);
		$excel_obj->addArray($excel_data);
		$excel_obj->addWorksheet($excel_obj->charset(L('exp_yc_yckcz'),CHARSET));
		$excel_obj->generateXML($excel_obj->charset(L('exp_yc_yckcz'),CHARSET).$_GET['curpage'].'-'.date('Y-m-d-H',time()));
	}

	/**
	 * 导出预存款提现记录
	 *
	 */
	public function export_cash_step1Op(){
	    $condition = array();
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['stime']);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['etime']);
        $start_unixtime = $if_start_date ? strtotime($_GET['stime']) : null;
        $end_unixtime = $if_end_date ? strtotime($_GET['etime']): null;
        if ($start_unixtime || $end_unixtime) {
            $condition['pdc_add_time'] = array('time',array($start_unixtime,$end_unixtime));
        }
        if (!empty($_GET['mname'])){
            $condition['pdc_member_name'] = $_GET['mname'];
        }
        if (!empty($_GET['pdc_bank_user'])){
        	$condition['pdc_bank_user'] = $_GET['pdc_bank_user'];
        }
		if ($_GET['paystate_search'] != ''){
			$condition['pdc_payment_state'] = $_GET['paystate_search'];
		}

		$model_pd = Model('predeposit');

		if (!is_numeric($_GET['curpage'])){
			$count = $model_pd->getPdCashCount($condition);
			$array = array();
			if ($count > self::EXPORT_SIZE ){	//显示下载链接
				$page = ceil($count/self::EXPORT_SIZE);
				for ($i=1;$i<=$page;$i++){
					$limit1 = ($i-1)*self::EXPORT_SIZE + 1;
					$limit2 = $i*self::EXPORT_SIZE > $count ? $count : $i*self::EXPORT_SIZE;
					$array[$i] = $limit1.' ~ '.$limit2 ;
				}
				Tpl::output('list',$array);
				Tpl::output('murl','index.php?act=predeposit&op=pd_cash_list');
				Tpl::showpage('export.excel');
			}else{	//如果数量小，直接下载
				$data = $model_pd->getPdCashList($condition,'','*','pdc_id desc',self::EXPORT_SIZE);
				$cashpaystate = array(0=>'未支付',1=>'已支付');
				foreach ($data as $k=>$v) {
					$data[$k]['pdc_payment_state'] = $cashpaystate[$v['pdc_payment_state']];
				}
				$this->createCashExcel($data);
			}
		}else{	//下载
			$limit1 = ($_GET['curpage']-1) * self::EXPORT_SIZE;
			$limit2 = self::EXPORT_SIZE;
			$data = $model_pd->getPdCashList($condition,'','*','pdc_id desc',"{$limit1},{$limit2}");
			$cashpaystate = array(0=>'未支付',1=>'已支付');
			foreach ($data as $k=>$v) {
				$data[$k]['pdc_payment_state'] = $cashpaystate[$v['pdc_payment_state']];
			}
			$this->createCashExcel($data);
		}
	}

	/**
	 * 生成导出预存款提现excel
	 *
	 * @param array $data
	 */
	private function createCashExcel($data = array()){
		Language::read('export');
		import('libraries.excel');
		$excel_obj = new Excel();
		$excel_data = array();
		//设置样式
		$excel_obj->setStyle(array('id'=>'s_title','Font'=>array('FontName'=>'宋体','Size'=>'12','Bold'=>'1')));
		//header
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_tx_no'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_tx_member'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_tx_money'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_tx_ctime'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_tx_state'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_tx_memberid'));
		foreach ((array)$data as $k=>$v){
			$tmp = array();
			$tmp[] = array('data'=>'NC'.$v['pdc_sn']);
			$tmp[] = array('data'=>$v['pdc_member_name']);
			$tmp[] = array('format'=>'Number','data'=>ncPriceFormat($v['pdc_amount']));
			$tmp[] = array('data'=>date('Y-m-d H:i:s',$v['pdc_add_time']));
			$tmp[] = array('data'=>$v['pdc_payment_state']);
			$tmp[] = array('data'=>$v['pdc_member_id']);
			$excel_data[] = $tmp;
		}
		$excel_data = $excel_obj->charset($excel_data,CHARSET);
		$excel_obj->addArray($excel_data);
		$excel_obj->addWorksheet($excel_obj->charset(L('exp_tx_title'),CHARSET));
		$excel_obj->generateXML($excel_obj->charset(L('exp_tx_title'),CHARSET).$_GET['curpage'].'-'.date('Y-m-d-H',time()));
	}

	/**
	 * 预存款明细信息导出
	 */
	public function export_mx_step1Op(){
	    $condition = array();
	    $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['stime']);
	    $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['etime']);
	    $start_unixtime = $if_start_date ? strtotime($_GET['stime']) : null;
	    $end_unixtime = $if_end_date ? strtotime($_GET['etime']): null;
	    if ($start_unixtime || $end_unixtime) {
	        $condition['lg_add_time'] = array('time',array($start_unixtime,$end_unixtime));
	    }
	    if (!empty($_GET['mname'])){
	        $condition['lg_member_name'] = $_GET['mname'];
	    }
	    if (!empty($_GET['aname'])){
	        $condition['lg_admin_name'] = $_GET['aname'];
	    }
		$model_pd = Model('predeposit');
		if (!is_numeric($_GET['curpage'])){
    		$count = $model_pd->getPdLogCount($condition);
    		$array = array();
    		if ($count > self::EXPORT_SIZE ){	//显示下载链接
    			$page = ceil($count/self::EXPORT_SIZE);
    			for ($i=1;$i<=$page;$i++){
    				$limit1 = ($i-1)*self::EXPORT_SIZE + 1;
    				$limit2 = $i*self::EXPORT_SIZE > $count ? $count : $i*self::EXPORT_SIZE;
    				$array[$i] = $limit1.' ~ '.$limit2 ;
    			}
    			Tpl::output('list',$array);
    			Tpl::output('murl','index.php?act=predeposit&op=pd_log_list');
    			Tpl::showpage('export.excel');
    		}else{	//如果数量小，直接下载
    			$data = $model_pd->getPdLogList($condition,'','*','lg_id desc',self::EXPORT_SIZE);
    			$this->createmxExcel($data);
    		}
    	}else{	//下载
    		$limit1 = ($_GET['curpage']-1) * self::EXPORT_SIZE;
    		$limit2 = self::EXPORT_SIZE;
    		$data = $model_pd->getPdLogList($condition,'','*','lg_id desc',"{$limit1},{$limit2}");
    		$this->createmxExcel($data);
    	}
	}

	/**
	 * 导出预存款明细excel
	 *
	 * @param array $data
	 */
	private function createmxExcel($data = array()){
		Language::read('export');
		import('libraries.excel');
		$excel_obj = new Excel();
		$excel_data = array();
		//设置样式
		$excel_obj->setStyle(array('id'=>'s_title','Font'=>array('FontName'=>'宋体','Size'=>'12','Bold'=>'1')));
		//header
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_mx_member'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_mx_ctime'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_mx_av_money'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_mx_freeze_money'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_mx_system'));
		$excel_data[0][] = array('styleid'=>'s_title','data'=>L('exp_mx_mshu'));
		foreach ((array)$data as $k=>$v){
			$tmp = array();
			$tmp[] = array('data'=>$v['lg_member_name']);
			$tmp[] = array('data'=>date('Y-m-d H:i:s',$v['lg_add_time']));
			if (floatval($v['lg_av_amount']) == 0){
			    $tmp[] = array('data'=>'');
			} else {
			    $tmp[] = array('format'=>'Number','data'=>ncPriceFormat($v['lg_av_amount']));
			}
			if (floatval($v['lg_freeze_amount']) == 0){
			    $tmp[] = array('data'=>'');
			} else {
			    $tmp[] = array('format'=>'Number','data'=>ncPriceFormat($v['lg_freeze_amount']));
			}
			$tmp[] = array('data'=>$v['lg_admin_name']);
			$tmp[] = array('data'=>$v['lg_desc']);
			$excel_data[] = $tmp;
		}
		$excel_data = $excel_obj->charset($excel_data,CHARSET);
		$excel_obj->addArray($excel_data);
		$excel_obj->addWorksheet($excel_obj->charset(L('exp_mx_rz'),CHARSET));
		$excel_obj->generateXML($excel_obj->charset(L('exp_mx_rz'),CHARSET).$_GET['curpage'].'-'.date('Y-m-d-H',time()));
	}
}
