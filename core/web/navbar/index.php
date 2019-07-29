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

		if ($_W['action']=='navbar.index'||$_W['action']=='navbar')
            $this->index();
        elseif ($_W['action']=='navbar.index.goodssource_add')
            $this->goodssource_add();
        elseif ($_W['action']=='navbar.index.navbar_delete')
            $this->navbar_delete();

	}
	public function index()
	{
        global $_GPC, $_W;

        $list = pdo_fetchall("SELECT * FROM " .tablename('qt_navbar'). " WHERE uniacid =:uniacid",array(':uniacid'=>$_W['uniacid']));

        include $this->template('haojingke/navbar/index');
	}
    public function goodssource_add()
	{
		global $_W;
		global $_GPC;
        $navbar=array();
        if(!empty($_GPC["id"])){
            $navbar = pdo_fetch("SELECT * FROM " .tablename('qt_navbar'). " WHERE uniacid =:uniacid and id=:id",array(':uniacid'=>$_W['uniacid'],':id'=>$_GPC['id']));
        }
        if ($_W['ispost']==1) {
            $data['uniacid']=$_W["uniacid"];
            $data['icon_url']=$_GPC["icon_url"];
            $data['title']=$_GPC["title"];
            $data['position']=$_GPC["position"];
            $data['path']=$_GPC["path"];
            $data['addtime']=time();

            if($_GPC["type"] == 'web_url'){
                $data['type']='web';
                $params = [
                    'web_url' =>$_GPC['web_url']
                ];
            }else if($_GPC["type"] == 'mobile'){
                $data['type']='contact';
                $params = [
                    'mobile' =>$_GPC['mobile']
                ];
            }else if($_GPC["type"] == 'service'){
                $data['type']='service';
                $params = [
                    'service' =>$_GPC['service']
                ];
            }else if($_GPC["type"] == 'theme_id'){
                $data['type']='navigate_theme';
                $params = [
                    'theme_id' =>$_GPC['theme_id']
                ];
            }else if($_GPC["type"] == 'diysource_id'){
                $data['type']='navigate_diysource';
                $params = [
                    'diysource_id' =>$_GPC['diysource_id']
                ];
            }
            $data['params'] = json_encode($params);


            if(!empty($_GPC["id"])){
                $res=pdo_update("qt_navbar",$data,array("id"=>$_GPC['id']));
            }else{
                $res=pdo_insert("qt_navbar",$data);
            }
            if($res)
//                show_message('操作成功！',webUrl('navbar/index'), 'success');
                show_json(1,"操作成功");
            else
                show_json(1,"操作失败");
//                show_message('操作失败！',webUrl('navbar/index'), 'warning');
        }
        include $this->template('haojingke/navbar/goodssource_add');
	}

    public function navbar_delete(){

        global $_W;
        global $_GPC;

        $res = pdo_fetch("SELECT * FROM ".tablename('qt_navbar')." WHERE id=:id"
            ,array(":id"=>$_GPC['id']));

        if (!empty($res)) {
            $res = pdo_delete('qt_navbar',array('id' => $res['id']));
//            $j = pdo_update('qt_navbar',array('is_delete'=>1),array('id' => $res['id']));
            if($res)
                show_json(1,"操作成功");
            else
                show_json(1,"操作失败");
        }else
            show_json(1,"此商品源不存在");

    }



}
?>