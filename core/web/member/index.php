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

        if ($_W['action']=='member.index'||$_W['action']=='member')
            $this->index();
        elseif ($_W['action']=='member.position_delete')
            $this->member_delete();
        elseif ($_W['action']=='member.member_edit')
            $this->member_edit();
        elseif ($_W['action']=='member.member_editpost')
            $this->member_editpost();
        elseif ($_W['action']=='member.member_subordinate')
            $this->member_subordinate();
	}
	/***
     * 会员信息
	***/
	public function index()
	{
        global $_GPC, $_W;
        $page = empty($_GPC["page"])?1:$_GPC["page"];
        $pagesize=empty($_GPC["pagesize"])?20:$_GPC["pagesize"];
        $_GPC['isweb'] = '1';
        $res = m('member')->member_list();
        $list = $res['data'];
        $totalcount = $res['total'];
        $pager = pagination($totalcount, $page, $pagesize);
        include $this->template('haojingke/member/index');
	}

    /***
     * 会员删除-删除
    ***/
    public function member_delete()
    {
        global $_W;
        global $_GPC;
        $j = pdo_delete('qt_operate_member',array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
        if ($j>0) {
            show_json(1,"删除成功！");
        }else{
            show_json(1,"删除失败！");
        }
    }



	/***
     * 会员升级/取消vip
    ***/
    public function member_vip()
    {
        global $_W;
        global $_GPC;

        $_GPC["uid"]=$_GPC["id"];

        if($_GPC["type"] == 1){
            $_GPC["vip_level"]="1";
        }else{
            $_GPC["vip"]="1";
        }

        $j = m('member')->member_update();
        if ($j>0) {
            show_json(1,"操作成功！");
        }else{
            show_json(1,"操作失败！");
        }
    }

    /**
     *下属会员
    ***/
    public function member_subordinate(){
        global $_GPC, $_W;
        $page = empty($_GPC["page"])?1:$_GPC["page"];
        $pagesize=empty($_GPC["pagesize"])?20:$_GPC["pagesize"];
        $res = m('member')->member_list();
        $list = $res['data'];
        $totalcount = $res['total'];
        $pager = pagination($totalcount, $page, $pagesize);
        include $this->template('haojingke/member/member_subordinate');
    }



    //会员-修改
    public function member_edit()
    {
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $edit_level = pdo_fetch("SELECT * FROM ". tablename('qt_operate_member'). " where id = ".$_GPC['id']);
        include $this->template('haojingke/member/member_edit');
    }

    //会员-修改
    public function member_editpost()
    {
        global $_W;
        global $_GPC;
        $uniacid=$_W['uniacid'];
        $data['level'] = $_GPC['level'];
        $data['type'] = $_GPC['type'];
        $memberid = 0;
        $m  = pdo_fetch("SELECT * FROM ".tablename('qt_operate_member'). " WHERE id=:id",array(':id'=>$_GPC['id']));
        $memberid = $m['memberid'];
        if (!empty($_GPC['invite'])) {
            //手动修改邀请人 从fans表里查询目标会员uid zxq.2018.05.21 core\web\member\index.php in 174
            $r  = pdo_fetch("SELECT * FROM ".tablename('qt_operate_member'). " WHERE uniacid=:uniacid and (openid = :memberid or memberid=:memberid)",array(':uniacid'=>$uniacid,':memberid'=>$_GPC['invite']));
            if (empty($r)) {
                show_json(1,"邀请人不存在或者会员ID/openid错误！");
            }else{
                if ($m['memberid'] == $r['memberid']) {
                    show_json(1,"邀请人不能选择自己!");
                }else{
                    $data['from_uid'] = $r['memberid'];
                    $data['from_uid2'] = $r['from_uid'];
                    $data['from_partner_uid'] = $r['from_partner_uid'];
                }
            }
        }else{
            $data['from_uid'] =0;
        }
        if (!empty($_GPC['partner_uid'])) {
            //手动修改合伙人从fans表里查询目标会员
            $p  = pdo_fetch("SELECT * FROM ".tablename('qt_operate_member'). " WHERE type=2 and uniacid=:uniacid and (openid = :memberid or memberid=:memberid)",array(':uniacid'=>$uniacid,':memberid'=>$_GPC['partner_uid']));
            if (empty($p)) {
                show_json(1,"合伙人不存在或者会员ID/openid错误！");
            }else{
                //$m  = pdo_fetch("SELECT * FROM ".tablename('qt_operate_member'). " WHERE id=:id",array(':id'=>$_GPC['id']));
                if ($m['memberid'] == $p['memberid']) {
                    show_json(1,"合伙人不能选择自己!");
                }else{
                    $data['from_partner_uid'] = $p['memberid'];
                }
            }
        }else{
            $data['from_partner_uid'] =0;
        }

        $data['updated_at'] = time();
        $data['created_at'] = time();
        if($memberid>0)
        {
            $r = pdo_update('qt_operate_member',$data,array('memberid'=>$memberid));
        }
        else
            $r = pdo_update('qt_operate_member',$data,array('id'=>$_GPC['id']));
        if($r)
        {
            if($_GPC['type']==1 || $_GPC['type']==2)
            {
                if($_W["wxpddauth"]==1 && $_W['global']['pddmodule_status']==1 && $m['pdd_bitno']=="")
                {
                    getpdd_Pidcreate($memberid);
                }
                if(($_W["wxgzhauth"]==1 || $_W["wxappauth"]==1)&& $_W['global']['jdmodule_status']==1 && $m['jd_bitno']=="")
                {
                    $otherwxapp_uniacid=0;
                    if($_W['acctype']=="小程序")
                        $otherwxapp_uniacid=$_W['global']['wxapp_uniacid'];
                    if($_W['acctype']=="公众号")
                        $otherwxapp_uniacid=pdo_fetch("select uniacid from ".tablename('nets_hjk_global')." where wxapp_uniacid=:wxapp_uniacid",array(':wxapp_uniacid'=>$_W['uniacid']));
                    $jdbit  = pdo_fetch("select bitno from ".tablename('nets_hjk_probit')." where uniacid=:uniacid and not exists(select 1 from ".tablename('qt_operate_member')." where jd_bitno=bitno and (uniacid=:uniacid or uniacid=:wxapp_uniacid))",array(':uniacid'=>$_W['uniacid'],':wxapp_uniacid'=>$otherwxapp_uniacid));
                    $da['jd_bitno']=$jdbit['bitno'];
                    $jd = pdo_update('qt_operate_member',$da,array('memberid'=>$memberid));
                    $probit['state']=1;
                    $probit['memberid']=$memberid;
                    pdo_update('nets_hjk_probit',$probit,array('bitno'=>$da['jd_bitno'],'uniacid'=>$_W['uniacid']));
                    if(!$jd)
                        show_json(1,"京东推广位分配失败，请手工分配！");
                }
            }
            show_json(1,"操作成功");
        }
        else
            show_json(1,"操作失败");
    }



}
?>