<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/7/10
 * Time: 17:05
 */

if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require_once IA_ROOT . '/addons/qt_shop/core/model/apicloud.php';
class Index_NetsHaojkMywPage extends WebPage
{
    public function main()
    {
        global $_GPC, $_W;
        if ($_W['action']=='sale.choujiang.index'||$_W['action']=='sale.choujiang')
            $this->index();
        elseif ($_W['action']=='sale.choujiang.choujiang_add')
            $this->choujiang_add();
        elseif ($_W['action']=='sale.choujiang.choujiang_addpost')
            $this->choujiang_addpost();
        elseif ($_W['action']=='sale.choujiang.choujiang_delete')
            $this->choujiang_delete();
        elseif ($_W['action']=='sale.choujiang.detail')
            $this->detail();
    }

    /***
     * 抽奖列表
    ***/
    public function index()
    {
        global $_GPC, $_W;
        $page = empty($_GPC["page"])?1:$_GPC["page"];
        $pagesize=empty($_GPC["pagesize"])?20:$_GPC["pagesize"];
        $res = m('choujiang')->cj_getlist();
        $list = $res['data'];
        $totalcount = $res['total'];
        $pager = pagination($totalcount, $page, $pagesize);
        include $this->template('haojingke/sale/choujiang/index');
    }

    /***
     * 查看详情
     ***/
    public function detail()
    {
        global $_W;
        global $_GPC;
        $page = empty($_GPC["page"])?1:$_GPC["page"];
        $pagesize=empty($_GPC["pagesize"])?20:$_GPC["pagesize"];
        $info = pdo_fetch('select * from '.tablename('qt_operate_choujiang').' where uniacid=:uniacid and id=:id  limit 1' , array(':uniacid' => $_W['uniacid'],':id' => $_GPC['id']));

        $where = '';
        $keyword = $_GPC["keyword"];
        if(!empty($keyword))
            $where = "and (uid like '%".$keyword."%' or nickname like '%".$keyword."%' or openid like '%".$keyword."%' )";
        $status = $_GPC['status'];
        if($status!='')
            $where.=" and status = '".$_GPC["status"]."'";
        if($status!='')
            $where.=" and status = '".$_GPC["status"]."'";
        $totalcount = m('choujiang')->cj_gettotalcount($_GPC['id']);
        $joincount = m('choujiang')->cj_getjoincount($_GPC['id']);
        $list = array();
        if($totalcount>0){
            $sql = "select id,uid,openid,nickname,formid,join_type,help_uid,open_code,open_at,status,created_at,1 ucount,FORMAT(100/" . $totalcount . ",2) pct from "
                .tablename('qt_operate_choujiang_detail')
                ." where choujiangid='".$_GPC['id']."' and uniacid='".$_W['uniacid']."' ".$where." ORDER by id DESC LIMIT "
                . (($page - 1) * $pagesize) ."," . $pagesize;
            $totalsql = "select COUNT(id) totalcount from "
                .tablename('qt_operate_choujiang_detail')
                ." where choujiangid='".$_GPC['id']."' and uniacid='".$_W['uniacid']."' ".$where;
            //合并用户
            if($_GPC['groupbyuid']=='on') {
                $sql = "select id,uid,openid,nickname,formid,join_type,help_uid,open_code,open_at,status,created_at,COUNT(id) ucount,FORMAT(COUNT(id)*100/" . $totalcount . ",2) pct from "
                    . tablename('qt_operate_choujiang_detail')
                    . " where choujiangid='" . $_GPC['id'] . "' and uniacid='" . $_W['uniacid'] . "' " . $where . "   GROUP BY uid  ORDER by id DESC LIMIT "
                    . (($page - 1) * $pagesize) . "," . $pagesize;
                $totalsql = "select COUNT(DISTINCT uid) totalcount from "
                    . tablename('qt_operate_choujiang_detail')
                    . " where choujiangid='" . $_GPC['id'] . "' and uniacid='" . $_W['uniacid'] . "' " . $where . "   GROUP BY uid ";
            }

            $list = pdo_fetchall($sql);
            $count = pdo_fetchcolumn($totalsql);
            if(count($list)==1&&empty($list[0]['id']))
                $list = array();
        }
        $pager = pagination($count, $page, $pagesize);
        if($_GPC['groupbyuid']=='on')
            $pager = pagination($joincount, $page, $pagesize);
        include $this->template('haojingke/sale/choujiang/detail');
    }

    /***
     * 发起抽奖（编辑）页面
     ***/
    public function choujiang_add()
    {
        global $_W;
        global $_GPC;
        $cjinfo=pdo_fetch("select * from ".tablename('qt_operate_choujiang')." where id=:id",array(":id"=>$_GPC['id']));
        $open_h=date('H')+1;
        if($open_h=='24')
            $open_h = "0";
        $open_m="00";
        $open_day = '';
        $open_day=date('Y-m-d');
        if(!empty($cjinfo)&&$cjinfo["open_at"]>0){
            $open_day=date('Y-m-d', $cjinfo['open_at']);
            $open_h=intval(date('H', $cjinfo['open_at']));
            $open_m=date('i', $cjinfo['open_at']);
        }
        $cjinfopicture=explode(",",$cjinfo['goods_pic']);
        include $this->template('haojingke/sale/choujiang/post');
    }

    /***
     * 发起抽奖提交
     ***/
    public function choujiang_addpost()
    {
        global $_W;
        global $_GPC;
//        $i=choujiang_edit();
        $res = m('choujiang')->cj_post();
        if ($res) {
            show_json(1,"操作成功");
        }else{
            show_json(1,"操作失败");
        }
    }


    public function choujiang_delete()
    {
        global $_W;
        global $_GPC;
        if(!empty($_GPC['id'])){
            //删除发送失败
            $cjinfo=pdo_fetch("select * from ".tablename('qt_operate_choujiang')." where id=:id",array(":id"=>$_GPC['id']));
            $data['status']=-1;//活动停用的
            $data["updated_at"]=time();
            $j=pdo_update("qt_operate_choujiang",$data,array("id"=>$cjinfo['id']));
            if ($j>0) {
                show_json(1,"操作成功");
            }else{
                show_json(1,"操作失败");
            }
        }
        show_json(1,"信息错误，此条记录可能已删除，请刷新页面");
    }

}
?>