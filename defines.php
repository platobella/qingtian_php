<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
define('NETS_HAOJIK_DEBUG', true);
define('HAOJK_API_HOST', 'http://api-gw.haojingke.com/index.php/api/');
define('JTT_API_HOST', 'http://japi.jingtuitui.com/api/');
define('jd_orderstate',json_encode(array(
	array('name'=>'全部','value'=>''),
	array('name'=>'待付款','value'=>15),
	array('name'=>'已付款','value'=>16),
	array('name'=>'已完成','value'=>17),
	array('name'=>'已结算','value'=>18)
)));
define('pdd_orderstate',json_encode(array(
	array('name'=>'全部','value'=>''),
	array('name'=>'未支付','value'=>-1),
	array('name'=>'已支付','value'=>'0'),
	array('name'=>'已成团','value'=>1),
	array('name'=>'确认收货','value'=>2),
	array('name'=>'审核成功','value'=>3),
	array('name'=>'审核失败','value'=>4),
	array('name'=>'已经结算','value'=>5),
	array('name'=>'无佣金订单','value'=>8)
)));
define('wxapp_page',json_encode(array(
    array('name'=>'首页','value'=>'mayiwo/pages/index/index'),
    array('name'=>'专题页','value'=>'mayiwo/pages/single/index'),
    array('name'=>'抽奖列表页','value'=>'page/choujiang/lotterylist/index'),
    array('name'=>'搜索','value'=>'mayiwo/pages/search/index'),
    array('name'=>'我的','value'=>'page/my/my/index')
)));

define('wxapp_page_plugin',json_encode(array(
    array('id'=>'category','name'=>'分类栏','isshow'=>'true','list'=>'','tag'=>'category','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/category.png'),
    array('id'=>'indexbanner','name'=>'轮播图','isshow'=>'true','list'=>'','tag'=>'indexbanner','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/banner.png'),
    array('id'=>'indexannouncement','name'=>'公告','isshow'=>'true','list'=>'','tag'=>'indexannouncement','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/indexannouncement.jpg'),
    array('id'=>'image3','name'=>'栅格3图','isshow'=>'true','list'=>'','tag'=>'image3','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/image3.png'),
    array('id'=>'indexswiper','name'=>'导航栏','isshow'=>'true','list'=>'','tag'=>'indexswiper','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/navbar.png'),
//    array('id'=>'image1','name'=>'栅格1图','isshow'=>'true','list'=>'','tag'=>'image1','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/image1.png'),
//    array('id'=>'image2','name'=>'栅格2图','isshow'=>'true','list'=>'','tag'=>'image2','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/image2.png'),
    array('id'=>'goods_list','name'=>'商品列表','isshow'=>'true','list'=>'','tag'=>'goods_list','type'=>'','value'=>'','img'=>'/skin/wxappcomponent/goods_list.png'),
)));

define('wxapp_my_page_plugin',json_encode(array(
    array('id'=>'indexbanner','name'=>'工具栏','isshow'=>'true','list'=>'','tag'=>'indexbanner','type'=>'menu','value'=>'','img'=>'/skin/wxappcomponent/toolsbar.png'),
)));

!(defined('NETS_HAOJIK_PATH')) && define('NETS_HAOJIK_PATH', IA_ROOT . '/addons/qt_shop/');
!(defined('NETS_HAOJIK_CORE')) && define('NETS_HAOJIK_CORE', NETS_HAOJIK_PATH . 'core/');
!(defined('NETS_HAOJIK_INC')) && define('NETS_HAOJIK_INC', NETS_HAOJIK_CORE . 'inc/');
!(defined('NETS_HAOJIK_FUNC')) && define('NETS_HAOJIK_FUNC', NETS_HAOJIK_CORE . 'function/');
!(defined('NETS_HAOJIK_VENDOR')) && define('NETS_HAOJIK_VENDOR', NETS_HAOJIK_PATH . 'vendor/');
!(defined('NETS_HAOJIK_CORE_WEB')) && define('NETS_HAOJIK_CORE_WEB', NETS_HAOJIK_CORE . 'web/');
!(defined('NETS_HAOJIK_CORE_MOBILE')) && define('NETS_HAOJIK_CORE_MOBILE', NETS_HAOJIK_CORE . 'mobile/');
!(defined('NETS_HAOJIK_PLUGIN')) && define('NETS_HAOJIK_PLUGIN', NETS_HAOJIK_PATH . 'plugin/');
!(defined('NETS_HAOJIK_URL')) && define('NETS_HAOJIK_URL', $_W['siteroot'] . 'addons/qt_shop/');
!(defined('NETS_HAOJIK_LOCAL')) && define('NETS_HAOJIK_LOCAL', '../addons/qt_shop/');
!(defined('NETS_HAOJIK_STATIC')) && define('NETS_HAOJIK_STATIC', NETS_HAOJIK_URL . 'static/');
!(defined('NETS_HAOJIK_WEB_STYLE')) && define('NETS_HAOJIK_WEB_STYLE',  '/addons/qt_shop/template/web/haojingke/style/');
!(defined('NETS_HAOJIK_NUMBER')) && define('NETS_HAOJIK_NUMBER', 'FALSE');
!(defined('NETS_HAOJIK_NUMBER')) && define('NETS_HAOJIK_NUMBER', 'FALSE');
!(defined('JD_APPID')) && define('JD_APPID', 'wx13e41a437b8a1d2e');
!(defined('JD_CUSTOMERINFO')) && define('JD_CUSTOMERINFO', 'fengyuntai');
!(defined('MGJ_APPID')) && define('MGJ_APPID', 'wxca3957e5474b3670');
?>