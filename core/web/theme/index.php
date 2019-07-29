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
//       var_dump($_W['action']);
        if ($_W['action']=='theme.index'||$_W['action']=='theme')
            $this->index();
        elseif ($_W['action']=='theme.index.img_show')
            $this->img_show();
        elseif ($_W['action']=='theme.index.goodssource_add')
            $this->goodssource_add();
    }

    public function index()
    {
        global $_GPC, $_W;
        $psize = 20;
        $pindex = max(1, intval($_GPC['page']));
        $theme_id = '';
        if($_GPC['theme_id']){
            $theme_id = $_GPC['theme_id'];
        }

        $theme = m('pdd_data')->goods_theme_get($theme_id , $pindex,$psize);
        $total = $theme['total'];
        $list = $theme['theme_list'];
        $pager = pagination($total, $pindex, $psize);

        include $this->template('haojingke/theme/index');
    }

    /***
     * 查看图片
     ***/
    public function img_show()
    {
        global $_W;
        global $_GPC;

        $img = $_GPC['img'];

        include $this->template('haojingke/theme/img_show');
    }

    /***
     * 添加
     ***/
    public function goodssource_add()
    {
        global $_W;
        global $_GPC;

        $theme_id = $_GPC['theme_id'];

        $diysource['title'] = $_GPC['title']?$_GPC['title']:'';
        $diysource['pic_url'] = $_GPC['pic_url']?$_GPC['pic_url']:'';
        $diysource['page_url'] = $_GPC['theme_id']? '/pages/classification-detail/index?theme_id='.$_GPC['theme_id'] : '';
        $diysource['open_type'] = 'navigate';
        $path_url = pdo_fetchall("select id,name from ".tablename("qt_path"));

        include $this->template('haojingke/banner/goodssource_add');
    }

}
?>