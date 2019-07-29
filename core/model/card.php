<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Card_NetsHaojkModel
{
    public function card_list(){

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

        if (!empty($endtime)) {
            $where .= " AND c.addtime >= " . $starttime . " AND c.addtime <=" . $endtime;
        }
        if (!empty($_GPC["keyword"])) {
            $where .= " AND (nickname like '%" . $_GPC["keyword"] . "%') ";
        }

        $list = pdo_fetchall("select c.id,m.nickname,status,c.addtime,pre_account,account_no,password,expire_day from ".tablename("qt_card")." c left join ".tablename("qt_operate_member")." m on c.use_user_id = m.id where c.uniacid=:uniacid " . $where . " order by c.id desc limit " . ($page - 1) * $pagesize . "," . $pagesize, array(":uniacid" => $_W["uniacid"]));

        foreach ($list as $key => $val){
            $list[$key]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
        }

        $totalcount = pdo_fetchcolumn("select count(id) from " . tablename("qt_card") . " c where  uniacid=:uniacid".$where, array(":uniacid" => $_W["uniacid"]));

        return array("data" => $list, "total" => $totalcount, "message" => "success", "status_code" => 200);

    }


    public function card_active(){
        global $_GPC, $_W;

        $account_no = $_GPC['account_no'];
        $password = $_GPC['password'];

//        if(empty($account_no) || empty($password)){
//            return result_data(1,"不能为空");
//        }
//        $account = pdo_get("qt_card",['account_no'=>$account_no , "status"=>0]);
//        if(empty($account) || $account['password'] != $password){
//            return result_data(2,"卡不正确");
//        }

        if(empty($password)){
            return result_data(1,"不能为空");
        }
        $account = pdo_get("qt_card",['password'=>$password , "status"=>0]);
        if(empty($account)){
            return result_data(2,"卡不正确");
        }

        pdo_update("qt_card",['use_user_id' => $_GPC['member']['id'],"use_time"=>time() , "status"=>1] , ['id'=>$account['id']]);

        pdo_update("qt_operate_member",['vip_level'=>1,'expire_time'=>time() + $account['expire_day'] *86400] ,['id'=>$_GPC['member']['id']]);
        return result_data(0,"激活成功");
    }
}