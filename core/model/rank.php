<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Rank_NetsHaojkModel
{

    public function goods_list(){
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        pdo_fetchall("select * from ".tablename("qt_goods")." ");
        $listfirst = $this->member_list();
        $totalall = $listfirst['total'];
        $listfirst = $listfirst['data'];

        m('fc')->fc_result(0, '成功',['friend_list'=>$listfirst,'total_count'=>$totalall]);
    }

    public function rank_list(){

    }


}