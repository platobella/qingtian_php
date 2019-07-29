<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Cash_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{
        global $_GPC;
        global $_W;

		if ($_W['action']=='finance.cash.index'||$_W['action']=='finance.cash')
            $this->index();
        if ($_W['action']=='finance.cash.cash_alipay')
            $this->cash_alipay();
        if ($_W['action']=='finance.cash.cash_wechart')
            $this->cash_wechart();
        if ($_W['action']=='finance.cash.cash_allow')
            $this->cash_allow();
        if ($_W['action']=='finance.cash.cash_refuse')
            $this->cash_refuse();
	}
	/***
     * 订单列表
	***/
	public function index()
	{
        global $_GPC, $_W;
        $page = empty($_GPC["page"])?1:$_GPC["page"];
        $pagesize=empty($_GPC["pagesize"])?20:$_GPC["pagesize"];
        $where=" AND l.logtype=2";
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
        if ($_GPC['cash_type'] != '') {
            $where.=" AND l.cash_type=".$_GPC['cash_type'];
        }
        $list = pdo_fetchall('select l.*,m.nickname,m.alipayno,m.mobile from '.tablename('qt_operate_balance_log')
            .' AS l left join '.tablename('qt_operate_member').' AS m ON m.uid=l.uid where l.uniacid=:uniacid and m.uniacid=:uniacid '.$where.' limit '.(($page - 1) * $pagesize) . ',' . $pagesize
            ,$parm );

        $total = pdo_fetchcolumn('SELECT count(0) FROM '.tablename('qt_operate_balance_log')
            .' AS l left join '.tablename('qt_operate_member').' AS m ON m.uid=l.uid where l.uniacid=:uniacid '.$where
            ,$parm );
        $pager = pagination($total, $page, $pagesize);
        include $this->template('haojingke/finance/cash/index');
	}

    //支付宝打款
    public function cash_alipay()
    {
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $_GPC['tradeno']=date('Ymd') . time();
        if (!empty($_GPC['id'])) {
            $data['updated_at'] = time();
            $data['status'] = 1;
            $sql="select * from ".tablename('qt_operate_sysset')." where uniacid=:uniacid";
            $set=pdo_fetch($sql,array(":uniacid"=>$uniacid));
            $info=pdo_fetch("SELECT a.*,m.alipayno FROM ".tablename('qt_operate_balance_log')." AS a LEFT JOIN " .tablename('qt_operate_member'). " AS m ON a.uid=m.uid and m.uniacid=a.uniacid and m.uniacid=".$uniacid." WHERE a.logtype =2 and a.status = 0 and a.id=:id",array(":id"=>$_GPC['id']));
            if ($info['cash_type'] == 2) {
                if (empty($set) || empty($set["alipay_appid"]))
                    show_json(1,'操作失败，请在系统提现设置中设置支付宝商户提现信息');
            }
            $biz_content = array();
            //单号
            $biz_content['out_biz_no'] = time();
            $biz_content['payee_type'] = 'ALIPAY_LOGONID';
            //支付宝账号
            $biz_content['payee_account'] =$info["alipayno"];
            //支付金额 最低0.1
            $biz_content['amount'] = abs($info["amount"]);
            $biz_content['payer_show_name'] = '余额提现';
            $biz_content['payee_real_name'] = '';
            $biz_content['remark'] = '余额提现';
            $biz_content = array_filter($biz_content);
            $config['method'] = 'alipay.fund.trans.toaccount.transfer';
            //app_id
            $config['app_id'] = $set["alipay_appid"];
            //private_key
            $config['private_key'] = $set["alipay_privatekey"];
            $config['biz_content'] = json_encode($biz_content);
            $res = publicAliPay($config);
            if($res['alipay_fund_trans_toaccount_transfer_response']['code']==10000){
                $data['remark']="[打款成功，交易单号为：".$res['alipay_fund_trans_toaccount_transfer_response']['order_id']."] ";
                pdo_update("qt_operate_balance_log",$data,array('id' => $_GPC['id'],'logtype'=> '2'));
                show_json(1,$data['remark']);
            }else{
                $data['updated_at'] = time();
                $data['status'] = 0;
                $data['remark']=$info['remark'].'支付宝打款失败，错误信息['.$res['alipay_fund_trans_toaccount_transfer_response']['sub_msg']."] ";
                pdo_update("qt_operate_balance_log",$data,array('id' => $_GPC['id'],'logtype'=> '2'));
                show_json(1,'支付宝打款失败，错误信息['.$res['alipay_fund_trans_toaccount_transfer_response']['sub_msg'].']');
            }
        }else{
            show_json(1,"数据不存在或者其他管理员已审核，请刷新页面！");
        }
    }

    //微信打款
    public function cash_wechart()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $_GPC['tradeno'] = date('Ymd') . time();
        if (!empty($_GPC['id'])) {
            $data['updated_at'] = time();
            $data['status'] = 1;
            $sql = "select * from " . tablename('qt_operate_sysset') . " where uniacid=:uniacid";
            $set = pdo_fetch($sql, array(":uniacid" => $uniacid));
            $info = pdo_fetch("SELECT a.*,m.alipayno,m.openid FROM " . tablename('qt_operate_balance_log') . " AS a LEFT JOIN " . tablename('qt_operate_member') . " AS m ON a.uid=m.uid and m.uniacid=a.uniacid and m.uniacid=" . $uniacid . " WHERE a.logtype =2 and a.status = 0 and a.id=:id", array(":id" => $_GPC['id']));
            if ($info['cashtype'] == 1) {
                if (empty($set) || empty($set["mchid"]))
                    show_json(1, '操作失败，请在系统设置中设置微信商户信息');
            }
            $res = false;
            //先修改记录为已完成
            $i = pdo_update("qt_operate_balance_log", $data, array('id' => $_GPC['id'], 'logtype' => '2'));
            if ($i > 0) {
                $res = payWeixin($info['openid'], $info["amount"], $set["mchid"], $_GPC['tradeno']);
                //微信打款失败的
                //var_dump($res);
                if (empty($res['errno'])) {
                    $data['remark'] = $info['remark'] . "[打款成功，交易单号为：" . $_GPC['tradeno'] . "] ";
                    pdo_update("qt_operate_balance_log", $data, array('id' => $_GPC['id'], 'logtype' => '2'));
                } else {
                    $data['updated_at'] = time();
                    $data['status'] = 0;
                    $data['remark'] = $info['remark'] . "[打款失败，交易单号为：" . $_GPC['tradeno'] . "] ";
                    pdo_update("qt_operate_balance_log", $data, array('id' => $_GPC['id'], 'logtype' => '2'));
                    show_json(1, '微信打款失败，错误码[' . $res['errno'] . '][' . $res['message'] . ']');
                }
                show_json(1, "操作成功");
            } else {
                show_json(1, "操作失败");
            }
        } else {
            show_json(1, "数据不存在或者其他管理员已审核，请刷新页面！");
        }
    }

    //手动发放
    public function cash_allow()
    {
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $_GPC['tradeno']=date('Ymd') . time();
        if (!empty($_GPC['id'])) {
            $data['status'] = 1;
            $data['updated_at'] = time();
            $data['remark']= "手动发放";
            pdo_update("qt_operate_balance_log",$data,array('id' => $_GPC['id'],'logtype'=> '2'));
            show_json(1,'手动发放成功！');
        }else{
            show_json(1,"数据不存在或者其他管理员已审核，请刷新页面！");
        }
    }
    //拒绝审核
    public function cash_refuse(){
        global $_GPC, $_W;
        $uniacid=$_W['uniacid'];
        if (!empty($_GPC['id'])) {
            $logInfo = pdo_get('qt_operate_balance_log',array('id'=>$_GPC['id']));
            $data['updated_at'] = time();
            $data['status'] = 2;//修改记录为拒绝
            $data['remark'] = $logInfo['remark'] . "[已拒绝，原金额已退回余额]";
            $b=  pdo_update('qt_operate_balance_log',$data,array('id'=>$_GPC['id'],'logtype'=> '2'));
            //退回余额
            if($logInfo){
                $member=pdo_fetch("SELECT * from  ".tablename("qt_operate_member")."  where uid=:uid "
                    ,array(":uid"=>$logInfo["uid"]));
                //返还到余额
                $m["credit"]=floatval($member['credit'])+floatval($logInfo['primaryamount']);//把原金额退回
                $b=pdo_update("qt_operate_member",$m,array("uid"=>$member["uid"]));
            }
            if($b)
                show_json(1,"操作成功");
            else
                show_json(1,"操作失败");
        }
        else
            show_json(1,"数据不存在或者其他管理员已审核，请刷新页面！");
    }
	
}
?>