<?php
/**
 * Created by PhpStorm.
 * User: ZHANG
 * Date: 2018/7/10
 * Time: 17:05
 */

if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require_once IA_ROOT . '/addons/qt_shop/core/model/sysset.model.php';
require_once IA_ROOT . '/addons/qt_shop/core/model/vipremark.model.php';
require_once IA_ROOT . '/addons/qt_shop/core/model/apicloud.php';
class Index_NetsHaojkMywPage extends WebPage
{
    public function main()
    {
        global $_GPC;
        global $_W;

        if ($_W['action'] == 'sysset.index' || $_W['action'] == 'sysset')
            $this->index();
        if ($_W['action'] == 'sysset.index.coupontype')
            $this->coupontype();
        if ($_W['action'] == 'sysset.index.share')
            $this->syssetshare();

        if ($_W['action'] == 'sysset.index.super_vip')
            $this->super_vip();
        if ($_W['action'] == 'sysset.index.vip')
            $this->vip();
        if ($_W['action'] == 'sysset.index.cashset')
            $this->cashset();
        if ($_W['action'] == 'sysset.index.payment_test')
            $this->payment_test();
        if ($_W['action'] == 'sysset.index.alipaypayment_test')
            $this->alipaypayment_test();
        if ($_W['action'] == 'sysset.index.sms')
            $this->sms();
        if ($_W['action'] == 'sysset.index.tplmsg')
            $this->tplmsg();
        if ($_W['action'] == 'sysset.index.gradefy')
            $this->gradefy();
        if ($_W['action'] == 'sysset.index.appentry')
            $this->appentry();
        if ($_W['action'] == 'sysset.index.diypage')
            $this->diypage();
        if ($_W['action'] == 'sysset.index.diypage_my')
            $this->diypage_my();
        if ($_W['action'] == 'sysset.index.update_taobao_user_id')
            $this->update_taobao_user_id();
        if ($_W['action'] == 'sysset.index.api_order_set')
            $this->api_order_set();
    }
    public function api_order_set(){

        global $_GPC, $_W;

        $global = m("fc")->fc_getglobal();

        $data = [
            "auth_key"=>$global['auth_key'],
            "op"=>"order_sync",
            "id"=>$_W['uniacid'],
            "host"=>$_SERVER['HTTP_HOST'],
        ];
        $resp = ihttp_post("http://shop.qt666.top/cloud_api.php",$data);
        $res = $resp['content'];
        $res = json_decode($res, true , 512 , JSON_BIGINT_AS_STRING);

        if ($res["errno"] == 0) {
            $_GPC['is_open_order_sync'] = 1;
            $res = sysset_save();
            show_message("开启成功", webUrl('sysset/index'), 'success');

        } else {
            show_message("失败", webUrl('sysset/index'), 'success');
        }

    }
    //授权服务器异步调用
    public function update_taobao_user_id(){
        global $_GPC, $_W;
        $res = sysset_save();
        show_message("授权成功", webUrl('index'), 'success');

    }
    //系统设置
    public function index()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            $_GPC['target_url']= "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=helpmsg&m=qt_shop&a=wxapp&do=wxapi&model=order&apiname=order_notice";;
            $resdata =  m('apicloud')->api_weordernoticeurl();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index'), 'success');
        }
        if (!is_dir(NETS_HAOJIK_PATH.'/cache/logs/'))
            mkdir(NETS_HAOJIK_PATH.'/cache/logs/');
        if (!is_dir(NETS_HAOJIK_PATH.'/cache/img/'))
            mkdir(NETS_HAOJIK_PATH.'/cache/img/');
        //自定义商品源
        $sourcelist=pdo_getall("qt_operate_diysource",array("uniacid"=>$_W["uniacid"]));


        include $this->template('haojingke/sysset/index');
    }

    //领券设置
    public function coupontype()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index/coupontype'), 'success');
        }
        include $this->template('haojingke/sysset/coupontype');
    }

    //分享设置
    public function syssetshare()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index/share'), 'success');
        }
        include $this->template('haojingke/sysset/share');
    }

    //申请合伙人设置
    public function vip()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        $vipremark =vipremark_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            $res_vipremark= vipremark_save();
            if ($res || $res_vipremark)
                show_message('操作成功！', webUrl('sysset/index/vip'), 'success');
        }
        include $this->template('haojingke/sysset/vip');
    }

    //申请合伙人设置
    public function super_vip()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index/super_vip'), 'success');
        }
        include $this->template('haojingke/sysset/super_vip');
    }



    //申请合伙人设置
    public function cashset()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        $uniacid = $_W['uniacid'];
        if ($_W['ispost'] == 1) {
            //上传证书
            $cert_path = ATTACHMENT_ROOT . 'botcert/';
			if (!file_exists($cert_path)){ mkdir ($cert_path);}//创建证书文件夹
            if (!empty($_FILES['weixin_cert_file'])) {
                file_upload1($_FILES['weixin_cert_file'], 'pem', 'apiclient_cert.pem');
            }
            if (!empty($_FILES['weixin_key_file'])) {
                file_upload1($_FILES['weixin_key_file'], 'pem', 'apiclient_key.pem');
            }
            if (!empty($_FILES['weixin_root_file'])) {
                file_upload1($_FILES['weixin_root_file'], 'pem', 'rootca.pem');
            }
            $b = true;
            if (!empty($_GPC['cert'])) {
                $content = $_GPC['cert'];//file_get_contents($cert_path . 'apiclient_cert.pem.' . $uniacid);
                $ret = file_put_contents($cert_path . 'apiclient_cert.pem.' . $uniacid, trim($content));
                $b = $b && $ret;
            }
            if (!empty($_GPC['key'])) {
                $content = $_GPC['key'];//file_get_contents($cert_path . 'apiclient_key.pem.' . $uniacid);
                $ret = file_put_contents($cert_path . 'apiclient_key.pem.' . $uniacid, trim($content));
                $b = $b && $ret;
            }
            if (!empty($_GPC['ca'])) {
                $content = $_GPC['ca'];//file_get_contents($cert_path . 'rootca.pem.' . $uniacid);
                $ret = file_put_contents($cert_path . 'rootca.pem.' . $uniacid, trim($content));
                $b = $b && $ret;
            }
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index/cashset'), 'success');
        }
        include $this->template('haojingke/sysset/cashset');
    }

    //提现测试
    public function payment_test()
    {
        global $_W;
        global $_GPC;

        $tradeno = date('Ymd') . time();
        $r = pdo_fetch("SELECT * FROM " . tablename('qt_operate_sysset') . " WHERE uniacid =:uniacid", array(':uniacid' => $_W['uniacid']));
        $openid = $_GPC["openid"];
        $money = 1;
        $mchid = $r['mchid'];

        $res = payWeixin($openid, $money, $mchid, $tradeno);
        //微信打款失败的
        //var_dump($res);
        if (empty($res['errno'])) {
            $remark = "[打款成功，交易单号为：" . $tradeno . "] ";
            show_json(1, $remark);
        } else {
            show_json(1, '微信打款失败,交易单号为：' . $tradeno . '，错误码[' . $res['errno'] . '][' . $res['message'] . ']');
        }
    }

    //支付宝提现测试
    public function alipaypayment_test()
    {
        global $_W;
        global $_GPC;

        $r = pdo_fetch("SELECT * FROM " . tablename('qt_operate_sysset') . " WHERE uniacid =:uniacid", array(':uniacid' => $_W['uniacid']));
        $openid = $_GPC["openid"];
        $alipay_appid = $r['alipay_appid'];

        $biz_content = array();
        //单号
        $biz_content['out_biz_no'] = time();
        $biz_content['payee_type'] = 'ALIPAY_LOGONID';
        //支付宝账号
        $biz_content['payee_account'] = $openid;
        //支付金额 最低0.1
        $biz_content['amount'] = 0.1;
        $biz_content['payer_show_name'] = '提现测试';
        $biz_content['payee_real_name'] = '';
        $biz_content['remark'] = '提现测试';
        $biz_content = array_filter($biz_content);
        $config['method'] = 'alipay.fund.trans.toaccount.transfer';
        //app_id
        $config['app_id'] = $alipay_appid;
        //private_key
        $config['private_key'] = $r["alipay_privatekey"];
        $config['biz_content'] = json_encode($biz_content);
        $res = publicAliPay($config);
        if ($res['alipay_fund_trans_toaccount_transfer_response']['code'] == 10000) {
            $remark = "[打款成功，交易单号为：" . $res['alipay_fund_trans_toaccount_transfer_response']['order_id'] . "] ";
            show_json(1, $remark);
        } else {
            show_json(1, '支付宝打款失败，错误信息[' . $res['alipay_fund_trans_toaccount_transfer_response']['sub_msg'] . ']');
        }
    }

    //短信设置
    public function sms()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index/sms'), 'success');
        }
        include $this->template('haojingke/sysset/sms');
    }

    //模板消息设置
    public function tplmsg()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index/tplmsg'), 'success');
        }
        include $this->template('haojingke/sysset/tplmsg');
    }

    //返利设置
    public function gradefy()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        if ($_W['ispost'] == 1) {
            $res = sysset_save();
            if ($res)
                show_message('操作成功！', webUrl('sysset/index/gradefy'), 'success');
        }
        include $this->template('haojingke/sysset/gradefy');
    }

    //小程序入口
    public function appentry()
    {
        global $_GPC, $_W;
        $data = sysset_get();
        $appentry = wxapp_page;
        $appentry = json_decode($appentry, true);
        include $this->template('haojingke/sysset/appentry');
    }

    //自定义页面
    public function diypage()
    {
        global $_GPC, $_W;
        $data = sysset_get();
         //自定义关键词
        $keywordlist=pdo_getall("qt_operate_keyword",array("uniacid"=>$_W["uniacid"]));

        //自定义商品源
        $sourcelist=pdo_getall("qt_operate_diysource",array("uniacid"=>$_W["uniacid"]));

//        $cnamelist=m('pdd_data')->goods_opt();
//        $cnamelist = pdo_fetchall("select * from ".tablename('qt_classification')." where parent_id = 0");

        $cnamelist = pdo_fetchall("select id,name,pdd_cid,jd_cid,tb_cid from " . tablename("qt_classification")." where parent_id=0 and is_delete=0 and uniacid=:uniacid ORDER BY sort DESC",['uniacid'=>$_W['uniacid']]);


        //系统默认的diy
        $plugin = wxapp_page_plugin;
        $plugin = json_decode($plugin, true);
        $plugin_html="";
        //设置过diy的
        $siteroot = str_replace('http://','https://',$_W['siteroot']);
        if (!empty($data['homepage_itemjson'])) {
            $plugin_diy = htmlspecialchars_decode($data['homepage_itemjson']);
            $plugin_diy = json_decode($plugin_diy, true);
            //追加新组件后重新赋值

            foreach ($plugin as $key =>$val){
                foreach ($plugin_diy as $k =>$v){
                    if($val['id'] == $v['id']){
                        $v['type'] = $plugin[$key]['type'];
                        $plugin[$key] = $v;
                    }
                }
            }
        }
        $appentry = wxapp_page;
        $appentry = json_decode($appentry, true);
        include $this->template('haojingke/sysset/diypage');
    }
    //我的自定义页面
    public function diypage_my()
    {
        global $_GPC, $_W;
        $data = sysset_get();

//        //自定义关键词
        $keywordlist=pdo_getall("qt_operate_keyword",array("uniacid"=>$_W["uniacid"]));

        //自定义商品源
        $sourcelist=pdo_getall("qt_operate_diysource",array("uniacid"=>$_W["uniacid"]));
        $cnamelist=m('pdd_data')->goods_opt();
        //系统默认的diy
        $plugin = wxapp_my_page_plugin;
        $plugin = json_decode($plugin, true);
        $plugin_html="";
        //设置过diy的
        $siteroot = str_replace('http://','https://',$_W['siteroot']);
        if (!empty($data['homepage_my_itemjson'])) {
            $plugin_diy = htmlspecialchars_decode($data['homepage_my_itemjson']);
            $plugin_diy = json_decode($plugin_diy, true);

            $plugin=$plugin_diy;
        }
        $appentry = wxapp_page;
        $appentry = json_decode($appentry, true);
        include $this->template('haojingke/sysset/diypage_my');
    }
    //保存自定义页面
    public function savediypage()
    {
        global $_GPC, $_W;
        $res = sysset_save();

        show_json(1, "操作成功");
    }

    public function savediypage_my(){
        global $_GPC, $_W;
        $res = sysset_save();

        show_json(1, "操作成功");
    }

    //重置自定义页面
    public function resetdiypage()
    {
        global $_GPC, $_W;
        $res = sysset_resetpage();
        show_json(1, "操作成功");
    }

}
?>