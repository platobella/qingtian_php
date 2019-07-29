<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require_once IA_ROOT . '/addons/qt_shop/core/model/sysset.model.php';
class Initialize_login_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{
        global $_GPC;
        global $_W;

		if ($_W['action']=='sysset.initialize_login')
            $this->index();
	}
	public function index()
	{
        global $_GPC, $_W;

        if(file_exists(IA_ROOT.'/addons/qt_shop/diy_login/backup/login.ctrl.php')){
            $file_exists = true;
        }else{
            $file_exists = false;
        }

        if ($_W['ispost'] == 1) {

            if($file_exists){

                $source = IA_ROOT.'/addons/qt_shop/diy_login/backup/login.ctrl.php';
                $dest  = IA_ROOT.'/web/source/user/login.ctrl.php';
                $ret = copy($source,$dest);
                if(!$ret){
                    show_json(2, "操作失败");
                }

                $dest  = IA_ROOT.'/addons/qt_shop/diy_login/backup/login.ctrl.php';
                $ret = unlink($dest);
                if(!$ret){
                    show_json(3, "操作失败");
                }

            }else{
                //备份
                $source = IA_ROOT.'/web/source/user/login.ctrl.php';
                $dest  = IA_ROOT.'/addons/qt_shop/diy_login/backup/login.ctrl.php';
                $ret = copy($source,$dest);
                if(!$ret){
                    show_json(2, "操作失败");
                }

                //把DIY登录页替换上去
                $source = IA_ROOT.'/addons/qt_shop/diy_login/login.ctrl.php';
                $dest  = IA_ROOT.'/web/source/user/login.ctrl.php';
                $ret = copy($source,$dest);
                if(!$ret){
                    show_json(3, "操作失败");
                }

                //资源文件
                $source  = IA_ROOT.'/addons/qt_shop/diy_login/assets';
                $dest = IA_ROOT.'/web/assets';
                copydir($source,$dest);

                //页面
                $source  = IA_ROOT.'/addons/qt_shop/diy_login/qt_login.html';
                $dest = IA_ROOT.'/web/themes/default/user/qt_login.html';
                $ret = copy($source,$dest);
                if(!$ret){
                    show_json(4, "操作失败");
                }

            }


            show_json(1, "操作成功");
        }


        include $this->template('haojingke/sysset/initialize_login');
	}

}
?>