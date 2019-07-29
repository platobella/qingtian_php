<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/7/30
 * Time: 16:44
 */

if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require_once IA_ROOT . '/addons/qt_shop/core/model/sysset.model.php';
class Memberposter_NetsHaojkMywPage extends WebPage
{
    public function main()
    {
        global $_GPC;
        global $_W;

        if ($_W['action']=='sysset.memberposter.index'||$_W['action']=='sysset.memberposter')
            $this->index();
    }
    public function index()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        $poster="";
        if(!empty($data['memberposter'])){
            $poster = htmlspecialchars_decode($data['memberposter']);
        }
        if ($_W['ispost'] == 1) {
            if(!empty($_GPC["memberposter"])){
                $sysset["memberposter"]=$_GPC["memberposter"];
            }
            $i=0;
            if(!empty($_GPC['id'])){
                m('fc')->fc_cache_delete("qt_operate_sysset");
                $i=pdo_update("qt_operate_sysset",$sysset,array("id"=>$_GPC['id']));
            }else{
                $i=pdo_insert("qt_operate_sysset",$sysset);
            }
            show_json(1, "操作成功");
        }
        include $this->template('haojingke/sysset/memberposter/index');
    }


}
?>