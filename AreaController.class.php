<?php

/*
                  _ooOoo_
                 o8888888o
                 88" . "88
                 (| -_- |)
                 O\  =  /O
              ____/`---'\____
            .'  \\|     |//  `.
           /  \\|||  :  |||//  \
          /  _||||| -:- |||||-  \
          |   | \\\  -  /// |   |
          | \_|  ''\---/''  |   |
          \  .-\__  `-`  ___/-. /
        ___`. .'  /--.--\  `. . __
     ."" '<  `.___\_<|>_/___.'  >'"".
    | | :  `- \`.;`\ _ /`;.`/ - ` : | |
    \  \ `-.   \_ __\ /__ _/   .-` /  /
======`-.____`-.___\_____/___.-`____.-'======
                  `=---='
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
                 佛祖保佑       永无BUG
                 
****************Powered by Adamin90***************
* @email: 14846869@qq.com
* Date: 2018/6/15
* Time: 14:49
* @link: http://www.lixiaopeng.top
**************************************************
*/

namespace Spider\Controller;


use Common\Controller\HomebaseController;
use QL\QueryList;

vendor("Ql.QueryList", "", ".class.php");

class AreaController extends HomebaseController
{

    private $baseurl = "http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2016/";

    private $areamodel;

    public function __construct()
    {
        parent::__construct();
        $this->areamodel = M("Area");
    }


    /**
     * @author Adam
     * Time: 2018/6/15 14:50
     */
    public function sheng()
    {
        $data = QueryList::Query($this->baseurl . "index.html", array("url" => array("td>a ", "href"), "name" => array("td>a", "text")), "", 'UTF-8', 'GB2312')->getData();
        foreach ($data as $k => $v) {
            $data[$k]["url"] = str_replace(".html", "", $v["url"]);
            $data[$k]["saveid"] = $this->generate_full_id($data[$k]["url"]);
            $res = $this->areamodel->where(array("area_id" => $data[$k]["url"]))->find();
            if (empty($res)) {
                $this->areamodel->data(array("area_id" => $data[$k]["url"], "area_pid" => 0, "area_name" => $data[$k]["name"], "level" => 1))->add();
            }
        }

        echo json_encode($data);
        die;


    }

    /**
     * 二级 市   http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2016/14.html
     * @author Adam
     * Time: 2018/6/20 13:43
     */
    public function secondway()
    {
        $toplevel = $this->areamodel->where(array("imported" => 0, "level" => 1))->order("area_id DESC")->find();

        if (empty($toplevel)) {
            echo "全部导入完毕，无需重新导入";
            die;

        }
        $fatherid = $toplevel["area_id"];
        $url = $this->baseurl . $toplevel["area_id"] . ".html";
        $data = QueryList::Query($url, array("url" => array("td:eq(0)>a", "href"), "name" => array("td:eq(1)>a", "text")), "tr.citytr", 'UTF-8', 'GB2312')->getData();
        foreach ($data as $k => $v) {
            $data[$k]["url"] = str_replace(".html", "", $this->reg_real_id($v["url"])[0]);
        }
        
        M()->startTrans();

        foreach ($data as $k1 => $v1) {
            $res["area_id"] = $v1["url"];
            $res["area_pid"] = $toplevel["area_id"];
            $res["level"] = 2;
            $res["area_name"] = $v1["name"];
            if (!$this->areamodel->data($res)->add()) {
                echo "循环导入失败" . M()->getLastSql();
                die;
                M()->rollback();


            }
        }

        $this->areamodel->where(array("area_id" => $fatherid))->save(array("imported" => 1));
        $sql = M()->getLastSql();
        M()->commit();
        echo "导入成功 " . $sql;
        die;


    }

    /**
     * 三级  区/县（市）
     * @author Adam
     * Time: 2018/6/21 15:34
     */
    public function thirdway()
    {
        $secondlevel = $this->areamodel->where(array("imported" => 0, "level" => 2))->order("area_id DESC")->find();

        if (empty($secondlevel)) {
            echo "区/县全部导入完毕，无需重新导入";
            die;

        }
        $topid = $secondlevel["area_pid"];
        $fatherid = $secondlevel["area_id"];
        $url = $this->baseurl . $topid . "/" . $fatherid . ".html";
        $data = QueryList::Query($url, array("url" => array("td:eq(0)>a", "href"), "name" => array("td:eq(1)", "text"), "extra" => array("td:eq(0)", "text")), "tr.countytr", 'UTF-8', 'GB2312')->getData();
        foreach ($data as $k => $v) {
            $data[$k]["url"] = str_replace(".html", "", $this->reg_real_id($v["url"])[0]);
            if (empty($data[$k]["url"])) {
                $data[$k]["url"] = $this->spit_pure_id($v["extra"]);   //防止空数据
            }
        }
        //  echo  json_encode($data);die;
        M()->startTrans();
        foreach ($data as $k => $v) {
            $res["area_id"] = $v["url"];
            $res["area_pid"] = $secondlevel["area_id"];
            $res["level"] = 3;
            $res["area_name"] = $v["name"];
            if (!$this->areamodel->data($res)->add()) {
                echo "循环导入失败" . M()->getLastSql();
                die;
                M()->rollback();


            }
        }
        if ($this->areamodel->where(array("area_id" => $fatherid))->save(array("imported" => 1)) == false) {
            echo "更新父类失败";
            M()->rollback();
            die;
        };
        $sql = M()->getLastSql();
        M()->commit();
        echo "导入成功" . $sql;
        die;
    }

    /**四级 办事处/镇
     * @author Adam
     * Time: 2018/6/21 15:35
     */
    public function fourthway()
    {
        $thirdlevel = $this->areamodel->where(array("imported" => 0, "level" => 3))->order("area_id DESC")->find();

        if (empty($thirdlevel)) {
            echo "办事处/镇全部导入完毕，无需重新导入";
            die;

        }
        $fatherid = $thirdlevel["area_id"];
        $secondid = $thirdlevel["area_pid"];
        $secondmeta = $this->areamodel->where(array("area_id" => $secondid))->find();
        $topid = $secondmeta["area_pid"];
        $url = $this->baseurl . $topid . "/" . str_replace($topid, "", $secondid) . "/" . $fatherid . ".html";
        $data = QueryList::Query($url, array("url" => array("td:eq(0)>a", "href"), "name" => array("td:eq(1)", "text"), "extra" => array("td:eq(0)", "text")), "tr.towntr", 'UTF-8', 'GB2312')->getData();
        foreach ($data as $k => $v) {
            $data[$k]["url"] = str_replace(".html", "", $this->reg_real_id($v["url"])[0]);
            if (empty($data[$k]["url"])) {
                $data[$k]["url"] = $this->spit_pure_id($v["extra"]);   //防止空数据
            }
        }
        M()->startTrans();
        foreach ($data as $k => $v) {
            $res["area_id"] = $v["url"];
            $res["area_pid"] = $thirdlevel["area_id"];
            $res["level"] = 4;
            $res["area_name"] = $v["name"];
            if (!$this->areamodel->data($res)->add()) {
                echo "循环导入失败" . M()->getLastSql();
                die;
                M()->rollback();


            }
        }
        if ($this->areamodel->where(array("area_id" => $fatherid))->save(array("imported" => 1)) == false) {
            echo "更新父类失败";
            M()->rollback();
            die;
        };
        $sql = M()->getLastSql();
        M()->commit();
        echo "导入成功" . $sql;
        die;

    }

    /**五级  居委会/村委会
     * @author Adam
     * Time: 2018/6/21 15:37
     */
    public function fifthway()
    {
        $fourthlevel = $this->areamodel->where(array("imported" => 0, "level" => 4))->order("area_id DESC")->find();

        if (empty($fourthlevel)) {
            echo "办事处/镇全部导入完毕，无需重新导入";
            die;

        }
        $fatherid = $fourthlevel["area_id"];
        $thirdid = $fourthlevel["area_pid"];
        $thirdmeta = $this->areamodel->where(array("area_id" => $thirdid))->find();
        $secondid = $thirdmeta["area_pid"];
        $secondmeta = $this->areamodel->where(array("area_id" => $secondid))->find();
        $topid = $secondmeta["area_pid"];
        $url = $this->baseurl . $topid . "/" . str_replace($topid, "", $secondid) . "/" . str_replace($secondid, "", $thirdid) . "/" . $fatherid . ".html";
        $data = QueryList::Query($url, array("url" => array("td:eq(0)", "text"), "name" => array("td:eq(2)", "text"), "extra" => array("td:eq(0)", "text")), "tr.villagetr", 'UTF-8', 'GB2312')->getData();
        foreach ($data as $k => $v) {
            $data[$k]["url"] = str_replace(".html", "", $this->reg_real_id($v["url"])[0]);
            if (empty($data[$k]["url"])) {
                $data[$k]["url"] = $this->spit_pure_id($v["extra"]);   //防止空数据
            }
        }
        M()->startTrans();
        foreach ($data as $k => $v) {
            $res["area_id"] = $v["url"];
            $res["area_pid"] = $fourthlevel["area_id"];
            $res["level"] = 5;
            $res["area_name"] = $v["name"];
            if (!$this->areamodel->data($res)->add()) {
                echo "循环导入失败111" . M()->getLastSql();
                die;
                M()->rollback();


            }
        }
        if ($this->areamodel->where(array("area_id" => $fatherid))->save(array("imported" => 1)) == false) {
            echo "更新父类失败";
            M()->rollback();
            die;
        };
        $sql = M()->getLastSql();
        M()->commit();
        echo "导入成功" . $sql;
        die;

    }

    /**
     * 生成完整的12位id
     * @param $id
     * @return string
     * @author Adam
     * Time: 2018/6/20 17:19
     */
    private function generate_full_id($id)
    {
        if (strlen($id) > 12) {
            return $id;
        } else {
            $needzerecount = 12 - strlen($id);
            for ($i = 0; $i < $needzerecount; $i++) {
                $id .= "0";
            }

            return $id;
        }


    }

    /**
     * 去除字符转后面的0
     * @param $id
     * @return null|string|string[]
     * @author Adam
     * Time: 2018/6/20 13:02
     */
    private function spit_pure_id($id)
    {
        $matches = preg_replace("/0+$/", "", $id);
        return $matches;

    }

    /**
     * 正则匹配id
     * @param $id
     * @return mixed
     * @author Adam
     * Time: 2018/6/20 17:22
     */
    public function reg_real_id($id)
    {
        preg_match("/\d+.html$/", $id, $matches);
        return $matches;
    }
}