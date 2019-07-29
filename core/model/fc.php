<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Fc_NetsHaojkModel
{
	public function fc_getglobal()
	{
		global $_W;
		$global = $this->fc_cache_load("qt_operate_sysset");
		if (empty($global)) {
			$global = pdo_get("qt_operate_sysset", array("uniacid" => $_W["uniacid"]));
			$global["homepage_itemjson"] = htmlspecialchars_decode($global["homepage_itemjson"]);
            $global["homepage_my_itemjson"] = htmlspecialchars_decode($global["homepage_my_itemjson"]);
			$global["memberposter"] = htmlspecialchars_decode($global["memberposter"]);
			$global["JD_APPID"] = JD_APPID;
			$global["JD_CUSTOMERINFO"] = JD_CUSTOMERINFO;
			$global["MGJ_APPID"] = MGJ_APPID;
			$list = pdo_fetchall("select * from " . tablename("qt_operate_keyword") . " where  uniacid=:uniacid and state =1  ORDER BY orderby DESC limit 0,10", array(":uniacid" => $_W["uniacid"]));
			$global["help_keyword"] = $list;
			$vipremarklist = pdo_fetchall("select * from " . tablename("qt_operate_vipremark") . " where  uniacid=:uniacid ", array(":uniacid" => $_W["uniacid"]));
			$global["vipremark"] = $vipremarklist;
			$this->fc_cache_write("qt_operate_sysset", $global, 60);
		}
		return $global;
	}
	public function fc_help_keyword()
	{
		global $_GPC, $_W;
		$page = empty($_GPC["page"]) ? 1 : $_GPC["page"];
		$pagesize = empty($_GPC["pagesize"]) ? 20 : $_GPC["pagesize"];
		$where = " and state =1 ";
		$keyword = $_GPC["keyword"];
		if (!empty($keyword)) {
			$where = "and (label like '%" . $keyword . "%' or title like '%" . $keyword . "%' or keyword like '%" . $keyword . "%')";
		}
		$list = pdo_fetchall("select * from " . tablename("qt_operate_keyword") . " where uniacid=:uniacid " . $where . " order by orderby desc limit " . ($page - 1) * $pagesize . "," . $pagesize, array(":uniacid" => $_W["uniacid"]));
		$totalcount = pdo_fetchcolumn("SELECT COUNT( id) count FROM " . tablename("qt_operate_keyword") . "  where uniacid=:uniacid  " . $where, array(":uniacid" => $_W["uniacid"]));
		return array("data" => $list, "total" => $totalcount, "message" => "success", "status_code" => 200);
	}
	public function fc_help_keywordinfo()
	{
		global $_GPC, $_W;
		$keyword = $_GPC["keyword"];
		$where = " and state =1 ";
		if (!empty($keyword)) {
			$where .= "and (label = '" . $keyword . "' or title = '" . $keyword . "' or keyword = '" . $keyword . "')";
		} else {
			$where .= " and   keyword = ''";
		}
		$info = pdo_fetch("select * from " . tablename("qt_operate_keyword") . " where uniacid=:uniacid " . $where . " order by orderby desc limit 1 ", array(":uniacid" => $_W["uniacid"]));
		return $info;
	}
	public function fc_formid()
	{
		global $_GPC, $_W;
		$uid = empty($_GPC["uid"]) ? $_W["fans"]["uid"] : $_GPC["uid"];
		if (empty($_GPC["formid"]) || empty($uid)) {
			return array("data" => '', "message" => "参数错误", "status_code" => 200);
		}
		$data["uid"] = $uid;
		$data["uniacid"] = $_W["uniacid"];
		$data["formid"] = $_GPC["formid"];
		$data["openid"] = $_GPC["openid"];
		$data["nickname"] = $_GPC["nickname"];
		$data["avatar"] = $_GPC["avatar"];
		$data["get_type"] = $_GPC["get_type"];
		$data["status"] = 0;
		$data["created_at"] = time();
		$i = pdo_insert("qt_operate_formid", $data);
		if ($i) {
			return array("data" => $i, "message" => "success", "status_code" => 200);
		}
		return array("data" => $i, "message" => "error", "status_code" => 200);
	}
	public function fc_cache_load($key)
	{
		global $_W;
		$prefix = "myw" . $_W["uniacid"];
		$cache = cache_load($prefix . $key);
		if (!empty($cache)) {
			if (isset($cache["expire"]) && floatval($cache["expire"]) <= time() && floatval($cache["expire"]) > 0) {
				cache_delete($prefix . $key);
				return '';
			}
		}
		if (!empty($cache["items"])) {
			return $cache["items"];
		} else {
			return $cache;
		}
	}
	public function fc_cache_write($key, $value, $time = 60)
	{
		global $_W;
		$prefix = "myw" . $_W["uniacid"];
		if ($time > 0) {
			$time = TIMESTAMP + $time;
		}
		cache_write($prefix . $key, array("expire" => $time, "items" => $value));
	}
	public function fc_cache_delete($key)
	{
		global $_W;
		$prefix = "myw" . $_W["uniacid"];
		cache_delete($prefix . $key);
	}
	public function fc_result($errno, $message, $data = '')
	{
		exit(json_encode(array("errno" => $errno, "message" => $message, "result" => $data)));
	}
    public function fc_json($data)
    {
        exit(json_encode($data));
    }

	function fc_log_debug($msg, $filename = '')
	{
		if (NETS_HAOJIK_DEBUG) {
			$filename = "debug_" . date("Ymd") . "_" . $filename . "_log.txt";
			$msg = "\r\n" . date("Y-m-d H:i:s", intval(time())) . "\t\r" . $msg . "\r\n";
			$filename = NETS_HAOJIK_PATH . "/cache/logs/" . $filename;
			file_put_contents($filename, $msg, FILE_APPEND);
		}
	}
	function fc_log($msg, $filename = '')
	{
		$filename = date("Ymd") . $filename . "_log.txt";
		$msg = "\r\n" . date("Y-m-d H:i:s", intval(time())) . "\t\r" . $msg . "\r\n";
		$filename = NETS_HAOJIK_PATH . "/cache/logs/" . $filename;
		file_put_contents($filename, $msg, FILE_APPEND);
	}
	public function fc_curl($url, $postFields = null)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->readTimeout) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
		}
		if ($this->connectTimeout) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
		}
		if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		if (is_array($postFields) && 0 < count($postFields)) {
			$postBodyString = '';
			$postMultipart = false;
			foreach ($postFields as $k => $v) {
				if ("@" != substr($v, 0, 1)) {
					$postBodyString .= "{$k}=" . urlencode($v) . "&";
				} else {
					$postMultipart = true;
				}
			}
			unset($k, $v);
			curl_setopt($ch, CURLOPT_POST, true);
			if ($postMultipart) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
			}
		}
		$reponse = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
				throw new Exception($reponse, $httpStatusCode);
			}
		}
		curl_close($ch);
		return $reponse;
	}
}