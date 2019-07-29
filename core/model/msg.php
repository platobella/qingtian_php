<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
include_once NETS_HAOJIK_PATH . "/function/wxBizMsgCrypt.php";
class Msg_NetsHaojkModel
{
	public $jdwenan;
	public $pddwenan;
	public $mgjwenan;
	public function __construct()
	{
		$this->jdwenan = "[{\"wenan\":\"【京东商城】[名称] ###————————###京东价：¥[价格]###内购价：¥[券后价]###领券优惠购买：[二合一链接] ###————————###京东商城 正品保证\"}\n\t,{\"wenan\":\"【京东】 [名称] ###--------------------###京东价：[价格]元###券后价：[券后价]元###领券下单：[二合一链接] ###--------------------###【推荐理由】[推荐]\"}\n\t,{\"wenan\":\"【JD】[名称] ###————————###京东价：¥[价格]###内购价：¥[券后价]###领券优惠购买：[二合一链接] ###————————###京东商城 正品保证\"}\n\t,{\"wenan\":\"【JD】 [名称] ###--------------------###京东价：[价格]元###券后价：[券后价]元###领券下单：[二合一链接] ###--------------------###【推荐理由】[推荐]\"}]";
		$this->pddwenan = "[{\"wenan\":\"【拼多多】[名称] ###————————###单购价：¥[价格]###拼购价：¥[券后价]###领券优惠购买：[二合一链接] ###————————###【推荐理由】[推荐]###拼多多 拼着买更便宜\"}\n\t,{\"wenan\":\"【拼多多】 [名称] ###--------------------###单购价：[价格]元###拼购价：[券后价]元###领券下单：[二合一链接] ###--------------------###【推荐理由】[推荐]###拼多多 拼着买更便宜\"}\n\t,{\"wenan\":\"【PDD】[名称] ###————————###单购价：¥[价格]###拼购价：¥[券后价]###领券优惠购买：[二合一链接] ###————————###【推荐理由】[推荐]###拼多多 拼多多 省得多\"}\n\t,{\"wenan\":\"【PDD】 [名称] ###--------------------###单购价：[价格]元###拼购价：[券后价]元###领券下单：[二合一链接] ###--------------------###【推荐理由】[推荐]###拼多多 拼多多 省得多\"}]";
		$this->mgjwenan = "[{\"wenan\":\"【蘑菇街】[名称] ###————————###单购价：¥[价格]###拼购价：¥[券后价]###领券优惠购买：[二合一链接] ###————————###【推荐理由】[推荐]###蘑菇优选，选你所爱\"}\n\t,{\"wenan\":\"【蘑菇街】 [名称] ###--------------------###单购价：[价格]元###拼购价：[券后价]元###领券下单：[二合一链接] ###--------------------###【推荐理由】[推荐]###择优选好物，品质过生活\"}\n\t,{\"wenan\":\"【MGJ】[名称] ###————————###单购价：¥[价格]###拼购价：¥[券后价]###领券优惠购买：[二合一链接] ###————————###【推荐理由】[推荐]###生活的品质，源于不将就的优选\"}\n\t,{\"wenan\":\"【MGJ】 [名称] ###--------------------###单购价：[价格]元###拼购价：[券后价]元###领券下单：[二合一链接] ###--------------------###【推荐理由】[推荐]###精挑细选，尽在蘑菇优选\"}]";
	}
	public function msg_server()
	{

		global $_GPC, $_W;
		$token = $_W["uniaccount"]["token"];
		$encodingaeskey = $_W["uniaccount"]["encodingaeskey"];
		$key = $_W["uniaccount"]["key"];
		$pc = new WXBizMsgCrypt($token, $encodingaeskey, $key);
		$signature = $_GPC["signature"];
		$timestamp = $_GPC["timestamp"];
		$nonce = $_GPC["nonce"];
		$res = $pc->checkSignature($signature, $timestamp, $nonce);
		if (isset($_GPC["echostr"])) {
			$echoStr = $_GPC["echostr"];
			if ($res) {
				echo $echoStr;
				exit;
			}
		} else {

			$this->responseMsg();
		}
		exit;
	}
	public function test()
	{
		global $_GPC, $_W;
	}
	private function responseMsg()
	{
		global $_GPC, $_W;
		$global = m("fc")->fc_getglobal();
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		$receive = $_GPC["__input"];
		$msg["touser"] = $receive["FromUserName"];
		$member = m("member")->member_infobyopenid($msg["touser"]);
		if (!empty($member)) {
			$_GPC["uid"] = $member["uid"];
		}
		$access_token = m("weixin")->weixin_get_accesstoken();

        m("fc")->fc_log_debug("checkAuthKeywork：" . json_encode($receive), "checkAuthKeywork");


		if ($receive["MsgType"] == "text") {
			$receivetext = $receive["Content"];



            //淘宝授权
            $taobao_msg = $this->checkAuthKeywork($receivetext, $msg);
            if (!empty($taobao_msg)) {
                m("weixin")->weixin_send_msg($taobao_msg, $access_token);
                return;
            }

            //系统关键词
			$msg = $this->organizeMsg($receivetext, $msg);
			if (!empty($msg)) {
				m("weixin")->weixin_send_msg($msg, $access_token);
				return;
			}



			m("fc")->fc_log_debug("responseMsg__content：" . $receivetext, "responseMsg");
			$msg = $this->checkkeywordsearchgoods($receivetext);
			if (!empty($msg)) {
				$this->sendgoodsmsg($msg, $receive["FromUserName"], $access_token);
				return;
			} else {
				$msg = $this->checkdetailgoods($receivetext);
				if (!empty($msg)) {
					$this->sendgoodsmsg($msg, $receive["FromUserName"], $access_token);
					return;
				} else {
					$msg = $this->othermsggoods($receivetext);
					if (count($msg["goods"]) > 2) {
						$content = "正在为您匹配商品信息，请稍等。";
						$msg["msgtype"] = "text";
						$msgcontent["content"] = urlencode($content);
						$msg["text"] = $msgcontent;
						$msg["touser"] = $receive["FromUserName"];
						m("weixin")->weixin_send_msg($msg, $access_token);
					}
                    m("fc")->fc_log_debug("othermsggoods：" . json_encode($msg), "othermsggoods");

					if (!empty($msg)) {

						if ($msg["wenan"] == "1" && !empty($msg["goods"])) {
							$content = $receivetext;
							$unionok = false;
							foreach ($msg["goods"] as $good) {
								$_GPC["source_type"] = $good["source_type"];
								$_GPC["skuid"] = $good["skuid"];
								if ($good["couponurl"] != '') {
									$_GPC["couponurl"] = $good["couponurl"];
								}
								$_GPC["custom_parameters"] = $uid;
								$res = m("apicloud")->api_getunionurl();
								if ($res["status_code"] == "200") {
									$shorturl = $res["data"];
									if ($good["source_type"] == "3") {
										$shorturl = $res["unionurl"]["url"];
									}
									$content = str_replace($good["url"], $shorturl, $content);
									$unionok = true;
								}
							}
							if (!$unionok) {
								if (!empty($global["service_msg"])) {
									$content = $global["service_msg"];
								} else {
									$content = "对不起，没找到对应的信息！";
								}
							}
							$msg["msgtype"] = "text";
							$msgcontent["content"] = urlencode($content);
							$msg["text"] = $msgcontent;
							$msg["touser"] = $receive["FromUserName"];
							m("weixin")->weixin_send_msg($msg, $access_token);
							return;
						} else {
							if ($msg["wenan"] == '' && !empty($msg["goods"])) {
								foreach ($msg["goods"] as $good) {


									if ($good["urltype"] == "1") {
										$_GPC["source_type"] = $good["source_type"];
										$_GPC["skuid"] = $good["skuid"];
										if ($good["couponurl"] != '') {
											$_GPC["couponurl"] = $good["couponurl"];
										}
										$_GPC["custom_parameters"] = $uid;
										$res = m("apicloud")->api_getunionurl();

                                        m('fc')->fc_log_debug('api_getunionurl：'.json_encode($res),'api_getunionurl');
										if ($res["status_code"] == "200") {
											$shorturl = $res["data"];
											if ($good["source_type"] == "3") {
												$shorturl = $res["unionurl"]["url"];
											}
											$msg["msgtype"] = "text";
											$msgcontent["content"] = urlencode($shorturl);
											$msg["text"] = $msgcontent;
											$msg["touser"] = $receive["FromUserName"];
											m("weixin")->weixin_send_msg($msg, $access_token);
										}
									} else {
										$_GPC["source_type"] = $good["source_type"];
										$_GPC["skuid"] = $good["skuid"];

										$res = m("apicloud")->api_goodsdetail();

                                        m('fc')->fc_log_debug('api_getunionurl：'.json_encode($res),'api_getunionurl');
										if (!empty($res['result']["data"])) {
											if ($good["couponurl"] != '') {
												$_GPC["couponurl"] = $good["couponurl"];
											} else {
												$_GPC["couponurl"] = '';
											}
											$msgtext = $this->organize_goods_wenan($res['result']["data"]);
											$msgimg = $this->organize_goods_img($res['result']["data"]);
											$msg = array("msgtext" => $msgtext, "msgimg" => $msgimg);
											$this->sendgoodsmsg($msg, $receive["FromUserName"], $access_token);
										}
									}
								}
								return;
							}
						}
					}
				}
			}
		}
		$msg["msgtype"] = "text";
		$text = "亲，您好！";
		if (!empty($global["service_msg"])) {
			$text = $global["service_msg"];
		}
		$msgcontent["content"] = urlencode($text);
		$msg["text"] = $msgcontent;
		m("weixin")->weixin_send_msg($msg, $access_token);
	}
	public function sendgoodsmsg($msg, $receive, $access_token)
	{
		m("fc")->fc_log_debug("msgtext：" . json_encode($msg["msgtext"]), "responseMsg");
		if (!empty($msg["msgtext"])) {
			$msg["msgtext"]["touser"] = $receive;
			m("weixin")->weixin_send_msg($msg["msgtext"], $access_token);
		}
		if (!empty($msg["msgimg"])) {
			$msg["msgimg"]["touser"] = $receive;
			m("weixin")->weixin_send_msg($msg["msgimg"], $access_token);
		}
	}
	public function othermsggoods($content = '')
	{
		global $_GPC, $_W;
		if (empty($content)) {
			$content = $_GPC["content"];
		}
		$wenan = '';
		if (preg_match("/^[\\x{4e00}-\\x{9fa5}]+\$/u", $content) > 0) {
			$wenan = "2";
		} else {
			if (preg_match("/[\\x{4e00}-\\x{9fa5}]/u", $content) > 0) {
				$wenan = "1";
			} else {
				$wenan = '';
			}
		}
		$urlarr = array();
		$goods = array();
		if ($wenan == '') {
			$sourceurl = $this->UrlAPI("0", $content);
			if (strpos($content, $sourceurl) !== false) {
				$sourceurl = $content;
			}
			$urlarr[]["url"] = $sourceurl;
		} else {
			$regex = "/(http|https):\\/\\/[A-Za-z0-9\\-]+\\.[A-Za-z0-9]+[\\/=\\?%\\-&_~`@[\\]\\’:+!]*([^<>\\”])*\$/";
			$regex = "/(https|http)?:\\/\\/[\\w-.%#?\\/\\\\=]+/i";
			$regex = "/(https|http)?:\\/\\/[^ '\\n]+/i";
			if (preg_match_all($regex, $content, $matches)) {
				foreach ($matches[0] as $matche) {
					$sourceurl = $this->UrlAPI("0", $matche);
					if ($sourceurl != '') {
						if (strpos($content, $sourceurl) !== false) {
							$sourceurl = $matche;
						}
						if (strpos($matche, "coupon") !== false) {
							$urlarr[]["url"] = $matche;
						} else {
							$urlarr[]["url"] = $sourceurl;
						}
					} else {
						$urlarr[]["url"] = $matche;
					}
				}
			}
		}


		if (count($urlarr) == 1) {
			$goods[] = $this->_analysis_url($urlarr[0]["url"]);
		} else {
			if (count($urlarr) == 2) {
				$couponurl = '';
				if (strpos($urlarr[0]["url"], "coupon") !== false) {
					$couponurl = $urlarr[0]["url"];
					$goods[] = $this->_analysis_url($urlarr[1]["url"]);
				} else {
					if (strpos($urlarr[1]["url"], "coupon") !== false) {
						$couponurl = $urlarr[1]["url"];
						$goods[] = $this->_analysis_url($urlarr[0]["url"]);
					}
				}
				if (!empty($couponurl) && !empty($goods)) {
					$goods[0]["couponurl"] = $couponurl;
					$wenan = '';
				} else {
					foreach ($urlarr as $url) {
						$goods[] = $this->_analysis_url($url["url"]);
					}
				}
			} else {
				if (count($urlarr) > 2) {
					foreach ($urlarr as $url) {
						$goods[] = $this->_analysis_url($url["url"]);
					}
				}
			}
		}
		return array("wenan" => $wenan, "goods" => $goods);
	}
	private function _analysis_url($content)
	{
		$skuid = $source_type = $couponurl = $urltype = '';
		$contentarr = explode("?", $content);
		if (strpos($content, "yangkeduo.com") !== false) {
			$source_type = "2";
			if (count($contentarr) > 1) {
				parse_str($contentarr[1], $param);
				if (isset($param["goods_id"]) && !empty($param["goods_id"])) {
					$skuid = $param["goods_id"];
				}
			}
		} else {
			if (strpos($content, "mogujie.com") !== false) {
				$source_type = "3";
				if (count($contentarr) > 1) {
					parse_str($contentarr[1], $param);
					if (isset($param["itemId"]) && !empty($param["itemId"])) {
						$skuid = $param["itemId"];
					}
					if (isset($param["promid"]) && !empty($param["promid"])) {
						$couponurl = $param["promid"];
					}
				} else {
					$skuidarr = explode("/", $contentarr[0]);
					$skuid = $skuidarr[count($skuidarr) - 1];
				}
			} else {
				if (strpos($content, "item.jd.com") !== false || strpos($content, "jingfen.jd.com") !== false || strpos($content, "wqitem.jd.com") !== false || strpos($content, "wqs.jd.com") !== false || strpos($content, "item.m.jd.com") !== false) {
					$source_type = "1";
					if (count($contentarr) > 1) {
						parse_str($contentarr[1], $param);
						if (isset($param["sku"]) && !empty($param["sku"])) {
							$skuid = $param["sku"];
						} else {
							$skuidarr = explode("/", $contentarr[0]);
							$skuid = $skuidarr[count($skuidarr) - 1];
							$skuid = str_replace(".html", '', $skuid);
						}
					} else {
						$skuidarr = explode("/", $contentarr[0]);
						$skuid = $skuidarr[count($skuidarr) - 1];
						$skuid = str_replace(".html", '', $skuid);
					}
				} else {
					if (strpos($content, "union-click.jd.com") !== false) {
						$source_type = "1";
						$urltype = "1";
						$skuid = $content;
						$couponurl = $content;
					} else {
						if (strpos($content, "u.jd.com") !== false) {
							$source_type = "1";
							$urltype = "1";
							$skuid = $content;
							$couponurl = $content;
						}
					}
				}
			}
		}
		return array("url" => $content, "skuid" => $skuid, "couponurl" => $couponurl, "source_type" => $source_type, "urltype" => $urltype);
	}
	private function organizeMsg($keyword, $msg)
	{
		global $_GPC, $_W;
		$_GPC["keyword"] = $keyword;
		$helpinfo = m("fc")->fc_help_keywordinfo();
		$text = "亲，您好！";
		$msgcontent["content"] = urlencode($text);
		$msg["text"] = $msgcontent;
		$msg["msgtype"] = "text";
		if (!empty($helpinfo)) {
			$msg["msgtype"] = "link";
			$msgcontent["title"] = urlencode($helpinfo["title"]);
			if (!empty($helpinfo["remark"])) {
				$msgcontent["description"] = urlencode($helpinfo["remark"]);
			}
			$msgcontent["url"] = "{$_W["siteroot"]}app/index.php?i={$_W["uniacid"]}&c=entry&do=helpmsg&m=qt_shop&k={$helpinfo["keyword"]}&id={$helpinfo["id"]}";
			if (!empty($helpinfo["picture"])) {
				$msgcontent["thumb_url"] = $helpinfo["picture"];
			}
			$msg["link"] = $msgcontent;
		}
		if (!empty($keyword) && empty($helpinfo)) {
			return '';
		}
		m("fc")->fc_log_debug("organizeMsg_msg：" . json_encode($msg), "responseMsg");
		return $msg;
	}


    private function checkAuthKeywork($keyword, $msg)
    {
        global $_GPC, $_W;
        if($keyword != "授权"){
            return '';
        }

        $url = murl("entry/site/auth",
            [   'm'=>'qt_shop',
                'uid'=>$_GPC['uid'],
            ]
            ,true,true);

        $msg["msgtype"] = "link";
        $msgcontent["title"] = urlencode("绑定授权");

        $msgcontent["description"] =urlencode("请点击链接，绑定授权");

        $msgcontent["url"] = "{$url}";

        $msgcontent["thumb_url"] = "http://shop.qt666.top/taobao.png";

        $msg["link"] = $msgcontent;


        m("fc")->fc_log_debug("checkAuthKeywork：" . json_encode($msg), "responseMsg");
        return $msg;
    }




	public function checkkeywordsearchgoods($receivetext)
	{
		global $_GPC, $_W;

		$firsttext = mb_substr($receivetext, 0, 2, "utf-8");
		$len = mb_strlen($receivetext, "utf-8");
		m("fc")->fc_log_debug("responseMsg__strlen：" . mb_strlen($receivetext, "utf-8"), "responseMsg");
		$text = "亲，您好！";
		if ($firsttext == "京东") {
			$keyword = trim(mb_substr($receivetext, 2, $len, "utf-8"));
			$_GPC["keyword"] = $keyword;
			$_GPC["source_type"] = "1";
			$text = "京东搜素关键词:" . $keyword . "！";
		} else {
			$firsttext = mb_substr($receivetext, 0, 3, "utf-8");
			$keyword = trim(mb_substr($receivetext, 3, $len, "utf-8"));
			if ($firsttext == "拼多多") {
				$_GPC["keyword"] = $keyword;
				$_GPC["source_type"] = "2";
				$text = "拼多多搜素关键词:" . $keyword . "！";
			}
			if ($firsttext == "蘑菇街") {
				$_GPC["keyword"] = $keyword;
				$_GPC["source_type"] = "3";
				$text = "蘑菇街搜素关键词:" . $keyword . "！";
			}
		}
		m("fc")->fc_log_debug("responseMsg__input_text：" . $text, "responseMsg");
		if (!empty($_GPC["source_type"])) {
			$_GPC["pagesize"] = "1";

			$_GPC["iscoupon"] = "0";
			$res = m("apicloud")->goods_listall();
			if (count($res["data"]) > 0) {
                $rand_index = rand(0, count($res["data"]) - 1);
				$msgtext = $this->organize_goods_wenan($res["data"][$rand_index]);
				$msgimg = $this->organize_goods_img($res["data"][$rand_index]);
				return array("msgtext" => $msgtext, "msgimg" => $msgimg);
			}
		}
		return '';
	}
	private function checkdetailgoods($receivetext)
	{
		global $_GPC, $_W;
		$content_ex = explode(":", $receivetext);
		$goodtype = '';
		$skuId = '';
		if (count($content_ex) == 2) {
			$skuId = $content_ex[1];
			$goodtype = $content_ex[0];
		}
		if (empty($goodtype) || empty($skuId)) {
			return '';
		}
		$_GPC["source_type"] = '';
		if ($goodtype == "jd") {
			$_GPC["skuid"] = $skuId;
			$_GPC["source_type"] = "1";
		} else {
			if ($goodtype == "pdd") {
				$_GPC["skuid"] = $skuId;
				$_GPC["source_type"] = "2";
			} else {
				if ($goodtype == "mgj") {
					$_GPC["skuid"] = $skuId;
					$_GPC["source_type"] = "3";
				}
			}
		}
		if (!empty($_GPC["source_type"])) {
			$res = m("apicloud")->api_goodsdetail();
			if ($res["status_code"] == "200" && !empty($res["data"])) {
				$msgtext = $this->organize_goods_wenan($res["data"]);
				$msgimg = $this->organize_goods_img($res["data"]);
				return array("msgtext" => $msgtext, "msgimg" => $msgimg);
			}
		}
		return '';
	}
	private function organize_goods_wenan($goods)
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		$wenan = json_decode($this->jdwenan, true)[rand(0, 3)]["wenan"];
		if ($goods["source_type"] == "2") {
			$wenan = json_decode($this->pddwenan, true)[rand(0, 3)]["wenan"];
		}
		if ($goods["source_type"] == "3") {
			$wenan = json_decode($this->mgjwenan, true)[rand(0, 3)]["wenan"];
		}
		if ($goods["source_type"] != "2") {
			if (!empty($goods["couponList"])) {
				$_GPC["couponurl"] = $goods["couponList"];
			}
		}
		$_GPC["source_type"] = $goods["source_type"];
		$_GPC["skuid"] = $goods["skuId"];
		$_GPC["custom_parameters"] = $uid;
		$res = m("apicloud")->api_getunionurl();
        m("fc")->fc_log_debug("responseMsg__unionurl ：" . json_encode($res), "responseMsg");
		if ($res["status_code"] == "200") {
			$shorturl = $res["data"];
			if ($goods["source_type"] == "3") {
				$shorturl = $res["unionurl"]["url"];
			}
			$wenan = str_replace("###", "\\n", $wenan);
			$wenan = str_replace("[名称]", $goods["skuName"], $wenan);
			$wenan = str_replace("[价格]", $goods["wlPrice"], $wenan);
			$wenan = str_replace("[券后价]", $goods["wlPrice_after"], $wenan);
			$wenan = str_replace("[二合一链接]", $shorturl, $wenan);
			$wenan = str_replace("[推荐]", $goods["skuDesc"], $wenan);
			$msg["msgtype"] = "text";
			$msgcontent["content"] = urlencode($wenan);
			$msg["text"] = $msgcontent;
			return $msg;
		}
		return '';
	}
	private function organize_goods_img($goods)
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		m("img")->img_wxapp_goods($goods);
		$postpath = "/addons/qt_shop/cache/img/" . $goods["skuId"] . "_" . $goods["source_type"] . "_" . $uid . "_exqrcode.jpg";
		$res = m("weixin")->weixin_uploadimage($postpath);
		if ($res["code"] == 200) {
			$media_id = $res["value"];
			$imgmsg["msgtype"] = "image";
			$imgmsg_image["media_id"] = $media_id;
			$imgmsg["image"] = $imgmsg_image;
			return $imgmsg;
		}
		return '';
	}
	private function UrlAPI($type, $url)
	{
		if ($type) {
			$baseurl = "http://www.newjson.com/Change/ShortUrl?method=encode&url=" . $url;
		} else {
			$baseurl = "http://www.newjson.com/Change/ShortUrl?method=decode&url=" . $url;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $baseurl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = array("url" => $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$strRes = curl_exec($ch);
		curl_close($ch);
		$arrResponse = json_decode($strRes, true);
		if (isset($arrResponse[0])) {
			return $arrResponse[0];
		}
		if (isset($arrResponse["content"])) {
			parse_str($arrResponse["content"], $parr);
			if (isset($parr["returnurl"])) {
				return $parr["returnurl"];
			}
			return $arrResponse["content"];
		} else {
			return '';
		}
	}
}