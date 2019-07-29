<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require_once IA_ROOT . '/addons/qt_shop/core/model/sysset.model.php';
require_once IA_ROOT . '/addons/qt_shop/core/model/apicloud.php';
class Usekeyword_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{
        global $_GPC;
        global $_W;

		if ($_W['action']=='sysset.usekeyword.index'||$_W['action']=='sysset.usekeyword')
            $this->index();
        elseif ($_W['action']=='sysset.usekeyword.add')
            $this->usekeyword_add();
        elseif ($_W['action']=='sysset.usekeyword.edit')
            $this->usekeyword_edit();
        elseif ($_W['action']=='sysset.usekeyword.start')
            $this->usekeyword_start();
        elseif ($_W['action']=='sysset.usekeyword.stop')
            $this->usekeyword_stop();
        elseif ($_W['action']=='sysset.usekeyword.delete')
            $this->usekeyword_delete();
	}
	public function index()
	{
        global $_GPC, $_W;
        $r = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/usekeyword/index'), 'success');
        }
        $data = pdo_fetchall("SELECT * FROM " .tablename('qt_operate_keyword'). " WHERE uniacid =:uniacid order by orderby desc",array(':uniacid'=>$_W['uniacid']));
        include $this->template('haojingke/sysset/usekeyword');
	}
    public function usekeyword_edit()
	{
		global $_W;
		global $_GPC;
        $uniacid=$_W['uniacid'];
        $result = '';
        $data=array();
        if(!empty($_GPC["id"])){
            $data = pdo_fetch("SELECT * FROM " .tablename('qt_operate_keyword'). " WHERE uniacid =:uniacid and id=:id",array(':uniacid'=>$_W['uniacid'],':id'=>$_GPC['id']));
        }
        if ($_W['ispost']==1) {
            $d['uniacid']=$_W["uniacid"];
            $d["label"]=$_GPC["label"];
            $d["keyword"]=$_GPC["keyword"];
            $d["title"]=$_GPC["title"];
            if ($data['picture'] == $_GPC['picture']) {
                $d['picture'] = $_GPC['picture'];
            }else{
                $d['picture'] = $_W['attachurl'].$_GPC['picture'];
            }
            $d['remark']=$_GPC['remark'];
            $d['content']=$_GPC['content'];
            $d['orderby']=$_GPC['orderby'];
            $d['state']=1;
            $d['created_at']=time();
            if(!empty($_GPC["id"])){
                $res=pdo_update("qt_operate_keyword",$d,array("id"=>$_GPC['id']));
            }else{
                $res=pdo_insert("qt_operate_keyword",$d);
            }
            if($res)
                show_message('操作成功！',webUrl('sysset/usekeyword/index'), 'success');
            else
                show_message('操作失败！',webUrl('sysset/usekeyword/index'), 'warning');
        }
        include $this->template('haojingke/sysset/usekeyword/post');
	}
    public function usekeyword_start()
	{
		global $_W;
		global $_GPC;
        $uniacid=$_W['uniacid'];
        $result = '';
        $data=array();
        if(!empty($_GPC["id"])){
            $d['state']=1;
            $d['created_at']=time();
            $res=pdo_update("qt_operate_keyword",$d,array("id"=>$_GPC['id']));
        }
        if($res)
            show_json(1,'操作成功！');
        else
            show_json(0,'操作失败！');

        include $this->template('haojingke/sysset/usekeyword/edit');
    }
    public function usekeyword_stop()
	{
		global $_W;
		global $_GPC;
        $uniacid=$_W['uniacid'];
        $result = '';
        $data=array();
        if(!empty($_GPC["id"])){
            $d['state']=0;
            $d['created_at']=time();
            $res=pdo_update("qt_operate_keyword",$d,array("id"=>$_GPC['id']));
        }
        if($res)
            show_json(1,'操作成功！');
        else
            show_json(0,'操作失败！');

        include $this->template('haojingke/sysset/usekeyword/edit');
    }
    public function usekeyword_delete()
	{
		global $_W;
		global $_GPC;
        $uniacid=$_W['uniacid'];
        $result = '';
        $data=array();
        if(!empty($_GPC["id"])){
            $res=pdo_delete("qt_operate_keyword",array("id"=>$_GPC['id']));
        }
        if($res)
            show_json(1,'操作成功！');
        else
            show_json(0,'操作失败！');
        include $this->template('haojingke/sysset/usekeyword/edit');
	}


}
?>