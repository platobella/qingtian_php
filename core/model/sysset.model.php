<?php

//
function sysset_get()
{
	global $_W;
	global $_GPC;
	$sysset = array();
	$sysset["uniacid"] = $_W["uniacid"];
	$res = pdo_get("qt_operate_sysset", $sysset);
	return $res;
}
function sysset_save()
{
	global $_W;
	global $_GPC;
	$sysset = array();
	$sysset["uniacid"] = $_W["uniacid"];
	if (!empty($_GPC["logo"])) {
		$sysset["logo"] = tomedia($_GPC["logo"]);
	}
	if (!empty($_GPC["title"])) {
		$sysset["title"] = $_GPC["title"];
	}
	if (!empty($_GPC["remark"])) {
		$sysset["remark"] = $_GPC["remark"];
	}
	if (!empty($_GPC["hjk_apikey"])) {
		$sysset["hjk_apikey"] = trim($_GPC["hjk_apikey"]);
	}
	if (!empty($_GPC["hjk_pddpid"])) {
		$sysset["hjk_pddpid"] = trim($_GPC["hjk_pddpid"]);
	}
	if (!empty($_GPC["applyvip"]) || $_GPC["applyvip"] == "0") {
		$sysset["applyvip"] = $_GPC["applyvip"];
	}
	if (!empty($_GPC["vip_fee"]) || $_GPC["vip_fee"] == "0") {
		$sysset["vip_fee"] = $_GPC["vip_fee"];
	}
	if (!empty($_GPC["vip_direct_count"]) || $_GPC["vip_direct_count"] == "0") {
		$sysset["vip_direct_count"] = $_GPC["vip_direct_count"];
	}
	if (!empty($_GPC["vip_indirect_count"]) || $_GPC["vip_indirect_count"] == "0") {
		$sysset["vip_indirect_count"] = $_GPC["vip_indirect_count"];
	}
	if (!empty($_GPC["vip_order_count"]) || $_GPC["vip_order_count"] == "0") {
		$sysset["vip_order_count"] = $_GPC["vip_order_count"];
	}

    if (!empty($_GPC["super_direct_count"]) || $_GPC["super_direct_count"] == "0") {
        $sysset["super_direct_count"] = $_GPC["super_direct_count"];
    }
    if (!empty($_GPC["super_indirect_count"]) || $_GPC["super_indirect_count"] == "0") {
        $sysset["super_indirect_count"] = $_GPC["super_indirect_count"];
    }
    if (!empty($_GPC["super_order_count"]) || $_GPC["super_order_count"] == "0") {
        $sysset["super_order_count"] = $_GPC["super_order_count"];
    }

	if (!empty($_GPC["mincash"]) || $_GPC["mincash"] == "0") {
		$sysset["mincash"] = $_GPC["mincash"];
	}
	if (!empty($_GPC["rate"]) || $_GPC["rate"] == "0") {
		$sysset["rate"] = $_GPC["rate"];
	}
	if (!empty($_GPC["mchid"])) {
		$sysset["mchid"] = $_GPC["mchid"];
	}
	if (!empty($_GPC["coup_appid"])) {
		$sysset["coup_appid"] = $_GPC["coup_appid"];
	}
	if (!empty($_GPC["couptype"]) || $_GPC["couptype"] == "0") {
		$sysset["couptype"] = $_GPC["couptype"];
	}
	if (!empty($_GPC["notice_applycash"])) {
		$sysset["notice_applycash"] = $_GPC["notice_applycash"];
	}
	if (!empty($_GPC["notice_auditingcash"])) {
		$sysset["notice_auditingcash"] = $_GPC["notice_auditingcash"];
	}
	if (!empty($_GPC["sign_credit1"])) {
		$sysset["sign_credit1"] = $_GPC["sign_credit1"];
	}
	if (!empty($_GPC["notice_tplno"])) {
		$sysset["notice_tplno"] = $_GPC["notice_tplno"];
	}
	if (!empty($_GPC["credit1_to_credit2"])) {
		$sysset["credit1_to_credit2"] = $_GPC["credit1_to_credit2"];
	}
	if (!empty($_GPC["subscribeurl"])) {
		$sysset["subscribeurl"] = $_GPC["subscribeurl"];
	}
	if (!empty($_GPC["notice_tplno_app"])) {
		$sysset["notice_tplno_app"] = $_GPC["notice_tplno_app"];
	}
	if (!empty($_GPC["owner_openid"])) {
		$sysset["owner_openid"] = $_GPC["owner_openid"];
	}
	if (!empty($_GPC["cashtype"]) || $_GPC["cashtype"] == "0") {
		$sysset["cashtype"] = $_GPC["cashtype"];
	}
	if ((!empty($_GPC["sms_type"]) || $_GPC["sms_type"] == 0) && $_GPC["sms_type"] != NULL) {
		$sysset["sms_type"] = $_GPC["sms_type"];
	}
	if (!empty($_GPC["dayu_appid"])) {
		$sysset["dayu_appid"] = $_GPC["dayu_appid"];
	}
	if (!empty($_GPC["dayu_appkey"])) {
		$sysset["dayu_appkey"] = $_GPC["dayu_appkey"];
	}
	if (!empty($_GPC["dayu_smstplid"])) {
		$sysset["dayu_smstplid"] = $_GPC["dayu_smstplid"];
	}
	if (!empty($_GPC["dayu_smssign"])) {
		$sysset["dayu_smssign"] = $_GPC["dayu_smssign"];
	}
	if (!empty($_GPC["sms_tpl"])) {
		$sysset["sms_tpl"] = $_GPC["sms_tpl"];
	}
	if (!empty($_GPC["service_msg"])) {
		$sysset["service_msg"] = $_GPC["service_msg"];
	}
	if (!empty($_GPC["kefuqr"])) {
		$sysset["kefuqr"] = tomedia($_GPC["kefuqr"]);
	}
	if (!empty($_GPC["isopenpartner"]) || $_GPC["isopenpartner"] == "0") {
		$sysset["isopenpartner"] = $_GPC["isopenpartner"];
	}
	if (!empty($_GPC["isshow_subsidy"]) || $_GPC["isshow_subsidy"] == "0") {
		$sysset["isshow_subsidy"] = $_GPC["isshow_subsidy"];
	}
	if (!empty($_GPC["leader_subsidename"])) {
		$sysset["leader_subsidename"] = $_GPC["leader_subsidename"];
	}
	if (!empty($_GPC["share_name"])) {
		$sysset["share_name"] = $_GPC["share_name"];
	}
	if (!empty($_GPC["vip_sidy"]) || $_GPC["vip_sidy"] == "0") {
		$sysset["vip_sidy"] = $_GPC["vip_sidy"];
	}
	if (!empty($_GPC["first_sidy"]) || $_GPC["first_sidy"] == "0") {
		$sysset["first_sidy"] = $_GPC["first_sidy"];
	}
	if (!empty($_GPC["second_sidy"]) || $_GPC["second_sidy"] == "0") {
		$sysset["second_sidy"] = $_GPC["second_sidy"];
	}
	if (!empty($_GPC["third_sidy"]) || $_GPC["third_sidy"] == "0") {
		$sysset["third_sidy"] = $_GPC["third_sidy"];
	}
	if (!empty($_GPC["alipay_appid"])) {
		$sysset["alipay_appid"] = $_GPC["alipay_appid"];
	}
	if (!empty($_GPC["alipay_privatekey"])) {
		$sysset["alipay_privatekey"] = $_GPC["alipay_privatekey"];
	}
	if (!empty($_GPC["homepage_title"])) {
		$sysset["homepage_title"] = $_GPC["homepage_title"];
	}
	if (!empty($_GPC["homepage_itemjson"])) {
		$sysset["homepage_itemjson"] = $_GPC["homepage_itemjson"];
	}
    if (!empty($_GPC["homepage_my_itemjson"])) {
        $sysset["homepage_my_itemjson"] = $_GPC["homepage_my_itemjson"];
    }
    if (!empty($_GPC["pdd_client_id"])) {
        $sysset["pdd_client_id"] = trim($_GPC["pdd_client_id"]);
    }
    if (!empty($_GPC["pdd_client_secret"])) {
        $sysset["pdd_client_secret"] = trim($_GPC["pdd_client_secret"]);
    }

    //淘宝授权
    if (!empty($_GPC["auth_key"])) {
        $sysset["auth_key"] = $_GPC["auth_key"];
    }
    if (!empty($_GPC["session_key"])) {
        if($sysset["session_key"] != $_GPC["session_key"]){
            $sysset["session_exp_time"] = time() + 86400*30;
        }

        $sysset["session_key"] = $_GPC["session_key"];

    }
//    if (!empty($_GPC["session_exp_time"])) {
//        $sysset["session_exp_time"] = $_GPC["session_exp_time"];
//    }
    if (!empty($_GPC["taobao_username"])) {
        $sysset["taobao_username"] = $_GPC["taobao_username"];
    }
    if (!empty($_GPC["taobao_user_id"])) {
        $sysset["taobao_user_id"] = $_GPC["taobao_user_id"];
    }
    if (!empty($_GPC["taobao_pid"])) {
        $sysset["taobao_pid"] = trim($_GPC["taobao_pid"]);
    }

    if (!empty($_GPC["taobao_app_key"])) {
        $sysset["taobao_app_key"] = trim($_GPC["taobao_app_key"]);
    }
    if (!empty($_GPC["taobao_app_secret"])) {
        $sysset["taobao_app_secret"] = trim($_GPC["taobao_app_secret"]);
    }


    if(array_key_exists("pdd_switch",$_GPC) ||
        array_key_exists("jd_switch",$_GPC) ||
        array_key_exists("taobao_switch",$_GPC)){

	    if (!empty($_GPC["pdd_switch"])) {
            $sysset["pdd_switch"] = 1;
        }else{
            $sysset["pdd_switch"] = 0;
        }
        if (!empty($_GPC["jd_switch"])) {
            $sysset["jd_switch"] = $_GPC["jd_switch"];
        }else{
            $sysset["jd_switch"] = 0;
        }
        if (!empty($_GPC["taobao_switch"])) {
            $sysset["taobao_switch"] = $_GPC["taobao_switch"];
        }else{
            $sysset["taobao_switch"] = 0;
        }

    }
    if(array_key_exists("coupon_switch",$_GPC)){
        if (!empty($_GPC["is_open_coupon"])) {
            $sysset["is_open_coupon"] = 1;
        }else{
            $sysset["is_open_coupon"] = 2;
        }
    }
    if(array_key_exists("auditing_switch",$_GPC)){
        if (!empty($_GPC["is_open_auditing"])) {
            $sysset["is_open_auditing"] = 1;
        }else{
            $sysset["is_open_auditing"] = 0;
        }
    }

    if (!empty($_GPC["auditing_version"])) {
        $sysset["auditing_version"] = trim($_GPC["auditing_version"]);
    }

    if(array_key_exists("self_source_id",$_GPC)){
        $sysset["self_source_id"] = $_GPC["self_source_id"];
    }

    if(array_key_exists("is_open_order_sync",$_GPC)){

        $sysset["order_sync_exp_time"] = time() + 86400 * 30;

        $sysset["is_open_order_sync"] = $_GPC["is_open_order_sync"];
    }


	$i = 0;
	$resp = pdo_get("qt_operate_sysset",['uniacid'=>$_W["uniacid"]]);
	if (!empty($resp)) {
		m("fc")->fc_cache_delete("qt_operate_sysset");
		$i = pdo_update("qt_operate_sysset", $sysset, array("id" => $resp["id"]));
	} else {
		$i = pdo_insert("qt_operate_sysset", $sysset);
	}
	return $i;
}
function sysset_resetpage()
{
	global $_W;
	global $_GPC;
	$sysset["homepage_itemjson"] = '';
	$sysset["homepage_itemhtml"] = '';
	$i = pdo_update("qt_operate_sysset", $sysset, array("id" => $_GPC["id"]));
	return $i;
}