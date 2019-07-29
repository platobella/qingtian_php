<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Index_NetsHaojkMywPage extends WebPage
{
	public function main() 
	{
        global $_GPC;
        global $_W;
		if ($_W['action']=='statistics.index'||$_W['action']=='statistics')
            $this->index();
	}
	//订单分析
	public function index()
	{
        global $_GPC, $_W;

        $today = strtotime(date('Y-m-d',TIMESTAMP));
        $starttime = $today - 7*60*60*30;
        $endtime =  TIMESTAMP + 86399;

        $uniacid=$_W['uniacid'];
        $page=1;
        $pagesize = 20;

        $list = pdo_fetchall("SELECT em.* FROM ".tablename("qt_operate_member")." AS em  where em.uniacid=:uniacid  ORDER BY id DESC limit 0,10" ,array(':uniacid'=>$uniacid));
        $totalcount = pdo_fetchcolumn("select count(0) from ".tablename("qt_operate_member")." AS em  WHERE uniacid= " .$uniacid);
        $sevendaycount = pdo_fetchcolumn("select count(0) from ".tablename("qt_operate_member")." AS em  WHERE created_at>=".$starttime." and uniacid= " .$uniacid);


        $where = " AND orderTime >= ".$starttime." AND orderTime <=".$endtime;
        $ordertotal = pdo_fetchall("SELECT COUNT(DISTINCT orderId) ordercount,sum(commission) commission,ordertype FROM ".tablename("qt_operate_order")
            ." where uniacid=:uniacid   ".$where." group by ordertype",array(':uniacid'=>$_W['uniacid']));
        $jd_income = $pdd_income =$mgj_income=$jd_count=$pdd_count=$mgj_count= 0;
        foreach ($ordertotal as $item){
            if($item['ordertype']=='1'){
                $jd_income = $item['commission'];
                $jd_count = $item['ordercount'];
            }
            if($item['ordertype']=='2'){
                $pdd_income = $item['commission'];
                $pdd_count = $item['ordercount'];
            }
            if($item['ordertype']=='3'){
                $mgj_income = $item['commission'];
                $mgj_count = $item['ordercount'];
            }
        }
        include $this->template('haojingke/statistics/index');
	}

    //销售统计
    public function orderstatistics(){
        include $this->template('haojingke/statistics/orderstatistics');
    }

    //销售统计
    public function getstatistics()
    {
        global $_GPC, $_W;

        $today = strtotime(date('Y-m-d',TIMESTAMP));
        $starttime = $today - 24*60*60*7;
        $endtime =  $today + 86399;
		if(!empty($_GPC['daterange'])){
			$data_arr=explode(" ~ ",$_GPC['daterange']);
			$starttime=strtotime($data_arr[0]);
			$endtime=strtotime($data_arr[1]);
		}
		$where=" where orderTime>".$starttime." and orderTime<".$endtime;
		if(!empty($_GPC["positionId"])){
			$where.=" and positionId =".$_GPC["positionId"];
		}
		if($_GPC["yn"]=="0"){
			$where.=" and valistatus ='无效-取消'";
		}
		if($_GPC["yn"]==1){
			$where.=" and valistatus !='无效-取消'";
		}
        $sql="SELECT DATE_FORMAT(FROM_UNIXTIME(orderTime),'%Y-%m-%d') AS 'date',
(select count(0) from ims_qt_operate_order where DATE_FORMAT(FROM_UNIXTIME(orderTime),'%Y-%m-%d')=date) AS 'count' ,
(select sum(cosPrice) from ims_qt_operate_order where DATE_FORMAT(FROM_UNIXTIME(orderTime),'%Y-%m-%d')=date) AS 'cosPrice' ,
(select sum(commission) from ims_qt_operate_order where DATE_FORMAT(FROM_UNIXTIME(orderTime),'%Y-%m-%d')=date) AS 'commission' ,
(select count(0) from ims_qt_operate_order where DATE_FORMAT(FROM_UNIXTIME(orderTime),'%Y-%m-%d')=date and valistatus ='无效-取消') AS 'cancelcount'

FROM ims_qt_operate_order ".$where."  group by date order by orderTime desc;";
		$data=pdo_fetchall($sql);
		$days=array();
		$commission=array();
		$cosPrice=array();
		$count=array();
		$cancelcount=array();
		foreach($data AS $d){
			array_push($days,$d['date']);
			array_push($commission,$d['commission']);
			array_push($cosPrice,$d['cosPrice']);
			array_push($count,$d['count']);
			array_push($cancelcount,$d['cancelcount']);
		}
		$list=array("days"=>$days,"count"=>$count,"cosPrice"=>$cosPrice,"commission"=>$commission);
		$d["data"]=$list;
		$d["message"]="success";
		$d["status_code"]=200;
        exit(json_encode($d));
    }
    //订单统计
    public function getorders30()
    {
        global $_GPC, $_W;
        $global = m("fc")->fc_getglobal();

        $sql="
		select '今日' AS 'days',
		(select count(0) from ims_qt_operate_order where  uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0) AS 'count' ,
		(SELECT COUNT(*) FROM (SELECT * FROM ims_qt_operate_order WHERE uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0 GROUP BY positionId) o) AS 'people' ,
		(select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0) AS 'cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0) AS 'commission' ,
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0 and  yn = 1) AS 'cancelcount',
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0  and yn>1) AS 'buy_count' ,
        (select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0  and yn>1) AS 'buy_cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0  and yn>1) AS 'buy_commission' from ims_qt_operate_order where DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=0  and yn>1) AS 'real_commission'
		";
        $sql1="
		select '昨日' AS 'days',
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1) AS 'count' ,
		(SELECT COUNT(*) FROM (SELECT * FROM ims_qt_operate_order WHERE uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1 GROUP BY positionId) o) AS 'people' ,
		(select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1) AS 'cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1) AS 'commission' ,
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1 and  yn = 1) AS 'cancelcount',
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1  and yn>1) AS 'buy_count' ,
        (select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1  and yn>1) AS 'buy_cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())=-1  and yn>1) AS 'buy_commission'
		";
        $sql2="
		select '近7日' AS 'days',
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7) AS 'count' ,
		(SELECT COUNT(*) FROM (SELECT * FROM ims_qt_operate_order WHERE uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7 GROUP BY positionId) o) AS 'people' ,
		(select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7) AS 'cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7) AS 'commission' ,
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7 and  yn = 1) AS 'cancelcount',
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7  and yn>1) AS 'buy_count' ,
        (select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7  and yn>1) AS 'buy_cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-7  and yn>1) AS 'buy_commission'
		";
        $sql3="
		select '近30日' AS 'days',
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30) AS 'count' ,
		(SELECT COUNT(*) FROM (SELECT * FROM ims_qt_operate_order WHERE uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30 GROUP BY positionId) o) AS 'people' ,
		(select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30) AS 'cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30) AS 'commission' ,
		(select count(0) from ims_qt_operate_order where  uniacid={$_W['uniacid']} andDATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30 and  yn = 1) AS 'cancelcount',
		(select count(0) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30  and yn>1) AS 'buy_count' ,
        (select sum(cosPrice) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30  and yn>1) AS 'buy_cosPrice' ,
		(select sum(commission) from ims_qt_operate_order where uniacid={$_W['uniacid']} and DATEDIFF(FROM_UNIXTIME(orderTime),NOW())<=0 && DATEDIFF(FROM_UNIXTIME(orderTime),NOW())>=-30  and yn>1) AS 'buy_commission',
		";
        $data=pdo_fetch($sql);
        $data1=pdo_fetch($sql1);
        $data2=pdo_fetch($sql2);
        $data3=pdo_fetch($sql3);
        $list=array($data,$data1,$data2,$data3);
        $d["data"]=$list;
        $d['sql'] = $sql1;
        exit(json_encode($d));
    }
    //订单明细
    public function order()
    {
        global $_GPC, $_W;
        include $this->template('haojingke/statistics/order');
    }
    //会员增长趋势
    public function member()
    {
        global $_GPC, $_W;
        $i = -5;
        while ($i<=5) {
            $years[$i]=date("Y", strtotime("+$i year"));
            $i++;
        }
        $year = $_GPC['year'];
        $j = 1;
        while ($j<= 12) {
            $mouths[$j] = $j;
            $j++;
        }
        $mouth = $_GPC['mouth'];
        if (!empty($_GPC['days'])) {
            switch ($_GPC['days']) {
                case '7':
                    $day_select = recent('7');
                    break;
                case '14':
                    $day_select = recent('14');
                    break;
                case '30':
                    $day_select = recent('30');
                    break;
            }
            $begin = $day_select['begin'];
            $end = $day_select['end'];
            $data = $this->_getMemberStatis($day_select['begin'],$day_select['end']);
        }else{
            $r = $_GPC['year'];
            $t = $_GPC['mouth'];
            if (empty($r)) {
                $r = date('Y');
            }
            switch ($t) {
                case  empty($t):
                    $time_select =$this->_getShiJianChuo($r,$t);
                    break;
                case !empty($t):
                    $time_select = $this->_getShiJianChuo($r,$t);
                    break;
            }
            $begin = $time_select['begin'];
            $end = $time_select['end'];
            $data = $this->_getMemberStatis($begin,$end);
        }
        if (empty($data)) {

//            $i = 0;
//            while ($i<=7) {
//                $date['begin']=mktime(0,0,0,date($t),date(0),date($r));
//                $date['end']=mktime(0,0,0,date($t),date($i)+7,date($r));
//                $i++;
//            }

            $date['begin']=mktime(0,0,0,intval($t),intval(0),intval($r));
            $date['end']=mktime(0,0,0,intval($t),intval($i)+7,intval($r));
            $data = $this->_getMemberStatis($begin,$end);
            if (empty($data)) {
                $data = array(array('num'=>0),array('num'=>0),array('num'=>0),array('num'=>0),array('num'=>0),array('num'=>0),array('num'=>0));
            }

        }
        include $this->template('haojingke/statistics/member');
    }


    function _getShiJianChuo($nian=0,$yue=0){
        if(empty($nian) || empty($yue)){
            $now = time();
            $nian = date("Y",$now);
            $yue =  date("m",$now);
        }
        $time['begin'] = mktime(0,0,0,$yue,1,$nian);
        $time['end'] = mktime(23,59,59,($yue+1),0,$nian);
        return $time;
    }

    //获取会员的统计数据
    //统计预估收入的订单数据
    /*
    * $begin 时间戳
    * $end 时间戳
    * 如 今日
    * $begintime=mktime(0,0,0,date('m'),date('d'),date('Y'));
    * $endtime=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
    */
    function _getMemberStatis($begin,$end){
        global $_GPC, $_W;
        //$begin='1504230045';
        //$end='1512092445';
        $sql="select t.created_at AS date,count(*) as num from
	(select from_unixtime(m.created_at,'%Y-%m-%d') as created_at from ".tablename("qt_operate_member")." AS m
	where uniacid=".$_W['uniacid']." and created_at between ".$begin." and ".$end.") t group by t.created_at order by t.created_at";
        $res=pdo_fetchall($sql);
        return $res;
    }
	
}
?>