<?php
//
if (!defined("IN_IA")) {
    exit("Access Denied");
}
class Classification_NetsHaojkModel
{
    public function api_listall()
    {
        global $_W, $_GPC;

        //判断是否有子类id
        $id = $_GPC['id'] ? $_GPC['id']:1;

        //父类分类
        $row['classification'] = pdo_fetchall("select * from " . tablename("qt_classification")." where parent_id=0 and is_delete=0 ORDER BY sort DESC");

        //获取子类
        $row['subclass'] =  $this->api_subclass($id);

        return ['result'=>['data'=>$row]];

    }

    //获取分类子类
    public function api_subclass($parent_id)
    {
        global $_W, $_GPC;
        $classif_subclass= pdo_fetchall("select * from " . tablename("qt_classification")."where parent_id=:parent_id and is_delete=0 ORDER BY sort DESC",array(":parent_id"=>$parent_id));
        foreach ($classif_subclass as $key=>$value){
            $classif_subclass[$key]['icon'] = to_attach_image($value['icon']);
        }
        return $classif_subclass;
    }
}
?>