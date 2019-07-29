<?php

//
if (!defined("IN_IA")) {
	exit("Access Denied");
}
class Route_NetsHaojkModel
{
	public function run($isweb = true)
	{
		global $_GPC;
		global $_W;
		require_once IA_ROOT . "/addons/qt_shop/core/inc/page.php";
		if ($isweb) {
			require_once NETS_HAOJIK_CORE . "inc/page_web.php";
			require_once NETS_HAOJIK_CORE . "inc/page_web_com.php";
		}
		$r = str_replace("//", "/", trim($_GPC["r"], "/"));
		$routes = explode(".", $r);
		$segs = count($routes);
		$method = "main";
		$root = $isweb ? NETS_HAOJIK_CORE_WEB : NETS_HAOJIK_CORE_MOBILE;
		switch ($segs) {
			case 0:
				$file = $root . "index.php";
				$class = "Index";
			case 1:
				$file = $root . $routes[0] . ".php";
				if (is_file($file)) {
					$class = ucfirst($routes[0]);
				} else {
					if (is_dir($root . $routes[0])) {
						$file = $root . $routes[0] . "/index.php";
						$class = "Index";
					} else {
						$method = $routes[0];
						$file = $root . "index.php";
						$class = "Index";
					}
				}
				$_W["action"] = $routes[0];
				break;
			case 2:
				$_W["action"] = $routes[0] . "." . $routes[1];
				$file = $root . $routes[0] . "/" . $routes[1] . ".php";
				if (is_file($file)) {
					$class = ucfirst($routes[1]);
				} else {
					if (is_dir($root . $routes[0] . "/" . $routes[1])) {
						$file = $root . $routes[0] . "/" . $routes[1] . "/index.php";
						$class = "Index";
					} else {
						$file = $root . $routes[0] . ".php";
						if (is_file($file)) {
							$method = $routes[1];
							$class = ucfirst($routes[0]);
						} else {
							if (is_dir($root . $routes[0])) {
								$method = $routes[1];
								$file = $root . $routes[0] . "/index.php";
								$class = "Index";
							} else {
								$file = $root . "index.php";
								$class = "Index";
							}
						}
					}
				}
				$_W["action"] = $routes[0] . "." . $routes[1];
				break;
			case 3:
				$_W["action"] = $routes[0] . "." . $routes[1] . "." . $routes[2];
				$file = $root . $routes[0] . "/" . $routes[1] . "/" . $routes[2] . ".php";
				if (is_file($file)) {
					$class = ucfirst($routes[2]);
				} else {
					if (is_dir($root . $routes[0] . "/" . $routes[1] . "/" . $routes[2])) {
						$file = $root . $routes[0] . "/" . $routes[1] . "/" . $routes[2] . "/index.php";
						$class = "Index";
					} else {
						$method = $routes[2];
						$file = $root . $routes[0] . "/" . $routes[1] . ".php";
						$_W["action"] = $routes[0] . "." . $routes[1];
						if (is_file($file)) {
							$class = ucfirst($routes[1]);
							$method = "main";
							$_W["action"] = $routes[0] . "." . $routes[1] . "." . $routes[2];
						} else {
							if (is_dir($root . $routes[0] . "/" . $routes[1])) {
								$file = $root . $routes[0] . "/" . $routes[1] . "/index.php";
								$class = "Index";
							} else {
								$_W["action"] = $routes[0] . "." . $routes[1];
							}
						}
					}
				}
				break;
			case 4:
				$_W["action"] = $routes[0] . "." . $routes[1] . "." . $routes[2];
				$method = $routes[3];
				$class = ucfirst($routes[2]);
				$file = $root . $routes[0] . "/" . $routes[1] . "/" . $routes[2] . ".php";
				break;
		}
		if (!is_file($file)) {
			show_message("未找到控制器 " . $r);
		}
		$_W["routes"] = $r;
		$_W["isplugin"] = false;
		$_W["controller"] = $routes[0];
		include $file;
		$class = ucfirst($class) . "_NetsHaojkMywPage";
		$instance = new $class();
		if (!method_exists($instance, $method)) {
			show_message("控制器 " . $_W["controller"] . " 方法 " . $method . " 未找到!");
		}
		$instance->{$method}();
		exit;
	}
}