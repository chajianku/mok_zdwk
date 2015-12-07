<?php
if (!defined('SYSTEM_ROOT')) {
    die('Insufficient Permissions');
}

function cron_mok_zdwk() {
    
    //如果今天签到过了直接返回日志
    if (option::get('mok_zdwk_run') == date('d')) {
        return option::get('mok_zdwk_log');
    }
    global $m;
    $prefix = DB_PREFIX;
    
    //选出用户的options和bduss
    $res = $m->query("SELECT {$prefix}users_options.`name` , {$prefix}users_options.`value` , {$prefix}baiduid.`bduss` 
FROM {$prefix}baiduid
INNER JOIN {$prefix}users_options ON {$prefix}users_options.uid = {$prefix}baiduid.uid
WHERE {$prefix}users_options.`name` =  'mok_zdwk_wk'
OR {$prefix}users_options.`name` =  'mok_zdwk_zd'");

    $wk = $zd = 0;
    $bduss = Array();
    if($m->num_rows($res) != 0){
        while ($row = $res->fetch_array()) {
            //判断该选项是否开启
            if($row['value'] == 'on'){
                //记录bduss（数量），如果bduss数组内没有该bduss，则加入数组
                if (!in_array($row['bduss'], $bduss)) {
                    $bduss[] = $row['bduss'];
                }
                if ($row['name'] === 'mok_zdwk_wk') {
                    $wk++;
                    $head = Array(
						'Accept:*/*',
						'Accept-Encoding:gzip, deflate, sdch',
						'Accept-Language:zh-CN,zh;q=0.8',
						'Connection:keep-alive',
						'Host:wenku.baidu.com',
						'Referer:http://wenku.baidu.com/task/browse/daily',
						'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36',
						'X-Requested-With:XMLHttpRequest'
					);
                    $c = new wcurl('http://wenku.baidu.com/task/submit/signin',$head);
                    $c->addCookie('BDUSS=' . $row['bduss']);
                    $c->exec();
                    $c->close();
                } else if ($row['name'] === 'mok_zdwk_zd') {
                    $zd++;
                    $c = new wcurl('http://zhidao.baidu.com/');
                    $c->addCookie('BDUSS=' . $row["bduss"]);
                    $stoken = $c->get();
                    $c->close();
                    $stoken = textMiddle($stoken, '"stoken":"', '",');
                    if ($stoken != "") {
                        $c = new wcurl('http://zhidao.baidu.com/submit/user');
                        $c->addCookie('BDUSS=' . $row["bduss"]);
                        $c->post(array('cm' => '100509', 'utdata' => '90,90,102,96,107,101,99,97,96,90,98,103,103,99,127,106,99,99,14138554765830', 'stoken' => $stoken));
                        $c->close();
                    }
                }
            }
        }
    }
    
    $log = "知道、文库签到完毕<br/>" . date("Y-m-d H:i:s") . "<br/>共计百度账号: " . count($bduss) . " 个<br/>知道签到: {$zd} 个<br/>文库签到: {$wk} 个";
    option::set('mok_zdwk_run', date('d'));
    option::set('mok_zdwk_log', $log);
    return $log;
}
