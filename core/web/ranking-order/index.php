<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Index_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{
        global $_GPC;
        global $_W;

		if ($_W['action']=='ranking-order.index'||$_W['action']=='ranking-order')
            $this->index();
        elseif ($_W['action']=='ranking-order.index.goodssource_add')
            $this->goodssource_add();
        elseif ($_W['action']=='ranking-order.index.goodssource_addpost')
            $this->goodssource_addpost();
	}
	/***
     * 订单列表
	***/
	public function index()
	{
        global $_GPC, $_W;


        if (!empty($_GPC['orderids'])) {
            $orderids = " and o.id = ".$_GPC['orderids'];
        }

        if (!empty($_GPC['yn'])) {
            $yn= " and o.status = ".$_GPC['yn'];
        }

        if (!empty($_GPC['uid'])) {
            $uid= " and o.uid = ".$_GPC['uid'];
        }

        $where = " 1=1 ";
        if($orderids || $uid || $yn){
            $where .=$orderids.$yn.$uid;
        }

        $where.=" and m.uniacid={$_W['uniacid']}";
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $list = pdo_fetchall("select o.*,m.nickname,g.title from " . tablename("qt_goods_order")." o left join ".tablename("qt_operate_member")." m on o.uid=m.uid join ".tablename("qt_goods")." g on g.id=o.goods_id where ".$where." ORDER BY o.id DESC limit ".(($pindex - 1) * $psize) . ',' . $psize);

        $total = pdo_fetchcolumn("select count(0) from ".tablename("qt_goods_order")." o left join ".tablename("qt_operate_member")." m on o.uid=m.uid join ".tablename("qt_goods")." g on g.id=o.goods_id where ".$where);
        $pager = pagination($total, $pindex, $psize);

        include $this->template('haojingke/ranking-order/index');
	}


    public function goodssource_add()
    {
        global $_W;
        global $_GPC;
        if(!empty($_GPC['id'])){
            $diysource = pdo_fetch("SELECT * FROM ".tablename('qt_goods_order')." WHERE id=:id "
                ,array(":id"=>$_GPC['id']));
        }

        include $this->template('haojingke/ranking-order/goodssource_add');
    }

    public function goodssource_addpost()
    {
        global $_W;
        global $_GPC;

        $source_query = array();

//        $sourcedata['goods_id'] = intval($_GPC['goods_id']);
//        $sourcedata['rank_id'] = intval($_GPC['rank_id']);
//        $sourcedata['uid'] = intval($_GPC['uid']);
        $sourcedata['express_name'] = $_GPC['express_name'];
        $sourcedata['express_no'] = $_GPC['express_no'];
        $sourcedata['name'] = $_GPC['name'];
        $sourcedata['address'] = $_GPC['address'];
        $sourcedata['status'] = $_GPC['status'];
        $sourcedata['mobile'] = $_GPC['mobile'];
        $sourcedata['delivery_time'] = strtotime($_GPC['delivery_time']);

        if (!empty($_GPC['id'])) {
            $res = pdo_update('qt_goods_order',$sourcedata,array('id'=>$_GPC['id']));
        }else{
            $res = pdo_insert('qt_goods_order',$sourcedata);
        }
        if($res)
            show_json(1,"操作成功");
        else
            show_json(1,"操作失败");
    }
	
}
?>