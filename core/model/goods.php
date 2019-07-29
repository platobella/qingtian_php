<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Goods_NetsHaojkModel
{

    public function goods_search(){
        global $_W;
        global $_GPC;

        $page = empty($_GPC["page"]) ? 1 : $_GPC["page"];
        $pagesize = empty($_GPC["pagesize"]) ? 5 : $_GPC["pagesize"];

        $where = "";
        if(!empty($_GPC["keyword"])){
            $where .=" and title like '%{$_GPC["keyword"]}%'";
        }
        if(!empty($_GPC["cid"])){
            $where .=" and cate_id=".$_GPC['cid'];
        }

        $sql = "select * from ".tablename("qt_goods")." where uniacid=:uniacid and is_delete=0 {$where} limit " . ($page - 1) * $pagesize . "," . $pagesize;
        $goods_list = pdo_fetchall("select * from ".tablename("qt_goods")." where uniacid=:uniacid and is_delete=0 {$where} limit " . ($page - 1) * $pagesize . "," . $pagesize,[":uniacid"=>$_W['uniacid']]);

        $list = [];
        foreach ($goods_list as $key =>$val){
            $list[] = $this->pdd_list_field_convert($val);
        }
        $cate_list = pdo_fetchall("select id,name from ".tablename("qt_classification")." where type=2",[]);

        foreach ($cate_list as $key =>$val){
            foreach ($goods_list as $key2 => $good){
                if($val['id'] == $good['cate_id']){
                    $cate_list[$key]['goods_list'][] =  $good;
                }
            }
        }

        $cart_count= pdo_fetchcolumn("select count(*) from ".tablename("qt_goods_cart")." where status=0 and user_id=:user_id",[":user_id"=>$_GPC['member']['id']]);

        return ['data'=>$list,"cate_list"=>$cate_list,"cart_count"=>$cart_count];
    }

    //订单列表
    public function get_goods_order_list(){

        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $page_size = 20;
        $page = empty($_GPC["page"]) ? 1: $_GPC["page"];
        $offset = $page_size * ($page -1);

        $list = pdo_fetchall('select o.id,o.addtime,o.status,o.express_name,o.express_no,g.title,g.image,g.original_price,g.price from '.tablename('qt_goods_order').' o left join '.tablename('qt_goods')." g on o.goods_id=g.id where uid=:uid order by o.id desc limit {$offset},{$page_size}",[":uid"=>$uid]);

        foreach ($list as $key =>$val){
            $list[$key]['addtime'] = date('Y-m-d',$val['addtime']);
            $image_list = unserialize($val['image']);
            $list[$key]['image'] = $image_list[0];
        }

        m('fc')->fc_result(0, '成功',$list);

    }

    public function add_cart(){

        global $_W;
        global $_GPC;
        $member = $_GPC['member'];

        pdo_insert("qt_goods_cart",['user_id'=>$member['id'],'goods_id'=>$_GPC['goods_id'],'addtime'=>time()]);

        return result_data(0,"添加成功");

    }

    public function cart_list(){
        global $_W;
        global $_GPC;
        $member = $_GPC['member'];

        $cart_list = pdo_fetchall("select c.id,g.id as goods_id , g.title,g.price,g.image,g.num,g.sales from ".tablename("qt_goods_cart")." c join ".tablename('qt_goods')." g on c.goods_id=g.id  where status=0 and user_id=:user_id",[':user_id'=>$member['id']]);

        $total_price = 0;
        foreach ($cart_list as $key => $val){
            $total_price += $val['price'];
            $images = unserialize($val['image']);
            $cart_list[$key]['image'] = $images[0];
        }

        return result_data(0,"查询成功",["total_price"=>$total_price,'cart_list'=>$cart_list]);
    }

    public function del_cart(){

        global $_W;
        global $_GPC;

        pdo_delete("qt_goods_cart",['id'=>$_GPC['id']]);

        return result_data(0,"删除成功");

    }

    public function pay(){
        global $_W;
        global $_GPC;
//        $global = m("fc")->fc_getglobal();
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        $skuids = $_GPC['skuid'];
//        $skuids = explode(',',$skuids);
//        $goods = pdo_fetch("select * from ".tablename("qt_goods")." where id=:skuid and uniacid=:uniacid and is_delete=0",[":uniacid"=>$_W['uniacid'],":skuid"=>$_GPC['skuid']]);
        if(empty($skuids)){
            $user_id = $_GPC['member']['id'];
            $goods_list = pdo_fetchall("select g.* from ".tablename("qt_goods_cart")." c join ".tablename("qt_goods")." g on c.goods_id = g.id where c.user_id=:user_id and c.status=0",[':user_id'=>$user_id ]);
        }else{
            $goods_list = pdo_fetchall("select * from ".tablename("qt_goods")." where id = :skuid and uniacid=:uniacid and is_delete=0",[":uniacid"=>$_W['uniacid'],":skuid"=>$skuids]);
        }

        $rand_num = rand(1000,9999);
        $date = date("YmdHis");
        $order_no = $date.$rand_num;


        $total_price = 0;
        foreach ($goods_list as $key => $val){
            $total_price = $total_price + $val['price'];
        }

        $name = $_GPC['name'];
        $address = $_GPC['address'];
        $mobile = $_GPC['mobile'];
        if((empty($name) || empty($name) || empty($name)) && empty($skuids)){
            return result_data(1,'不能为空');
        }

        pdo_insert("qt_goods_order",[
            'uniacid'=>$_W['uniacid'],
            'goods_id'=>$_GPC['skuid'],
            'uid'=>$uid,
            'status'=>0,
            'addtime'=>time(),
            'order_no'=>$order_no,
            "name"=>$name,
            "address"=>$address,
            "mobile"=>$mobile
        ]);

        return array("tid" => $order_no, "user" => $_W["openid"], "fee" => $total_price, "title" => "精选商品购买");

    }

    //领取
    public function receive_invite(){

        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        $order_id = $_GPC['order_id'];
        $name = $_GPC['name'];
        $address = $_GPC['address'];
        $mobile = $_GPC['mobile'];

        $res = pdo_update('qt_goods_order',["status"=>2,"name"=>$name,"address"=>$address,"mobile"=>$mobile],["id"=>$_GPC['order_id']]);

        if($res){
            m('fc')->fc_result(0, '成功','');
        }else{
            m('fc')->fc_result(2, '失败','');
        }
    }


    public function goods_detail(){
        global $_W;
        global $_GPC;

        $goods = pdo_fetch("select * from ".tablename("qt_goods")." where id=:skuid and uniacid=:uniacid and is_delete=0",[":uniacid"=>$_W['uniacid'],":skuid"=>$_GPC['skuid']]);

        $goods = $this->pdd_list_field_convert($goods);

        return ['data'=>$goods];
    }
    //拼多多商品列表字段转换
    function pdd_list_field_convert($pdd_data){

        $image_list = unserialize($pdd_data['image']);
        $goods = [];
        $goods['source_type'] = 5;
        $goods['skuId'] = $pdd_data['id'];
        $goods['skuName'] = $pdd_data['title'];
        $goods['skuDesc'] = $pdd_data['title'];
        $goods['picUrl'] = $image_list[0];
        $goods['wlPrice'] = $pdd_data['original_price'];
        $goods['wlCommissionShare'] = 0;
        $goods['discount'] = 0;
        $goods['wlPrice_after'] = $pdd_data['price'];
        $goods['quota'] = '';
        $goods['cid'] = 0;
        $goods['wlCommission'] = 0;
        $goods['sales'] = $pdd_data['sales'];
        $goods['detail'] = $image_list;
        return $goods;
    }


}