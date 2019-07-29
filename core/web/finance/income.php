<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Income_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{
        global $_GPC;
        global $_W;

		if ($_W['action']=='finance.income.index'||$_W['action']=='finance.income')
            $this->index();
	}
	/***
     * 余额日志
	***/
	public function index()
	{
        global $_GPC, $_W;
        $page = empty($_GPC["page"])?1:$_GPC["page"];
        $pagesize=empty($_GPC["pagesize"])?20:$_GPC["pagesize"];
        $where=" AND l.logtype!=2";
        $parm=array(':uniacid' => $_W['uniacid']);
        if(!empty($_GPC['uid'])){
            $where.=" and l.uid = :uid";
            $parm["uid"]=$_GPC["uid"];
        }
        $date = array();
        if(!empty($_GPC['daterange']))
            $date = explode(' ~ ', $_GPC['daterange']);
        $starttime = 0;
        $endtime =  TIMESTAMP + 86399;
        if(count($date)==2){
            $starttime =strtotime($date[0]);
            $endtime =strtotime($date[1]) + 86399;
        }
        if(!empty($starttime)){
            $where.= " AND l.created_at >= ".$starttime. " AND l.created_at <=" .$endtime;
        }
        if(!empty($_GPC['keyword'])){
            $where.= " AND (m.nickname like '%".$_GPC['keyword']."%'  or m.mobile like '%".$_GPC['keyword']."%' )";
        }
        if ($_GPC['logtype'] != '') {
            $where.=" AND l.logtype=".$_GPC['logtype'];
        }
        $list = pdo_fetchall('select l.*,m.nickname,m.alipayno,m.mobile from '.tablename('qt_operate_balance_log')
            .' AS l left join '.tablename('qt_operate_member').' AS m ON m.uid=l.uid where l.uniacid=:uniacid '.$where.' limit '.(($page - 1) * $pagesize) . ',' . $pagesize
            ,$parm );

        $total = pdo_fetchcolumn('SELECT count(0) FROM '.tablename('qt_operate_balance_log')
            .' AS l left join '.tablename('qt_operate_member').' AS m ON m.uid=l.uid where l.uniacid=:uniacid '.$where
            ,$parm );
        $pager = pagination($total, $page, $pagesize);
        include $this->template('haojingke/finance/income/index');
	}

}
?>