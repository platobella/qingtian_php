<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Index_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{

        global $_W;
        $_W['acctype'] = "公众号";
        $iswxapp = pdo_fetch("SELECT * FROM " .tablename('account_wxapp'). " WHERE uniacid =:uniacid",array(':uniacid'=>$_W['uniacid']));
        if($iswxapp)
            $_W['acctype'] = "小程序";
        include $this->template('haojingke/index');
	}
}
?>