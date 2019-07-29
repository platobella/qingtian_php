<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/7/12
 * Time: 9:34
 */
/**
 * 查询单个内容
 */
function vipremark_get(){
    global $_W;
    global $_GPC;
    $vip=array();
    $vip["uniacid"]=$_W["uniacid"];
    $res=pdo_get("qt_operate_vipremark",$vip);
    return $res;
}
/**
 * 查询申请vip描述内容
 */
function vipremark_getall(){
    global $_W;
    global $_GPC;
    $vip=array();
    $vip["uniacid"]=$_W["uniacid"];
    $res=pdo_getall("qt_operate_vipremark",$vip);
    return $res;
}
/**
 * 保存申请vip描述内容
 */
function vipremark_save(){
    global $_W;
    global $_GPC;
    $i=0;
    $vip=array();
    $vip["uniacid"]=$_W["uniacid"];
    if(!empty($_GPC["viptitle"])){
        $vip["title"]=$_GPC["viptitle"];
    }
    if(!empty($_GPC["content1"])){
        $vip["content1"]=$_GPC["content1"];
    }
    if(!empty($_GPC["content2"])){
        $vip["content2"]=$_GPC["content2"];
    }
    if(!empty($_GPC["content3"])){
        $vip["content3"]=$_GPC["content3"];
    }
    if(!empty($_GPC["remark"])){
        $vip["remark"]=$_GPC["remark"];
    }
    if(!empty($_GPC["img1"])){
        $vip["img1"]=tomedia($_GPC["img1"]);
    }
    if(!empty($_GPC["img2"])){
        $vip["img2"]=tomedia($_GPC["img2"]);
    }
    if(!empty($_GPC["img3"])){
        $vip["img3"]=tomedia($_GPC["img3"]);
    }
    if(!empty($_GPC['vipremark_id'])){
        $vip["updated_at"]=time();
        $i=pdo_update("qt_operate_vipremark",$vip,array("id"=>$_GPC['vipremark_id']));
    }else{
        $vip["created_at"]=time();
        $i=pdo_insert("qt_operate_vipremark",$vip);
    }
    return $i;
}
/**
 * 删除申请vip描述内容
 */
function vipremark_delete(){
    global $_W;
    global $_GPC;
    $vip=array();
    $vip["uniacid"]=$_W["uniacid"];
    $vip["id"]=$_GPC["id"];
    $res=pdo_delete("qt_operate_vipremark",$vip);
    return $res;
}