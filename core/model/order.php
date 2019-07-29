<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Order_NetsHaojkModel
{
	public function order_notice()
	{
        ignore_user_abort(true);

		global $_GPC, $_W;

        $global = m("fc")->fc_getglobal();

        $orderlist1 = [];
        $orderlist2 = [];
        $orderlist3 = [];
        $orderlist4 = [];

        if($global['taobao_switch']){
            $orderlist1 = m("taobao_data")->sync_order_v2($global);
            $orderlist4 = m("taobao_data")->sync_settle_order_v2($global);
        }

        if($global['pdd_switch']){
            $orderlist2 = m("pdd_data")->pdd_open_order($global);
        }

        if($global['jd_switch']){
            $orderlist3 = m("jd_data")->get_order($global);
        }

        $orderlist = array_merge($orderlist1,$orderlist2,$orderlist3,$orderlist4);

		m("fc")->fc_log_debug("orderlist：" . json_encode($orderlist), "order_notice");

		if(!$orderlist){
		   return result_data(1,"无数据",'');
        }

		foreach ($orderlist as &$order) {
			$order["uniacid"] = $_W["uniacid"];
			unset($order["id"]);
            $positionId = urldecode($order["positionId"]);
            $customer_data = explode(',',$positionId);

            if(count($customer_data) == 2){
                $order["positionId"] = $customer_data[0];
                $order["uniacid"] = $customer_data[1];
            }else{
                $order["positionId"] = $customer_data[0];
            }

			m("fc")->fc_log_debug("positionId：" . $order["positionId"], "order_notice");
			$parent = m("member")->member_getbyuid($order["positionId"]);
			if (!empty($parent)) {

			    //订单状态处理
				$order["yn"] = "0";
				if ($order["ordertype"] == "1") {
					if ($order["validCode"] == "15") {
						$order["yn"] = "1";
					}
					if ($order["validCode"] == "16") {
						$order["yn"] = "2";
					}
					if ($order["validCode"] == "17") {
						$order["yn"] = "3";
					}
					if ($order["validCode"] == "18") {
						$order["yn"] = "4";
					}
				}
				if ($order["ordertype"] == "2") {
					if ($order["validCode"] == "-1") {
						$order["yn"] = "1";
					}
					if ($order["validCode"] == "0" || $order["validCode"] == "1") {
						$order["yn"] = "2";
					}
					if ($order["validCode"] == "2" || $order["validCode"] == "3") {
						$order["yn"] = "3";
					}
					if ($order["validCode"] == "5") {
						$order["yn"] = "4";
					}
				}
				if ($order["ordertype"] == "3") {
					if ($order["validCode"] == "-1") {
						$order["yn"] = "1";
					}
					if ($order["validCode"] == "0") {
						$order["yn"] = "2";
					}
					if ($order["validCode"] == "2") {
						$order["yn"] = "3";
					}
					if ($order["validCode"] == "5") {
						$order["yn"] = "4";
					}
				}
                if ($order["ordertype"] == "4") {
                    if ($order["validCode"] == 13) {
                        $order["yn"] = "0";
                    }
                    if ($order["validCode"] == 12) {
                        $order["yn"] = "2";
                    }
                    if ($order["validCode"] == 14) {
                        $order["yn"] = "3";
                    }
                    if ($order["validCode"] == 3) {
                        $order["yn"] = "4";
                    }
                }


				m("fc")->fc_log_debug("Fee：" . $order["Fee"], "order_notice");
				if ($order["Fee"] <= 0) {
					$order["Fee"] = $order["commission"];
					m("fc")->fc_log_debug("Fee：" . $order["Fee"], "order_notice");
				}
				if (empty($order["finalRate"])) {
					$order["finalRate"] = $order["commissionRate"];
					if ($order["ordertype"] == "1") {
						$order["finalRate"] = $order["commissionRate"] * 0.9;
					}
				}
				$info = $this->order_check($order["orderId"], $order["ordertype"], $order["skuId"],$order["uniacid"]);
				if ($info) {
					if ($info["validCode"] != $order["validCode"] || $info["valistatus"] != $order["valistatus"]) {

					    //结算
						if ($order["yn"] == "4" && $info["yn"] != "4") {
							$this->order_commission_balance($info);
						}

						$i = pdo_update("qt_operate_order", $order, array("orderId" => $order["orderId"], "skuId" => $order["skuId"]));
					}
				} else {
					$order["from_pid"] = $parent["from_id"];
					$order["parent_from_pid"] = $parent["parent_from_id"];
					$order["vip_pid"] = $parent["from_vip_id"];
					$res = pdo_insert("qt_operate_order", $order);
				}
			}
		}

		$this->delete_temp_file();
        return result_data(0,"成功",$orderlist);
	}

    public function delete_temp_file(){
        $temp_dir = IA_ROOT."/addons/qt_shop/cache/img/";
        delfile($temp_dir,60*30);//删除半个小时的数据

        $temp_dir = IA_ROOT."/addons/qt_shop/cache/logs/";
        delfile($temp_dir,86400*7);//删除七天前的日志
    }

    public function web_order_list()
    {
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        if (empty($uid) && empty($_GPC["isweb"])) {
            return array("data" => '', "total" => "0", "message" => "success", "status_code" => 200);
        }
        $global = m("fc")->fc_getglobal();
        $page = empty($_GPC["page"]) ? 1 : $_GPC["page"];
        $pagesize = empty($_GPC["pagesize"]) ? 20 : $_GPC["pagesize"];
        $keyword = $_GPC["keyword"];
        $where = '';
        if (!empty($uid)) {
            if ($_GPC["level"] == "0") {
                $where = " and positionId =" . $uid;
            } else {
                if ($_GPC["level"] == "1") {
                    $where = " and from_pid =" . $uid;
                } else {
                    if ($_GPC["level"] == "2") {
                        $where = " and parent_from_pid =" . $uid;
                    } else {
                        if ($_GPC["level"] == "3") {
                            $where = " and vip_pid =" . $uid;
                        } else {
                            $where = " and ( positionId =" . $uid . " or  from_pid =" . $uid . "  or  parent_from_pid =" . $uid . " or vip_pid =" . $uid . ")";
                        }
                    }
                }
            }
        }
        if (!empty($keyword)) {
            $where .= " and (orderId like '%" . $keyword . "%' or skuId like '%" . $keyword . "%' or skuName like '%" . $keyword . "%')";
        }
        if (!empty($_GPC["ordertype"])) {
            $where .= " and ordertype = '" . $_GPC["ordertype"] . "'";
        }
        if (!empty($_GPC["yn"])) {
            $where .= " and yn = '" . $_GPC["yn"] . "'";
        }
        if ($_GPC["status"] == "1") {
            $where .= " and yn > '1'";
        }
        $date = array();
        if (!empty($_GPC["daterange"])) {
            $date = explode(" ~ ", $_GPC["daterange"]);
        }
        $starttime = 0;
        $endtime = TIMESTAMP + 86399;
        if (count($date) == 2) {
            $starttime = strtotime($date[0]);
            $endtime = strtotime($date[1]) + 86399;
        }
        if (!empty($endtime)) {
            $where .= " AND o.orderTime >= " . $starttime . " AND o.orderTime <=" . $endtime;
        }
        $sql = "SELECT DISTINCT o.Fee,o.ordertype,o.orderId,o.orderTime,o.finishTime,o.positionId,o.from_pid,o.parent_from_pid,o.vip_pid,m.nickname,m.avatar FROM " . tablename("qt_operate_order") . " AS o left join " . tablename("qt_operate_member") . " AS m on o.positionId=m.uid   where m.uniacid=:uniacid and o.uniacid=:uniacid  " . $where . " ORDER BY o.orderTime DESC limit " . ($page - 1) * $pagesize . "," . $pagesize;
        $list = pdo_fetchall($sql , array(":uniacid" => $_W["uniacid"]));
        foreach ($list as &$order) {

            //判断是来自哪一个层级的佣金
            if($uid == $order['positionId'] ){
                $order['Fee'] = number_format($order['Fee'] * $global["first_sidy"]/100,2);
            }else if($uid == $order['from_pid']){
                $order['Fee'] = number_format($order['Fee'] * $global["second_sidy"]/100,2);
            }else if($uid == $order['parent_from_pid']){
                $order['Fee'] = number_format($order['Fee'] * $global["third_sidy"]/100,2);
            }


            $order["skus"] = pdo_fetchall("SELECT skuId,skuName,picUrl,skuNum,skuReturnNum,cosPrice,commission
			
			, case when yn>=1 then round(Fee*" . $global["first_sidy"] . "/100,2) else 0 end   estimateFee1
			, case when yn>=1 then round(Fee*" . $global["second_sidy"] . "/100,2) else 0 end   estimateFee2
			, case when yn>=1 then round(Fee*" . $global["third_sidy"] . "/100,2) else 0 end   estimateFee3
			, case when yn>=1 then round(Fee*" . $global["vip_sidy"] . "/100,2) else 0 end   estimateFeevip
			,commissionRate,Fee,finalRate,valistatus,validCode from " . tablename("qt_operate_order") . " where uniacid=:uniacid and orderId = :orderId ", array(":orderId" => $order["orderId"],":uniacid" => $_W["uniacid"]));
        }
        $totalcount = pdo_fetchcolumn("SELECT COUNT(DISTINCT orderId) ordercount FROM " . tablename("qt_operate_order") . " AS o left join " . tablename("qt_operate_member") . " AS m on o.positionId=m.uid   where o.uniacid=:uniacid  " . $where, array(":uniacid" => $_W["uniacid"]));

        return ["data" => $list, "total" => $totalcount];

    }


    public function order_list()
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		if (empty($uid) && empty($_GPC["isweb"])) {
			return array("data" => '', "total" => "0", "message" => "success", "status_code" => 200);
		}
		$global = m("fc")->fc_getglobal();
		$page = empty($_GPC["page"]) ? 1 : $_GPC["page"];
		$pagesize = empty($_GPC["pagesize"]) ? 20 : $_GPC["pagesize"];
		$keyword = $_GPC["keyword"];
		$where = '';
		if (!empty($uid)) {
			if ($_GPC["level"] == "0") {
				$where = " and positionId =" . $uid;
			} else {
				if ($_GPC["level"] == "1") {
					$where = " and from_pid =" . $uid;
				} else {
					if ($_GPC["level"] == "2") {
						$where = " and parent_from_pid =" . $uid;
					} else {
						if ($_GPC["level"] == "3") {
							$where = " and vip_pid =" . $uid;
						} else {
							$where = " and ( positionId =" . $uid . " or  from_pid =" . $uid . "  or  parent_from_pid =" . $uid . " or vip_pid =" . $uid . ")";
						}
					}
				}
			}
		}
		if (!empty($keyword)) {
			$where .= " and (orderId like '%" . $keyword . "%' or skuId like '%" . $keyword . "%' or skuName like '%" . $keyword . "%')";
		}
		if (!empty($_GPC["ordertype"])) {
			$where .= " and ordertype = '" . $_GPC["ordertype"] . "'";
		}
		if (!empty($_GPC["yn"])) {
			$where .= " and yn = '" . $_GPC["yn"] . "'";
		}
		if ($_GPC["status"] == "1") {
			$where .= " and yn > '1'";
		}
		$date = array();
		if (!empty($_GPC["daterange"])) {
			$date = explode(" ~ ", $_GPC["daterange"]);
		}
		$starttime = 0;
		$endtime = TIMESTAMP + 86399;
		if (count($date) == 2) {
			$starttime = strtotime($date[0]);
			$endtime = strtotime($date[1]) + 86399;
		}
		if (!empty($endtime)) {
			$where .= " AND o.orderTime >= " . $starttime . " AND o.orderTime <=" . $endtime;
		}
		$sql = "SELECT DISTINCT o.Fee,o.yn,o.ordertype,o.orderId,o.orderTime,o.finishTime,o.positionId,o.from_pid,o.parent_from_pid,o.vip_pid,m.nickname,m.avatar FROM " . tablename("qt_operate_order") . " AS o left join " . tablename("qt_operate_member") . " AS m on o.positionId=m.uid   where m.uniacid=:uniacid and o.uniacid=:uniacid  " . $where . " ORDER BY o.orderTime DESC limit " . ($page - 1) * $pagesize . "," . $pagesize;
		$list = pdo_fetchall($sql , array(":uniacid" => $_W["uniacid"]));
		foreach ($list as &$order) {

		    //判断是来自哪一个层级的佣金
		    if($uid == $order['positionId'] ){
                $order['Fee'] = number_format($order['Fee'] * $global["first_sidy"]/100,2);
            }else if($uid == $order['from_pid']){
                $order['Fee'] = number_format($order['Fee'] * $global["second_sidy"]/100,2);
            }else if($uid == $order['parent_from_pid']){
                $order['Fee'] = number_format($order['Fee'] * $global["third_sidy"]/100,2);
            }
            if($uid == $order['vip_pid']){
                $order['Fee'] = $order['Fee'] + number_format($order['Fee'] * $global["vip_sidy"]/100,2);
            }


			$order["skus"] = pdo_fetchall("SELECT skuId,skuName,picUrl,skuNum,skuReturnNum,cosPrice,commission
			
			, case when yn>=1 then round(Fee*" . $global["first_sidy"] . "/100,2) else 0 end   estimateFee1
			, case when yn>=1 then round(Fee*" . $global["second_sidy"] . "/100,2) else 0 end   estimateFee2
			, case when yn>=1 then round(Fee*" . $global["third_sidy"] . "/100,2) else 0 end   estimateFee3
			, case when yn>=1 then round(Fee*" . $global["vip_sidy"] . "/100,2) else 0 end   estimateFeevip
			,commissionRate,Fee,finalRate,valistatus,validCode from " . tablename("qt_operate_order") . " where uniacid=:uniacid and orderId = :orderId ", array(":orderId" => $order["orderId"],":uniacid" => $_W["uniacid"]));
		}
		$totalcount = pdo_fetchcolumn("SELECT COUNT(DISTINCT orderId) ordercount FROM " . tablename("qt_operate_order") . " AS o left join " . tablename("qt_operate_member") . " AS m on o.positionId=m.uid   where o.uniacid=:uniacid  " . $where, array(":uniacid" => $_W["uniacid"]));

        m('fc')->fc_result(0, '获取成功',["data" => $list, "total" => $totalcount]);

	}

	//今日可领奖励
	public function today_reward(){

        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        if (empty($uid)) {
            return '';
        }
        $today_time = strtotime(date('Y-m-d'));

        $order_list = pdo_fetchall('select * from '.tablename('qt_operate_order').' where ordertype=:ordertype and validCode!=:validCode and reward_uid=:reward_uid and final_commission > lead_commission and orderTime<:orderTime',[':ordertype'=>2,':validCode'=>'-1',':reward_uid'=>$uid,':orderTime'=>$today_time]);

        $today_money = 0;
        foreach ($order_list as $key => $order){
            $final_commission = $order['final_commission'];
            $today_money += $final_commission*0.1;
        }

        //查询今日是否已经领取
        $cash = pdo_fetch('select * from '.tablename('qt_operate_cash_log').' where uid=:uid and addtime>:today_time',[':uid'=>$uid,':today_time'=>$today_time]);
        $is_receive = false;
        if($cash){
            $is_receive = true;
        }

        m('fc')->fc_result(0, '成功',['today_money'=>$today_money,'is_receive'=>$is_receive]);
    }

    //领取今日奖励
    public function receive_today_reward(){

        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        if (empty($uid)) {
            return '';
        }

        $today_time = strtotime(date('Y-m-d'));
        //查询今日是否已经领取
        $cash = pdo_fetch('select * from '.tablename('qt_operate_cash_log').' where uid=:uid and addtime>:today_time and type=2',[':uid'=>$uid,':today_time'=>$today_time]);
        if(!$cash){
            $order_list = pdo_fetchall('select * from '.tablename('qt_operate_order').' where ordertype=:ordertype and validCode!=:validCode and reward_uid=:reward_uid and final_commission > lead_commission and orderTime<:orderTime',[':ordertype'=>2,':validCode'=>'-1',':reward_uid'=>$uid,':orderTime'=>$today_time]);
            $today_money = 0;
            foreach ($order_list as $key => $order){
                $final_commission = $order['final_commission'];
                $money = $final_commission*0.1;
                $today_money += $money;

                $data = ['lead_commission'=>$order['lead_commission']+$money];

                pdo_update('qt_operate_order',$data,['id'=>$order['id']]);

            }
            $log = $this->_get_cash_log($today_money,$uid,0);
            m("member")->member_set_freeze_price($uid,$today_money,$log);

            m('fc')->fc_result(0, '领取成功',[]);

        }else{

            m('fc')->fc_result(1, '今日您已经领取过啦',[]);
        }

    }

    //返利池记录
    public function pool_log_list(){
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        if (empty($uid)) {
            m('fc')->fc_result(10, '登录一下',[]);
        }

        $log_list = pdo_fetchall('select m.nickname,m.avatar,o.picUrl,o.skuName,o.skuId,o.final_commission,o.is_time_reward,o.lead_commission,o.validCode,o.orderTime from '.tablename('qt_operate_order').' o left join '.tablename('qt_operate_member').' m on o.positionId=m.uid where ordertype=2 and validCode!=:validCode and reward_uid=:reward_uid',[":reward_uid"=>$uid,":validCode"=>'-1']);

        m('fc')->fc_result(0, '成功',$log_list);

    }

    //返现记录
    public function cash_log_list(){

    }


	public function order_getcount()
	{
		global $_GPC, $_W;
		$where = '';
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		if (!empty($uid)) {
			if ($_GPC["level"] == "0") {
				$where = " and positionId =" . $uid;
			} else {
				if ($_GPC["level"] == "1") {
					$where = " and from_pid =" . $uid;
				} else {
					if ($_GPC["level"] == "2") {
						$where = " and parent_from_pid =" . $uid;
					} else {
						if ($_GPC["level"] == "3") {
							$where = " and vip_pid =" . $uid;
						} else {
							$where = " and ( positionId =" . $uid . " or  from_pid =" . $uid . "  or  parent_from_pid =" . $uid . " or vip_pid =" . $uid . ")";
						}
					}
				}
			}
		}
		if (!empty($_GPC["ordertype"])) {
			$where .= " and ordertype = '" . $_GPC["ordertype"] . "'";
		}
		if (!empty($_GPC["yn"])) {
			$where .= " and yn = '" . $_GPC["yn"] . "'";
		}
		if ($_GPC["status"] == "1") {
			$where .= " and yn > '1'";
		}
		$date = array();
		if (!empty($_GPC["daterange"])) {
			$date = explode(" ~ ", $_GPC["daterange"]);
		}
		$starttime = 0;
		$endtime = TIMESTAMP + 86399;
		if (count($date) == 2) {
			$starttime = strtotime($date[0]);
			$endtime = strtotime($date[1]) + 86399;
		}
		if (!empty($endtime)) {
			$where .= " AND orderTime >= " . $starttime . " AND orderTime <=" . $endtime;
		}
		$totalcount = pdo_fetchcolumn("SELECT COUNT(DISTINCT orderId) ordercount FROM " . tablename("qt_operate_order") . " where uniacid=:uniacid  " . $where, array(":uniacid" => $_W["uniacid"]));
		return $totalcount;
	}
	public function order_check($orderid, $ordertype, $skuid , $uniacid)
	{
		global $_W;
		$info = pdo_fetch("select * from " . tablename("qt_operate_order") . " where   uniacid=:uniacid and orderId = :orderId and ordertype=:ordertype  and skuId=:skuId limit 1", array(":uniacid" => $uniacid, ":orderId" => $orderid, ":ordertype" => $ordertype, ":skuId" => $skuid));
		return $info;
	}






	public function order_commission_balance($order)
	{
		global $_W;
		$global = m("fc")->fc_getglobal();
		$info = pdo_fetch("select * from " . tablename("qt_operate_balance_log") . " where log_type = :log_type and logno=:logno  and uniacid=:uniacid limit 1", array(":uniacid" => $_W["uniacid"], ":log_type" => $order["ordertype"], ":logno" => $order["orderId"]));
		if (empty($info)) {
			$member1 = m("member")->member_getbyuid($order["positionId"]);
			if ($member1) {
				$log = $this->_get_balance_log($order, $global["first_sidy"]);
				m("member")->member_setcredit($order["positionId"], $log["amount"], $log);
			}
			if (intval($order["from_id"]) > 0) {
				$member2 = m("member")->member_getbyuid($order["from_id"]);
				if ($member2) {
					$log = $this->_get_balance_log($order, $global["second_sidy"]);
					m("member")->member_setcredit($order["from_id"], $log["amount"], $log);
				}
			}
			if (intval($order["parent_from_pid"]) > 0) {
				$member3 = m("member")->member_getbyuid($order["parent_from_pid"]);
				if ($member3) {
					$log = $this->_get_balance_log($order, $global["third_sidy"]);
					m("member")->member_setcredit($order["parent_from_pid"], $log["amount"], $log);
				}
			}
			if (intval($order["vip_pid"]) > 0) {
				$membervip = m("member")->member_getbyuid($order["vip_pid"]);
				if ($membervip) {
					$log = $this->_get_balance_log($order, $global["vip_sidy"]);
					m("member")->member_setcredit($order["vip_pid"], $log["amount"], $log);
				}
			}
		}
	}


	//返利池
    public function order_commission_pool_price($order)
    {
        global $_W;

        $global = m("fc")->fc_getglobal();
        $info = pdo_fetch("select * from " . tablename("qt_operate_cash_log") . " where order_id=:order_id  and uniacid=:uniacid limit 1", array(":uniacid" => $_W["uniacid"], ":order_id" => $order["orderId"]));
        if (empty($info)) {
            $member1 = m("member")->member_getbyuid($order["reward_uid"]);
            if ($member1) {

                //查询上级分享者订单
                $orderTime = $order['orderTime'];
                $current_time = time();
                $is_time_reward = $orderTime+3600 > $current_time;//是否可奖励限时奖励

                $reward = $order["Fee"] * $global['reward_rate'] / 100;

                $time_reward = 0;
                if($is_time_reward){
                    $time_reward = $order["Fee"] * $global['reward_time_rate'] / 100;
                }

                $total_reward = $reward + $time_reward;
                $data = [];
                $data["final_commission"] = $total_reward;
                $data["is_time_reward"] = $is_time_reward ? 1 : 0;

                pdo_update("qt_operate_order", $data, ["id" => $order["id"]]);

                $log = $this->_get_cash_log($total_reward,$order["reward_uid"],$order['id']);
                m("member")->member_set_pool_price($order["reward_uid"],$total_reward,$log);

            }
        }
    }

    //冻结金额转到可提现余额
    public function order_commission_cash_price($order)
    {
        $member1 = m("member")->member_getbyuid($order["reward_uid"]);
        if ($member1) {
            $log = $this->_get_cash_log($order['lead_commission'],$order["reward_uid"],$order['id']);
            m("member")->member_set_freez_to_cash($order["reward_uid"],$order['lead_commission'],$log);

        }
    }

	public function _get_balance_log($order, $sidy)
	{
		$log["log_type"] = $order["ordertype"];
		$log["logno"] = $order["Fee"];
		$log["isintegral"] = "0";
		$log["amount"] = $order["Fee"] * $sidy / 100;
		$log["primaryamount"] = $order["Fee"] * $sidy / 100;
		$log["logtype"] = "1";
		$log["status"] = "1";
		$log["cash_pct"] = $sidy;
		$log["pay_at"] = time();
		return $log;
	}


    public function _get_cash_log($money,$uid,$order_id)
    {
        $log["money"] =$money;
        $log["addtime"] = time();
        $log["uid"] = $uid;
        $log["order_id"] = $order_id;
        return $log;
    }


	public function order_getstatistics()
	{
	}
	public function order_income()
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		if (empty($uid)) {
			return '';
		}
		$_GPC["daterange"] = date("Y-m-d") . " ~ " . date("Y-m-d");
		if ($_GPC["incometype"] == "1") {
			$_GPC["daterange"] = date("Y-m-d", strtotime("-1 day")) . " ~ " . date("Y-m-d ", strtotime("-1 day"));
		}
		if ($_GPC["incometype"] == "2") {
			$_GPC["daterange"] = date("Y-m-01") . " ~ " . date("Y-m-d");
		}
		if ($_GPC["incometype"] == "3") {
			$_GPC["daterange"] = date("Y-m-01", strtotime("-1 month")) . " ~ " . date("Y-m-t", strtotime("-1 month"));
		}
		$_GPC["incomelevel"] = "0";
		$myincome = $this->_myincome_statistics($uid);
		$_GPC["incomelevel"] = "1";
		$teamincome = $this->_myincome_statistics($uid);
		$_GPC["incomelevel"] = "2";
		$teamincome_sec = $this->_myincome_statistics($uid);
		$teamincome["incomeall"] = $teamincome["incomeall"] + $teamincome_sec["incomeall"];
		$teamincome["jd_income"] = $teamincome["jd_income"] + $teamincome_sec["jd_income"];
		$teamincome["pdd_income"] = $teamincome["pdd_income"] + $teamincome_sec["pdd_income"];
		$teamincome["mgj_income"] = $teamincome["mgj_income"] + $teamincome_sec["mgj_income"];
		$teamincome["jd_count"] = $teamincome["jd_count"] + $teamincome_sec["jd_count"];
		$teamincome["mgj_count"] = $teamincome["mgj_count"] + $teamincome_sec["mgj_count"];
		$_GPC["incomelevel"] = "3";
		$vipincome = $this->_myincome_statistics($uid);
		$totalincome = $myincome["incomeall"] + $teamincome["incomeall"] + $vipincome["incomeall"];
		$data = array("totalincome" => $totalincome, "myincome" => $myincome, "teamincome" => $teamincome, "vipincome" => $vipincome);
		return $data;
	}
	public function _myincome_statistics($uid)
	{
		global $_GPC, $_W;
		$date = array();
		$global = m("fc")->fc_getglobal();
		$sidy = 0;
		if (!empty($_GPC["daterange"])) {
			$date = explode(" ~ ", $_GPC["daterange"]);
		}
		$starttime = 0;
		$endtime = TIMESTAMP + 86399;
		$where = " yn > '0'";
		if (count($date) == 2) {
			$starttime = strtotime($date[0]);
			$endtime = strtotime($date[1]) + 86399;
		}
		if (!empty($uid)) {
			if ($_GPC["incomelevel"] == "0") {
				$where = " and positionId =" . $uid;
				$sidy = $global["first_sidy"];
			} else {
				if ($_GPC["incomelevel"] == "1") {
					$where = " and from_pid =" . $uid;
					$sidy = $global["second_sidy"];
				} else {
					if ($_GPC["incomelevel"] == "2") {
						$where = " and parent_from_pid =" . $uid;
						$sidy = $global["third_sidy"];
					} else {
						if ($_GPC["incomelevel"] == "3") {
							$where = " and vip_pid =" . $uid;
							$sidy = $global["vip_sidy"];
						}
					}
				}
			}
		}
		if (!empty($endtime)) {
			$where .= " AND orderTime >= " . $starttime . " AND orderTime <=" . $endtime;
		}
		$list = pdo_fetchall("SELECT COUNT(DISTINCT orderId) ordercount,sum(commission) commission,ordertype FROM " . tablename("qt_operate_order") . " where uniacid=:uniacid   " . $where . " group by ordertype", array(":uniacid" => $_W["uniacid"]));
		$jd_income = $pdd_income = $mgj_income = $jd_count = $pdd_count = $mgj_count = $incomeall = 0;
		foreach ($list as $item) {
			if ($item["ordertype"] == "1") {
				$jd_income = $item["commission"] * $sidy / 100;
				$jd_count = $item["ordercount"];
				$incomeall = $incomeall + $jd_income;
			}
			if ($item["ordertype"] == "2") {
				$pdd_income = $item["commission"] * $sidy / 100;
				$pdd_count = $item["ordercount"];
				$incomeall = $incomeall + $pdd_income;
			}
			if ($item["ordertype"] == "3") {
				$mgj_income = $item["commission"] * $sidy / 100;
				$mgj_count = $item["ordercount"];
				$incomeall = $incomeall + $mgj_income;
			}
		}
		$data = array("incomeall" => $incomeall, "jd_income" => $jd_income, "pdd_income" => $pdd_income, "mgj_income" => $mgj_income, "jd_count" => $jd_count, "pdd_count" => $pdd_count, "mgj_count" => $mgj_count);
		return $data;
	}
    public function tip(){
        $msg = "警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责";
    }
}




