<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Member_NetsHaojkModel
{

    public function my_friends()
    {

        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $listfirst = $this->member_list();
        $totalall = $listfirst['total'];
        $listfirst = $listfirst['data'];

        m('fc')->fc_result(0, '成功',['friend_list'=>$listfirst,'total_count'=>$totalall]);
    }

    //授权服务器异步调用
    public function update_taobao_user_id(){
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        $taobao_user_id  = $_GPC["taobao_user_id"];

        m("fc")->fc_log_debug("taobao_auth：{$uid} = " . $taobao_user_id, "taobao_auth");
        $user = $this->member_getbyuid($uid);
        if(empty($user) || empty($taobao_user_id)){
            m('fc')->fc_result(1, '用户或淘宝id不存在',[]);
        }
        //这里只要后六位即可
        $taobao_user_id = substr($taobao_user_id,-6);
        pdo_update("qt_operate_member",['taobao_user_id'=>$taobao_user_id],['uid'=>$uid]);

        m('fc')->fc_result(0, '修改成功',[]);
    }



    //检测超级会员
    public function update_super_vip(){
        global $_GPC, $_W;
        $global = m("fc")->fc_getglobal();
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        $member = pdo_get('qt_operate_member',['uniacid'=>$_W['uniacid'],'uid'=>$uid]);

        if($member['vip_level'] == 1){
            return ["errno" => 1, "message" => '已经是高级会员', "result" => ''];
        }

        $isok = true; //默认通过
        m("fc")->fc_log_debug("super_direct_count：" . $global["super_direct_count"], "super_direct_count");
        if (intval($global["super_direct_count"]) > 0) {
            $_GPC["level"] = "1";
            $count = $this->member_getcount();
            m("fc")->fc_log_debug("super_direct_count_real：" . $count, "super_direct_count");
            if (intval($count) >= $global["super_direct_count"]) {
                $isok = true;
            } else {
                return ["errno" => 1, "message" => '直接会员数不够', "result" => ''];
            }
        }

        m("fc")->fc_log_debug("super_indirect_count：" . $global["super_indirect_count"], "super_indirect_count");
        if (intval($global["super_indirect_count"]) > 0) {
            $_GPC["level"] = "2";
            $count = $this->member_getcount();
            m("fc")->fc_log_debug("super_indirect_count_real：" . $count, "super_indirect_count");
            if (intval($count) >= $global["super_indirect_count"] && $isok) {
                $isok = true;
            } else {
                return ["errno" => 1, "message" => '间接会员数不够', "result" => ''];
            }
        }

        m("fc")->fc_log_debug("super_order_count：" . $global["super_order_count"], "super_order_count");
        if (intval($global["super_order_count"]) > 0) {
            $_GPC["level"] = '';
            $_GPC["status"] = "1";
            $count = m("order")->order_getcount();
            m("fc")->fc_log_debug("super_order_count_real：" . $count, "super_order_count");
            if (intval($count) >= $global["super_order_count"] && $isok) {
                $isok = true;
            } else {
                return ["errno" => 1, "message" => '订单数不够', "result" => ''];
            }
        }

        if($isok){
            pdo_update('qt_operate_member',['vip_level'=>1],['uniacid'=>$_W['uniacid'],'uid'=>$uid]);
        }

        return ["errno" => 0, "message" => '成功', "result" => ''];

    }


    //查询收益
    public function my_cash_money($params){
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $_GPC["level"] = 1;

        $global = m("fc")->fc_getglobal();


        $condition = " and uniacid={$_W['uniacid']}";

        //查询待返现
        if(!empty($params['wait_income'])){
            $condition .= " and yn < 4";
        }

        //订单类型
        if(!empty($params['ordertype'])){
            $condition .= " and ordertype = ".$params['ordertype'];
        }

        //时间筛选
        if(!empty($params['start_time'])){
            $condition .= " and orderTime > {$params['start_time']}";
        }

        if(!empty($params['end_time'])){
            $condition .= " and orderTime < {$params['end_time']}";
        }


        //一级返利
        $sub_money_1 = pdo_fetchcolumn('select sum(o.commission) from '
            .tablename('qt_operate_order').' o where yn>1 and positionId=:positionId'.$condition,
            [":positionId"=>$uid]);
        $sub_money_1 = $global['first_sidy'] * $sub_money_1 / 100;

        //二级返利
        $sub_money_2 = pdo_fetchcolumn('select sum(o.commission) from '
            .tablename('qt_operate_order').' o where yn>1 and from_pid=:from_pid'.$condition,
            [":from_pid"=>$uid]);
        $sub_money_2 = $global['second_sidy'] * $sub_money_2 / 100;

        //三级返利
        $sub_money_3 = pdo_fetchcolumn('select sum(o.commission) from '
            .tablename('qt_operate_order').' o where yn>1 and parent_from_pid=:parent_from_pid'.$condition,
            [":parent_from_pid"=>$uid]);
        $sub_money_3 = $global['third_sidy'] * $sub_money_3 / 100;

        //合伙人
        $sub_money_4 = pdo_fetchcolumn('select sum(o.commission) from '
            .tablename('qt_operate_order').' o where yn>1 and vip_pid=:vip_pid'.$condition,
            [":vip_pid"=>$uid]);
        $sub_money_4 = $global['vip_sidy'] * $sub_money_4 / 100;

        $totalincome = floatval($sub_money_1) + floatval($sub_money_2) + floatval($sub_money_3)  + floatval($sub_money_4);

        return $totalincome;
    }


    public function my_cash(){
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $global = m("fc")->fc_getglobal();
        $rule['mincash'] = $global['mincash'];
        $rule['rate'] = $global['rate'];
        $rule['cashtype'] = $global['cashtype'];

        $member = pdo_fetch("SELECT * FROM " . tablename("qt_operate_member") . " WHERE `uid` = :uid", array(":uid" => $uid));
        $data['money'] =  $member['credit'];
        $data['alipayno'] =  $member['alipayno'];

        $data['rule'] =  $rule;

        m('fc')->fc_result(0, '成功',$data);
    }


    //    累计省钱和余额
    public function my_commission(){
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        //今日
        $today_start = strtotime(date("Y-m-d"),time());
        $today_end = $today_start + 86400;

        $params = [
            'start_time'=>$today_start,
            'end_time'=>$today_end,
        ];

        $today = $this->my_cash_money($params);

        //本月
        $month_start=mktime(0,0,0,date('m'),1,date('Y'));
        $month_end=mktime(0,0,0,date('m')+1,1,date('Y'));
        $params = [
            'start_time'=>$month_start,
            'end_time'=>$month_end,
        ];
        $month = $this->my_cash_money($params);

        //上月
        $last_month_start=mktime(0,0,0,date('m')-1,1,date('Y'));
        $last_month_end=mktime(0,0,0,date('m'),1,date('Y'));

        $params = [
            'start_time'=>$last_month_start,
            'end_time'=>$last_month_end,
            "wait_income"=>true
        ];

        $last_month = $this->my_cash_money($params);


        $member = pdo_fetch("SELECT * FROM " . tablename("qt_operate_member") . " WHERE `uid` = :uid", array(":uid" => $uid));
        $money =  $member['credit'];

        $_GPC["level"] = 0;
        $fans_count = $this->member_getcount();
        return ['today'=>''.number_format($today,2),'month'=>''.number_format($month,2),'last_month'=>''.number_format($last_month,2),'fans_count'=>$fans_count,'money'=>$money];

    }

	public function member_info($openid = '', $check = "1")
	{
		global $_GPC, $_W;
		if (empty($openid)) {
			$openid = $_W["openid"];
		}
		$uid = intval($openid);
		if ($uid == 0) {
			$info = pdo_fetch("select * from " . tablename("qt_operate_member") . " where openid=:openid and uniacid=:uniacid limit 1", array(":uniacid" => $_W["uniacid"], ":openid" => $openid));
		} else {
			$info = pdo_fetch("select * from " . tablename("qt_operate_member") . " where uid=:uid  and uniacid=:uniacid  limit 1", array(":uid" => $uid, ":uniacid" => $_W["uniacid"]));
		}
		if (!empty($info)) {
			if (!strexists($info["avatar"], "http://") && !strexists($info["avatar"], "https://")) {
				$info["avatar"] = tomedia($info["avatar"]);
			}
			if ($_W["ishttps"]) {
				$info["avatar"] = str_replace("http://", "https://", $info["avatar"]);
			}
			if (empty($info["avatar"]) || empty($info["nickname"]) || (!empty($_GPC["from_id"]) && empty($info["from_id"])) || $info["expire_time"] < time()) {
				$info = $this->member_check($openid);
			}
		} else {
			if ($check == "1") {
				$info = $this->member_check($openid);
			}
		}
		if (!empty($info)) {
			$info["recommender"] = '';
            $info["expire_time"] = empty($info["expire_time"]) ? "" : date("Y-m-d H:i:s",$info["expire_time"]);
			if (!empty($info["from_id"])) {
				$parent = $this->member_getbyuid($info["from_id"]);
				$info["recommender"] = $parent["nickname"];
			}
		}
		return result_data(0,'成功',empty($info) ? '' : $info);
	}
	public function member_infobyopenid($openid)
	{
		global $_GPC, $_W;
		$info = pdo_fetch("select * from " . tablename("qt_operate_member") . " where openid=:openid and uniacid=:uniacid limit 1", array(":uniacid" => $_W["uniacid"], ":openid" => $openid));
		return empty($info) ? '' : $info;
	}
	public function member_check($openid = '')
	{
		global $_GPC, $_W;
		if (empty($openid)) {
			$openid = $_W["openid"];
		}
		$from_id = $_GPC["from_id"];
		$uid = intval($openid);
		if ($uid == 0) {
			$info = pdo_fetch("select * from " . tablename("qt_operate_member") . " where openid=:openid and uniacid=:uniacid limit 1", array(":uniacid" => $_W["uniacid"], ":openid" => $openid));
		} else {
			$info = pdo_fetch("select * from " . tablename("qt_operate_member") . " where uid=:uid  and uniacid=:uniacid limit 1", array(":uniacid" => $_W["uniacid"], ":uid" => $uid));
		}
		if (empty($_W["fans"])) {
			return '';
		}
		$member["uid"] = $_W["fans"]["uid"];
		$member["openid"] = $_W["fans"]["openid"];
		$member["sex"] = $_W["fans"]["sex"];
		$member["avatar"] = $_W["fans"]["avatar"];
		$member["nickname"] = $_W["fans"]["nickname"];
		if (empty($info)) {
			$member["uniacid"] = $_W["uniacid"];
			$member["vip"] = 0;
			$member["integral"] = 0;
			$member["credit"] = 0;
			$member["realname"] = '';
			$member["alipayno"] = '';
			$member["from_id"] = $from_id;
			$parent = $this->member_getbyuid($from_id);
			$member["parent_from_id"] = $parent["from_id"];
			$member["from_vip_id"] = $parent["from_vip_id"];
			$member["created_at"] = time();
			$i = pdo_insert("qt_operate_member", $member);
			if ($i) {
				$info = $this->member_getbyuid($member["uid"], true);
			}
		} else {
			$member["updated_at"] = time();
			if(empty($info["from_id"]) && !empty($from_id)){
                $parent = $this->member_getbyuid($from_id);
                //上级不能是自己 ， 这样就循环绑定
                if($parent["from_id"] != $member["uid"] && intval($member["uid"]) > intval($from_id)){
                    $member["from_id"] = $from_id;
                    $member["parent_from_id"] = $parent["from_id"];
                }
            }
//            if($info['expire_time'] < time()){
//                $member['vip_level'] = 0;
//            }
			$i = pdo_update("qt_operate_member", $member, array("uniacid" => $_W["uniacid"], "uid" => $member["uid"]));
			if ($i) {
				$info = $this->member_getbyuid($member["uid"], true);
			}
		}
		return $info;
	}
	public function member_getbyuid($uid, $nocache = false)
	{
		$parent = m("fc")->fc_cache_load("member_getbyuid" . $uid);
		if (empty($parent) || $nocache) {
			$parent = pdo_fetch("SELECT * FROM " . tablename("qt_operate_member") . " WHERE `uid` = :uid", array(":uid" => $uid));
			m("fc")->fc_cache_write("member_getbyuid" . $uid, $parent, 60);
		}
		return $parent;
	}
	public function member_parentidbyuid($uid)
	{
		$parent = m("fc")->fc_cache_load("member_parentidbyuid" . $uid);
		if (empty($parent)) {
			$parent = pdo_fetchcolumn("SELECT from_id FROM " . tablename("qt_operate_member") . " WHERE `uid` = :uid", array(":uid" => $uid));
			m("fc")->fc_cache_write("member_parentidbyuid" . $uid, $parent, 60);
		}
		return $parent;
	}
	public function member_list()
	{
		global $_GPC, $_W;
		$page = empty($_GPC["page"]) ? 1 : $_GPC["page"];
		$pagesize = empty($_GPC["pagesize"]) ? 20 : $_GPC["pagesize"];
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
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		if (empty($uid) && empty($_GPC["isweb"])) {
			return array("data" => '', "total" => "0", "message" => "success", "status_code" => 200);
		}
		$where = '';
		if (intval($uid) > 0) {
			if ($_GPC["level"] == "1") {
				$where = " and from_id =" . $uid;
			} else {
				if ($_GPC["level"] == "2") {
					$where = " and parent_from_id =" . $uid;
				} else {
					$where = " and ( from_id =" . $uid . " or  parent_from_id =" . $uid . "  or  from_vip_id =" . $uid . ")";
				}
			}
			$where .= " AND vip=0  ";
		}
		if (!empty($endtime)) {
			$where .= " AND created_at >= " . $starttime . " AND created_at <=" . $endtime;
		}
		if (!empty($_GPC["keyword"])) {
			$where .= " AND (nickname like '%" . $_GPC["keyword"] . "%' or openid like '%" . $_GPC["keyword"] . "%' or uid like '%" . $_GPC["keyword"] . "%') ";
		}
		if ($_GPC["vip"] != '') {
			$where .= " AND vip = " . $_GPC["vip"];
		}
		$list = pdo_fetchall("select * from " . tablename("qt_operate_member") . " where  uniacid=:uniacid " . $where . " order by uid desc limit " . ($page - 1) * $pagesize . "," . $pagesize, array(":uniacid" => $_W["uniacid"]));

		foreach ($list as $key => $val){
            $list[$key]['created_at'] = date('Y-m-d H:i:s',$val['created_at']);
        }

		$totalcount = pdo_fetchcolumn("select count(id) from " . tablename("qt_operate_member") . " where  uniacid=:uniacid".$where, array(":uniacid" => $_W["uniacid"]));

        return array("data" => $list, "total" => $totalcount, "message" => "success", "status_code" => 200);
	}
	public function member_nicknamebyuid($uid)
	{
		$nickname = m("fc")->fc_cache_load("member_nicknamebyuid" . $uid);
		if (empty($parent)) {
			$nickname = pdo_fetchcolumn("SELECT nickname FROM " . tablename("qt_operate_member") . " WHERE `uid` = :uid", array(":uid" => $uid));
			m("fc")->fc_cache_write("member_nicknamebyuid" . $uid, $nickname, 60);
		}
		return $nickname;
	}
	public function member_uidbyopenid($openid = '')
	{
		global $_W;
		if (empty($openid)) {
			$openid = $_W["openid"];
		}
		return pdo_fetchcolumn("SELECT uid FROM " . tablename("qt_operate_member") . " WHERE openid=:openid and uniacid=:uniacid limit 1", array(":uniacid" => $_W["uniacid"], ":openid" => $openid));
	}
	public function member_update()
	{
		global $_GPC, $_W;
		$from_id = $_GPC["from_id"];
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		if (!empty($_GPC["realname"])) {
			$member["realname"] = $_GPC["realname"];
		}
		if (!empty($_GPC["alipayno"])) {
			$member["alipayno"] = $_GPC["alipayno"];
		}
		if (!empty($_GPC["subscribe"])) {
			$member["subscribe"] = $_GPC["subscribe"];
		}
		if (!empty($_W["fans"])) {
			$member["avatar"] = $_W["fans"]["avatar"];
			$member["nickname"] = $_W["fans"]["nickname"];
		}
		if (!empty($from_id)) {
			$member["from_id"] = $from_id;
			$parent = $this->member_getbyuid($from_id);
			$member["parent_from_id"] = $parent["from_id"];
			$member["from_vip_id"] = $parent["from_vip_id"];
		}
		if ($_GPC["vip"] == "1") {
			$member["vip"] = 1;
			$member["from_vip_id"] = $uid;
		}

        if ($_GPC["vip_level"] == "1") {
            $member["vip_level"] = 1;
        }


		$member["updated_at"] = time();
		$i = pdo_update("qt_operate_member", $member, array("uid" => $uid));
		if ($i) {
			$info = $this->member_getbyuid($uid);
			if ($member["vip"] == 1) {
				$this->member_update_from_vip_id($uid, $uid);
			}
		}
		return $info;
	}
	public function member_setcredit($openid = '', $credits = 0, $log = array())
	{
		global $_W;
		$member = $this->member_info($openid);
        $member = $member['result'];
        if ($member) {
			$newcredit = $credits + $member["credit"];
			if ($newcredit < 0) {
				return false;
			}
			pdo_update("qt_operate_member", array("credit" => $newcredit), array("uid" => $member["uid"]));
			if (!empty($log)) {
				$log["created_at"] = time();
				pdo_insert("qt_operate_balance_log", $log);
			}
		}
	}


	public function member_credit_log($uid = '')
	{
		global $_GPC, $_W;
		if (empty($uid)) {
			$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		}
		$page = empty($_GPC["page"]) ? 1 : $_GPC["page"];
		$pagesize = empty($_GPC["pagesize"]) ? 20 : $_GPC["pagesize"];
		$where = '';
		if (!empty($_GPC["logtype"])) {
			$where .= " and logtype=" . $_GPC["logtype"];
		}
		$lsit = pdo_fetchall("select * from " . tablename("qt_operate_balance_log") . " where uid = :uid and uniacid=:uniacid " . $where . " limit " . ($page - 1) * $pagesize . "," . $pagesize, array(":uniacid" => $_W["uniacid"], ":uid" => $uid));
		return $lsit;
	}
	public function member_apply_cash()
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		if (!empty($_GPC["alipayno"])) {
			$this->member_update();
		}
		$global = m("fc")->fc_getglobal();
		$member = $this->member_getbyuid($uid, true);
		if (floatval($member["credit"]) >= floatval($global["mincash"])) {
			$log["uniacid"] = $_W["uniacid"];
            $log["uid"] =$uid;
			$log["logno"] = $member["credit"];
			$log["isintegral"] = "0";
			if (floatval($global["rate"]) > 0) {
				$log["amount"] = $member["credit"] * (1 - $global["rate"] / 100);
			} else {
				$log["amount"] = $member["credit"];
			}
			$log["primaryamount"] = $member["credit"];
			$log["logtype"] = "2";
			$log["status"] = "0";
			$log["cash_pct"] = $global["rate"];
			$log["cash_type"] = $_GPC["cash_type"];
			$log["created_at"] = time();
			$log["remark"] = "【提现】佣金提现";
			$i = pdo_insert("qt_operate_balance_log", $log);
			if ($i) {
				$updatemember["credit"] = 0;
				$updatemember["updated_at"] = time();
				pdo_update("qt_operate_member", $updatemember, array("uid" => $member["uid"], "uniacid" => $_W["uniacid"]));
				return result_data(0,"已提交申请，等待审核！",[]);

			} else {
                return result_data(1,"提现失败，请稍后再试",[]);

			}
		} else {
            return result_data(2,"最小提现金额不可低于￥",[]);
		}
	}
	public function member_update_from_vip_id($uid, $vipuid)
	{
		global $_GPC, $_W;
		$list = pdo_fetchall("select * from " . tablename("qt_operate_member") . " where vip=0 and from_id=:uid and uniacid=:uniacid ", array(":uid" => $uid, ":uniacid" => $_W["uniacid"]));
		$updatedata["from_vip_id"] = $vipuid;
		$updatedata["updated_at"] = time();
		m("fc")->fc_log_debug("list count：" . count($list) . ",uid:" . $uid . ",vipuid:" . $vipuid, "member_update_from_vip_id");
		$i = pdo_update("qt_operate_member", $updatedata, array("from_id" => $uid, "vip" => 0));
		foreach ($list as $member) {
			m("fc")->fc_log_debug("uid:" . $member["uid"] . ",nickname:" . $member["nickname"], "member_update_from_vip_id");
			if ($member["uid"] > 0 && $member["vip"] == 0) {
				$this->member_update_from_vip_id($member["uid"], $vipuid);
			}
		}
	}
	public function member_apply_vip()
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		$global = m("fc")->fc_getglobal();
		$remark = '';
		$isok = false;
		if ($global["applyvip"] == "0") {
			$isok = true;
			$remark = "-自动通过";
		} else {
			if ($global["applyvip"] == "1") {
				m("fc")->fc_log_debug("isok：" . $isok, "member_apply_vip");
				$isok = $this->member_check_vipup($global);
				$remark = "-满足条件";
				m("fc")->fc_log_debug("isok：" . $isok, "member_apply_vip");
				if (!$isok) {
					$tid = date("YmdHis");
					return array("tid" => $tid, "user" => $_W["openid"], "fee" => $global["vip_fee"], "title" => "申请合伙人-付费");
				}
			}
		}
		if ($isok) {
			$_GPC["vip"] = "1";
			$info = $this->member_update();
			$log["uid"] = $uid;
			$log["uniacid"] = $_W["uniacid"];
			$log["log_type"] = 0;
			$log["logno"] = date("YmdHis");
			$log["isintegral"] = 0;
			$log["amount"] = 0;
			$log["primaryamount"] = 0;
			$log["logtype"] = 4;
			$log["status"] = 1;
			$log["cash_pct"] = 0;
			$log["remark"] = "升级合伙人" . $remark;
			$log["pay_at"] = time();
			$log["complete_at"] = time();
			$log["created_at"] = time();
			pdo_insert("qt_operate_balance_log", $log);
			m("fc")->fc_log_debug("info：" . json_encode($info), "member_apply_vip");
		}
		m("fc")->fc_log_debug("isok11：" . $isok, "member_apply_vip");
		if (empty($info)) {
			$info = $this->member_getbyuid($uid, true);
		}
		return $info;
	}
	public function member_check_vipup($global)
	{
		global $_GPC, $_W;
		$isok = false;
		m("fc")->fc_log_debug("vip_direct_count：" . $global["vip_direct_count"], "member_apply_vip");
		if (intval($global["vip_direct_count"]) > 0) {
			$_GPC["level"] = "1";
			$count = $this->member_getcount();
			m("fc")->fc_log_debug("vip_direct_count_real：" . $count, "member_apply_vip");
			if (intval($count) >= $global["vip_direct_count"]) {
				$isok = true;
			} else {
				$isok = false;
			}
		} else {
			$isok = true;
		}
		m("fc")->fc_log_debug("vip_indirect_count：" . $global["vip_direct_count"], "member_apply_vip");
		if (intval($global["vip_indirect_count"]) > 0) {
			$_GPC["level"] = "2";
			$count = $this->member_getcount();
			m("fc")->fc_log_debug("vip_indirect_count_real：" . $count, "member_apply_vip");
			if (intval($count) >= $global["vip_direct_count"] && $isok) {
				$isok = true;
			} else {
				$isok = false;
			}
		}
		m("fc")->fc_log_debug("vip_order_count：" . $global["vip_direct_count"], "member_apply_vip");
		if (intval($global["vip_order_count"]) > 0) {
			$count = m("order")->order_getcount();
			m("fc")->fc_log_debug("vip_order_count_real：" . $count, "member_apply_vip");
			$_GPC["level"] = '';
			$_GPC["status"] = "1";
			if (intval($count) >= $global["vip_direct_count"] && $isok) {
				$isok = true;
			} else {
				$isok = false;
			}
		}
		return $isok;
	}
	public function member_vipup_condition()
	{
		global $_GPC, $_W;
		$_GPC["level"] = "1";
		$firstcount = $this->member_getcount();
		$_GPC["level"] = "2";
		$secondcount = $this->member_getcount();
		unset($_GPC["level"]);
		$_GPC["status"] = "1";
		$ordercount = m("order")->order_getcount();
		$data = array("firstcount" => $firstcount, "secondcount" => $secondcount, "ordercount" => $ordercount);
		return $data;
	}
	public function member_getcount()
	{
		global $_GPC, $_W;
		$where = '';
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
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
		if (!empty($uid)) {
			if ($_GPC["level"] == "0") {
				$where = " and ( from_id =" . $uid . " or  parent_from_id =" . $uid . "  or  from_vip_id =" . $uid . ")";
			} else {
				if ($_GPC["level"] == "1") {
					$where = " and from_id =" . $uid;
				} else {
					if ($_GPC["level"] == "2") {
						$where = " and parent_from_id =" . $uid;
					}
				}
			}
			$where .= " AND vip=0  ";
		}
		if (!empty($endtime)) {
			$where .= " AND created_at >= " . $starttime . " AND created_at <=" . $endtime;
		}
		$totalcount = pdo_fetchcolumn("SELECT COUNT(DISTINCT id) total FROM " . tablename("qt_operate_member") . "   where uniacid=:uniacid  " . $where, array(":uniacid" => $_W["uniacid"]));
		return $totalcount;
	}
	public function member_address()
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		$member["userName"] = $_GPC["userName"];
		$member["postalCode"] = $_GPC["postalCode"];
		$member["provinceName"] = $_GPC["provinceName"];
		$member["cityName"] = $_GPC["cityName"];
		$member["countyName"] = $_GPC["countyName"];
		$member["address"] = $_GPC["address"];
		$member["telNumber"] = $_GPC["telNumber"];
		$member["updated_at"] = time();
		$i = pdo_update("qt_operate_member", $member, array("uniacid" => $_W["uniacid"], "uid" => $uid));
	}
	public function member_myteam()
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		$recommendmember = $this->member_getbyuid($this->member_parentidbyuid($uid));
		$_GPC["level"] = 0;
		$totalall = $this->member_getcount();
		$_GPC["level"] = 1;
		$totalfirst = $this->member_getcount();
		$_GPC["level"] = 2;
		$totalsecond = $this->member_getcount();
		$_GPC["daterange"] = date("Y-m-d") . " ~ " . date("Y-m-d");
		$_GPC["level"] = 1;
		$totaltoday = $this->member_getcount();
		$_GPC["daterange"] = date("Y-m-d", strtotime("-1 day")) . " ~ " . date("Y-m-d ", strtotime("-1 day"));
		$_GPC["level"] = 1;
		$totalyesterday = $this->member_getcount();
		unset($_GPC["daterange"]);
		$_GPC["level"] = 1;
		$listfirst = $this->member_list();
		$_GPC["level"] = 2;
		$listsecond = $this->member_list();
		$data = array("recommendmember" => $recommendmember, "totalall" => $totalall, "totalfirst" => $totalfirst, "totalsecond" => $totalsecond, "totaltoday" => $totaltoday, "totalyesterday" => $totalyesterday, "listfirst" => $listfirst["data"], "listsecond" => $listsecond["data"]);
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