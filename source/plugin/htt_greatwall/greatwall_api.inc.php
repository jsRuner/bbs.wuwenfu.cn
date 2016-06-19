<?php
/**
 * 活动接口。返回需要的参数。
 */
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
error_reporting(E_ALL);
defined('DATA_PATH')    or define('DATA_PATH',      DISCUZ_ROOT.'/data/plugindata/');
define('IS_AJAX',       ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST['ajax']) || !empty($_GET['ajax'])) ? true : false);


function htt_random_str($length=5){
    $hash = '';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $max = strlen($chars) - 1;
    PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
    for($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}
global $_G;
$op = $_GET['op'];

function get_rand($proArr) {
    $result = '';

    //概率数组的总概率精度
    $proSum = array_sum($proArr);

    //概率数组循环
    foreach ($proArr as $key => $proCur) {
        $randNum = mt_rand(1, $proSum);
        if ($randNum <= $proCur) {
            $result = $key;
            break;
        } else {
            $proSum -= $proCur;
        }
    }
    unset ($proArr);

    return $result;
}

switch($op){
    case 'initdata': //初始化数据
        //登录与没有登录.获取接口,需要携带project=1的参数 需要传递当前登陆者的数据。
        $pm['project'] = $_GET['project'];

        $pm = http_build_query($pm);
        $uri['gamesatrt']  = "/plugin.php?id=htt_greatwall:greatwall_api&op=gamesatrt&".$pm;
        $uri['savegame']  = "/plugin.php?id=htt_greatwall:greatwall_api&op=savegame&".$pm;
        $uri['lottery']  = "/plugin.php?id=htt_greatwall:greatwall_api&op=lottery&".$pm;
        $uri['savewin']  = "/plugin.php?id=htt_greatwall:greatwall_api&op=savewin&".$pm;
        $uri['sendsms']  = "/plugin.php?id=htt_greatwall:greatwall_api&op=sendsms&".$pm;

        $data['URI'] = $uri;

        $_G['greatwall_userdata'] = array(
//            'id'=>$_G['uid'],
            'id'=>$_G['uid'],
            'gstime'=>0,
            'plog'=>array()
        );

        $userinfo['LG_USER'] = array('id'=>$_G['uid']) ;
//        $userinfo['remain'] = time() ; //原来是项目的奖品钱

        #获取奖品列表。
        $search = ' AND project_id = '.intval($_GET['project']).' AND prizes_nums != 0 AND status = \'1\'';
        $prize_arr = C::t('#htt_greatwall#prize')->fetch_all_by_search($search,0,1000);
        //计算一下当前的红包总价值。虚构一下。基数1466303721 +红包实际的价值
        $all_prices = 0;

        foreach($prize_arr as $key=>$prize){
            $current = $prize['prizes_nums'] - $prize['prizes_nums_used'];
            $all_prices += $current * $prize['price'];
        }
        $userinfo['remain'] = $all_prices;


        $data['DATA'] = $userinfo;


        $initda = $data;
        header('Content-Type:application/javascript; charset=utf-8');
        $s[] = 'var API={};';
        foreach ($initda as $k=>$v) $s[] = 'API.'.$k.'='.json_encode($v).';';
        exit(join("\r\n",$s));
        break;

    case 'gamesatrt': //设置游戏开始时间
        $data = array();
        if(!IS_AJAX){
            $data['status'] = -1;
            $data['info'] = 'need ajax';
        }else{
            //需要判断是否登录了。


            $_G['greatwall_userdata']['gstime'] = time();


            $data['status'] = 1;
            $data['info'] = 'ok';
            $data['url'] = '';
        }
        echo json_encode($data);
        break;
    case 'savegame': //领取红包.
        $data = array();
        $data['status'] = 1;
        $data['info'] = 'ok';
        $data['bi'] = $bi;
        $data['count'] = $count;
        echo json_encode($data);
        break;
    case 'lottery'://抽奖


        #获取奖品列表。
        $search = ' AND project_id = '.intval($_GET['project']).' AND prizes_nums != 0 AND status = \'1\'';
        $prize_arr = C::t('#htt_greatwall#prize')->fetch_all_by_search($search,0,100);


//        var_dump($prize_arr);

        $count = 0;

        $pids = array();

        $uesed_probability = 0;
        foreach ($prize_arr as $key => $val) {

            //如果数量不足了。则不参与。
            if($val['prizes_nums'] <= $val['prizes_nums_used']){
                continue;
            }

            $arr[$val['id']] = $val['probability'];

            $count += $val['probability'];






        }
        //差100%则 最小的奖品中奖概率提升
        $min_pid = $prize_arr[count($prize_arr)-1]['id'];
        if($count < 100){
            $arr[$min_pid] += 100-$count;
        }




        //如果奖品数量没了，怎么破。


        //必须中奖。也就是必须100%。否则界面报错。
       /* if($count <100){
            $data = array();
            $data['status'] = 0;
//            $data['info'] = "奖品的参数设置错误";
            $data['fg'] = '-202';
            echo json_encode($data);
            break;
//            $count = 100-$count; //如果不足100则补充一个不中奖的概率
//            $arr[0] = $count;
        }*/

        $rid = get_rand($arr); //根据概率获取奖项id

        //需要判断是什么奖品，传递对应的指。数字应当是0-5
        //0表示没有奖品。
        $keys = 0;

        foreach($prize_arr as $key=>$val){
            if($val['id'] == $rid){
                $keys = $key+1;
                break;
            }
        }

        $data = array();
        $data['status'] = 1;
        $data['info'] = 'ok';
        $data['prize_id'] = $rid;
        $data['rid'] = $rid;
        $data['keys'] = $keys;//确定了抽奖的标记。
        $data['dnum'] = '0';
        $data['fg'] = '1';
        $data['ticket'] = '';
        echo json_encode($data);
        break;

    case 'savewin': //保存中奖用户信息 savewin 对应的路由就是 saveapply
                      //{"status":1,"info":"\u60a8\u7684\u4fe1\u606f\u63d0\u4ea4\u6210\u529f\uff01","ticket":"YYYWKKXF9MF9","money":"1000","rid":"244"}
//       var_dump($_POST['ext']);
//       exit();
        //存储信息。
//        error_reporting(E_ALL);

        $ticket = htt_random_str(11);

        //解决乱码问题。当前是gbk。乱码了，说明是utf8。使用utf8转gbk
        $ext_temp = $_POST['ext'];
        foreach($ext_temp as $key=>$item){
            $ext[$key] =  iconv("UTF-8", "gbk", $item);
        }
        $name =   iconv("UTF-8", "gbk", $_GET['name']);
//        $addr =  iconv("UTF-8", "gbk", $_GET['ext']['city']);


       $insertdata = array(
           'project_id'=>$_GET['project'],
           'member_id'=>$_G['uid'],
           'prize_id'=>$_POST['rid'],
           'name'=>$name,
           'mobile'=>$_POST['mobile'],
           'addr'=>$ext['city'],
           'config'=>serialize($ext),
           'ticket'=>$ticket,
           'ip'=>$_G['clientip'],
           'status'=>0,
           'created'=>date('Y-m-d H:i:s'),
       );
//        echo 11;

       C::t('#htt_greatwall#prize_log')->insert($insertdata);




       $prize =  C::t('#htt_greatwall#prize')->fetch_by_pid($_POST['rid']);

        $updatedata = array(
            'prizes_nums_used' => $prize['prizes_nums_used'] +1,
        );

        C::t('#htt_greatwall#prize')->update($_POST['rid'],$updatedata);



        //对应的奖品数量减少。


//        var_dump($prize);
//        exit();

        /*$data = array();
        $data['status'] = 1;
        $data['info'] = '你的信息已经提交';
        $data['ticket'] = $ticket; //红包对应的券号
        $data['money'] = $prize['price']; //红包的金额。
        $data['rid'] = $_POST['rid'];*/

       /* var_dump($data);

        $xx = $data;

        echo 222;
        echo json_encode(array('status'=>1));
        echo 111;
        exit();*/

        echo json_encode(array(
            'status'=>1,
            'info'=>'ok',
            'ticket'=>$ticket,
            'money'=>$prize['price'],
            'rid'=>$_POST['rid']
        ));

        break;
    case 'sendsms': //发送短息。
        $data = array();
        $data['status'] = 1;
        $data['info'] = 'ok';
        $data['url'] = '';
        echo json_encode($data);
        break;
    default: //todo 默认需要待定处理
        $data = array();
        $data['status'] = 1;
        $data['info'] = 'ok';
        $data['url'] = '';
        echo json_encode($data);
        break;
}





?>