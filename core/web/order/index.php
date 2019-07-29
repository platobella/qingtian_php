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

		if ($_W['action']=='order.index'||$_W['action']=='order')
            $this->index();
	}
	/***
     * 订单列表
	***/
	public function index()
	{
        global $_GPC, $_W;
        $page = empty($_GPC["page"])?1:$_GPC["page"];
        $pagesize=empty($_GPC["pagesize"])?20:$_GPC["pagesize"];
        $_GPC['isweb'] = '1';
        $res = m('order')->web_order_list();

        $list = $res['data'];
        $totalcount = $res['total'];
        $pager = pagination($totalcount, $page, $pagesize);
        include $this->template('haojingke/order/index');
	}
	
}
?>