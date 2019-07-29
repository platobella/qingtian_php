<?php
// +------------------------------------------------------------------------------------------------
// | 【使用说明】请将本文件上传至老虎系统网站服务器：/addons/对应标识/inc/mobile/  目录下。
// +------------------------------------------------------------------------------------------------
// | [18淘客助手api文件(老虎微信淘宝客专用-兼容所有新老版本的老虎系统)] Copyright (c) 2018 18.LA
// +------------------------------------------------------------------------------------------------
// | 最后修改：2018年9月5日
// +------------------------------------------------------------------------------------------------
// | 官网：http://taoke.18.la/
// +------------------------------------------------------------------------------------------------
global $_W, $_GPC, $return, $cfg;

$cfg = m("fc")->fc_getglobal();


$return  = array('state'=>'ok','code'=>1,'message'=>'','version'=>'3.0','system'=>urlencode('晴天系统'),'updatetime'=>urlencode('2018年9月5日 18:18:18'));

//如果未传入有效参数
if (!isset($_GPC['key']) || !isset($_GPC['api'])) {
    $return['code']=0;
	$return['message']=urlencode('API接口正常');
	exit(urldecode(json_encode($return)));
}

//判断密钥
if($cfg['taoke_18_key']!=$_GPC['key']){
	$return['code']=0;
	$return['message']=urlencode('密钥错误');
	exit(urldecode(json_encode($return)));
}

//设置常用变量
$_W['uniacid']=$_GPC['i'];//公众号id
$api=$_GPC['api'];//api名称
$op=$_GPC['op'];
$dtime=time();//当前时间
$message="";

//接口验证
if($api=='verify'){
	$return['code']=1;
	$return['message']=urlencode('验证成功');
	exit(urldecode(json_encode($return)));
}
//【订单同步】
elseif($api=='postorder'){
	//获取post过来的订单内容
	$content=htmlspecialchars_decode($_GPC['content']);
	//json解码
	$contentArr=@json_decode($content, true);
	//如果数组不为空
	if(!empty($contentArr)){
		$orderDbTable=$this->modulename."_tkorder";//订单数据库表名
		$field=tableField(tablename($orderDbTable));//获取订单表的所有字段
		$resultStr="";//记录订单入库结果字符串
		//遍历订单数组
		foreach($contentArr as $orderID=>$orderData)
		{
			//查询数据库
			$ord=pdo_fetch('select * from '.tablename($orderDbTable)." where weid='{$_W['uniacid']}'  and orderid='{$orderID}'");
			if(count($orderData)>1){//订单号相同的多个订单集合
				//如果已存在
				if (!empty($ord)){
					$result=pdo_delete($orderDbTable,array ('orderid'=>$orderID,'weid'=>$_W['uniacid']));//删除该订单id的所有订单
				}
				//将所有订单ID相同的订单添加到数据库
				$resultTem=1;
				foreach($orderData as $data){
					$newdata=orderFormat($data,$field);//格式化订单数据
					//原有字段值保持不变
					if (!empty($ord)){
						if (!empty($ord['type']))	$newdata['type']=$ord['type'];
						if (!empty($ord['zdgd']))	$newdata['zdgd']=$ord['zdgd'];
					}
					//添加到数据
					$result=pdo_insert($orderDbTable,$newdata);
					if (!empty($result)) {//添加成功
						$resultTem = ($resultTem==1) ? 1 : 0;//如果上次状态为1,这次也标记为1，否则设置为0
					}else{//添加失败
						//尝试修改唯一索引为普通索引
						orderIndex(tablename($orderDbTable));
						//再重新添加一次数据
						$result=pdo_insert($orderDbTable,$newdata);
						if (!empty($result)){
							$resultTem = ($resultTem==1) ? 1 : 0;//如果上次状态为1,这次也标记为1，否则设置为0
						}else{
							$resultTem=0;//将状态标记为失败
						}
					}
				}
				$resultStr=resultState($resultStr,$orderID,$resultTem);//记录订单入库状态
			}else{//订单号不同的单个订单
				$newdata=orderFormat($orderData[0],$field);
				if (!empty($ord)){//如果已存在
					if($ord['orderzt']=='订单失效'){
						$resultStr=resultState($resultStr,$orderID,"1");//失效订单，无需更新，也返回更新成功
						continue;//跳过本次循环
					}
					$result=pdo_update($orderDbTable,$newdata,array('orderid' =>$orderID,'weid'=>$_W['uniacid']));//更新数据库
					if (!empty($result)) {
						//更新成功
						$resultStr=resultState($resultStr,$orderID,"1");
					}else{
						//更新失败
						$resultStr=resultState($resultStr,$orderID,"0");
					}
				}else{
				   //如果不存在,添加到数据
					$result=pdo_insert($orderDbTable,$newdata);
					if (!empty($result)) {
						//添加成功
						$resultStr=resultState($resultStr,$orderID,"1");
					}else{
						//添加失败
						$resultStr=resultState($resultStr,$orderID,"0");
					}
				}
			}
		}
		returnExit($return,1,"result:".$resultStr);//code值设置为1，表示成功
	}else{
		returnExit($return,0,"传入订单数据不正确");//code值设置为0，表示失败
	}
}

//【定时任务】
elseif ($api == 'timetask') {

    m('fc')->fc_log_debug('timetask：123132','timetask');


	//【加载定时任务插件文件】
    if (!empty($op)) {
        $timetaskfilename = IA_ROOT . "/addons/tiger_newhu/inc/mobile/18timetask_" . $op . ".inc.php";
        if (file_exists($timetaskfilename) !== false) {
            include $timetaskfilename;
        }
    }
	//【京东订单同步】
    if ($op == "jdorder") {

		/****************京东订单同步开始****************/
		//【接收参数】
		$start = empty($_GPC['start']) ? 0 : trim($_GPC['start']);//开始天数(默认为0，即从当天开始)
		$day = empty($_GPC['day']) ? 1 : trim($_GPC['day']);//要同步的天数(默认同步1天)
		$hour=empty($_GPC['hour']) ? 24 : trim($_GPC['hour']);//每次同步几小时的订单(默认每次同步24小时)
		$progress=empty($_GPC['progress']) ? 0 : trim($_GPC['progress']);//同步进度参数(默认从0开始，程序自动处理，请勿手工传入此参数)
		$allcount=empty($_GPC['allcount']) ? 0 : trim($_GPC['allcount']);//记录获取订单总数的参数(默认从0开始，程序自动处理，请勿手工传入此参数)
		$sleep=empty($_GPC['sleep']) ? 1 : trim($_GPC['sleep']);//执行完当前页后执行下一页的间隔时间


		//【计算相关时间】
		$startTime=date("Y-m-d H:i:s",strtotime("-".$start." day"));//计算开始时间
		$endTime=date("Y-m-d H:i:s",strtotime("-".$day." day", strtotime($startTime)));//计算结束时间(strtotime可以接受第二个参数，类型timestamp,为指定日期)
		$allHour=abs(floor((strtotime($endTime)-strtotime($startTime))/3600));//开始时间和结束时间相差小时数
		$progressTime=date("Y-m-d H:i:s",strtotime("-".$progress." hours", strtotime($startTime)));//计算进度时间
		//echo '开始时间：'.$startTime.' 结束时间：'.$endTime.' 相差小时数：'.$allHour.' 进度：'.$progress.'/'.$allHour;
		//exit;

		//【引用京东api文件】
		$jdsdkfilename=IA_ROOT . "/addons/tiger_newhu/inc/sdk/tbk/jd.php";
		if (file_exists($jdsdkfilename) == false) returnExit($return, 0, '你的淘客系统缺少:'.$jdsdkfilename.'文件');//判断文件是否存在
		include $jdsdkfilename; 
		//【读取京东相关配置】
		$jdset=pdo_fetch("select * from ".tablename('tuike_jd_jdset')." where weid='{$_W['uniacid']}' order by id desc");
		$jdsign=pdo_fetch("select * from ".tablename('tuike_jd_jdsign')." where weid='{$_W['uniacid']}' order by id desc");
		if(empty($jdset) || empty($jdsign))	returnExit($return, 0, '读取京东模块配置失败');

		$thisStartTime=date("Y年m月d日H时",strtotime($progressTime));
		$count=0;
		//【每次同步小时数循环获取订单】
		for ($i=0; $i<$hour; $i++) {
			if($i>0) $progressTime=date('Y-m-d H:i:s', strtotime ("-1 hours", strtotime($progressTime)));//计算进度时间
			$orderTime=date("YmdH",strtotime($progressTime));//计算要同步的订单时间(格式:年月日时,例如:2018080808)

			/*获取京东订单并入库开始*/
			//【通过API接口获取订单数据】
			$page=1;
			$res=getkhorder($jdsign['access_token'],$jdset['unionid'],$orderTime,$jdset['appkey'],$jdset['appsecret'],$page);
			//【判断是否获取到数据】
			if(!empty($res)){
				foreach($res as $k=>$v){
					$data=array(
						'weid'=>$_W['uniacid'],
						'finishTime'=>substr($v['finishTime'] , 0 , 10),
						'orderEmt'=>$v['orderEmt'],
						'orderId'=>$v['orderId'],
						'orderTime'=>substr($v['orderTime'] , 0 , 10),
						'parentId'=>$v['parentId'],
						'payMonth'=>$v['payMonth'],
						'plus'=>$v['plus'],
						'popId'=>$v['popId'],
						'actualCommission'=>$v['skuList'][0]['actualCommission'],
						'actualCosPrice'=>$v['skuList'][0]['actualCosPrice'],
						'actualFee'=>$v['skuList'][0]['actualFee'],
						'commissionRate'=>$v['skuList'][0]['commissionRate'],
						'estimateCommission'=>$v['skuList'][0]['estimateCommission'],
						'estimateCosPrice'=>$v['skuList'][0]['estimateCosPrice'],
						'estimateFee'=>$v['skuList'][0]['estimateFee'],
						'finalRate'=>$v['skuList'][0]['finalRate'],
						'firstLevel'=>$v['skuList'][0]['firstLevel'],
						'frozenSkuNum'=>$v['skuList'][0]['frozenSkuNum'],
						'payPrice'=>$v['skuList'][0]['payPrice'],
						'pid'=>$v['skuList'][0]['pid'],
						'price'=>$v['skuList'][0]['price'],
						'secondLevel'=>$v['skuList'][0]['secondLevel'],
						'siteId'=>$v['skuList'][0]['siteId'],
						'skuId'=>$v['skuList'][0]['skuId'],
						'skuName'=>$v['skuList'][0]['skuName'],
						'skuNum'=>$v['skuList'][0]['skuNum'],
						'skuReturnNum'=>$v['skuList'][0]['skuReturnNum'],
						'spId'=>$v['skuList'][0]['spId'],
						'subSideRate'=>$v['skuList'][0]['subSideRate'],
						'subUnionId'=>$v['skuList'][0]['subUnionId'],
						'subsidyRate'=>$v['skuList'][0]['subsidyRate'],
						'thirdLevel'=>$v['skuList'][0]['thirdLevel'],
						'traceType'=>$v['skuList'][0]['traceType'],
						'unionAlias'=>$v['skuList'][0]['unionAlias'],
						'unionTrafficGroup'=>$v['skuList'][0]['unionTrafficGroup'],
						'unionTag'=>$v['skuList'][0]['unionTag'],
						'validCode'=>$v['skuList'][0]['validCode'],
						
						'unionId'=>$v['unionId'],
						'unionUserName'=>$v['unionUserName'],
						'createtime'=>time()
					);
					//print_r($data);
					//exit;

					 /*订单入库开始*/
					 $ord=pdo_fetchall ( 'select * from ' . tablename ( $this->modulename . "_jdorder" ) . " where weid='{$_W['uniacid']}' and orderId='{$v['orderId']}'" );
					 if(empty($ord)){
						if(!empty($data['orderId'])){
							//插入数据
							$a=pdo_insert ($this->modulename . "_jdorder", $data );
							$count++;
						}						 	
					 }else{
						if(!empty($v['orderId'])){
							//更新数据
							$b=pdo_update($this->modulename . "_jdorder",$data, array ('orderId' =>$v['orderId'],'weid'=>$_W['uniacid']));
							$count++;
						}
					 }
					 /*订单入库结束*/

				}
				
			}
			/*获取京东订单并入库结束*/

			$progress++;//进度加1
			
		}
		$thisEndTime=date("Y年m月d日H时",strtotime($progressTime));

		//计算同步进度百分比
		$percent=round(($progress/$allHour)*100,2);
		if($percent>100)	$percent=100;
		//计算本轮同步成功的总数
		$allcount+=$count;
		//判断是否同步完成所有任务
		if($progress<$allHour){
			$message=($count>0)? "成功同步".$count."个京东订单(进度:".$percent."% 时间段:".$thisStartTime."-".$thisEndTime.")" : "同步京东订单成功(进度:".$percent."% 时间段:".$thisStartTime."-".$thisEndTime.")";
			$return['timetaskdata'] = array('param' => 'progress=' . $progress.'&allcount='.$allcount, 'sleep' => $sleep);//返回回传参数
		}else{
			$message=$startTime."到".$endTime."共".$day."天的京东订单同步完毕，累计获取".$allcount."个订单!";
		}
		returnExit($return, 1, $message);
		/****************京东订单同步结束****************/
		
    }
    //【拼多多订单同步】
    elseif ($op == "pddorder") {

		/****************拼多多订单同步开始****************/
		//【接收参数】
		$start = empty($_GPC['start']) ? 0 : trim($_GPC['start']);//开始天数(默认为0，即从当天开始)
		$day = empty($_GPC['day']) ? 1 : trim($_GPC['day']);//要同步的天数(默认同步1天)

		$page=empty($_GPC['page']) ? 1 : trim($_GPC['page']);//同步页码进度参数(默认从1开始，程序自动处理，请勿手工传入此参数)

		$allcount=empty($_GPC['allcount']) ? 0 : trim($_GPC['allcount']);//记录获取订单总数的参数(默认从0开始，程序自动处理，请勿手工传入此参数)
		$sleep=empty($_GPC['sleep']) ? 1 : trim($_GPC['sleep']);//执行完当前页后执行下一页的间隔时间


		//【计算相关时间】
		$startTime=date("Y-m-d H:i:s",strtotime("-".$start." day"));//计算开始时间
		$endTime=date("Y-m-d H:i:s",strtotime("-".$day." day", strtotime($startTime)));//计算结束时间(strtotime可以接受第二个参数，类型timestamp,为指定日期)

		//echo '开始时间：'.$startTime.' 结束时间：'.$endTime;
		//exit;

		//【引用拼多多api文件】
		$pddsdkfilename=IA_ROOT . "/addons/tiger_newhu/inc/sdk/tbk/pdd.php";
		if (file_exists($pddsdkfilename) == false) returnExit($return, 0, '你的淘客系统缺少:'.$jdsdkfilename.'文件');//判断文件是否存在
		include $pddsdkfilename; 
		//【读取拼多多相关配置】
		$pddset=pdo_fetch("select * from ".tablename('tuike_pdd_set')." where weid='{$_W['uniacid']}'");
		$owner_name=$pddset['ddjbbuid'];
		if(empty($pddset))	returnExit($return, 0, '读取拼多多模块配置失败');

		$count=0;
		//【通过API接口获取订单数据】
		$start_time=strtotime($endTime);
		$end_time=strtotime($startTime);
		$res=pddtgworder1($owner_name,$page,$start_time,$end_time,$p_id);	
		//判断是否出错
		if(!empty($orderlist['error_response']['error_msg'])){
			returnExit($return, 1, $orderlist['error_response']['error_msg']);
		}
		$orderlist=$res['order_list_get_response']['order_list'];				
		//遍历获取到的数据
		foreach($orderlist as $k=>$v){
			$row = pdo_fetch("SELECT * FROM " . tablename($this->modulename.'_pddorder') . " WHERE weid='{$_W['uniacid']}' and order_sn='{$v['order_sn']}'");
			$data=array(
				"weid"=>$_W['uniacid'],
				"order_sn" =>$v['order_sn'],
				"goods_id" => $v['goods_id'],
				"goods_name" => $v['goods_name'],
				"goods_thumbnail_url" => $v['goods_thumbnail_url'],
				"goods_quantity" => $v['goods_quantity'],
				"goods_price" => $v['goods_price']/100,
				"order_amount" => $v['order_amount']/100,
				"order_create_time" => $v['order_create_time'],
				"order_settle_time" => $v['order_settle_time'],
				"order_verify_time" => $v['order_verify_time'],
				"order_receive_time" => $v['order_receive_time'],
				"order_pay_time" => $v['order_pay_time'],
				"promotion_rate" => $v['promotion_rate']/10,
				"promotion_amount" => $v['promotion_amount']/100,
				"batch_no" => $v['batch_no'],
				"order_status" =>$v['order_status'],
				"order_status_desc" => $v['order_status_desc'],
				"verify_time" => $v['verify_time'],
				"order_group_success_time" => $v['order_group_success_time'],
				"order_modify_at" => $v['order_modify_at'],
				"status" => $v['status'],
				"type" => $v['type'],
				"group_id" => $v['group_id'],
				"auth_duo_id" => $v['auth_duo_id'],
				"custom_parameters" => $v['custom_parameters'],
				"p_id" => $v['p_id'],
			);					
			if (!empty($row)){
				pdo_update($this->modulename."_pddorder", $data, array('order_sn' => $v['order_sn'],'weid'=>$_W['uniacid']));
				//echo "更新订单：".$data['order_sn']."成功<br>";
			}else{
				pdo_insert($this->modulename."_pddorder", $data);
				//echo "新订单入库：".$data['order_sn']."成功<br>";
			}
			$count++;
		}

		//计算本轮同步成功的总数
		$allcount+=$count;

		//判断是否获取完毕
		if(!empty($orderlist)){
			$message=($count>0)? "同步拼多多第".$page."页订单成功(本次获取".$count."个)" : "同步拼多多第".$page."页订单成功!";
			$return['timetaskdata'] = array('param' => 'page=' . ($page+1).'&allcount='.$allcount, 'sleep' => $sleep);//返回回传参数
		}else{
			$message=$startTime."到".$endTime."共".$day."天的拼多多订单同步完毕，累计获取".$allcount."个订单!";
		}

		//输出结果并退出程序
		returnExit($return, 1, $message);
		/****************拼多多订单同步结束****************/

    } 
    //【不带返回参数测试】
    elseif ($op == "test1") {
        //1、执行业务函数
        //2、返回结果
        returnExit($return, 1, '定时任务测试1执行成功');
    } 
	//【带传入参数、带回传参数测试】
	elseif ($op == 'test2') {
        //1、【接收参数】
        $day = empty($_GPC['day']) ? 1 : $_GPC['day'];//接配置参数(post参数，即软件定时任务里面设置的任务参数)
        $page = empty($_GPC['page']) ? 1 : $_GPC['page'];//接收页码参数(回传参数)
        //2、【执行业务函数】
        //3、【判断执行结果，并构造回传参数】
        //判断是否执行到最后一页（我这里假设最多只有10页，具体情况根据业务代码判断是否最后一页）
        if ($page >= 10) {
            //执行到最后一页，不回传参数即可
			$message = '本轮所有任务执行完毕';//返回信息
        } else {
            $page++;//页码加1
            //追加回传参数 param 为下次要回传的参数字段，支持任意参数构造(命名注意不要和系统已有的post参数冲突)；  sleep 是控制访问下一页的间隔时间参数，单位为秒
            $return['timetaskdata'] = array('param' => 'test1=111&test2=222&page=' . $page, 'sleep' => 1);//构造回传参数(test1=111&test2=222 为其他测试参数，可以删除)
			$message = '传入的day参数值为:' . $day . ',回传的page参数为：' . $page;//返回信息
        }
        //3、【返回结果】
        returnExit($return, 1, $message);
    } else {
        returnExit($return, 0, '任务标识不正确');
    }
}
else
{
	returnExit($return,0,'未传入有效API参数');//code值设置为1，表示成功
}


//返回json信息并退出
function returnExit($return,$code,$message,$data=''){
	$return['code']=$code;
	$return['message']=urlencode($message);
	if(!empty($data))	$return['data']=$data;
	exit(urldecode(json_encode($return)));
}

//构造返回状态结果字符串
function resultState($result,$id,$v){
	if (!empty($result))	$result.='|';
	$result.=$id.':'.$v;
	return	$result;
}

//获取数据库表的所有字段
function tableField($table){
	$sqlColumns=pdo_fetchall('SHOW COLUMNS FROM '.$table);//查询表的所有字段
	//处理查询结果
	foreach($sqlColumns as $value)
	{
		if(isset($value['Field']) && isset($value['Type'])){
			$sqlField[$value['Field']]=$value['Type'];
		}
	}
	return $sqlField;
}

//格式化订单数据
function orderFormat($data,$field){
	global $_W;
	//将数据存储到符合当前系统的新数组
	$newData=array(
		'weid'=>$_W['uniacid'],
		'addtime'=>strtotime($data['创建时间']),//创建时间
		'orderid'=>$data['订单编号'],//订单编号
		'numid'=>$data['商品ID'],//商品ID
		'shopname'=>$data['所属店铺'],//店铺名称
		'title'=>$data['商品信息'],//商品标题
		'orderzt'=>$data['订单状态'],//订单状态
		'srbl'=>$data['收入比率'],//收入比例
		'fcbl'=>$data['分成比率'],//分成比例
		'fkprice'=>$data['付款金额'],//付款金额
		'xgyg'=>$data['效果预估'],//效果预估
		'jstime'=>strtotime($data['结算时间']),//结算时间
		'pt'=>$data['成交平台'],//平台
		'mtid'=>$data['来源媒体ID'],//媒体ID
		'mttitle'=>$data['来源媒体名称'],//媒体名称
		'tgwid'=>$data['广告位ID'],//推广位ID
		'tgwtitle'=>$data['广告位名称'],//推广位名称
		'tbsbuid6'=>substr($data['订单编号'],-6),//订单后6位
		'createtime'=>TIMESTAMP,
	);

	//处理维权订单
	if(strpos($data['维权状态'],"维权创建") !== false || strpos($data['维权状态'],"等待处理") !== false) {
		$newData['orderzt']='订单付款';//强制将订单状态设置为：订单付款
		$newData['wq']=1;//标记为维权
	}
	elseif(strpos($data['维权状态'],"维权成功") !== false) {
		$newData['orderzt']='订单失效';//强制将订单状态设置为：订单失效
		$newData['wq']=1;//标记为维权
	}
	elseif(strpos($str,"维权失败") !== false) {
		//$data['订单状态']='订单结算';//强制将订单状态设置为：订单结算(因为订单也有可能本身是其他状态，所以此处不做处理即可)
		$newData['wq']=0;//取消维权状态
	}

	//过滤数据库不支持的字段(因为各个版本的数据库字段有差异，所以要处理下)
	foreach($newData as $key=>$value){
		if(isset($field[$key]))
		{
			$saveData[$key]=$value;
		}
	}
	return	$saveData;
}


//将订单表orderid索引改为普通索引
function orderIndex($table){
	$allIndex=pdo_fetchall("show index from".$table);
	//print_r($allIndex);
	foreach($allIndex as $index){
		//判断orderid是否唯一索引
		if($index['Key_name']=='orderid' && $index['Non_unique']==0){
			//将索引修改为普通索引
			$ree=pdo_query("ALTER TABLE ".$table." DROP INDEX `orderid`, ADD INDEX `orderid` (`weid`, `orderid`, `numid`) USING BTREE;");
			//echo '修改索引';
			return;
		}
	}
	//echo '未修改索引';
}


//处理商品分类
function goodsCat($table,$softCat){
	global $_W;
	//查询数据库的分类名和对应ID
	$allCat=pdo_fetchall('select title,id from '.$table." where weid='{$_W['uniacid']}'");
	//print_r($allCat);
	foreach($allCat as $cat){
		$cat['title']=str_replace("其他","其它",$cat['title']);//兼容分类名字为“其他”时的情况
		$dbCat[$cat['title']]=$cat['id'];
	}

	//将程序商品分类关系转化成对应的id
	foreach($softCat as $key=>$value){
		$value=str_replace("其他","其它",$value);//兼容分类名字为“其他”时的情况
		if (isset($dbCat[$value]))	$newCat[$key]=$dbCat[$value];
	}
	//print_r($softCat);
	return $newCat;
}


//格式化商品数据
function goodsFormat($cat,$data,$field){
	global $_W;
	//处理采集来源
	if($data['采集来源']=='大淘客'){
		$data['采集来源']=1;
	}elseif($data['采集来源']=='好单库'){
		$data['采集来源']=2;
	}elseif($data['采集来源']=='轻淘客'){
		$data['采集来源']=4;
	}elseif($data['采集来源']=='一手单'){
		$data['采集来源']=5;
	}elseif($data['采集来源']=='QQ群'){
		$data['采集来源']=8;
	}
	//处理商品类目
	if (empty($cat[$data['商品类目']])){
		$data['商品类目']=0;
	}else{
		$data['商品类目']=$cat[$data['商品类目']];
	}

	$newData=array(
		'weid'=>$_W['uniacid'],//公众号ID
		'zy'=>$data['采集来源'],//1大淘客 2互力 3鹊桥库
		'itemid'=>$data['商品ID'],//商品id
		'itemtitle'=>$data['商品标题'],//商品标题
		'itemshorttitle'=>$data['商品短标题'],//商品短标题
		'itemdesc'=>$data['商品文案'],//商品文案
		'itemprice'=>$data['商品原价'],//商品原价
		'itemsale'=>$data['商品销量'],//商品销量
		'itemsale2'=>$data['商品两小时销量'],//商品最近2小时销量
		//'conversion_ratio'=>$data[''],//优惠券转化率
		'itempic'=>$data['商品图片'],//商品主图
		'fqcat'=>$data['商品类目'],//商品类目
		'itemendprice'=>$data['商品券后价'],//商品券后价
		'shoptype'=>$data['店铺类型'],//店铺类型 天猫(B) C店(C) 企业店铺
		'userid'=>$data['卖家ID'],//卖家ID
		'sellernick'=>$data['卖家昵称'],//卖家昵称
		'tktype'=>$data['佣金计划'],//佣金方式(鹊桥活动 定向计划 通用计划 隐藏计划 营销计划）
		'tkrates'=>$data['佣金比例'],//佣金比例
		//'ctrates'=>$data[''],//村淘佣金比例
		'cuntao'=>$data['是否村淘'],//是否村淘（1是）
		'tkmoney'=>$data['预计佣金'],//预计可得(宝贝价格*佣金比率/100) 
		'tkurl'=>$data['定向计划链接'],//定向计划链接
		'planlink'=>$data['营销计划链接'],//营销计划链接
		'quan_id'=>$data['优惠券ID'],//优惠券ID
		'couponurl'=>$data['优惠券链接'],//优惠券链接
		'couponmoney'=>$data['优惠券金额'],//优惠券面额
		'couponsurplus'=>$data['优惠券剩余量'],//优惠券剩余数量
		'couponreceive'=>$data['优惠券领取量'],//优惠券领取数量
		//'couponreceive2'=>$data[''],//2小时内优惠券领取量
		'couponnum'=>$data['优惠券总数量'],//优惠券总数量
		'couponexplain'=>$data['优惠券使用条件'],//优惠券说明 使用条件
		'couponstarttime'=>$data['优惠券开始时间'],//优惠券开始时间
		'couponendtime'=>$data['优惠券结束时间'],//优惠券结束时间
		'starttime'=>$data['最后修改时间'],//发布时间
		'isquality'=>$data['是否优选'],//是否优选 1为是
		'item_status'=>$data['失效状态'],//产品状态：0为正常
		//'report_status'=>$data[''],//举报处理情况(1为待处理；2为忽略；3为下架)
		'is_brand'=>$data['是否品牌商品'],//是否为品牌产品：1为是
		'is_live'=>$data['是否直播商品'],//是否为直播产品：1为是
		'videoid'=>$data['商品视频ID'],//商品视频id
		'activity_type'=>$data['活动类型'],//活动类型（普通活动、聚划算、淘抢购）
		'createtime'=>TIMESTAMP,//最后修改时间
		//'tj'=>$data[''],//1 秒杀 2 叮咚抢 
		//'zt'=>$data[''],//专题
		//'test8888'=>'66666666',//干扰测试
		//'zd'=>$data[''],//0不置顶  1置顶
		//'qf'=>$data[''],//0不群发  1群发库
	);
	//过滤数据库不支持的字段(因为各个版本的数据库字段有差异，所以要处理下)
	foreach($newData as $key=>$value){
		if(isset($field[$key]))
		{
			$saveData[$key]=$value;
		}
	}
	return	$saveData;
}