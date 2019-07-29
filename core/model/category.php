<?php
if (!defined("IN_IA")) {
    exit("Access Denied");
}
class Category_NetsHaojkModel
{
    public function get_list(){

        $global = m("fc")->fc_getglobal();
        if (empty($global) && $global["hjk_apikey"]) {
            return array("data" => '', "message" => "请先完成系统配置！", "status_code" => -1);
        }

        $url = HAOJK_API_HOST . "pdd/myapi";
        $data = array("apikey" => $global["hjk_apikey"], "type" => "cname");
        $res = ihttp_post($url, $data);
        $res = $res["content"];
        $res = json_decode($res, true);


        m('fc')->fc_result(0, '成功',$res);
    }
}