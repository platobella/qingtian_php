<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
class Weixin_NetsHaojkModel
{

    /***
     * 调用微信api，获取access_token，有效期7200s
     ***/
    public function weixin_get_accesstoken(){
        global $_GPC, $_W;
        $accesstoken = m('fc')->fc_cache_load('wx_accesstoken');
        m('fc')->fc_log_debug('wx_accesstoken_cache：'.$accesstoken,'wx_accesstoken');
//        if(empty($accesstoken)){
            $key=$_W['uniaccount']["key"];
            $secret=$_W['uniaccount']["secret"];
            load()->func('communication');
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$key.'&secret='.$secret;
            $result = ihttp_get($url);
            $res=$result["content"];
            if(empty($res)){
                m('fc')->fc_cache_delete("wx_accesstoken");
                return "500";//'api return error';
            }
            if(!empty($res)){
                $res=json_decode($res);
                m('fc')->fc_log_debug('access_token：'.$res->access_token,'weixin_get_accesstoken');
                m('fc')->fc_cache_write('wx_accesstoken',$res->access_token,7000);
                return $res->access_token;
            }else{
                m('fc')->fc_cache_delete("wx_accesstoken");
                return "500";//'api return error';
            }
//        }
        return $accesstoken;
    }

    /***
     * 微信支付
     ***/
    public function weixin_topay($fee,$openid,$title=''){
        global $_GPC, $_W;
        $tid=date('YmdHis');
        $order = array(
            'tid' => $tid,
            'user' => $openid,
            'fee' => $fee,
            'title' => $title,
        );

        $pay_params = $this->pay($order);

        if (is_error($pay_params)) {

            return $pay_params;
        }
        return false;

    }
    /***
     * 微信支付
     ***/
    public function weixin_payresult($fee,$openid,$title=''){
        global $_GPC, $_W;
        $tid=date('YmdHis');
        $order = array(
            'tid' => $tid,
            'user' => $openid,
            'fee' => $fee,
            'title' => $title,
        );

        $pay_params = $this->pay($order);

        if (is_error($pay_params)) {

            return $pay_params;
        }
        return false;

    }

    /***
     * 客服消息发送
     ***/
    public function weixin_send_msg($msg,$token=''){
        if(empty($token))
            $token = $this->weixin_get_accesstoken();
        load()->func('communication');
        m('fc')->fc_log_debug('wx_accesstoken_weixin_send_msg：'.$token,'wx_accesstoken');
        m('fc')->fc_log_debug('weixin_send_msg：'.json_encode($msg),'weixin_send_msg');

        $msg=json_encode($msg);
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$token;
        //中文urldecode
        $msg=urldecode($msg);
        $result = ihttp_post($url,$msg);
        m('fc')->fc_log_debug('weixin_send_msg：'.json_encode($result),'weixin_send_msg');
        if(empty($result)){
            return "500";
        }
        $res = json_decode($result,true);
        if($res){
            return $res;
        }else{
            m('fc')->fc_cache_delete("wx_accesstoken");
            return "500";//'api return error';
        }
    }

    /***
     * 模板消息发送
     ***/
    public function weixin_send_temp_msg($tempdata,$template_id='',$token=''){
        if(empty($token))
            $token = $this->weixin_get_accesstoken();
        global $_W,$_GPC;
        $global = m('fc')->fc_getglobal();
        if(empty($template_id))
            $template_id = $global['notice_tplno_app'];
        $forminfo = array();
        if(empty($tempdata['formid'])){
            $forminfo =  pdo_fetch('select * from '.tablename('qt_operate_formid').' where openid=:openid and uniacid=:uniacid 
             and created_at+7*24*3600>'.time().' order by created_at limit 1'
                , array(':uniacid' => $_W['uniacid'],':openid' => $tempdata['openid']));
            if($forminfo)
                $tempdata['formid'] = $forminfo['formid'];
        }
        if(empty($tempdata['formid']))
            return;
        $membersubscribe = pdo_fetch('select 1 from '.tablename('qt_operate_member').' where openid=:openid and uniacid=:uniacid 
             and subscribe=1', array(':uniacid' => $_W['uniacid'],':openid' => $tempdata['openid']));
        if(!$membersubscribe)
            return;
        $json_array=array(
            "touser"=>$tempdata['openid'],
            "template_id"=>$template_id,//通知模版消息编号
            "page"=>$tempdata['url'],
            "form_id"=>$tempdata['formid'],
            "data"=>$tempdata['data'],
            "emphasis_keyword"=> "keyword1.DATA"
        );
        $json_template = json_encode($json_array);
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$token;
        $d=ihttp_post($url, $json_template);
        if($forminfo && json_decode($d["content"])->errcode==0)
            $j = pdo_delete('qt_operate_formid',array('id'=>$forminfo['id']));
        return json_decode($d["content"]);
    }


    /***
     * 上传图片
     ***/
    public function weixin_uploadimage($img) {
        //return $img;
        //$img='/addons/qt_shop/cache/1_bg.png';
        $file_info=array(
            'filename'=>$img,  //图片相对于网站根目录的路径
            'content-type'=>'image/png',  //文件类型
            'filelength'=>'90011'         //图文大小
        );
        $token=$this->weixin_get_accesstoken();
        $url="https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$token}&type=image";
        $curl = curl_init ();
        $real_path=$_SERVER['DOCUMENT_ROOT'].$file_info['filename'];

        if(!file_exists($real_path)){
            return  array('code'=>404,'value'=>'文件['.$real_path.']不存在');
        }
        $data= array("media"=>new CURLFile(realpath($real_path)));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec ( $curl );

        curl_close ( $curl );
        $res=json_decode($result,true);
        if(!empty($res['media_id'])){
            return array('code'=>200,'value'=>$res['media_id']);
        }
        m('fc')->fc_cache_delete("wx_accesstoken");
        return  array('code'=>500,'value'=>$res['errmsg']);
    }


    /*
     * 企业微信打款给微信用户
     */
    function weixin_sendhb($settings,$toUser,$tradeno=""){
        global $_GPC, $_W;
        define('MB_ROOT', IA_ROOT . '/attachment/botcert');//定义的微信支付证书路径
        load()->func('communication');
        if (empty($settings['tj_amount'])){
            return;
        }

        $amount=$settings['tj_amount'];
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $pars = array();
        $pars['mch_appid'] =$settings['appid'];
        $pars['mchid'] = $settings['mchid'];
        $pars['nonce_str'] = random(32);
        $pars['partner_trade_no'] = $tradeno;
        $pars['openid'] =$toUser;
        $pars['check_name'] = "NO_CHECK";
        $pars['amount'] =$amount;
        $pars['desc'] = "中奖红包";
        $pars['spbill_create_ip'] =$settings['ip'];
        ksort($pars, SORT_STRING);
        $string1 = '';
        foreach($pars as $k => $v) {
            $string1 .= "{$k}={$v}&";
        }
        $string1 .= "key={$settings['password']}";
        $pars['sign'] = strtoupper(md5($string1));
        $xml = array2xml($pars);
        $extras = array();
        if(!empty($settings["uniacid"])){
            if(file_exists(MB_ROOT . '/rootca.pem.'.$settings["uniacid"])){
                $extras['CURLOPT_CAINFO'] = MB_ROOT . '/rootca.pem.'.$settings["uniacid"];
            }
            $extras['CURLOPT_SSLCERT'] = MB_ROOT . '/apiclient_cert.pem.'.$settings["uniacid"];
            $extras['CURLOPT_SSLKEY'] = MB_ROOT . '/apiclient_key.pem.'.$settings["uniacid"];
        }
        $procResult = null;
        $resp = ihttp_request($url, $xml, $extras);
        if(is_error($resp)){
            $procResult = $resp;
        } else {
            $xml = '<?xml version="1.0" encoding="utf-8"?>' . $resp['content'];
            $dom = new DOMDocument();
            if($dom->loadXML($xml)) {
                $xpath = new DOMXPath($dom);
                $code = $xpath->evaluate('string(//xml/return_code)');
                $ret = $xpath->evaluate('string(//xml/result_code)');
                if(strtolower($code) == 'success' && strtolower($ret) == 'success') {
                    $procResult = true;
                } else {
                    $error = $xpath->evaluate('string(//xml/err_code_des)');
                    $procResult = error(-2, $error);
                }
            } else {
                $procResult = error(-1, 'error response');
            }
            //var_dump($procResult);
        }
        // echo "04_5.发送红包结果:".json_encode($procResult)."<br/>";

        return $procResult;
    }


    function weixin_get_wxconfig_admin($mchid=""){
        global $_GPC, $_W;
        // echo "04_4.发送红包配置2:1<br/>";
        $uniacid=$_W['uniaccount']['uniacid'];
        $setting = uni_setting($uniacid, array('payment', 'recharge'));
        // echo "04_4.发送红包配置2:2<br/>";
        $pay = $setting['payment'];
        $wxconfig['appid']=trim($_W['uniaccount']['key']);
        $wxconfig['appsecret']=trim($_W['uniaccount']['secret']);
        $wxconfig['mchid']=trim($mchid);
        $wxconfig['ip']=$this->weixin_get_ip();//服务器IP
        // echo "04_4.发送红包配置2:3<br/>";
        $wxconfig['password']=$pay['wechat']['signkey'];
        // echo "04_4.发送红包配置2:<br/>";
        //var_dump($wxconfig);
        return $wxconfig;
    }
    function weixin_get_ip(){
        // echo "04_4.发送红包配置2:4<br/>";
        if(isset($_SERVER)){
            // echo "04_4.发送红包配置2:41<br/>";
            if($_SERVER['SERVER_ADDR']){
                $server_ip=$_SERVER['SERVER_ADDR'];
            }
        }else{
            // echo "04_4.发送红包配置2:2<br/>";
            $server_ip = getenv('SERVER_ADDR');
        }

        // echo "04_4.发送红包配置2:43:".$server_ip."<br/>";
        // echo "04_4.发送红包配置2:43<br/>";
        if($this->weixin_isPrivate($server_ip)||empty($server_ip)){
            // echo "04_4.发送红包配置2:44<br/>";
            $host=$_SERVER['HTTP_HOST'];
            $arr = explode('.',$host);
            if(count($arr)==3){
                $host=$arr[1].".".$arr[2];
            }
            $set["from_host"]=$host;
            // echo "04_4.发送红包配置2:45<br/>";
            $server_ip = gethostbyname($_SERVER["HTTP_HOST"]);
        }
        // echo "04_4.发送红包配置2:5<br/>";
        return $server_ip;
    }
    function weixin_isPrivate($ip) {
        // echo "04_4.发送红包配置2:46<br/>";
        $i = explode('.', $ip);
        // echo "04_4.发送红包配置2:47<br/>";
        if ($i[0] == 10) return true;
        // echo "04_4.发送红包配置2:48<br/>";
        if ($i[0] == 172 && $i[1] > 15 && $i[1] < 32) return true;
        // echo "04_4.发送红包配置2:49<br/>";
        if ($i[0] == 127 && $i[1] == 0) return true;
        // echo "04_4.发送红包配置2:491<br/>";
        if ($i[0] == 192 && $i[1] == 168) return true;
        // echo "04_4.发送红包配置2:492<br/>";
        return false;
    }
}
?>