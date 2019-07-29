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
        if ($_W['action']=='banner.index'||$_W['action']=='banner')
            $this->index();
        elseif ($_W['action']=='banner.index.goodssource_add')
            $this->goodssource_add();
        elseif ($_W['action']=='banner.index.goodssource_addpost')
            $this->goodssource_addpost();
        elseif ($_W['action']=='banner.index.goodssource_delete')
            $this->goodssource_delete();
    }

    public function index()
    {
        global $_GPC, $_W;
        $where = " is_delete=0 ";

        if (!empty($_GPC['type'])) {
            $where.= " and type = ".$_GPC['type'];
            $type = $_GPC['type'];
        }

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $list = pdo_fetchall("select * from " . tablename("qt_banner")." where ".$where." ORDER BY id DESC limit ".(($pindex - 1) * $psize) . ',' . $psize);

        $total = pdo_fetchcolumn("select count(0) from ".tablename("qt_banner")." where ".$where);
        $pager = pagination($total, $pindex, $psize);

        include $this->template('haojingke/banner/index');
    }

    /***
     * 商品分类-添加/修改
     ***/
    public function goodssource_add()
    {
        global $_W;
        global $_GPC;
        if(!empty($_GPC['id'])){
            $diysource = pdo_fetch("SELECT * FROM ".tablename('qt_banner')." WHERE id=:id and is_delete=0"
                ,array(":id"=>$_GPC['id']));
        }
        $path_url = pdo_fetchall("select id,name from ".tablename("qt_path"));

        include $this->template('haojingke/banner/goodssource_add');
    }

    /***
     * 商品分类-添加/修改提交
     ***/
    public function goodssource_addpost()
    {
        global $_W;
        global $_GPC;

        $sourcedata['title'] = $_GPC['source_title'];
        $sourcedata['page_url'] = $_GPC['page_url'];
        $sourcedata['sort'] = $_GPC['source_sort']?$_GPC['source_sort']:0;
        $sourcedata['pic_url'] =$_GPC['source_pic_url'];
        $sourcedata['open_type'] =$_GPC['open_type'];
        $sourcedata['addtime'] =time();
        if (!empty($_GPC['id'])) {
            $res = pdo_update('qt_banner',$sourcedata,array('id'=>$_GPC['id']));
        }else{
            $res = pdo_insert('qt_banner',$sourcedata);
        }
        if($res)
            show_json(1,"操作成功");
        else
            show_json(1,"操作失败");
    }

    /***
     * 商品分类-删除
     ***/
    public function goodssource_delete()
    {
        global $_W;
        global $_GPC;
        $res = pdo_fetch("SELECT * FROM ".tablename('qt_banner')." WHERE id=:id and is_delete=0"
            ,array(":id"=>$_GPC['id']));

        if (!empty($res)) {
            $j = pdo_update('qt_banner',array('is_delete'=>1),array('id' => $res['id']));
            if($j)
                show_json(1,"操作成功");
            else
                show_json(1,"操作失败");
        }else
            show_json(1,"此商品源不存在");
    }

}
?>