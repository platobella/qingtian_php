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
        if ($_W['action']=='ranking.index'||$_W['action']=='ranking')
            $this->index();
        elseif ($_W['action']=='ranking.index.goodssource_add')
            $this->goodssource_add();
        elseif ($_W['action']=='ranking.index.goodssource_addpost')
            $this->goodssource_addpost();
        elseif ($_W['action']=='ranking.index.goodssource_delete')
            $this->goodssource_delete();
    }

    public function index()
    {
        global $_GPC, $_W;
        $where = " 1=1 ";

        if (!empty($_GPC['search_title'])) {
            $where.= "and title like '%".$_GPC['search_title']."%' ";
        }

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $list = pdo_fetchall("select g.*,c.name from " . tablename("qt_goods")." g left join ".tablename('qt_classification')." c on g.cate_id = c.id where g.uniacid=:uniacid and  ".$where." ORDER BY id DESC limit ".(($pindex - 1) * $psize) . ',' . $psize,[":uniacid"=>$_W['uniacid']]);

        $total = pdo_fetchcolumn("select count(0) from ".tablename("qt_goods")." where uniacid=:uniacid and  ".$where,[':uniacid'=>$_W['uniacid']]);
        $pager = pagination($total, $pindex, $psize);

        include $this->template('haojingke/ranking/index');
    }

    /***
     * 商品分类-添加/修改
     ***/
    public function goodssource_add()
    {
        global $_W;
        global $_GPC;
        if(!empty($_GPC['id'])){
                $diysource = pdo_fetch("SELECT * FROM ".tablename('qt_goods')." WHERE id=:id and is_delete=0"
                    ,array(":id"=>$_GPC['id']));
                $diysource['image'] = unserialize($diysource['image']);

            if($_GPC['type'] == 1){
                include $this->template('haojingke/ranking/preview');
                return;
            }
        }

        $cate_list = pdo_fetchall("select * from ".tablename("qt_classification")." where type=2");

        include $this->template('haojingke/ranking/goodssource_add');
    }

    /***
     * 商品分类-添加/修改提交
     ***/
    public function goodssource_addpost()
    {
        global $_W;
        global $_GPC;

        $source_query = array();

        $sourcedata['title'] = $_GPC['title'];
        $sourcedata['num'] = $_GPC['num'];

        $sourcedata['uniacid'] = $_W['uniacid'];
        $sourcedata['sales'] = $_GPC['sales'];
        $sourcedata['original_price'] = $_GPC['original_price'];
        $sourcedata['price'] = $_GPC['price'];

        $sourcedata['start_time'] = strtotime($_GPC['start_time']);
        $sourcedata['end_time'] = strtotime($_GPC['end_time']);
        for ($i=0;$i<count($_GPC['image']);$i++){
            if($_GPC['image'][$i] == ""){
                unset($_GPC['image'][$i]);
            }
        }
        $sourcedata['image'] = serialize(array_merge($_GPC['image']));
        $sourcedata['addtime'] =time();
        $sourcedata['cate_id'] = $_GPC['cate_id'];

        if (!empty($_GPC['id'])) {
            $res = pdo_update('qt_goods',$sourcedata,array('id'=>$_GPC['id']));
        }else{
            $res = pdo_insert('qt_goods',$sourcedata);
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
        $res = pdo_fetch("SELECT * FROM ".tablename('qt_goods')." WHERE id=:id"
            ,array(":id"=>$_GPC['id']));

        if (!empty($res)) {
//            $j = pdo_update('qt_goods',array('is_delete'=>1),array('id' => $res['id']));
            $j = pdo_delete('qt_goods',array('id' => $res['id']));
            if($j)
                show_json(1,"操作成功");
            else
                show_json(1,"操作失败");
        }else
            show_json(1,"此商品源不存在");
    }

}
?>