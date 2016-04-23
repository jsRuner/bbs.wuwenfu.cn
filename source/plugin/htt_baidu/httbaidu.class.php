<?php
/**
 *	[百度贴吧(htt_baidu.{modulename})] (C)2016-2099 Powered by 北岸的云.
 *	Version: 1.0
 *	Date: 2016-4-22 21:48
 *	string forumdisplay_forumaction
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
class plugin_htt_baidu {

	const DEBUG = 0;
    protected static $postReportAction = array('post_newthread_succeed', 'post_edit_succeed', 'post_reply_succeed',
        'post_newthread_mod_succeed', 'post_newthread_mod_succeed', 'post_reply_mod_succeed',
        'edit_reply_mod_succeed', 'edit_newthread_mod_succeed');
	//TODO - Insert your code here
    protected static $cloudAppService;
    protected static $securityService;
    protected static $securityStatus;

    public function __construct() {
        self::$cloudAppService = Cloud::loadClass('Service_App');
        self::$securityStatus = self::$cloudAppService->getCloudAppStatus('security');
        self::$securityService = Cloud::loadClass('Service_Security');
    }

	//TODO - Insert your code here
	function global_header()
	{
		global $_G;
		return $fid = $_G['fid'];
		// return 2226662;
		# code...
	}

}


//脚本嵌入点类
class plugin_htt_baidu_forum extends plugin_htt_baidu {

	 public function post_security(){
        return true;
    }

	function forumdisplay_forumaction_output() {
		#读取数据。判断是否关注了该版块。
		global $_G;

		$uid = $_G['uid'];
		$fid = $_G['fid'];

		$guanzhuinfo = '';

		$query = DB::query("SELECT * FROM  `pre_httbaidu` WHERE  `fid`=$fid and `uid`=$uid LIMIT 0 , 30");
		while($item = DB::fetch($query)) {
			// var_dump($item);
			$guanzhuinfo = $item;
		}
		include_once template('htt_baidu:button');

		if (empty($guanzhuinfo)){

        	return $button_no_html;
		}else{
        	return $button_yes_html;
			
		}
	}

	//发帖后触发 {"param":["post_reply_succeed","forum.php?mod=viewthread&tid=96&pid=99&page=1&extra=#pid99",{"fid":"2","tid":"96","pid":99,"from":null,"sechash":""},[],0]}
	 public function post_report_message($param) {
        global $_G, $extra, $redirecturl;
        #获取uid fid 增加一次积分。该积分值读取插件的设置
       	$uid = $_G['uid'];
		$fid = $_G['fid'];
		$query = DB::query("update `pre_httbaidu` set `credit`=`credit`+10 WHERE  `fid`=$fid and `uid`=$uid");
    }
    //帖子左侧显示版块的积分.
    //增加缓存处理。读取缓存数据。过期后再读取数据库的。
    //这里要输出的非登录用户的uid,而是发帖左侧人的版块等级与积分，是个多次输出过程
    function viewthread_sidebottom_output()
    {	
    	// global $_G;
    	global $_G,$postlist,$_GET;

    	// $tid = $postlist['0']['tid'];
    	$tid = $_GET['tid'];

    	// file_put_contents('1.txt', json_encode($postlist));

    	// return 11;
    	loadcache('plugin');
		$var = $_G['cache']['plugin'];

		// var_dump($var);
		$cache_time =  $var['htt_baidu']['cache_time'];

		$cache_file = DISCUZ_ROOT.'./data/sysdata/cache_htt_baidu_contents_'.$tid.'.php';

		//缓存过期了。
		if(($_G['timestamp'] - @filemtime($cache_file)) > $cache_time*60) {
			foreach ($postlist as $key => $value) {
				$info =array();

				// $uid = $_G['uid'];
				$uid = $value['uid'];
				// $uid = $post['authorid'];
				$fid = $_G['fid'];



				$guanzhuinfo = '';
		    	$query = DB::query("SELECT * FROM  `pre_httbaidu` WHERE  `fid`=$fid and `uid`=$uid");
				while($item = DB::fetch($query)) {
					// var_dump($item);
					$guanzhuinfo = $item;
				}
				if(empty($guanzhuinfo)){
					$credit = 0;
				}else{

					$credit = $guanzhuinfo['credit'];
				}

				//计算等级名称。查询所有等级。然后计算出等级。todo：需要缓存
				$level_list = array();
				$touxian = " "; #默认 新手入门。如果没设置的话
		    	$query = DB::query("SELECT * FROM  `pre_httbaidu_level` WHERE  1 order by `floor` asc  ");
				while($item = DB::fetch($query)) {
					//毕竟是否大于下限，小于上限。如果是-1。则只比较下限
					if($item['ceil'] == -1){
						if ($credit>=$item['floor']) {
							$touxian = $item['leveltitle'];
						}

					}else{

						if ($credit>=$item['floor'] && $credit <$item['ceil']) {
							$touxian = $item['leveltitle'];
						}
					}
				}

				// $info['credit'] = $credit;
				// $info['touxian'] = urlencode($touxian);
				// include_once template('htt_baidu:side');
    			// $return =array($side_html);
    			$side_html = '<dl class="pil cl">
    <dt>本版积分</dt><dd><a href="home.php?mod=space&amp;uid=1&amp;do=profile" target="_blank" class="xi2">'.$credit.'</a></dd>
</dl>

<dl class="pil cl">
    <dt>本版等级</dt><dd><a href="home.php?mod=space&amp;uid=1&amp;do=profile" target="_blank" class="xi2">'.$touxian.'</a></dd>
</dl>';
				// $echoq[] = urlencode($side_html);
				// $echoq[$tid][] = $side_html;
				$echoq[] =$side_html;

				$cachestr[] = urlencode($side_html);

				// file_put_contents('2.txt', $side_html);
			}
		//写入缓存的需要加''
		// $cacheArray .= "\$contents='".json_encode($echoq)."';\n";
		$cacheArray = "\$contents='".json_encode($cachestr)."';\n";
		require_once libfile('function/cache');
		writetocache('htt_baidu_contents', $cacheArray); 
    	
    	}else{
		   //你可以从缓存文件里读了.
    		//读取缓存的数据
    		include_once DISCUZ_ROOT.'./data/sysdata/cache_htt_baidu_contents.php';
			
			$contents= json_decode($contents,true);
			// var_dump($contents);
			foreach ($contents as $key => $value) {

				$echoq[] = urldecode($value);
			}
    	}
    	return $echoq;
    }
}





?>