<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Groupsend_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{
        global $_GPC;
        global $_W;

		if ($_W['action']=='sale.groupsend.index'||$_W['action']=='sale.groupsend')
            $this->index();
        elseif ($_W['action']=='sale.groupsend.groupsend_add')
            $this->groupsend_add();
        elseif ($_W['action']=='sale.groupsend.groupsend_addpost')
            $this->groupsend_addpost();
        elseif ($_W['action']=='sale.groupsend.groupsend_msg_temp_list')
            $this->groupsend_msg_temp_list();
        elseif ($_W['action']=='sale.groupsend.groupsend_msg_temp_delete')
            $this->groupsend_msg_temp_delete();
        elseif ($_W['action']=='sale.groupsend.groupsend_msg_temp_add')
            $this->groupsend_msg_temp_add();
        elseif ($_W['action']=='sale.groupsend.groupsend_msg_temp_addpost')
            $this->groupsend_msg_temp_addpost();
        elseif ($_W['action']=='sale.groupsend.groupsend_msg_send_single')
            $this->groupsend_msg_send_single();
        elseif ($_W['action']=='sale.groupsend.groupsend_msg_add')
            $this->groupsend_msg_add();
        elseif ($_W['action']=='sale.groupsend.groupsend_msg_send')
            $this->groupsend_msg_send();
        elseif ($_W['action']=='sale.groupsend.groupsend_delete')
            $this->groupsend_delete();
	}

	//待发记录
	public function index()
	{
        global $_GPC, $_W;
        $uniacid=$_W['uniacid'];

        $where='';
        if($_GPC['state']!='')
            $where .= " AND state = ".$_GPC['state'];
        if(!empty($_GPC['keyword'])){
            $where .= " AND (openid like '%".$_GPC['keyword']."%' or title like '%".$_GPC['keyword']."%') ";
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $list = pdo_fetchall("SELECT * FROM ".tablename('qt_sendmsg_rec')." WHERE uniacid=".$uniacid.$where." order by id desc limit ". (($pindex - 1) * $psize) . ',' . $psize);
        $totalcount = pdo_fetchcolumn("select count(0) from ".tablename("qt_sendmsg_rec")." WHERE uniacid= " .$uniacid.$where);
        $pager = pagination($totalcount, $pindex, $psize);

        $message=$this->getMessageCount(0);
        include $this->template('haojingke/sale/groupsend/index');
	}

    //群发(商品)消息-添加
    public function groupsend_add()
    {
        global $_W;
        global $_GPC;
        $fans_mass = getFansGroupcount();
        include $this->template('haojingke/sale/groupsend/groupsend_add');
    }
    //群发(商品)消息-添加提交
    public function groupsend_addpost()
    {
        global $_W;
        global $_GPC;
        $goods = htmlspecialchars_decode($_GPC['goods']);
//        $goods=json_decode($goods);
        $fans_res = $_GPC['fans'];
        $skuName = json_decode($goods)->skuName;

//        $skuName = json_decode($goods_res);
        $send_res = setGoodsGroupSend($goods,$skuName,$fans_res);
        show_json(1,"操作成功");
    }

    //群发（商品/普通）消息-删除
    public function groupsend_delete()
    {
        global $_W;
        global $_GPC;
        if(!empty($_GPC['id'])){
            //删除发送失败
            if($_GPC['id']=='-1'){
                $j = pdo_delete('qt_sendmsg_rec',array('state'=>'-1','uniacid'=>$_W['uniacid']));
                if ($j>0) {
                    show_json(1,"删除成功");
                }else{
                    show_json(1,"删除失败");
                }
            }
            //删除待发送
            if($_GPC['id']=='-2'){
                $j = pdo_delete('qt_sendmsg_rec',array('state'=>'0','uniacid'=>$_W['uniacid']));
                if ($j>0) {
                    show_json(1,"删除成功");
                }else{
                    show_json(1,"删除失败");
                }
            }
            //删除全部
            if($_GPC['id']=='-3'){
                $j = pdo_delete('qt_sendmsg_rec',array('uniacid'=>$_W['uniacid']));
                if ($j>0) {
                    show_json(1,"删除成功");
                }else{
                    show_json(1,"删除失败");
                }
            }
            $j = pdo_delete('qt_sendmsg_rec',array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
            if ($j>0) {
                show_json(1,"删除成功");
            }else{
                show_json(1,"删除失败");
            }
        }
        show_json(1,"信息错误，此条记录可能已删除，请刷新页面");
    }

    //群发消息-消息模板列表
    public function groupsend_msg_temp_list()
    {
        global $_W;
        global $_GPC;
        $msc_data = pdo_fetchall("SELECT * FROM ".tablename('qt_sendmsg'). " WHERE uniacid=".$_W['uniacid']." order by id desc");

        include $this->template('haojingke/sale/groupsend/groupsend_msg_temp_list');
    }
    //群发消息-消息模板删除
    public function groupsend_msg_temp_delete()
    {
        global $_W;
        global $_GPC;
        if(empty($_GPC['mid']))
            show_json(1,"信息错误，此条提示语可能已删除，请刷新页面");
        $del_mass_msc = pdo_delete('qt_sendmsg',array('id'=>$_GPC['mid']));
        if($del_mass_msc)
            show_json(1,"操作成功");
        else
            show_json(1,"操作失败");
    }
    //群发消息-消息模板添加
    public function groupsend_msg_temp_add()
    {
        global $_W;
        global $_GPC;
        $id=$_GPC['mid'];
        if(!empty($id))
            $data = pdo_fetch("SELECT * FROM ".tablename('qt_sendmsg'). " WHERE uniacid=".$_W['uniacid']." and id = ".$id);

        include $this->template('haojingke/sale/groupsend/groupsend_msg_temp_add');
    }
    //群发消息-消息模板添加/编辑提交
    public function groupsend_msg_temp_addpost()
    {
        global $_W;
        global $_GPC;
        $res['topcolor'] = $_GPC['topcolor'];
        $res['title']    = $_GPC['title'];
        $res['titlecolor'] = $_GPC['titlecolor'];
        $res['taskname'] = $_GPC['taskname'];
        $res['tasknamecolor'] = $_GPC['tasknamecolor'];
        $res['tasktype'] = $_GPC['tasktype'];
        $res['tasktypecolor'] = $_GPC['tasktypecolor'];
        $res['taskresult'] = $_GPC['taskresult'];
        $res['taskresultcolor'] = $_GPC['taskresultcolor'];
        $res['remark'] = $_GPC['remark'];
        $res['remarkcolor'] = $_GPC['remarkcolor'];
        $res['url'] = $_GPC['url'];
        $res['uniacid'] = $_W['uniacid'];
        $res['createtime'] = time();
        if(!empty($_GPC['mid']))
            $send_msg = pdo_update('qt_sendmsg',$res,array('id'=>$_GPC['mid']));
        else
            $send_msg =pdo_insert('qt_sendmsg',$res);

        if($send_msg)
            show_json(1,"操作成功");
        else
            show_json(1,"操作失败");
    }

    //群发消息-普通消息添加
    public function groupsend_msg_add()
    {
        global $_W;
        global $_GPC;
        $msgid=$_GPC["id"];
        $qt_sendmsg_rec=pdo_fetch("select title from ".tablename('qt_sendmsg')." where id=:id",array(':id' => $msgid));
        $title=$qt_sendmsg_rec["title"];
        $qt_operate_formid =  pdo_fetchall('SELECT * , COUNT( DISTINCT openid ) FROM '.tablename('qt_operate_formid').' where uniacid=:uniacid 
             and created_at+7*24*3600>'.time().' GROUP BY openid ORDER BY created_at'
            , array(':uniacid' => $_W['uniacid']));
        foreach ($qt_operate_formid as $item)
        {
            $log['uniacid']=$_W['uniacid'];
            $log['msgid']=$msgid;
            $log['title']=$title;
            $log['openid']=$item['openid'];
            $log['type']=0;
            $log['state']=0;
            $log['createtime']=time();
            $i=pdo_insert("qt_sendmsg_rec",$log);
        }
        show_json(1,"添加成功！");
    }

    //群发普通消息
    public function groupsend_msg_send()
    {
        global $_W;
        global $_GPC;
        $this->starMessageGroupSend();
        $message=$this->getMessageCount(0);
        exit(json_encode($message));
    }

    //群发商品消息
    public function groupsend_goods_send()
    {
        global $_W;
        global $_GPC;
        starGoodsGroupSend();
        $message=getMessageCount(1);
        exit(json_encode($message));
    }

    //输入openid 单发普通模板消息
    public function groupsend_msg_send_single()
    {
        global $_W;
        global $_GPC;

        if(empty($_GPC['openid']))
            show_json(1,"请输入openid");
        if(empty($_GPC['id']))
            show_json(1,"消息模板不存在");
        $openid=$_GPC['openid'];
        $id=$_GPC['id'];
        $msc_data = pdo_fetch("SELECT * FROM ".tablename('qt_sendmsg'). " WHERE uniacid=".$_W['uniacid']." and id = ".$id);
        if(empty($msc_data))
            show_json(1,"消息模板不存在");
        $url=$msc_data['url'];
        $data= array();
        $data['openid']=$openid;
        $data['url']=$msc_data['url'];
        $data['data'] = array(
            'keyword1'=>array('value'=>$msc_data['title'],'color'=>$msc_data['titlecolor']),
            'keyword2'=>array('value'=>$msc_data['taskname'],'color'=>$msc_data['tasknamecolor']),
            'keyword3'=>array('value'=>$msc_data['tasktype'],'color'=>$msc_data['tasktypecolor']),
        );

        $res=m('weixin')->weixin_send_temp_msg($data);
        $res=json_decode($res);
        //m('fc')->fc_log_debug(json_encode($res),'模板消息');
        $log['uniacid']=$_W['uniacid'];
        $log['msgid']=0;
        $log['title']=$msc_data['title'];
        $log['openid']=$openid;
        $log['type']=0;
        $log['createtime']=time();
        if($res->errcode==0){
            //var_dump($res);
            $log['state']=1;
            $i=pdo_insert("qt_sendmsg_rec",$log);
            show_json(1,'发送成功['.$res->errmsg.']！');
        }else{
            $log['state']=-1;
            $i=pdo_insert("qt_sendmsg_rec",$log);
            show_json(1,'发送失败['.$res->errmsg.']！');
        }
    }
    /**
     * 查询消息数量
     * type=0,普通消息；type=1,商品消息
     */
    public function getMessageCount($type){
        global $_W;
        global $_GPC;
        $sql="SELECT (SELECT count(0)  FROM ".tablename("qt_sendmsg_rec")." WHERE uniacid=".$_W['uniacid']." and type=:type and state=0) AS 'waitingcount',
	        (SELECT count(0)  FROM ".tablename("qt_sendmsg_rec")." WHERE uniacid=".$_W['uniacid']." and type=:type and state=1) AS 'successcount',
	        (SELECT count(0)  FROM ".tablename("qt_sendmsg_rec")." WHERE uniacid=".$_W['uniacid']." and type=:type and state=-1) AS 'errorcount'";
        $list=pdo_fetch($sql,array(":type"=>$type));
        return $list;
    }
    /**
     * 开始群发信息，从待发送列表发送
     * type=0,普通消息；type=1,商品消息
     */
    public function starMessageGroupSend(){
        global $_W;
        global $_GPC;
        //每次取待发送消息的第一条
        $message=pdo_fetch("SELECT *  FROM ".tablename("qt_sendmsg_rec")." WHERE uniacid=".$_W['uniacid']." and state=0 order by id");
        if(!empty($message)){
            $openid=$message['openid'];
            $msc_data = pdo_fetch("SELECT * FROM ".tablename('qt_sendmsg'). " WHERE uniacid=".$_W['uniacid']." and id = ".$message["msgid"]);
            $url=$msc_data["url"];
            $member=pdo_fetch('select nickname from '.tablename('qt_operate_member').' where openid=:openid and uniacid=:uniacid 
             ', array(':uniacid' => $_W['uniacid'],':openid' => $openid));

            $data= array();
            $data['openid']=$openid;
            $data['url']=$msc_data['url'];
            $data['data'] = array(
                'keyword1'=>array('value'=>$msc_data['title'],'color'=>$msc_data['titlecolor']),
                'keyword2'=>array('value'=>"亲爱的".$member["nickname"]),
                'keyword3'=>array('value'=>$msc_data['taskname'],'color'=>$msc_data['tasknamecolor']),
            );

            $res=m('weixin')->weixin_send_temp_msg($data);

            if($res->errcode==0){
                //var_dump($res);
                $log['state']=1;
                $i=pdo_update("qt_sendmsg_rec",$log,array("id"=>$message['id']));
                //message('发送成功['.$res->errmsg.']！'.$i, $this->createWebUrl('single_shot'), 'success');
            }else{
                $log['state']=-1;
                $i=pdo_update("qt_sendmsg_rec",$log,array("id"=>$message['id']));
                //message('发送失败['.$res->errmsg.']！', $this->createWebUrl('single_shot'), 'error');
            }
        }
    }
}
?>