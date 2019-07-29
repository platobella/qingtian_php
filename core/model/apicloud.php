<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Apicloud_NetsHaojkModel
{
	public function api_listall()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global)) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}

		if(empty($_GPC["cid"]) && empty($_GPC["diysourceid"]) &&  empty($_GPC["keyword"])){
            //查询默认的推荐商品
            $homepage_itemjson = json_decode($global['homepage_itemjson'],1);
            foreach ($homepage_itemjson as $key => $navbar){
                if($navbar['id']== "category"){
                    if(!empty($navbar['list']['list'])){
                        $_GPC["diysourceid"] = $navbar['list']['list'][0]['picture_url'];
                        $_GPC["source_type"] = $navbar['list']['list'][0]['url'];
                    }
                }
            }
        }



        //自定义数据源
        if (!empty($_GPC["diysourceid"])) {
            $this->_reset_gpc($_GPC["diysourceid"]);
        }

        $data = $this->build_goods_params();

//        $data = [];
//        if(!empty($_GPC["page"])){
//            $data['page'] = $_GPC["page"];
//        }
//        if(!empty($_GPC["keyword"])){
//            $data['keyword'] =  $_GPC["keyword"];
//        }

        if(!empty($_GPC["cid"])){

            $cate = pdo_get("qt_classification",["type"=>1,"id"=>$_GPC["cid"]]);
            if($_GPC["source_type"] == 4){
                $_GPC['cid'] =  $cate["tb_cid"];
            }else if($_GPC["source_type"] == 1){
                $_GPC['cid'] =  $cate["jd_cid"];
            }else{
                $data['opt_id'] =  $cate["pdd_cid"];
            }

        }
        if(!empty($_GPC["skuids"])){

            //只有一页
            if($_GPC["page"]>1){
                $res['data'] = [];
            }else{
                $sourcegoods=pdo_fetchall("select * from ".tablename('qt_operate_diysource_goods')." where uniacid=:uniacid and source_id=:source_id"
                    ,array(":uniacid"=>$_W['uniacid'],":source_id"=>$_GPC["diysourceid"]));
                $list = [];
                foreach($sourcegoods AS $good){
                    $goods_data = json_decode($good['goods_data_json'],1);
                    $list[] = $goods_data;
                }
                $res['data'] = $list;
            }


        }else if($_GPC["source_type"] == 4){
//            $res = m("taobao_data")->da_taoke_goods_search(1,$global);
            $res = m("taobao_data")->goods_search($global);
        }else if($_GPC["source_type"] == 1){
            $res = m("jd_data")->goods_search($global);
        }else{
            $res = m("pdd_data")->goods_search($data,$global);
        }

        //淘宝接口


        $res['data'] = $this->commissionListConvert($res['data'],$global);

		if($res){

		    //第一次加载时查询分类和导航等
		    if(empty($_GPC["page"]) || $_GPC["page"] == 1){

                $res["homepage_itemjson"] = json_decode($global['homepage_itemjson'],1);

                foreach ($res["homepage_itemjson"] as $key => $navbar){
                    if($navbar['isshow']== "false"){
                        unset($res["homepage_itemjson"][$key]);
                    }
                }

                $res['classification'] = pdo_fetchall("select id,name,pdd_cid,jd_cid,tb_cid from " . tablename("qt_classification")." where type=1 and parent_id=0 and is_delete=0 and uniacid=:uniacid ORDER BY sort DESC",['uniacid'=>$_W['uniacid']]);

                $navbar_list = pdo_fetchall("select * from " . tablename("qt_navbar")." where position=0 ORDER BY sort DESC,id desc");
                foreach ($navbar_list as $key =>$val){
                    $navbar_list[$key]['icon_url'] = to_attach_image($val['icon_url']);
                    $navbar_list[$key]['params'] = json_decode($val['params'],1);
                }
                $res['navbar_list'] =$navbar_list;

                $str = pdo_fetchall("select id,pic_url,page_url,open_type from " . tablename("qt_banner")." where is_delete=0 and type=1 ORDER BY sort DESC");

                $res['banner'] = $str;

                $res['pdd_switch'] = $global['pdd_switch'];
                $res['jd_switch'] = $global['jd_switch'];
                $res['taobao_switch'] = $global['taobao_switch'];
                $res['is_open_auditing'] = $global['is_open_auditing'];
                $res['auditing_version'] = $global['auditing_version'];
                $res['help_keyword'] = $global['help_keyword'];

            }


            return result_data(0, '成功',$res);
//            m('fc')->fc_result(0, '成功',$res);
        }else{
		    return result_data(1, '失败','');
//            m('fc')->fc_result(1, '失败','');
        }
	}

	//佣金转换
	public function commissionListConvert($list , $set = false){
        global $_GPC,$_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $user = m('member')->member_getbyuid($uid);
	    if(!$set){
            $set = m("fc")->fc_getglobal();
        }
        foreach ($list as &$goods){
            if($user['vip_level'] == 1){
                $goods['wlCommission'] = number_format($goods['wlCommission'] * $set['first_sidy'] / 100,2);
            }else{
                $goods['wlCommission'] = 0;
            }
        }
        return $list;
    }
    public function commissionConvert($goods , $set = false){
        global $_GPC,$_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $user = m('member')->member_getbyuid($uid);
        if(!$set){
            $set = m("fc")->fc_getglobal();
        }

        if($user['vip_level'] == 1){
            $goods['wlCommission'] = number_format($goods['wlCommission'] * $set['first_sidy'] / 100,2);
        }else{
            $goods['wlCommission'] = 0;
        }

        return $goods;
    }

	//商品列表模型
    public function goods_listall()
    {
        global $_W;
        global $_GPC;
        load()->func("communication");
        $global = m("fc")->fc_getglobal();
        if (empty($global)) {
            return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
        }

        $data = [];

        if(!empty($_GPC["page"])){
            $data['page'] = $_GPC["page"];
        }
        if(!empty($_GPC["keyword"])){
            $data['keyword'] =  $_GPC["keyword"];
        }
        if(!empty($_GPC["cid"])){
            $data['opt_id'] =  $_GPC["cid"];
        }

        $res = m("pdd_data")->goods_search($data,$global);
        //淘宝接口
//        $res = m("taobao_data")->da_taoke_goods_search(1,$global);
        if($res){

            return $res;
        }else{
            return [];
        }
    }

    /**
     * @param bool $total_commission  是否显示总佣金
     * @return array|void
     */
    //商品列表接口
	public function get_goods_list($total_commission = false){
        global $_W;
        global $_GPC;
        load()->func("communication");
        $global = m("fc")->fc_getglobal();
        if (empty($global)) {
            return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
        }

        //自定义数据源
        if (!empty($_GPC["diysourceid"])) {
            $this->_reset_gpc($_GPC["diysourceid"]);
        }
        //精选
        if($_GPC["source_type"] == 5 && $global['self_source_id'] != 0){
            $this->_reset_gpc($global['self_source_id']);
        }

        $data = $this->build_goods_params();

        if(!empty($_GPC["cid"])){
            $cate = pdo_get("qt_classification",["id"=>$_GPC["cid"]]);
            if($_GPC["source_type"] == 4){
                $_GPC['cid'] =  $cate["tb_cid"];
            }else if($_GPC["source_type"] == 1){
                $_GPC['cid'] =  $cate["jd_cid"];
            }else{
                $data['opt_id'] =  $cate["pdd_cid"];
            }
        }

        if(!empty($_GPC["skuids"])){

            $sourcegoods=pdo_fetchall("select * from ".tablename('qt_operate_diysource_goods')." where uniacid=:uniacid and source_id=:source_id"
                ,array(":uniacid"=>$_W['uniacid'],":source_id"=>$_GPC["diysourceid"]));
            $list = [];
            foreach($sourcegoods AS $good){
                $goods_data = json_decode($good['goods_data_json'],1);
                $list[] = $goods_data;
            }
            $res['data'] = $list;

        }else if($_GPC["source_type"] == 4){
            $res = m("taobao_data")->goods_search($global);
        }else if($_GPC["source_type"] == 1){
            $res = m("jd_data")->goods_search($global);
        }else if($_GPC["source_type"] == 5){
            $res = m("goods")->goods_search($global);
        }else{
            //拼多多
            if(!empty($_GPC["theme_id"])){
                $pindex = max(1, intval($_GPC['page']));
                $res = m('pdd_data')->goods_theme_get($_GPC['theme_id'],$pindex);
            }else{
                $res = m("pdd_data")->goods_search($data,$global);
            }
        }

        if(!$total_commission){
            $res['data'] = $this->commissionListConvert($res['data'],$global);
        }


        if($res){
            return result_data(0, '成功',$res);
//            m('fc')->fc_result(0, '成功',$res);
        }else{
//            m('fc')->fc_result(1, '失败','');
            return result_data(1, '失败','');
        }
    }


    //构建商品列表查询参数
    public function build_goods_params(){
        global $_GPC;
	    $data = [];
        if(!empty($_GPC["page"])){
            $data['page'] = $_GPC["page"];
        }
        if(!empty($_GPC["keyword"])){
            $data['keyword'] =  $_GPC["keyword"];
        }
        if(!empty($_GPC["cid"])){
            $data['opt_id'] =  $_GPC["cid"];
        }

        $range_list = [];
        //券后价筛选
        if(!empty($_GPC["maxprice"]) || !empty($_GPC["minprice"])){
            $range_list[] =
                ["range_from"=>floatval($_GPC["minprice"]) * 100,
                    "range_to"=>floatval($_GPC["maxprice"]) * 100,
                    "range_id"=>1];
        }

        //佣金比率筛选
        if(!empty($_GPC["mincommission"]) || !empty($_GPC["maxcommission"])){
            $range_list[] =
                ["range_from"=>floatval($_GPC["mincommission"]) * 10,
                    "range_to"=>floatval($_GPC["maxcommission"]) * 10,
                    "range_id"=>2];
        }

        //佣金金额筛选
        if(!empty($_GPC["mincommissionpirce"]) || !empty($_GPC["maxcommissionpirce"])){
            $range_list[] =
                ["range_from"=>floatval($_GPC["mincommissionpirce"]) * 100,
                    "range_to"=>floatval($_GPC["maxcommissionpirce"]) * 100,
                    "range_id"=>6];
        }

        //优惠券金额区间
        if(!empty($_GPC["min_coupon_price"]) || !empty($_GPC["max_coupon_price"])){
            $range_list[] =
                ["range_from"=>floatval($_GPC["min_coupon_price"]) * 100,
                    "range_to"=>floatval($_GPC["max_coupon_price"]) * 100,
                    "range_id"=>3];
        }

        //销量区间
        if(!empty($_GPC["min_sale"]) || !empty($_GPC["max_sale"])){
            $range_list[] =
                ["range_from"=>$_GPC["min_sale"],
                    "range_to"=>$_GPC["max_sale"],
                    "range_id"=>5];
        }

        $data['range_list'] = json_encode($range_list);

        return $data;

    }

    public function webview(){
        global $_W,$_GPC;
        $data = pdo_fetch("SELECT * FROM " .tablename('qt_operate_keyword'). " WHERE uniacid =:uniacid and id=:id",array(':uniacid'=>$_W['uniacid'],':id'=>$_GPC['id']));
        $data["content"]=html_entity_decode($data["content"]);

        return result_data(0, '成功',$data);
    }
    //获取全局变量
    public function get_global(){

        $global = m("fc")->fc_getglobal();

        $data = [];
        $data['pdd_switch'] = $global['pdd_switch'];
        $data['jd_switch'] = $global['jd_switch'];
        $data['taobao_switch'] = $global['taobao_switch'];
        $data['memberposter'] = $global['memberposter'];
        $data['is_open_auditing'] = $global['is_open_auditing'];
        $data['auditing_version'] = $global['auditing_version'];
        $data['help_keyword'] = $global['help_keyword'];
        $data['isopenpartner'] = $global['isopenpartner'];

        m('fc')->fc_result(0, '成功', ["global"=>$data],1);
    }


	function api_getwxapp_url()
    {
        global $_W;
        global $_GPC;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        load()->func("communication");
        $global = m("fc")->fc_getglobal();
        if (empty($global) && $global["hjk_apikey"]) {
            return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
        }

        $info = m("member")->member_getbyuid($uid, true);
        if ($info && empty($info["avatar"])) {
            if (intval($info["from_id"]) > 0) {
                $info = m("member")->member_getbyuid($info["from_id"], true);
                if (!empty($info)) {
                    $uid = $info["uid"];
                }
            }
        }
        if (empty($uid)) {
            $uid = 1;
        }

        $custom_parameters =  "{$uid},{$_W['uniacid']}";

        $res = m("pdd_data")->generate_wxapp_url( $_GPC["skuid"],$custom_parameters,$global);

//        $res['global'] = $global;
        return ['result'=>$res];
    }

	function like_goods(){
        global $_W;
        global $_GPC;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        $skuId = $_GPC['skuId'];

        $like = pdo_fetch('select * from '.tablename('qt_operate_like').' where skuId=:skuId and uid=:uid',[':skuId'=>$skuId,':uid'=>$uid]);

        if($like){
            pdo_delete('qt_operate_like',['skuId'=>$skuId,'uid'=>$uid]);
            m('fc')->fc_result(0, '取消成功',[]);
        }else{
            pdo_insert('qt_operate_like',[
                'skuId'=>$skuId,
                'skuName'=>$_GPC['skuName'],
                'source_type'=>$_GPC['source_type'],
                'picUrl'=>$_GPC['picUrl'],
                'wlPrice_after'=>$_GPC['wlPrice_after'],
                'wlPrice'=>$_GPC['wlPrice'],
                'uid'=>$uid,
                'addtime'=>time()
            ]);
            m('fc')->fc_result(0, '收藏成功',[]);
        }

    }
    function like_batch_cancle(){

        global $_W;
        global $_GPC;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        $skuIds = $_GPC['skuIds'];

        $id_list = explode(',',$skuIds);

        foreach ($id_list as $key =>$val){
            pdo_delete('qt_operate_like',['skuId'=>$val,'uid'=>$uid]);
        }

        m('fc')->fc_result(0, '取消成功',[]);
    }

    function like_list(){
        global $_W;
        global $_GPC;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];


        $like_list = pdo_fetchall('select * from '.tablename('qt_operate_like').' where uid=:uid',[':uid'=>$uid]);

        if(count($like_list) == 0){
            m('fc')->fc_result(0, '成功',["data"=>[],"totalpage"=>0]);
        }

//        $skuId_str = '';
//
//        foreach ($like_list as $key =>$val){
//            $skuId_str .= $val['skuId'].',';
//        }
//        $skuId_str = substr($skuId_str,0,-1);
//
//
//        $global = m("fc")->fc_getglobal();
//        $params = [
//            "goods_id_list"=>"[{$skuId_str}]"
//        ];
//        $res = m("pdd_data")->goods_search($params,$global);

        m('fc')->fc_result(0, '成功',['data'=>$like_list]);
    }

    public function my(){

        global $_W;
        global $_GPC;
        $global = m("fc")->fc_getglobal();


        m('member')->update_super_vip();

        $navbar_list = json_decode($global['homepage_my_itemjson'],1);
        foreach ($navbar_list as $key => $navbar){
            if($navbar['isshow']== "false"){
                unset($navbar_list[$key]);
            }
        }
        $navbar_list = empty($navbar_list[0]['list']['list']) ? [] : $navbar_list[0]['list']['list'];
        $res["navbar_list"]= $navbar_list;

        $member_info = m('member')->member_info();
        $res['member_info'] =$member_info['result'];
        $res['my_cash'] = m('member')->my_commission();
        $res['isopenpartner'] = $global['isopenpartner'];
        m('fc')->fc_result(0, '成功',$res);
    }


	public function api_theme_goods_list(){

        global $_W;
        global $_GPC;
        load()->func("communication");
        $global = m("fc")->fc_getglobal();
        if (empty($global) && $global["hjk_apikey"]) {
            return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
        }

        $res = m("pdd_data")->goods_theme($global);
        if($res){

            m('fc')->fc_result(0, '成功',$res);
        }else{
            m('fc')->fc_result(1, '失败','');
        }

	}

	function api_goodsdetail()
    {
        global $_W;
        global $_GPC;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        load()->func("communication");
        $global = m("fc")->fc_getglobal();
        if (empty($global) && $global["hjk_apikey"]) {
            return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
        }

        //精选
        if($_GPC["source_type"] == 5 && $global['self_source_id'] != 0){
            $this->_reset_gpc($global['self_source_id']);
        }


        if($_GPC["source_type"] == 4) {

             $res = m("taobao_data")->goods_detail($_GPC["skuid"], $global);
//            $res = m("taobao_data")->da_taoke_goods_detail($_GPC["skuid"], $global);
        }else if($_GPC["source_type"] == 1){
            $res = m("jd_data")->goods_detail($global);
        }else if($_GPC["source_type"] == 5){
            $res = m("goods")->goods_detail($global);
        }else{
            $res = m("pdd_data")->goods_detail($_GPC["skuid"],$global);
        }

        $res['data'] = $this->commissionConvert($res['data'],$global);

        if($res){
            $like = pdo_fetch('select * from '.tablename('qt_operate_like').' where skuId=:skuId and uid=:uid',[':skuId'=>$_GPC["skuid"],':uid'=>$uid]);

            $res["data"]['is_like'] = !empty($like) ? true : false;
            return result_data(0, '成功',$res);

        }else{
            return result_data(0, '成功',$res);
        }

    }

    function get_taobao_tlk(){
        global $_W;
        global $_GPC;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $skuid = $_GPC['skuid'];
        if(empty($skuid)){
            return result_data(1, '失败');
        }
        $global = m("fc")->fc_getglobal();

        $res = m("taobao_data")->get_taobao_tlk($skuid);

//        $resp = ihttp_get("http://shop.qt666.top/cloud_api.php?auth_key={$global['auth_key']}&pid={$global['taobao_pid']}&session_key={$global['session_key']}&skuid={$skuid}&op=tkl_create");
//        $res = $resp['content'];
//        $res = json_decode($res, true);
        m("fc")->fc_log_debug("cloud_api：" . json_encode($res), "cloud_api");
        if ($res["errno"] == 0) {
            $data = $res['result'];

            $user = m('member')->member_getbyuid($uid);
            if($user['vip_level'] == 1){
                $data['wlCommission'] = number_format($data['zk_final_price'] * ($data['commission_rate'] / 100) * ($global['first_sidy'] / 100),2);
            }else{
                $data['wlCommission'] = 0;
            }

            return result_data(0, '成功',$data);
        } else {
            return result_data(2, '失败',$res);
        }
    }

	function api_getunionurl()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/getunionurl";
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		$info = m("member")->member_getbyuid($uid, true);
		if ($info && empty($info["avatar"])) {
			if (intval($info["from_id"]) > 0) {
				$info = m("member")->member_getbyuid($info["from_id"], true);
				if (!empty($info)) {
					$uid = $info["uid"];
				}
			}
		}
		if (empty($uid)) {
			$uid = 1;
		}
		$pid = $_GPC["source_type"] == "2" ? $global["hjk_pddpid"] : $uid;
		$custom_parameters =  "{$uid},{$_W["uniacid"]}";
		$data = array("apikey" => $global["hjk_apikey"], "skuid" => $_GPC["skuid"], "couponurl" => empty($_GPC["couponurl"]) ? '' : $_GPC["couponurl"], "pid" => $pid, "custom_parameters" =>$custom_parameters, "multi_group" => $_GPC["multi_group"], "source_type" => $_GPC["source_type"], "isgy" => "0");
		$res = ihttp_post($url, $data);
		$res = $res["content"];
		$res = json_decode($res, true);

		if($_GPC['source_type'] == 1){
            $resp['app_id'] = "wx13e41a437b8a1d2e";
            $resp['page_path'] ="/pages/jingfenblank/item?spreadUrl=".$res['data'];
        }else{
            $resp['wxapp'] = $res;
            $resp['app_id'] = $res['wxapp']['app_id'];
            $resp['page_path'] = $res['wxapp']['page_path'];
        }

		return result_data(0,'成功',$resp);
	}
	function api_cname()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/cname";
		$data = array("apikey" => $global["hjk_apikey"]);
		$cachedata = m("fc")->fc_cache_load("api_cname");
		if (empty($cachedata)) {
			$res = ihttp_post($url, $data);
			$res = $res["content"];
			$res = json_decode($res, true);
			if ($res["status_code"] == "200") {
				$cachedata = $res;
				m("fc")->fc_cache_write("api_cname", $cachedata, 60);
			} else {
				return $res;
			}
		}
		return $cachedata;
	}
	function api_pddcname()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/pddcname";
		$data = array("apikey" => $global["hjk_apikey"]);
		$cachedata = m("fc")->fc_cache_load("api_pddcname");
		if (empty($cachedata)) {
			$res = ihttp_post($url, $data);
			$res = $res["content"];
			$res = json_decode($res, true);
			if ($res["status_code"] == "200") {
				$cachedata = $res;
				m("fc")->fc_cache_write("api_pddcname", $cachedata, 60);
			} else {
				return $res;
			}
		}
		return $cachedata;
	}
	function api_mgjcname()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/mgjcname";
		$data = array("apikey" => $global["hjk_apikey"]);
		$cachedata = m("fc")->fc_cache_load("api_mgjcname");
		if (empty($cachedata)) {
			$res = ihttp_post($url, $data);
			$res = $res["content"];
			$res = json_decode($res, true);
			if ($res["status_code"] == "200") {
				$cachedata = $res;
				m("fc")->fc_cache_write("api_mgjcname", $cachedata, 60);
			} else {
				return $res;
			}
		}
		return $cachedata;
	}

	function api_wetask()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/wetask";
		$data = array("apikey" => $global["hjk_apikey"], "target_url" => $_GPC["target_url"], "site_type" => $_GPC["site_type"], "site_ip" => $_GPC["site_ip"], "timing_type" => $_GPC["timing_type"], "task_at" => $_GPC["task_at"], "task_type" => $_GPC["task_type"]);
		m("fc")->fc_log_debug(json_encode($data), "定时任务");
		$res = ihttp_post($url, $data);
		$res = $res["content"];
		$res = json_decode($res, true);
		m("fc")->fc_log_debug(json_encode($res), "定时任务");
		return $res;
	}
	function api_weordernoticeurl()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/weordernoticeurl";
		$data = array("apikey" => $global["hjk_apikey"], "target_url" => $_GPC["target_url"], "pdd_pid" => $global["hjk_pddpid"]);
		$res = ihttp_post($url, $data);
		$res = $res["content"];
		$res = json_decode($res, true);
		return $res;
	}
	function api_syncorder()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/syncorder";
		$data = array("apikey" => $global["hjk_apikey"], "source_type" => $_GPC["source_type"], "begintime" => $_GPC["begintime"], "endtime" => $_GPC["endtime"], "pdd_pid" => $global["hjk_pddpid"]);
		$res = ihttp_post($url, $data);
		$res = $res["content"];
		$res = json_decode($res, true);
		return $res;
	}

    function api_syncorder_command()
    {
        global $_W;
        global $_GPC;

        date_default_timezone_set('Asia/Shanghai');
        $begintime = strtotime(date('Y-m-d'));
        $endtime = $begintime + 86400;
        $source_type = 2; //2拼多多
        m("fc")->fc_log_debug("syncorder：" . 11111111, "order_syncorder");

        load()->func("communication");
        $global = m("fc")->fc_getglobal();
        if (empty($global) && $global["hjk_apikey"]) {
            return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
        }
        $url = HAOJK_API_HOST . "platform/syncorder";
        $data = array("apikey" => $global["hjk_apikey"], "source_type" => $source_type, "begintime" =>$begintime, "endtime" => $endtime, "pdd_pid" => $global["hjk_pddpid"]);
        $res = ihttp_post($url, $data);
        $res = $res["content"];
        $res = json_decode($res, true);
        return $res;
    }


	function api_getspreadorderlist()
	{
		global $_W;
		global $_GPC;
		load()->func("communication");
		$global = m("fc")->fc_getglobal();
		if (empty($global) && $global["hjk_apikey"]) {
			return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
		}
		$url = HAOJK_API_HOST . "platform/getspreadorderlist";
		$data = array("apikey" => $global["hjk_apikey"], "begintime" => $_GPC["begintime"], "endtime" => $_GPC["endtime"], "page" => $_GPC["begintime"], "pagesize" => $_GPC["begintime"]);
		$res = ihttp_post($url, $data);
		$res = $res["content"];
		$res = json_decode($res, true);
		return $res;
	}
	function api_getsearchkeyword()
	{
		load()->func("communication");
		$url = HAOJK_API_HOST . "index/getsearchkeyword";
		$data = array("page" => 1, "pagesize" => 20);
		$res = ihttp_post($url, $data);
		$res = $res["content"];
		$res = json_decode($res, true);
		return $res;
	}
	function _reset_gpc($id)
	{
		global $_W, $_GPC;
		$diysource = pdo_fetch("SELECT * FROM " . tablename("qt_operate_diysource") . " WHERE source_status=1 and uniacid=:uniacid and  id=:id ", array(":uniacid" => $_W["uniacid"], ":id" => $id));
		if (!empty($diysource)) {
			if ($diysource["source_type"] == "2") {
				$sourcegoods = pdo_fetchall("select * from " . tablename("qt_operate_diysource_goods") . " where uniacid=:uniacid and source_id=:source_id", array(":uniacid" => $_W["uniacid"], ":source_id" => $id));
				$skuids = '';
				foreach ($sourcegoods as $good) {
					if (empty($skuids)) {
						$skuids .= $good["skuId"];
					} else {
						$skuids .= "," . $good["skuId"];
					}
				}
				$_GPC["skuids"] = $skuids;
			} else {
			    //商品源类型为条件筛选
				if ($diysource["source_type"] == "1") {
					$querydata = json_decode($diysource["source_query"], true);
					//券后价
					if (!is_null($querydata["minprice"])) {
						$_GPC["minprice"] = $querydata["minprice"];
					}
					if (!is_null($querydata["maxprice"])) {
						$_GPC["maxprice"] = $querydata["maxprice"];
					}

					//佣金比例
					if (!is_null($querydata["mincommission"])) {
						$_GPC["mincommission"] = $querydata["mincommission"];
					}
					if (!is_null($querydata["maxcommission"])) {
						$_GPC["maxcommission"] = $querydata["maxcommission"];
					}

                    //佣金金额
					if (!is_null($querydata["mincommissionpirce"])) {
						$_GPC["mincommissionpirce"] = $querydata["mincommissionpirce"];
					}
					if (!is_null($querydata["maxcommissionpirce"])) {
						$_GPC["maxcommissionpirce"] = $querydata["maxcommissionpirce"];
					}

                    //优惠券金额区间
                    if (!is_null($querydata["min_coupon_price"])) {
                        $_GPC["min_coupon_price"] = $querydata["min_coupon_price"];
                    }
                    if (!is_null($querydata["max_coupon_price"])) {
                        $_GPC["max_coupon_price"] = $querydata["max_coupon_price"];
                    }

                    //销量区间
                    if (!is_null($querydata["min_sale"])) {
                        $_GPC["min_sale"] = $querydata["min_sale"];
                    }
                    if (!is_null($querydata["max_sale"])) {
                        $_GPC["max_sale"] = $querydata["max_sale"];
                    }


					if (!is_null($querydata["keyword"])) {
						$_GPC["keyword"] = $querydata["keyword"];
					}

					if (!is_null($querydata["cid"])) {
						if ($querydata["source_type"] == "2") {
							$_GPC["cid"] = $querydata["cid"];
						}
					}

					if ($_GPC["sort"] == '') {
						$_GPC["sort"] = $querydata["sort"];
					}
					if ($_GPC["source_type"] == '' || $_GPC["source_type"] == '5') {
						$_GPC["source_type"] = $querydata["source_type"];
					}

					if ($_GPC["sortby"] == '') {
						$_GPC["sortby"] = $querydata["sortby"];
					}
				}
			}
		}
	}
	function _clear_apilist_gpc($id)
	{
		global $_GPC;
		unset($_GPC["source_type"]);
		unset($_GPC["skuid"]);
		unset($_GPC["minprice"]);
		unset($_GPC["maxprice"]);
		unset($_GPC["mincommission"]);
		unset($_GPC["maxcommission"]);
		unset($_GPC["mincommissionpirce"]);
		unset($_GPC["maxcommissionpirce"]);
		unset($_GPC["keyword"]);
		unset($_GPC["cname"]);
		unset($_GPC["cid"]);
		unset($_GPC["iscoupon"]);
		unset($_GPC["goodstype"]);
		unset($_GPC["sort"]);
		unset($_GPC["sortby"]);
		unset($_GPC["skuids"]);
	}

    public function tip(){
        $msg = "警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
                警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责
        警告 代码内有正版授权检测，一经发现此站点未授权，将不定时删除所有此模块文件及数据库表，概不负责";
    }
}