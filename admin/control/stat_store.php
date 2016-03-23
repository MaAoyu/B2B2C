<?php
/**
 * 统计管理（店铺）
 **by 好商城V3 www.33hao.com 运营版*/

defined('InShopNC') or exit('Access Invalid!');
class stat_storeControl extends SystemControl{
	private $links = array(
        array('url'=>'act=stat_store&op=newstore','lang'=>'stat_newstore'),
        array('url'=>'act=stat_store&op=hotrank','lang'=>'stat_storehotrank'),
        array('url'=>'act=stat_store&op=storesales','lang'=>'stat_storesales'),
        array('url'=>'act=stat_store&op=degree','lang'=>'stat_storedegree'),
        array('url'=>'act=stat_store&op=storearea','lang'=>'stat_storearea'),
    );

    private $search_arr;//处理后的参数
    private $store_class;//店铺分类

	public function __construct(){
        parent::__construct();
        Language::read('stat');
        import('function.statistics');
        import('function.datehelper');
        $model = Model('stat');
        //存储参数
		$this->search_arr = $_REQUEST;
		//处理搜索时间
		if (in_array($_REQUEST['op'],array('hotrank','storesales'))){
		    $this->search_arr = $model->dealwithSearchTime($this->search_arr);
    		//获得系统年份
    		$year_arr = getSystemYearArr();
    		//获得系统月份
    		$month_arr = getSystemMonthArr();
    		//获得本月的周时间段
    		$week_arr = getMonthWeekArr($this->search_arr['week']['current_year'], $this->search_arr['week']['current_month']);
    		Tpl::output('year_arr', $year_arr);
    		Tpl::output('month_arr', $month_arr);
    		Tpl::output('week_arr', $week_arr);
		}
		Tpl::output('search_arr', $this->search_arr);
		//店铺分类
		$this->store_class = rkcache('store_class', true);
		Tpl::output('store_class', $this->store_class);
    }
    /**
	 * 新增店铺
	 */
    public function newstoreOp(){
        //导出excel连接地址
        $actionurl = 'index.php?act=stat_store&op=newstore';
    	$where = array();
    	//所属店铺分类
    	$search_sclass = intval($_REQUEST['search_sclass']);
    	if ($search_sclass > 0){
    	    $where['sc_id'] = $search_sclass;
    	    $actionurl .= "&search_sclass=$search_sclass";
    	}
		$field = ' count(*) as allnum ';
		if(!$_REQUEST['search_type']){
			$_REQUEST['search_type'] = 'day';
		}
		//初始化时间
		//天
		if(!$_REQUEST['search_time']){
		    $_REQUEST['search_time'] = date('Y-m-d', time()-86400);
		}
		$search_time = strtotime($_REQUEST['search_time']);//搜索的时间
		Tpl::output('search_time',$_REQUEST['search_time']);
		//周
		if(!$_REQUEST['search_time_year']){
			$_REQUEST['search_time_year'] = date('Y', time());
		}
		if(!$_REQUEST['search_time_month']){
			$_REQUEST['search_time_month'] = date('m', time());
		}
		if(!$_REQUEST['search_time_week']){
			$_REQUEST['search_time_week'] =  implode('|', getWeek_SdateAndEdate(time()));
		}
		$current_year = $_REQUEST['search_time_year'];
		$current_month = $_REQUEST['search_time_month'];
		$current_week = $_REQUEST['search_time_week'];
		$year_arr = getSystemYearArr();
		$month_arr = getSystemMonthArr();
		$week_arr = getMonthWeekArr($current_year, $current_month);

		Tpl::output('current_year', $current_year);
		Tpl::output('current_month', $current_month);
		Tpl::output('current_week', $current_week);
		Tpl::output('year_arr', $year_arr);
		Tpl::output('month_arr', $month_arr);
		Tpl::output('week_arr', $week_arr);

    	$model = Model('stat');
		$statlist = array();//统计数据列表
		if($_REQUEST['search_type'] == 'day'){
			//构造横轴数据
			for($i=0; $i<24; $i++){
				//统计图数据
				$curr_arr[$i] = 0;//今天
				$up_arr[$i] = 0;//昨天
				//统计表数据
				$uplist_arr[$i]['timetext'] = $i;
				$currlist_arr[$i]['timetext'] = $i;
				//方便搜索会员列表，计算开始时间和结束时间
				$currlist_arr[$i]['stime'] = $search_time+$i*3600;
				$currlist_arr[$i]['etime'] = $currlist_arr[$i]['stime']+3600;
				$uplist_arr[$i]['val'] = 0;
				$currlist_arr[$i]['val'] = 0;
				//横轴
				$stat_arr['xAxis']['categories'][] = "$i";
			}
			$stime = $search_time - 86400;//昨天0点
			$etime = $search_time + 86400 - 1;//今天24点

			$today_day = @date('d', $search_time);//今天日期
			$yesterday_day = @date('d', $stime);//昨天日期

			$where['store_time'] = array('between',array($stime,$etime));
			$field .= ' ,DAY(FROM_UNIXTIME(store_time)) as dayval,HOUR(FROM_UNIXTIME(store_time)) as hourval ';
			$memberlist = $model->getNewStoreStatList($where, $field, 0, '', 0, 'dayval,hourval');
			if($memberlist){
				foreach($memberlist as $k => $v){
					if($today_day == $v['dayval']){
						$curr_arr[$v['hourval']] = intval($v['allnum']);
						$currlist_arr[$v['hourval']]['val'] = intval($v['allnum']);
					}
					if($yesterday_day == $v['dayval']){
						$up_arr[$v['hourval']] = intval($v['allnum']);
						$uplist_arr[$v['hourval']]['val'] = intval($v['allnum']);
					}
				}
			}
			$stat_arr['series'][0]['name'] = '昨天';
			$stat_arr['series'][0]['data'] = array_values($up_arr);
			$stat_arr['series'][1]['name'] = '今天';
			$stat_arr['series'][1]['data'] = array_values($curr_arr);

			//统计数据标题
			$statlist['headertitle'] = array('小时','昨天','今天','同比');
			Tpl::output('actionurl',$actionurl.'&search_type=day&search_time='.date('Y-m-d',$search_time));
		}

		if($_REQUEST['search_type'] == 'week'){
			$current_weekarr = explode('|', $current_week);
			$stime = strtotime($current_weekarr[0])-86400*7;
			$etime = strtotime($current_weekarr[1])+86400-1;
			$up_week = @date('W', $stime);//上周
			$curr_week = @date('W', $etime);//本周
			//构造横轴数据
			for($i=1; $i<=7; $i++){
				//统计图数据
				$up_arr[$i] = 0;
				$curr_arr[$i] = 0;
				$tmp_weekarr = getSystemWeekArr();
				//统计表数据
				$uplist_arr[$i]['timetext'] = $tmp_weekarr[$i];
				$currlist_arr[$i]['timetext'] = $tmp_weekarr[$i];
				//方便搜索会员列表，计算开始时间和结束时间
				$currlist_arr[$i]['stime'] = strtotime($current_weekarr[0])+($i-1)*86400;
				$currlist_arr[$i]['etime'] = $currlist_arr[$i]['stime']+86400 - 1;
				$uplist_arr[$i]['val'] = 0;
				$currlist_arr[$i]['val'] = 0;
				//横轴
				$stat_arr['xAxis']['categories'][] = $tmp_weekarr[$i];
				unset($tmp_weekarr);
			}
			$where['store_time'] = array('between', array($stime,$etime));
			$field .= ',WEEKOFYEAR(FROM_UNIXTIME(store_time)) as weekval,WEEKDAY(FROM_UNIXTIME(store_time))+1 as dayofweekval ';
			$memberlist = $model->getNewStoreStatList($where, $field, 0, '', 0, 'weekval,dayofweekval');
			if($memberlist){
				foreach($memberlist as $k=>$v){
					if ($up_week == $v['weekval']){
						$up_arr[$v['dayofweekval']] = intval($v['allnum']);
						$uplist_arr[$v['dayofweekval']]['val'] = intval($v['allnum']);
					}
					if ($curr_week == $v['weekval']){
						$curr_arr[$v['dayofweekval']] = intval($v['allnum']);
						$currlist_arr[$v['dayofweekval']]['val'] = intval($v['allnum']);
					}
				}
			}
			$stat_arr['series'][0]['name'] = '上周';
			$stat_arr['series'][0]['data'] = array_values($up_arr);
			$stat_arr['series'][1]['name'] = '本周';
			$stat_arr['series'][1]['data'] = array_values($curr_arr);
			//统计数据标题
			$statlist['headertitle'] = array('星期','上周','本周','同比');
			Tpl::output('actionurl',$actionurl.'&search_type=week&search_time_year='.$current_year.'&search_time_month='.$current_month.'&search_time_week='.$current_week);
		}

		if($_REQUEST['search_type'] == 'month'){
			$stime = strtotime($current_year.'-'.$current_month."-01 -1 month");
			$etime = getMonthLastDay($current_year,$current_month)+86400-1;

			$up_month = date('m',$stime);
			$curr_month = date('m',$etime);
			//计算横轴的最大量（由于每个月的天数不同）
			$up_dayofmonth = date('t',$stime);
			$curr_dayofmonth = date('t',$etime);
			$x_max = $up_dayofmonth > $curr_dayofmonth ? $up_dayofmonth : $curr_dayofmonth;

		    //构造横轴数据
			for($i=1; $i<=$x_max; $i++){
				//统计图数据
				$up_arr[$i] = 0;
				$curr_arr[$i] = 0;
				//统计表数据
				$uplist_arr[$i]['timetext'] = $i;
				$currlist_arr[$i]['timetext'] = $i;
				//方便搜索会员列表，计算开始时间和结束时间
				$currlist_arr[$i]['stime'] = strtotime($current_year.'-'.$current_month."-01")+($i-1)*86400;
				$currlist_arr[$i]['etime'] = $currlist_arr[$i]['stime']+86400 - 1;
				$uplist_arr[$i]['val'] = 0;
				$currlist_arr[$i]['val'] = 0;
				//横轴
				$stat_arr['xAxis']['categories'][] = $i;
			}
			$where['store_time'] = array('between', array($stime,$etime));
			$field .= ',MONTH(FROM_UNIXTIME(store_time)) as monthval,day(FROM_UNIXTIME(store_time)) as dayval ';
			$memberlist = $model->getNewStoreStatList($where, $field, 0, '', 0, 'monthval,dayval');
		    if($memberlist){
				foreach($memberlist as $k=>$v){
					if ($up_month == $v['monthval']){
						$up_arr[$v['dayval']] = intval($v['allnum']);
						$uplist_arr[$v['dayval']]['val'] = intval($v['allnum']);
					}
					if ($curr_month == $v['monthval']){
						$curr_arr[$v['dayval']] = intval($v['allnum']);
						$currlist_arr[$v['dayval']]['val'] = intval($v['allnum']);
					}
				}
			}
			$stat_arr['series'][0]['name'] = '上月';
			$stat_arr['series'][0]['data'] = array_values($up_arr);
			$stat_arr['series'][1]['name'] = '本月';
			$stat_arr['series'][1]['data'] = array_values($curr_arr);
			//统计数据标题
			$statlist['headertitle'] = array('日期','上月','本月','同比');
			Tpl::output('actionurl',$actionurl.'&search_type=month&search_time_year='.$current_year.'&search_time_month='.$current_month);
		}

		//计算同比
		foreach ((array)$currlist_arr as $k=>$v){
			$tmp = array();
			$tmp['seartime'] = $v['stime'].'|'.$v['etime'];
			$tmp['timetext'] = $v['timetext'];
			$tmp['currentdata'] = $v['val'];
			$tmp['updata'] = $uplist_arr[$k]['val'];
			$tmp['tbrate'] = getTb($tmp['updata'], $tmp['currentdata']);
			$statlist['data'][]  = $tmp;
		}

		//导出Excel
        if ($_GET['exporttype'] == 'excel'){
			//导出Excel
			import('libraries.excel');
		    $excel_obj = new Excel();
		    $excel_data = array();
		    //设置样式
		    $excel_obj->setStyle(array('id'=>'s_title','Font'=>array('FontName'=>'宋体','Size'=>'12','Bold'=>'1')));
			//header
			foreach ($statlist['headertitle'] as $v){
			    $excel_data[0][] = array('styleid'=>'s_title','data'=>$v);
			}
			//data
			foreach ($statlist['data'] as $k=>$v){
				$excel_data[$k+1][] = array('data'=>$v['timetext']);
				$excel_data[$k+1][] = array('format'=>'Number','data'=>$v['updata']);
				$excel_data[$k+1][] = array('format'=>'Number','data'=>$v['currentdata']);
				$excel_data[$k+1][] = array('data'=>$v['tbrate']);
			}
			$excel_data = $excel_obj->charset($excel_data,CHARSET);
			$excel_obj->addArray($excel_data);
		    $excel_obj->addWorksheet($excel_obj->charset('新增店铺统计',CHARSET));
		    $excel_obj->generateXML($excel_obj->charset('新增店铺统计',CHARSET).date('Y-m-d-H',time()));
			exit();
		} else {
			//得到统计图数据
    		$stat_arr['title'] = '新增店铺统计';
            $stat_arr['yAxis'] = '新增店铺数';
    		$stat_json = getStatData_LineLabels($stat_arr);
    		Tpl::output('stat_json',$stat_json);
    		Tpl::output('statlist',$statlist);
    		Tpl::output('top_link',$this->sublink($this->links, 'newstore'));
			Tpl::showpage('stat.newstore');
		}
    }
	/**
     * 热卖排行
     */
    public function hotrankOp(){
        if(!$this->search_arr['search_type']){
			$this->search_arr['search_type'] = 'day';
		}
		$model = Model('stat');
		//获得搜索的开始时间和结束时间
		$searchtime_arr = $model->getStarttimeAndEndtime($this->search_arr);
		Tpl::output('searchtime',implode('|',$searchtime_arr));
        Tpl::output('top_link',$this->sublink($this->links, 'hotrank'));
        Tpl::showpage('stat.store.hotrank');
    }
    /**
     * 热卖排行列表
     */
    public function hotrank_listOp(){
        $datanum = 30;
        $model = Model('stat');
        switch ($_GET['type']){
		   case 'ordernum':
		       $sort_text = '下单量';
		       break;
		   default:
		       $_GET['type'] = 'orderamount';
		       $sort_text = '下单金额';
		       break;
		}
		$where = array();
        $searchtime_arr_tmp = explode('|',$this->search_arr['t']);
		foreach ((array)$searchtime_arr_tmp as $k=>$v){
		    $searchtime_arr[] = intval($v);
		}
        //店铺分类
    	$search_sclass = intval($_REQUEST['search_sclass']);
		if ($search_sclass){
		    $where['sc_id'] = $search_sclass;
		}
		$where['order_isvalid'] = 1;//计入统计的有效订单
		$where['order_add_time'] = array('between',$searchtime_arr);
		//查询统计数据
		$field = ' store_id,store_name ';
		switch ($_GET['type']){
		   case 'ordernum':
		       $field .= ' ,COUNT(*) as ordernum ';
		       $orderby = 'ordernum desc';
		       break;
		   default:
		       $_GET['type'] = 'orderamount';
		       $field .= ' ,SUM(order_amount) as orderamount ';
		       $orderby = 'orderamount desc';
		       break;
		}
		$orderby .= ',store_id';
		$statlist = $model->statByStatorder($where, $field, 0, $datanum, $orderby, 'store_id');
		foreach ((array)$statlist as $k=>$v){
		    $statlist[$k]['sort'] = $k+1;
		}
		/**
		 * 飙升榜
		 */
		$soaring_statlist = array();//飙升榜数组
		//查询期间产生订单的店铺数
		$where = array();
		//店铺分类
    	$search_sclass = intval($_REQUEST['search_sclass']);
		if ($search_sclass){
		    $where['sc_id'] = $search_sclass;
		}
		$where['order_isvalid'] = 1;//计入统计的有效订单
		$where['order_add_time'] = array('between',$searchtime_arr);
		$field = 'COUNT(*) as countnum';
		$countnum = $model->getoneByStatorder($where, $field);
		$countnum = $countnum['countnum'];
		if ($countnum > 0){
    		$store_arr = array();
    		$field = 'store_id,store_name,order_amount';
    		for ($i=0; $i<$countnum; $i+=1000){//由于数据库底层的限制，所以每次查询1000条
    		    $order_list = array();
    		    $order_list = $model->statByStatorder($where, $field, 0, $i.',1000', 'order_id');
    		    foreach ((array)$order_list as $k=>$v){
    		        $store_arr[$v['store_id']]['orderamount'] = $store_arr[$v['store_id']]['orderamount'] + $v['order_amount'];
    		        $store_arr[$v['store_id']]['ordernum'] = intval($store_arr[$v['store_id']]['ordernum']) + 1;
    		        $store_arr[$v['store_id']]['store_name'] = $v['store_name'];
    		        $store_arr[$v['store_id']]['store_id'] = $v['store_id'];
    		    }
    		}
    		//查询同一时间周期相比的环比数值
    		$where = array();
    		$stime = $searchtime_arr[0] - ($searchtime_arr[1] - $searchtime_arr[0]) - 1;
    		$etime = $searchtime_arr[0] - 1;
    		//店铺分类
        	$search_sclass = intval($_REQUEST['search_sclass']);
    		if ($search_sclass){
    		    $where['sc_id'] = $search_sclass;
    		}
    		$where['order_isvalid'] = 1;//计入统计的有效订单
    		$where['order_add_time'] = array('between',array($stime,$etime));
    		$field = 'COUNT(*) as up_countnum';
    		$up_countnum = $model->getoneByStatorder($where, $field);
    		$up_countnum = $up_countnum['up_countnum'];
    		$up_store_arr = array();
    		if ($up_countnum > 0){
        		$field = 'store_id,store_name,order_amount';
        		for ($i=0; $i<$up_countnum; $i+=1000){//由于数据库底层的限制，所以每次查询1000条
        		    $order_list = array();
        		    $order_list = $model->statByStatorder($where, $field, 0, $i.',1000', 'store_id');
        		    foreach ((array)$order_list as $k=>$v){
        		        $up_store_arr[$v['store_id']]['orderamount'] = $up_store_arr[$v['store_id']]['orderamount'] + $v['order_amount'];
        		        $up_store_arr[$v['store_id']]['ordernum'] = intval($up_store_arr[$v['store_id']]['ordernum']) + 1;
        		    }
        		}
    		}
    		//计算环比飙升数值
    		$soaring_arr = array();
    		foreach ((array)$store_arr as $k=>$v){
    		    if ($up_store_arr[$k][$_GET['type']] > 0){//上期数值大于0，则计算飙升值，否则不计入统计
    		        $soaring_arr[$k] = round((($v[$_GET['type']]-$up_store_arr[$k][$_GET['type']])/$up_store_arr[$k][$_GET['type']]*100),2);
    		    }
    		}
    		arsort($soaring_arr);//降序排列数组
    		$i = 1;
    		//取出前10名飙升店铺
    		foreach ((array)$soaring_arr as $k=>$v){
    		    if ($i <= $datanum){
            		$tmp = array();
            		$tmp['sort'] = $i;
            		$tmp['store_name'] = $store_arr[$k]['store_name'];
            		$tmp['store_id'] = $store_arr[$k]['store_id'];
            		$tmp['hb'] = $v;
            		switch ($_GET['type']){
            		   case 'ordernum':
            		       $tmp['ordernum'] = $store_arr[$k]['ordernum'];
            		       break;
            		   case 'orderamount':
            		       $tmp['orderamount'] = ncPriceFormat($store_arr[$k]['orderamount']);
            		       break;
            		}
    		        $soaring_statlist[] = $tmp;
    		        $i++;
    		    } else {
    		        break;
    		    }
    		}
		}
		Tpl::output('soaring_statlist',$soaring_statlist);
		Tpl::output('statlist',$statlist);
		Tpl::output('sort_text',$sort_text);
		Tpl::output('stat_field',$_GET['type']);
		Tpl::showpage('stat.store.hotrank.list','null_layout');
    }
    /**
     * 店铺等级
     */
    public function degreeOp(){
    	$where = array();
    	$field = ' count(*) as allnum,grade_id ';
    	$model = Model('stat');
    	//查询店铺分类下的店铺
    	$search_sclass = intval($_REQUEST['search_sclass']);
    	if ($search_sclass > 0){
    	    $where['sc_id'] = $search_sclass;
    	}
    	$storelist = $model->getNewStoreStatList($where, $field, 0, '', 0, 'grade_id');
    	$sd_list = $model->getStoreDegree();
    	$statlist['headertitle'] = array();
    	$statlist['data'] = array();
    	//处理数组数据
    	if(!empty($storelist)){
    		foreach ($storelist as $k=>$v){
    			$storelist[$k]['p_name'] = $v['grade_id'] > 0?$sd_list[$v['grade_id']]:'平台店铺';
    			$storelist[$k]['allnum'] = intval($v['allnum']);
    			$statlist['headertitle'][] = $v['grade_id'] > 0?$sd_list[$v['grade_id']]:'平台店铺';
    			$statlist['data'][] = $v['allnum'];
    		}
    		$data = array(
    			'title'=>'店铺等级统计',
    			'name'=>'店铺个数',
    			'label_show'=>true,
    			'series'=>$storelist
    		);
    		Tpl::output('stat_json',getStatData_Pie($data));
    	}
		Tpl::output('top_link',$this->sublink($this->links, 'degree'));
		Tpl::showpage('stat.storedegree');
    }
	/**
	 * 查看店铺列表
	 */
	public function showstoreOp(){
		$model = Model('stat');
		$where = array();
		if (in_array($_GET['type'],array('newbyday','newbyweek','newbymonth','storearea'))){
		    $actionurl = 'index.php?act=stat_store&op=showstore&type='.$_GET['type'].'&t='.$this->search_arr['t'];
    		$searchtime_arr_tmp = explode('|',$this->search_arr['t']);
    		foreach ((array)$searchtime_arr_tmp as $k=>$v){
    		    $searchtime_arr[] = intval($v);
    		}
		    $where['store_time'] = array('between',$searchtime_arr);
		}
		//商品分类
		$sc_id = intval($_GET['scid']);
		if ($sc_id > 0){
		    $where['sc_id'] = $sc_id;
		    $actionurl .="&scid=$sc_id";
		}
		//省份
		if (isset($_GET['provid'])){
		    $province_id = intval($_GET['provid']);
		    $where['province_id'] = $province_id;
		    $actionurl .="&provid=$province_id";
		}

		if ($_GET['exporttype'] == 'excel'){
		    $store_list = $model->getNewStoreStatList($where);
		} else {
		    $store_list = $model->getNewStoreStatList($where, '', 10);
		}

		//店铺等级
		$model_grade = Model('store_grade');
		$grade_list = $model_grade->getGradeList();
		if (!empty($grade_list)){
			$search_grade_list = array();
			foreach ($grade_list as $k => $v){
				$search_grade_list[$v['sg_id']] = $v['sg_name'];
			}
		}
		//导出Excel
        if ($_GET['exporttype'] == 'excel'){
            //导出Excel
			import('libraries.excel');
		    $excel_obj = new Excel();
		    $excel_data = array();
		    //设置样式
		    $excel_obj->setStyle(array('id'=>'s_title','Font'=>array('FontName'=>'宋体','Size'=>'12','Bold'=>'1')));
			//header
		    $excel_data[0][] = array('styleid'=>'s_title','data'=>'店铺名称');
		    $excel_data[0][] = array('styleid'=>'s_title','data'=>'店主账号');
		    $excel_data[0][] = array('styleid'=>'s_title','data'=>'店主卖家账号');
		    $excel_data[0][] = array('styleid'=>'s_title','data'=>'所属等级');
		    $excel_data[0][] = array('styleid'=>'s_title','data'=>'有效期至');
		    $excel_data[0][] = array('styleid'=>'s_title','data'=>'开店时间');
			//data
			foreach ($store_list as $k=>$v){
				$excel_data[$k+1][] = array('data'=>$v['store_name']);
				$excel_data[$k+1][] = array('data'=>$v['member_name']);
				$excel_data[$k+1][] = array('data'=>$v['seller_name']);
				$excel_data[$k+1][] = array('data'=>$search_grade_list[$v['grade_id']]);
				$excel_data[$k+1][] = array('data'=>$v['store_end_time']?date('Y-m-d', $v['store_end_time']):'无限制');
				$excel_data[$k+1][] = array('data'=>date('Y-m-d', $v['store_time']));
			}
			$excel_data = $excel_obj->charset($excel_data,CHARSET);
			$excel_obj->addArray($excel_data);
		    $excel_obj->addWorksheet($excel_obj->charset('新增店铺',CHARSET));
		    $excel_obj->generateXML($excel_obj->charset('新增店铺',CHARSET).date('Y-m-d-H',time()));
			exit();
        }
        Tpl::output('search_grade_list', $search_grade_list);
        Tpl::output('actionurl',$actionurl);
		Tpl::output('store_list',$store_list);
		Tpl::output('show_page',$model->showpage(2));
		$this->links[] = array('url'=>'act=stat_store&op=showstore','lang'=>'stat_storelist');
		Tpl::output('top_link',$this->sublink($this->links, 'showstore'));
	    Tpl::showpage('stat.info.storelist');
	}

	/**
	 * 销售统计
	 */
	public function storesalesOp(){
	    if(!$this->search_arr['search_type']){
			$this->search_arr['search_type'] = 'day';
		}
		$model = Model('stat');
		//获得搜索的开始时间和结束时间
		$searchtime_arr = $model->getStarttimeAndEndtime($this->search_arr);
		Tpl::output('searchtime',implode('|',$searchtime_arr));
        Tpl::output('top_link',$this->sublink($this->links, 'storesales'));
        Tpl::showpage('stat.store.sales');
	}
	/**
	 * 店铺销售统计列表
	 */
	public function storesales_listOp(){
		$model = Model('stat');
	    $searchtime_arr_tmp = explode('|',$this->search_arr['t']);
		foreach ((array)$searchtime_arr_tmp as $k=>$v){
		    $searchtime_arr[] = intval($v);
		}
		$where = array();
		$where['order_isvalid'] = 1;//计入统计的有效订单
		$where['order_add_time'] = array('between',$searchtime_arr);
		//店铺分类
    	$search_sclass = intval($_REQUEST['search_sclass']);
		if ($search_sclass){
		    $where['sc_id'] = $search_sclass;
		}
		//店铺名称
		$where['store_name'] = array('like',"%{$_GET['search_sname']}%");
		//查询总条数
	    $count_arr = $model->getoneByStatorder($where, 'COUNT(DISTINCT store_id) as countnum');
		$countnum = intval($count_arr['countnum']);
		//列表字段
		$field = " store_id,store_name,SUM(order_amount) as orderamount, COUNT(*) as ordernum, COUNT(DISTINCT buyer_id) as membernum";
		//排序
		$orderby_arr = array('membernum asc','membernum desc','ordernum asc','ordernum desc','orderamount asc','orderamount desc');
	    if (!in_array(trim($this->search_arr['orderby']),$orderby_arr)){
		    $this->search_arr['orderby'] = 'membernum desc';
		}
		$orderby = trim($this->search_arr['orderby']).',store_id asc';

	    if ($this->search_arr['exporttype'] == 'excel'){
		    $statlist = $model->statByStatorder($where, $field, 0, 0, $orderby, 'store_id');
		} else {
		    $statlist = $model->statByStatorder($where, $field, array(20,$countnum), 0, $orderby, 'store_id');
		    foreach ((array)$statlist as $k=>$v){
		        $v['view'] = "<a href='javascript:void(0);' nc_type='showtrends' data-param='{\"storeid\":\"{$v['store_id']}\"}'>走势图</a>";
		        $statlist[$k] = $v;
		    }
		}

		//列表header
		$statheader = array();
        $statheader[] = array('text'=>'店铺名称','key'=>'store_name');
        $statheader[] = array('text'=>'下单会员数','key'=>'membernum','isorder'=>1);
        $statheader[] = array('text'=>'下单量','key'=>'ordernum','isorder'=>1);
        $statheader[] = array('text'=>'下单金额','key'=>'orderamount','isorder'=>1);

	    //导出Excel
        if ($this->search_arr['exporttype'] == 'excel'){
            //导出Excel
			import('libraries.excel');
		    $excel_obj = new Excel();
		    $excel_data = array();
		    //设置样式
		    $excel_obj->setStyle(array('id'=>'s_title','Font'=>array('FontName'=>'宋体','Size'=>'12','Bold'=>'1')));
			//header
			foreach ($statheader as $k=>$v){
			    $excel_data[0][] = array('styleid'=>'s_title','data'=>$v['text']);
			}
			//data
			foreach ($statlist as $k=>$v){
    			foreach ($statheader as $h_k=>$h_v){
    			    $excel_data[$k+1][] = array('data'=>$v[$h_v['key']]);
    			}
			}
			$excel_data = $excel_obj->charset($excel_data,CHARSET);
			$excel_obj->addArray($excel_data);
		    $excel_obj->addWorksheet($excel_obj->charset('店铺销售统计',CHARSET));
		    $excel_obj->generateXML($excel_obj->charset('店铺销售统计',CHARSET).date('Y-m-d-H',time()));
			exit();
        }
		Tpl::output('statlist',$statlist);
		Tpl::output('statheader',$statheader);
		Tpl::output('orderby',$this->search_arr['orderby']);
		Tpl::output('actionurl',"index.php?act={$this->search_arr['act']}&op={$this->search_arr['op']}&t={$this->search_arr['t']}&search_sclass={$search_sclass}&search_sname={$_GET['search_sname']}");
		Tpl::output('show_page',$model->showpage(2));
		Tpl::showpage('stat.listandorder','null_layout');
	}
	/**
	 * 销售走势
	 */
	public function storesales_trendsOp(){
	    $storeid = intval($_GET['storeid']);
	    if ($storeid <= 0){
	        Tpl::output('stat_error','走势图加载错误');
	        Tpl::showpage('stat.store.salestrends');
	        exit();
	    }
	    if (!$_GET['search_type']){
	        $_GET['search_type'] = 'day';
	    }
		$model = Model('stat');
		$where = array();
		$where['store_id'] = $storeid;
	    $searchtime_arr_tmp = explode('|',$this->search_arr['t']);
		foreach ((array)$searchtime_arr_tmp as $k=>$v){
		    $searchtime_arr[] = intval($v);
		}
		$where['order_isvalid'] = 1;//计入统计的有效订单
		$where['order_add_time'] = array('between',$searchtime_arr);

		$field = " store_id,store_name,SUM(order_amount) as orderamount, COUNT(*) as ordernum, COUNT(DISTINCT buyer_id) as membernum";
		$stat_arr = array('orderamount'=>array(),'ordernum'=>array(),'membernum'=>array());
		$statlist = array();
		if($_GET['search_type'] == 'day'){
			//构造横轴数据
			for($i=0; $i<24; $i++){
				//横轴
				foreach ($stat_arr as $k=>$v){
				    $stat_arr[$k]['xAxis']['categories'][] = "$i";
				    $statlist[$k][$i] = 0;
				}
			}
			$field .= ' ,HOUR(FROM_UNIXTIME(order_add_time)) as timeval ';
		}
	    if($_GET['search_type'] == 'week'){
	        //构造横轴数据
	        for($i=1; $i<=7; $i++){
	            $tmp_weekarr = getSystemWeekArr();
				//横轴
	            foreach ($stat_arr as $k=>$v){
				    $stat_arr[$k]['xAxis']['categories'][] = $tmp_weekarr[$i];
				    $statlist[$k][$i] = 0;
				}
				unset($tmp_weekarr);
			}
			$field .= ' ,WEEKDAY(FROM_UNIXTIME(order_add_time))+1 as timeval ';
		}
		if($_GET['search_type'] == 'month'){
		    //计算横轴的最大量（由于每个月的天数不同）
			$dayofmonth = date('t',$searchtime_arr[0]);
		    //构造横轴数据
			for($i=1; $i<=$dayofmonth; $i++){
				//横轴
			    foreach ($stat_arr as $k=>$v){
				    $stat_arr[$k]['xAxis']['categories'][] = $i;
				    $statlist[$k][$i] = 0;
				}
			}
			$field .= ' ,day(FROM_UNIXTIME(order_add_time)) as timeval ';
		}
		//查询数据
		$statlist_tmp = $model->statByStatorder($where, $field, 0, '', 'timeval','timeval');
		//整理统计数组
		$storename = '';
	    if($statlist_tmp){
			foreach($statlist_tmp as $k => $v){
			    $storename = $v['store_name'];
			    foreach ($stat_arr as $t_k=>$t_v){
			        if ($k == 'orderamount'){
			            $statlist[$t_k][$v['timeval']] = round($v[$t_k],2);
			        } else {
			            $statlist[$t_k][$v['timeval']] = intval($v[$t_k]);
			        }
			    }
			}
		}
		foreach ($stat_arr as $k=>$v){
		    $stat_arr[$k]['legend']['enabled'] = false;
    		switch ($k){
    		    case 'orderamount':
    		        $caption = '下单金额';
    		        break;
    		    case 'ordernum':
    		        $caption = '下单量';
    		        break;
    		    default:
    		        $caption = '下单会员数';
    		        break;
    		}
    		$stat_arr[$k]['series'][0]['name'] = $caption;
		    $stat_arr[$k]['series'][0]['data'] = array_values($statlist[$k]);
    		$stat_arr[$k]['title'] = $caption.'走势';
    		$stat_arr[$k]['yAxis'] = $caption;
    		//得到统计图数据
    		$stat_json[$k] = getStatData_LineLabels($stat_arr[$k]);
		}
		Tpl::output('storename',$storename);
		Tpl::output('stat_json',$stat_json);
		Tpl::showpage('stat.store.salestrends','null_layout');
	}
	/**
	 * 地区分布
	 */
	public function storeareaOp(){
		$model = Model('stat');
		//获得搜索的开始时间和结束时间
		$searchtime_arr = $model->getStarttimeAndEndtime($this->search_arr);
		$where = array();
		if (trim($_GET['search_time'])){
		    $where['store_time'] = array('elt',strtotime($_GET['search_time']));
		}
		$search_sclass = intval($_REQUEST['search_sclass']);
		if ($search_sclass > 0){
		    $where['sc_id'] = $search_sclass;
		}
		$field = ' province_id, COUNT(*) as storenum ';
		$statlist_tmp = $model->statByStore($where, $field, 0, 0, 'storenum desc,province_id', 'province_id');
		// 地区
        $province_array = Model('area')->getTopLevelAreas();
        //地图显示等级数组
        $level_arr = array(array(1,2,3),array(4,5,6),array(7,8,9),array(10,11,12));
        $statlist = array();
		foreach ((array)$statlist_tmp as $k=>$v){
		    $v['level'] = 4;//排名
		    foreach ($level_arr as $lk=>$lv){
		        if (in_array($k+1,$lv)){
		            $v['level'] = $lk;//排名
		        }
		    }
		    $province_id = intval($v['province_id']);
		    $v['sort'] = $k+1;
		    $v['provincename'] = ($t = $province_array[$province_id]) ? $t : '其他';
		    $statlist[$province_id] = $v;
		}
        $stat_arr = array();
		foreach ((array)$province_array as $k=>$v){
		    if ($statlist[$k]){
		        $stat_arr[] = array('cha'=>$k,'name'=>$v,'des'=>"，店铺量：{$statlist[$k]['storenum']}",'level'=>$statlist[$k]['level']);
		    } else {
		        $stat_arr[] = array('cha'=>$k,'name'=>$v,'des'=>'，无订单数据','level'=>4);
		    }
		}
		$stat_json = getStatData_Map($stat_arr);
		Tpl::output('stat_json',$stat_json);
		Tpl::output('statlist',$statlist);
		$actionurl = 'index.php?act=stat_store&op=showstore&type=storearea';
		if (trim($_GET['search_time'])){
		    $actionurl = $actionurl.'&t=0|'.strtotime($_GET['search_time']);
		}
		if ($search_sclass > 0){
		    $actionurl .= "&scid=$search_sclass";
		}
		Tpl::output('actionurl',$actionurl);
		Tpl::output('top_link',$this->sublink($this->links, 'storearea'));
		Tpl::showpage('stat.storearea');
	}
}
