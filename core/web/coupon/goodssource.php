<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
class Goodssource_NetsHaojkMywPage extends WebPage
{
    public function main()
    {
        global $_GPC;
        global $_W;

        if ($_W['action']=='coupon.goodssource.index'||$_W['action']=='goodssource')
            $this->index();
        elseif ($_W['action']=='coupon.goodssource.goodssource_add')
            $this->goodssource_add();
        elseif ($_W['action']=='coupon.goodssource.goodssource_addpost')
            $this->goodssource_addpost();
        elseif ($_W['action']=='coupon.goodssource.goodssource_delete')
            $this->goodssource_delete();
        elseif ($_W['action']=='coupon.goodssource.sourcegoods')
            $this->sourcegoods();
        elseif ($_W['action']=='coupon.goodssource.sourcegoods_add')
            $this->sourcegoods_add();
        elseif ($_W['action']=='coupon.goodssource.sourcegoods_save')
            $this->sourcegoods_save();
        elseif ($_W['action']=='coupon.goodssource.sourcegoods_delete')
            $this->sourcegoods_delete();
        elseif ($_W['action']=='coupon.goodssource.sourcegoods_addpost')
            $this->sourcegoods_addpost();
    }

    /***
     * 商品源列表
    ***/
    public function index()
    {
        global $_GPC, $_W;
        $uniacid=$_W['uniacid'];

        $where = " 1=1 and uniacid=".$_W['uniacid']." ";

        if (!empty($_GPC['source_name'])) {
            $where.= " and source_name like '%".$_GPC['source_name']."%'";
        }
        if (!empty($_GPC['source_type'])) {
            $where.= " and source_type = ".$_GPC['source_type'];
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $list = pdo_fetchall("SELECT * FROM ".tablename('qt_operate_diysource')." where ".$where. " ORDER BY id DESC limit ". (($pindex - 1) * $psize) . ',' . $psize);
        $total = pdo_fetchcolumn("select count(0) from ".tablename("qt_operate_diysource")." where".$where);
        $pager = pagination($total, $pindex, $psize);
        include $this->template('haojingke/coupon/goodssource/index');
    }

    /***
     * 商品源-添加/修改
    ***/
    public function goodssource_add()
    {
        global $_W;
        global $_GPC;
        if(!empty($_GPC['id'])){
            $diysource = pdo_fetch("SELECT * FROM ".tablename('qt_operate_diysource')." WHERE uniacid=:uniacid and  id=:id "
                ,array(":uniacid"=>$_W['uniacid'],":id"=>$_GPC['id']));

            $source_query =json_decode($diysource['source_query'],true);
        }

        $pddcate=m('pdd_data')->goods_opt();

        include $this->template('haojingke/coupon/goodssource/goodssource_add');
    }

    /***
     * 商品源-添加/修改提交
    ***/
    public function goodssource_addpost()
    {
        global $_W;
        global $_GPC;
        $source_query = array();
        $sourcedata['source_name'] = $_GPC['source_name'];
        $sourcedata['source_type'] = $_GPC['diysource_type'];
        $sourcedata['source_sort'] = $_GPC['source_sort'];
        $sourcedata['source_pic'] = $_GPC['source_pic'];
        $sourcedata['source_status'] = '1';
        $sourcedata['uniacid']=$_W['uniacid'];

        if($_GPC['diysource_type']=='1'){


            //券后价
            $source_query['minprice']=$_GPC['minprice'];
            $source_query['maxprice']=$_GPC['maxprice'];

            //佣金比例
            $source_query['mincommission']=$_GPC['mincommission'];
            $source_query['maxcommission']=$_GPC['maxcommission'];

            //佣金金额
            $source_query['mincommissionpirce']=$_GPC['mincommissionpirce'];
            $source_query['maxcommissionpirce']=$_GPC['maxcommissionpirce'];

            //优惠券金额
            $source_query['min_coupon_price']=$_GPC['min_coupon_price'];
            $source_query['max_coupon_price']=$_GPC['max_coupon_price'];

            //销量
            $source_query['min_sale']=$_GPC['min_sale'];
            $source_query['max_sale']=$_GPC['max_sale'];

            $source_query['keyword']=$_GPC['keyword'];

            $source_query['cid']=$_GPC['cid'];

            $source_query['sort']=$_GPC['sort'];

            $source_query['sortby']=$_GPC['sortby'];

        }
        $source_query['source_type']=$_GPC['source_type'];

        $sourcedata['source_query'] = json_encode($source_query);
        if (!empty($_GPC['id'])) {
            $sourcedata['updated_at'] = time();
            $res = pdo_update('qt_operate_diysource',$sourcedata,array('id'=>$_GPC['id']));
        }else{
            $sourcedata['created_at'] = time();
            $res = pdo_insert('qt_operate_diysource',$sourcedata);
        }
        if($res)
            show_json(1,"操作成功");
        else
            show_json(1,"操作失败");
    }

    /***
     * 商品源-删除
    ***/
    public function goodssource_delete()
    {
        global $_W;
        global $_GPC;
        $res = pdo_fetch("SELECT * FROM ".tablename('qt_operate_diysource')." WHERE uniacid=:uniacid and  id=:id "
            ,array(":uniacid"=>$_W['uniacid'],":id"=>$_GPC['id']));
        if (!empty($res)) {
            $j = pdo_delete('qt_operate_diysource',array('id'=>$_GPC['id'],"uniacid"=>$_W['uniacid']));
            $r = pdo_delete('qt_operate_diysource_goods',array('source_id'=>$_GPC['id'],"uniacid"=>$_W['uniacid']));
            if($j)
                show_json(1,"操作成功");
            else
                show_json(1,"操作失败");
        }else
            show_json(1,"此商品源不存在");
    }

    /***
     * 商品源-商品列表
    ***/
    public function sourcegoods()
    {
        global $_GPC, $_W;
        $page=empty($_GPC["page"])?1:$_GPC['page'];
        $where = '';
        if (!empty($_GPC['diysource']))
            $where = " and source_id='".$_GPC['diysource']."'";
        $diysource=pdo_fetchall("select * from ".tablename('qt_operate_diysource')." where uniacid=:uniacid and source_type=2"
            ,array(":uniacid"=>$_W['uniacid']));
        $sourcegoods=pdo_fetchall("select * from ".tablename('qt_operate_diysource_goods')." where uniacid=:uniacid and source_id=:source_id"
            ,array(":uniacid"=>$_W['uniacid'],":source_id"=>$_GPC['diysource']));

        $querydata = json_decode($diysource['source_query']);

        $list = [];
        foreach($sourcegoods AS $good){
            $goods_data = json_decode($good['goods_data_json'],1);
            $list[] = $goods_data;
        }
        $total=pdo_fetchcolumn("select COUNT(id) from ".tablename('qt_operate_diysource_goods')." where uniacid=:uniacid and source_id=:source_id"
            ,array(":uniacid"=>$_W['uniacid'],":source_id"=>$_GPC['diysource']));
        $pager = pagination($total, $page, 20);

        include $this->template('haojingke/coupon/goodssource/sourcegoods');
    }

    /***
     * 商品源-商品添加
    ***/
    public function sourcegoods_add()
    {
        global $_W;
        global $_GPC;
        $diysource=pdo_fetchall("select * from ".tablename('qt_operate_diysource')." where uniacid=:uniacid and source_type=2",array(":uniacid"=>$_W['uniacid']));

        include $this->template('haojingke/coupon/goodssource/sourcegoods_add');
    }
    
    function unicodeDecode($unicode_str){
        $json = '{"str":"'.$unicode_str.'"}';
        $arr = json_decode($json,true);
        if(empty($arr)) return '';
        return $arr['str'];
    }


    /***
     * 商品源-商品更新排序
    ***/
    public function sourcegoods_save()
    {
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];




        $r=pdo_update('qt_operate_diysource_goods',array("source_sort"=>$_GPC['source_sort']),array('id'=>$id));
        if($r)
            show_json(1,"操作成功");
        else
            show_json(1,"操作失败");
    }

    /***
     * 商品源-商品删除
    ***/
    public function sourcegoods_delete()
    {
        global $_W;
        global $_GPC;
        $id=$_GPC['id'];
        $r=pdo_delete('qt_operate_diysource_goods',array('id'=>$id,'uniacid'=>$_W['uniacid']));
        if($r)
            show_json(1,"操作成功");
        else
            show_json(1,"操作失败");
    }

    /***
     * 商品源-商品添加提交
    ***/
    public function sourcegoods_addpost()
    {
        global $_W;
        global $_GPC;
        $skuId = $_GPC['skuId'];
        if(empty($_GPC['diysource']))
            show_json(1,"请选择要加入的商品源！");
        if(!empty($skuId)){
            $goods_data_json = $_GPC['goods_data_json'];
            $goods_data_json = htmlspecialchars_decode($goods_data_json);

            $goodsdata["skuId"]=$skuId;
            $goodsdata["uniacid"]=$_W['uniacid'];
            $goodsdata["source_id"]=$_GPC['diysource'];
            $goodsdata["source_sort"]=$_GPC['source_sort'];
            $goodsdata["source_type"]=$_GPC['source_type'];
            $goodsdata["goods_data_json"]=$goods_data_json;

            $goods=pdo_fetch("select * from ".tablename("qt_operate_diysource_goods")
                ." where uniacid=:uniacid and skuId=:skuId and source_id=:source_id"
                ,array(":uniacid"=>$_W['uniacid'],":source_id"=>$_GPC['diysource'],":skuId"=>$skuId));
            if($goods){
                $goodsdata['updated_at'] = time();
                $i=pdo_update("qt_operate_diysource_goods",$goodsdata,array("uniacid"=>$_W['uniacid'],"id"=>$goods['id']));
            }
            else{
                $goodsdata['created_at'] = time();
                $i=pdo_insert("qt_operate_diysource_goods",$goodsdata);
            }
        }
        $msg = "操作成功";
        if($i)
            show_json(1,$msg);
        else
            show_json(1,"操作失败");
    }

}
?>