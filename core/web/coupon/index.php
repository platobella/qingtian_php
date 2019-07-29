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

		if ($_W['action']=='coupon.index'||$_W['action']=='coupon')
            $this->index();
	}
	/***
     * 全网查券
	***/
	public function index()
	{
        global $_GPC, $_W;
        //var_dump(WXAPP_DIY);
        $uniacid=$_W['uniacid'];

        $page=1;
        if(!empty($_GPC["page"]))
            $page = $_GPC["page"];
        $pagesize=20;
        $querydata['page']=$page;
        $querydata['pageSize']=$pagesize;
        $_GPC["istotal"]='1';
//        $res=m('apicloud')->api_cname();
//        $cate= $res['data'];
//        $res=m('apicloud')->api_pddcname();
//        $pddcate= $res['data'];
//        $res=m('apicloud')->api_mgjcname();
//        $mgjcate= $res['data'];

        $pddcate = pdo_fetchall("select id,name,pdd_cid,jd_cid,tb_cid from " . tablename("qt_classification")." where parent_id=0 and is_delete=0 and uniacid=:uniacid ORDER BY sort DESC",['uniacid'=>$_W['uniacid']]);


        if(!empty($_GPC['diysource']))
            $_GPC['diysourceid']=$_GPC['diysource'];
        if($_GPC["source_type"]=='3')
            $_GPC['cid']=$_GPC['mgjcid'];

        $resgoods = m('apicloud')->get_goods_list(true);

        if($_GPC["source_type"]=='3'&&!empty($_GPC['mgjcid']))
            $_GPC['cid']='';
        $list = $resgoods['result']['data'];
        $where = " source_status=1 and uniacid=".$_W['uniacid']." ";
        $diysource = pdo_fetchall("SELECT * FROM ".tablename('qt_operate_diysource')." where ".$where. " ORDER BY source_sort DESC ");

        $pager = pagination($resgoods['total'], $page, $pagesize);
        include $this->template('haojingke/coupon/index');
	}
	
}
?>