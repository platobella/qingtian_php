<?php

//decode by http://www.yunlu99.com/
if (!defined("IN_IA")) {
	exit("Access Denied");
}
require_once IA_ROOT . "/addons/qt_shop/function/phpqrcode.php";
class Img_NetsHaojkModel
{
	public $dirurl;
	public $imgurl;
	public $url;
	public function __construct()
	{
		global $_W;
		$this->dirurl = IA_ROOT . "/addons/qt_shop/cache/";
		$this->imgurl = IA_ROOT . "/addons/qt_shop/cache/img/";
		$this->url = $_W["siteroot"] . "addons/qt_shop/cache/img/";
	}
	public function test()
	{
		global $_GPC, $_W;
		$res = m("apicloud")->api_listall();
		if ($res["status_code"] == "200" && count($res["data"]) > 0) {
			$img = $this->img_wxapp_goods($res["data"][0]);
		}
		$_GPC["uid"] = "18872";
		$_GPC["bgpath"] = "http://www.91jdj.cn/attachment/images/70/2018/07/Us3IolE0RR00wwzsmsCs0evaS0E8wL.jpeg";
		$img = $this->_checkremote($_GPC["bgpath"]);
		var_dump($img);
	}
    public function img_wxapp_goods($goods = array())
    {
        global $_GPC, $_W;
        $goods = empty($_GPC["goods"]) ? $goods : json_decode(htmlspecialchars_decode($_GPC["goods"]), true);
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        //获取并保存二维码图片
        $qrpath = $this->_wxapp_goods_pdd($goods);

        $res = $this->_getgoodsimg($goods, 7, $qrpath);
        return $res;
    }

    public function img_share_preview($goods = array())
    {
        global $_GPC, $_W;
        $goods = empty($_GPC["goods"]) ? $goods : json_decode(htmlspecialchars_decode($_GPC["goods"]), true);
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        //获取并保存二维码图片
//        m("fc")->fc_log_debug("img_share_preview：" . json_encode($goods), "img_share_preview");
        $res = $this->get_share_img($goods, 7);
        return $res["res"];
    }


    public function img_wxapp_member()
    {
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        if (!empty($uid)) {
            $bgpath = $_GPC["bgpath"];
            $bgpath = $this->_checkremote($bgpath);

            $filename = time() . ".jpg";
            $pictureurl = $this->imgurl . $filename;
            $this->_getimage($bgpath, $this->imgurl, $filename, 1);

            $wxapppage = empty($_GPC["wxapppage"]) ? "pages/home/index" : $_GPC["wxapppage"];
            load()->func("communication");
            $token = m("weixin")->weixin_get_accesstoken();
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $token;
            $par["scene"] = $uid;
            $par["page"] = $wxapppage;
            $par["width"] = 280;
            $par["auto_color"] = true;
            $c["r"] = 0;
            $c["g"] = 0;
            $c["b"] = 0;
            $par["line_color"] = $c;
            $qrpath = $this->dirurl . "qr_" . $uid . ".jpg";
            if (true) {
                $result = ihttp_post($url, json_encode($par));
                file_put_contents($qrpath, $result["content"]);
                $this->_resize_img($qrpath, $qrpath, 180, 180);
            }
            $qrres = $this->_getgmemberimg($uid, 1, $qrpath, $pictureurl);

            return result_data(0,"成功",['data'=>"{$qrres["res"]}?r=".time()]);
        }
        return result_data(1,"失败",['data'=>'']);
    }
    public function img_wxapp_cj()
    {
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        $id = $_GPC["id"];
        if (!empty($id) && !empty($uid)) {
            $info = pdo_fetch("select * from " . tablename("hjk_operate_choujiang") . " where uniacid=:uniacid and id=:id  limit 1", array(":uniacid" => $_W["uniacid"], ":id" => $id));
            if (empty($info)) {
                return '';
            }
            load()->func("communication");
            $token = m("weixin")->weixin_get_accesstoken();
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $token;
            $par["scene"] = $uid . "_" . $id;
            $par["page"] = "page/choujiang/lottery/index";
            $par["width"] = 480;
            $par["auto_color"] = true;
            $c["r"] = 0;
            $c["g"] = 0;
            $c["b"] = 0;
            $par["line_color"] = $c;
            $qrpath = $this->imgurl . "cj_qr_" . $uid . ".jpg";
            if (true) {
                $result = ihttp_post($url, json_encode($par));
                file_put_contents($qrpath, $result["content"]);
            }
            $cjinfopicture = explode(",", $info["goods_pic"]);
            $pic = '';
            if (count($cjinfopicture) > 0) {
                $pic = $cjinfopicture[0];
            }
            $time = '';
            if ($info["open_type"] == "0") {
                $time = "满 " . $info["open_count"] . "人 自动开奖";
            }
            if ($info["open_type"] == "1") {
                $time = date("m-d H:i", $info["open_at"]) . " 开奖";
            }
            $qrres = $this->_getcjimg($uid, $info["title"], $time, '', $pic, 7, $qrpath);
            return $qrres["res"];
        }
        return '';
    }
    public function _wxapp_goods_qr($goods)
    {
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        load()->func("communication");
        $access_token = m("weixin")->weixin_get_accesstoken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $par["scene"] = $goods["skuId"] . "_" . $uid . "_" . $goods["source_type"];
        $par["page"] = "mayiwo/pages/detail/index";
        $par["width"] = 231;
        $par["auto_color"] = true;
        $c["r"] = 0;
        $c["g"] = 0;
        $c["b"] = 0;
        $par["line_color"] = $c;
        $qrpath = IA_ROOT . "/addons/haojk_myw/cache/img/app_goods_qr_" . $goods["skuId"] . "_" . $goods["source_type"] . "_" . $uid . ".jpg";
        if (!file_exists($qrpath)) {
            $result = ihttp_post($url, json_encode($par));
            file_put_contents($qrpath, $result["content"]);
            $this->_resize_img($qrpath, $qrpath, 231, 231);
        }
        return $qrpath;
    }

    public function _wxapp_goods_pdd($goods)
    {
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        load()->func("communication");
        $access_token = m("weixin")->weixin_get_accesstoken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $par["scene"] = $goods["skuId"] . "_" . $uid . "_" . $goods["source_type"];
        $par["page"] = "pages/home/goods_detail";
        $par["width"] = 231;
        $par["auto_color"] = true;
        $c["r"] = 0;
        $c["g"] = 0;
        $c["b"] = 0;
        $par["line_color"] = $c;
        $qrpath = IA_ROOT . "/addons/qt_shop/cache/img/app_goods_qr_" . $goods["skuId"] . "_" . $goods["source_type"] . "_" . $uid . ".jpg";
        if (!file_exists($qrpath)) {
            $result = ihttp_post($url, json_encode($par));
            file_put_contents($qrpath, $result["content"]);
            $this->_resize_img($qrpath, $qrpath, 231, 231);
        }
        return $qrpath;
    }



    function _resize_img($img_src, $new_img_path, $new_width, $new_height)
    {
        $img_info = @getimagesize($img_src);
        if (!$img_info || $new_width < 1 || $new_height < 1 || empty($new_img_path)) {
            return false;
        }
        if (strpos($img_info["mime"], "jpeg") !== false) {
            $pic_obj = imagecreatefromjpeg($img_src);
        } else {
            if (strpos($img_info["mime"], "gif") !== false) {
                $pic_obj = imagecreatefromgif($img_src);
            } else {
                if (strpos($img_info["mime"], "png") !== false) {
                    $pic_obj = imagecreatefrompng($img_src);
                } else {
                    return false;
                }
            }
        }
        $pic_width = imagesx($pic_obj);
        $pic_height = imagesy($pic_obj);
        if (function_exists("imagecopyresampled")) {
            $new_img = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);
        } else {
            $new_img = imagecreate($new_width, $new_height);
            imagecopyresized($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);
        }
        if (preg_match("~.([^.]+)\$~", $new_img_path, $match)) {
            $new_type = strtolower($match[1]);
            switch ($new_type) {
                case "jpg":
                    imagejpeg($new_img, $new_img_path);
                    break;
                case "gif":
                    imagegif($new_img, $new_img_path);
                    break;
                case "png":
                    imagepng($new_img, $new_img_path);
                    break;
                default:
                    imagejpeg($new_img, $new_img_path);
            }
        } else {
            imagejpeg($new_img, $new_img_path);
        }
        imagedestroy($pic_obj);
        imagedestroy($new_img);
        return true;
    }
    public function _getgoodsimg($goods, $size = 7, $qrpath = '')
    {
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];

        $goods["wlPrice_after"] = number_format($goods["wlPrice_after"],2);
        $goods["wlPrice"] = number_format($goods["wlPrice"],2);

        $goods['avatar']=$_W['fans']['avatar'];
        $goods['nickname']=$_W['fans']['nickname'];
//        m("fc")->fc_log_debug("img_wxapp_goods：" . json_encode($goods), "img_wxapp_goods");

        if (empty($couponprice)) {
            $couponprice = 0.0;
        }
        if ($couponprice == "null") {
            $couponprice = 0.0;
        }
        $filename = time() . ".jpg";
        $pictureurl = $this->imgurl . $filename;
        $this->_getimage($goods["picUrl"], $this->imgurl, $filename, 1);
        $value = $goods["materiaUrl"];
        $errorCorrectionLevel = "L";
        $matrixPointSize = $size;
        $filename = $goods["skuId"] . "_" . $goods["source_type"] . "_" . $uid . "_exqrcode.jpg";
        $qrcode = $this->imgurl . $goods["skuId"] . "_" . $goods["source_type"] . "_qrcode.jpg";
        $ex_qr = $this->imgurl . $filename;
        $url = $this->url . $filename . "?t=" . time();
        if (empty($qrpath)) {
            QRcode::png($value, $qrcode, $errorCorrectionLevel, $matrixPointSize, 2);
        } else {
            $qrcode = $qrpath;
        }


        $bg = $this->dirurl . "pdd_bg_white.png";

        $bg = imagecreatefromstring(file_get_contents($bg));
        $bg_width = imagesx($bg);
        $bg_height = imagesy($bg);

        $fileuser = time() . "name.jpg";
        $pictureuser = $this->imgurl . $fileuser;
        $this->_getimage($goods["avatar"], $this->imgurl, $fileuser, 1);

        $src_img = imagecreatefromstring(file_get_contents($pictureuser));
        $w = imagesx($src_img);
        $h = imagesy($src_img);
        $w = $h = min($w, $h);

        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bgs = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bgs);
        $r   = $w / 2; //圆半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                    imagesetpixel($img, $x, $y, $rgbColor);
                }
            }
        }
        $picture_width = imagesx($src_img);
        $picture_height = imagesy($src_img);
        $logo_picture_width = 120;
        $logo_picture_height = 120;
        imagecopyresampled($bg, $img, 62, 64, 0, 0, $logo_picture_width, $logo_picture_height, $picture_width, $picture_height);


        $picture = imagecreatefromstring(file_get_contents($pictureurl));
        $picture_width = imagesx($picture);
        $picture_height = imagesy($picture);
        $logo_picture_width = 500;
        $logo_picture_height = 480;
        imagecopyresampled($bg, $picture, 120, 296, 0, 0, $logo_picture_width, $logo_picture_height, $picture_width, $picture_height);
        $QR = imagecreatefromstring(file_get_contents($qrcode));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_qr_width = $QR_width - 5;
        $logo_qr_height = $QR_height - 12;
        $src_x = 28;
        $src_y = $bg_height - $logo_qr_height - 28;
        imagecopyresampled($bg, $QR, $src_x, $src_y, 0, 0, $logo_qr_width, $logo_qr_height, $QR_width, $QR_height);
        $font = $this->dirurl . "msyh.ttf";
        $black = imagecolorallocate($bg, 28, 28, 28);
        $fontSize = 27;
        $circleSize = 0;
        $left = 118;
        $top = $bg_height - 510;
        $title1 = mb_substr($goods["skuName"], 0, 14, "utf-8");
        $title2 = mb_substr(str_replace($title1, '', $goods["skuName"]), 0, 14, "utf-8");
        $title3 = mb_substr(str_replace($title1.$title2, '', $goods["skuName"]), 0, 14, "utf-8");
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title1);
        $left = 118;
        $top = $bg_height - 460;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title2);
        $left = 118;
        $top = $bg_height - 410;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title3);

        $black = imagecolorallocate($bg, 56, 56, 56);
        $fontSize = 30;
        $left = 240;
        $top = 100;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $goods["nickname"]);

        $fontSize = 22;
        $left = 240;
        $top = 160;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, '我发现了一个好东西');


        $left = 240;
        $top = 210;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, '比拼多多还便宜, 推荐给你看看~');

        $black = imagecolorallocate ( $bg, 110,110,110);
        $fontSize = 22;
        $left = 118;
        $top = $bg_height - 290;
        if(strlen($goods["wlPrice"]) == 4){
            $line_x = 206;
        }else if(strlen($goods["wlPrice"]) == 5 ){
            $line_x = 226;
        }else if(strlen($goods["wlPrice"]) == 6 ){
            $line_x = 245;
        }else if(strlen($goods["wlPrice"]) == 7 ){
            $line_x = 265;
        }
        imageline($bg,120,$bg_height - 301,$line_x,$bg_height - 301,$black);
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, "￥". $goods["wlPrice"]);


        $black = imagecolorallocate($bg, 253, 81, 80);
        $fontSize = 22;
        $left = 118;
        $top = $bg_height - 325;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, "￥ " );

        $fontSize = 35;
        $left = 145;
        $top = $bg_height - 325;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $goods["wlPrice_after"]);

        $black = imagecolorallocate($bg, 59,59,59);
        $fontSize = 18;
        $left = 270;
        $top = $bg_height - 150;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, '购物前，先领券，享受拼多多内部价');
        $top = $bg_height - 110;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, '长按识别小程序码，发现更多优惠好物');
        imagejpeg($bg, $ex_qr, 75);
        return array("code" => "200", "msg" => "操作成功!", "result" => ['data'=>$url]);
    }

    public function get_share_img($goods, $size = 7)
    {
        global $_GPC, $_W;
        $uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
        if (empty($couponprice)) {
            $couponprice = 0.0;
        }
        if ($couponprice == "null") {
            $couponprice = 0.0;
        }
        $filename = time() . ".jpg";
        $pictureurl = $this->imgurl . $filename;
        $this->_getimage($goods["picUrl"], $this->imgurl, $filename, 0);
        $value = $goods["materiaUrl"];
        $errorCorrectionLevel = "L";
        $matrixPointSize = $size;
        $filename = $goods["skuId"] . "_" . $goods["source_type"] . "_" . $uid . "_exqrcode.jpg";
        $qrcode = $this->imgurl . $goods["skuId"] . "_" . $goods["source_type"] . "_qrcode.jpg";
        $ex_qr = $this->imgurl . $filename;
        $url = $this->url . $filename . "?t=" . time();
        if (empty($qrpath)) {
            QRcode::png($value, $qrcode, $errorCorrectionLevel, $matrixPointSize, 2);
        } else {
            $qrcode = $qrpath;
        }

        $bg = $this->dirurl . "pdd_bg_share.png";

        $bg = imagecreatefromstring(file_get_contents($bg));
        $bg_width = imagesx($bg);
        $bg_height = imagesy($bg);


        $picture = imagecreatefromstring(file_get_contents($pictureurl));
        $picture_width = imagesx($picture);
        $picture_height = imagesy($picture);
        $logo_picture_width = 260;
        $logo_picture_height = 260;
        imagecopyresampled($bg, $picture, 0, 0, 0, 0, $logo_picture_width, $logo_picture_height, $picture_width, $picture_height);


//        $QR = imagecreatefromstring(file_get_contents($qrcode));
//        $QR_width = imagesx($QR);
//        $QR_height = imagesy($QR);
//        $logo_qr_width = $QR_width - 5;
//        $logo_qr_height = $QR_height - 12;
//        $src_x = $bg_width - $logo_qr_width - 28;
//        $src_y = $bg_height - $logo_qr_height - 28;
//        imagecopyresampled($bg, $QR, $src_x, $src_y, 0, 0, $logo_qr_width, $logo_qr_height, $QR_width, $QR_height);

        $font = $this->dirurl . "msyh.ttf";

//        标题(换行处理)
        $black = imagecolorallocate($bg, 28, 28, 28);
        $fontSize = 28;
        $circleSize = 0;
        $left = 280;
        $top = 50;
        $title1 = mb_substr($goods["skuName"], 0, 8, "utf-8");
        $title2 = mb_substr(str_replace($title1, '', $goods["skuName"]), 0, 8, "utf-8");
        $title3 = mb_substr(str_replace($title1.$title2, '', $goods["skuName"]), 0, 8, "utf-8");
        $title4 = mb_substr(str_replace($title1.$title2.$title3, '', $goods["skuName"]), 0, 8, "utf-8");
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title1);
        $top = 98;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title2);
        $top = 148;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title3);
        $top = 198;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title4);

        //原价
        $black = imagecolorallocate($bg, 110,110,110);
        $fontSize = 22;

        if(strlen($goods["wlPrice_after"]) == 4){
            $left = 400;
        }else if(strlen($goods["wlPrice_after"]) == 5 ){
            $left = 420;
        }else if(strlen($goods["wlPrice_after"]) == 6){
            $left = 440;
        }else if(strlen($goods["wlPrice_after"]) == 7){
            $left = 460;
        }

        $top = 260;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, "￥". $goods["wlPrice"]);

        //现价
        $black = imagecolorallocate($bg, 253, 81, 80);
        $fontSize = 26;

        $left = 280;
        $top = 260;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, "￥".$goods["wlPrice_after"]);


        $black = imagecolorallocate($bg, 255, 255, 255);
        $top = 375;
        $fontSize = 22;

        if(strlen($goods["discount"]) == 5 || strlen($goods["discount"]) == 4){
            $left = 135;
        }else if(strlen($goods["discount"]) == 6){
            $left = 150;
        }else if(strlen($goods["discount"]) == 7){
            $left = 170;
        }
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, '元券');


        if(strlen($goods["wlCommission"]) == 4){
            $left = 395;
        }else if(strlen($goods["wlCommission"]) == 5){
            $left = 400;
        }else if(strlen($goods["wlCommission"]) == 6){
            $left = 420;
        }else    if(strlen($goods["wlCommission"])==7){
            $left = 435;
        }
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font,'元返利 (最高)');

        //优惠券
        $fontSize = 30;
        $left = 20;

        if(strlen($goods["discount"]) == 4){
            $left = 50;
        }else if(strlen($goods["discount"]) == 5){
            $left = 25;
        }
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $goods["discount"]);


        //返利
        $fontSize = 30;
        $left = 295;
        if(strlen($goods["wlCommission"]) == 4){
            $left = 310;
        }else if(strlen($goods["wlCommission"]) == 6 ||strlen($goods["wlCommission"]) == 5){
            $left = 290;
        }else if(strlen($goods["wlCommission"])>=7){
            $left = 280;
        }
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $goods["wlCommission"]);



        imagejpeg($bg, $ex_qr, 75);
        return array("code" => "200", "msg" => "操作成功!", "res" => $url);
    }


    public function _getgmemberimg($uid, $iswxapp = 0, $qrpath = '', $bgpath = '')
    {
        global $_W;
        $uri = url("entry", array("m" => "nets_haojk", "do" => "index", "from_uid" => $uid));
        $value = $_W["siteroot"] . substr($uri, 1, strlen($uri));
        $errorCorrectionLevel = "L";
        $matrixPointSize = 8;
        $filename = $uid . "_exqrcode.jpg";
        $qrcode = $this->imgurl . $uid . "_qrcode.jpg";
        if ($iswxapp == 1) {
            $filename = $uid . "_appqrcode.jpg";
            $qrcode = $this->imgurl . "qr_" . $uid . ".jpg";
        }
        $ex_qr = $this->imgurl . $filename;
        $url = $this->url . $filename;
        if (file_exists($ex_qr)) {
        }
        if ($iswxapp != 1) {
            QRcode::png($value, $qrcode, $errorCorrectionLevel, $matrixPointSize, 2);
        }
        if (!empty($qrpath)) {
            $qrcode = $qrpath;
        }
        $bg = $this->dirurl . "pdd_bg_white.png";
        $uniac_bg = $this->dirurl . $_W["uniacid"] . "_bg.png";
        if (file_exists($uniac_bg)) {
            $bg = $uniac_bg;
        }
        if (!empty($bgpath)) {
            $bg = $bgpath;
        }
        $QR = imagecreatefromstring(file_get_contents($qrcode));
        $bg = imagecreatefromstring(file_get_contents($bg));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $bg_width = imagesx($bg);
        $bg_height = imagesy($bg);
        $logo_qr_width = $QR_width;
        $scale = $bg_width / $logo_qr_width;
        $logo_qr_height = $QR_height;
        $src_x = $bg_width - $logo_qr_width - 150;
        $src_y = $bg_height - $logo_qr_height - 10;
        imagecopyresampled($bg, $QR, $src_x, $src_y, 0, 0, $logo_qr_width, $logo_qr_height, $QR_width, $QR_height);
        imagejpeg($bg, $ex_qr);
        return array("code" => "200", "msg" => "操作成功!", "res" => $url);
    }
    public function _getcjimg($uid, $title, $time, $url, $picture, $size = 7, $qrpath = '')
    {
        if (empty($couponprice)) {
            $couponprice = 0.0;
        }
        if ($couponprice == "null") {
            $couponprice = 0.0;
        }
        $filename = time() . ".jpg";
        $pictureurl = $this->imgurl . $filename;
        $this->_getimage($picture, $this->imgurl, $filename, 0);
        $value = $url;
        $errorCorrectionLevel = "L";
        $matrixPointSize = $size;
        $filename = $uid . "_cj_exqrcode.jpg";
        $qrcode = $this->imgurl . $uid . "_cj_qrcode.jpg";
        $ex_qr = $this->imgurl . $filename;
        $url = $this->url . $filename;
        if (empty($qrpath)) {
            QRcode::png($value, $qrcode, $errorCorrectionLevel, $matrixPointSize, 2);
        } else {
            $qrcode = $qrpath;
        }
        $bg = $this->dirurl . "helpset_bg.jpg";
        $bg = imagecreatefromstring(file_get_contents($bg));
        $bg_width = imagesx($bg);
        $bg_height = imagesy($bg);
        $picture = imagecreatefromstring(file_get_contents($pictureurl));
        $picture_width = imagesx($picture);
        $picture_height = imagesy($picture);
        $logo_picture_width = 900;
        $logo_picture_height = 520;
        $src_x = 119;
        $src_y = 65;
        imagecopyresampled($bg, $picture, $src_x, $src_y, 0, 0, $logo_picture_width, $logo_picture_height, $picture_width, $picture_height);
        $QR = imagecreatefromstring(file_get_contents($qrcode));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_qr_width = $QR_width - 70;
        $logo_qr_height = $QR_height - 70;
        $src_x = $bg_width - $logo_qr_width - 385;
        $src_y = $bg_height - $logo_qr_height - 485;
        imagecopyresampled($bg, $QR, $src_x, $src_y, 0, 0, $logo_qr_width, $logo_qr_height, $QR_width, $QR_height);
        $font = $this->dirurl . "msyh.ttf";
        $black = imagecolorallocate($bg, 245, 245, 245);
        $fontSize = 30;
        $circleSize = 0;
        $left = 130;
        $top = 628;
        $title1 = mb_substr($title, 0, 18, "utf-8");
        $newtitle1 = $title1 . "         ";
        $title2 = mb_substr(str_replace($title1, '', $title), 0, 18, "utf-8");
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $newtitle1);
        $top = 680;
        imagefttext($bg, $fontSize, $circleSize, $left, $top, $black, $font, $title2);
        $black = imagecolorallocate($bg, 224, 245, 245);
        $fontSize = 30;
        $left = 700;
        $top2 = 680;
        $time = '' . $time;
        imagefttext($bg, $fontSize, $circleSize, $left, $top2, $black, $font, $time);
        imagejpeg($bg, $ex_qr, 75);
        $url .= "?v=" . time();
        return array("code" => "200", "msg" => "操作成功!", "res" => $url);
    }
    function _getimage($url, $save_dir = '', $filename = '', $type = 0)
    {
        if (trim($url) == '') {
            return array("file_name" => '', "save_path" => '', "error" => 1);
        }
        if (trim($save_dir) == '') {
            $save_dir = "./";
        }
        if (trim($filename) == '') {
            $ext = strrchr($url, ".");
            if ($ext != ".gif" && $ext != ".jpg") {
                return array("file_name" => '', "save_path" => '', "error" => 3);
            }
            $filename = time() . $ext;
        }
        if (0 !== strrpos($save_dir, "/")) {
            $save_dir .= "/";
        }

        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return array("file_name" => '', "save_path" => '', "error" => 5);
        }

        m("fc")->fc_log_debug("img_wxapp_goods：" . $url, "img_wxapp_goods");

        if ($type) {

            $ch = curl_init();
            $timeout = 0;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        $fp2 = @fopen($save_dir . $filename, "a");
        fwrite($fp2, $img);
        fclose($fp2);
        unset($img, $url);
        return array("file_name" => $filename, "save_path" => $save_dir . $filename, "error" => 0);
    }
    function _checkremote($imgurl)
    {
        global $_W;
        $remoteurl = '';
        if ($_W["setting"]["remote"]["type"] == 1) {
            $remoteurl = $_W["setting"]["remote"]["ftp"]["url"];
        }
        if ($_W["setting"]["remote"]["type"] == 2) {
            $remoteurl = $_W["setting"]["remote"]["alioss"]["url"];
        }
        if ($_W["setting"]["remote"]["type"] == 3) {
            $remoteurl = $_W["setting"]["remote"]["qiniu"]["url"];
        }
        if ($_W["setting"]["remote"]["type"] == 4) {
            $remoteurl = $_W["setting"]["remote"]["cos"]["url"];
        }
        if (!empty($remoteurl)) {
            $localurl = $this->imgurl . "bg/";
            $filenames = explode("/", $imgurl);
            $filename = $filenames[count($filenames) - 1];
            $newfile = $localurl . $filename;
            if (!file_exists($newfile)) {
                $this->_getimage($imgurl, $localurl, $filename);
            }
            if (file_exists($newfile)) {
                $imgurl = $this->url . "bg/" . $filename;
            }
        }
        return $imgurl;
    }
}