<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/7/12
 * Time: 9:50
 */

if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require_once IA_ROOT . '/addons/qt_shop/core/model/vipremark.model.php';
class Index_NetsHaojkMywPage extends WebPage
{
    public function main()
    {
        global $_GPC;
        global $_W;

        if ($_W['action'] == 'vipremark.index' || $_W['action'] == 'vipremark')
            $this->index();
        if ($_W['action'] == 'vipremark.index.contentedit')
            $this->contentedit();
        if ($_W['action'] == 'vipremark.index.delete')
            $this->delete();
    }

    //内容列表
    public function index()
    {
        global $_GPC, $_W;
        $data = vipremark_getall();
        include $this->template('haojingke/vipremark/index');
    }
    //内容删除
    public function delete()
    {
        global $_GPC, $_W;
        $data = vipremark_delete();
        if ($data) {
            show_json(1,"删除成功");
        }else{
            show_json(0,"删除失败");
        }

    }
    //内容编辑
    public function contentedit()
    {
        global $_GPC, $_W;
        $data = vipremark_get();
        if ($_W['ispost'] == 1) {
            $res = vipremark_save();
            if ($res)
                show_json(1,"操作成功");
        }
        include $this->template('haojingke/vipremark/contentedit');
    }
}