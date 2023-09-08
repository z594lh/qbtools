<?php

require_once 'MySQL.php';
require_once 'qbApi.php';
require_once 'util.php';
require_once 'sort.php';
class operate extends qbApi
{

    public $util;
    public function __construct()
    {
        parent::__construct();
        MySQL::connect('localhost', 'root', 'root', 'mysql');
        $this->util = new util();
    }

    /**
     * 拆包
     */
    public function partpackage(){
        $data = $this->getTorrentsInfo();
        $partconfig = $this->config['partpackage'];
        $raite = $partconfig['raite'];
        $min_size = $this->util->toByte($partconfig['min_size']);
        $max_size = $this->util->toByte($partconfig['max_size']);
        $size = 0;
        foreach ($data as $k => $v){
            /*已处理过的标签。直接跳过*/
            if(strpos($v['tags'], '无法拆包') !== false || strpos($v['tags'], '已拆包') !== false || strpos($v['tags'], '小于'.$partconfig['single_file_size'].'G不拆') !== false){
                /*无法拆包且删除失败。再次尝试删除*/
                if(strpos($v['tags'], '无法拆包') !== false){
                    $this->echoString('删除无法拆包1:'.$v['name'].'('.$v['hash'].')');
                    $this->deleteTorrents($v['hash']);
                }
                $this->echoString('当前标签:'.$v['tags'].'跳过拆包',false,false);
                continue;
            }
            /*限速*/
            if($partconfig['limit_category']){
                $limit_category = explode(',',$partconfig['limit_category']);
                if(in_array($v['category'],$limit_category)){
                    if($partconfig['upspeed'] && $partconfig['upspeed']>0){
                        $this->setTorrentUploadLimit(array('hashes'=>$v['hash'],'limit'=>$partconfig['upspeed']*1024*1024));
                    }
                    if($partconfig['dlspeed'] && $partconfig['dlspeed']>0){
                        $this->setTorrentDownloadLimit(array('hashes'=>$v['hash'],'limit'=>$partconfig['dlspeed']*1024*1024));
                    }
                }
            }

            /*根据种子大小和配置的拆包比例，算出最终拆包后的大小(不超过最大、最小size)*/
            $raite_size = $v['total_size'] * $raite;
            $size = $raite_size>$max_size?$max_size:$raite_size;
            $size = $size>$min_size?$size:$min_size;
            $tamp_size = 0;
            $_files = $this->torrents_files($v['hash']);
            /*随机打乱顺序*/
            shuffle($_files);

            $notdownload = array();
            $download = array();
            foreach ($_files as $kk => $vv){
                /*小于10m的文件忽略*/
                if($vv['size']<= 10*1024*1024){
                    continue;
                }
                /*累加。超过最大size则不下载了。*/
                if($tamp_size+$vv['size']>$size){
                    $notdownload[] = $vv['index'];
                }else{
                    $tamp_size = $tamp_size + $vv['size'];
                    $download[] = $vv['index'];
                }
            }
            if(count($notdownload)>0){
                $_ids = implode('|',$notdownload);
                $arr = array('hash'=>$v['hash'],'id'=>$_ids, 'priority'=>'0');
                $this->setFilePriority($arr);
            }
            if(count($download)>0){
                $_ids = implode('|',$download);
                $arr = array('hash'=>$v['hash'],'id'=>$_ids, 'priority'=>'1');
                $this->setFilePriority($arr);
            }

            if(count($_files)<=1){
                if(isset($partconfig['single_file_category']) && $partconfig['single_file_category']!='' && isset($partconfig['single_file_size']) && $partconfig['single_file_size'] !=''){
                    $single_file_category = explode(',',$partconfig['single_file_category']);
                    if($_files[0]['size'] < $this->util->toByte($partconfig['single_file_size']) && in_array($v['category'],$single_file_category) ){
                        $this->addTorrentTags(array('hashes'=>$v['hash'],'tags'=>'小于'.$partconfig['single_file_size'].'G不拆'));
                        $arr = array('hash'=>$v['hash'],'id'=>$_files[0]['index'], 'priority'=>'1');
                        $this->setFilePriority($arr);
                        $this->resumeTorrents($v['hash']);
                        $this->echoString('小于'.$partconfig['single_file_size'].'G不拆:'.$v['name'].'('.$v['hash'].')');
                        continue;
                    }
                }
                $this->addTorrentTags(array('hashes'=>$v['hash'],'tags'=>'无法拆包'));
                $this->deleteTorrents($v['hash']);
                $this->echoString('删除无法拆包:'.$v['name'].'('.$v['hash'].')');
            }else{
                $this->addTorrentTags(array('hashes'=>$v['hash'],'tags'=>'已拆包'));
                $this->resumeTorrents($v['hash']);
                $this->echoString('已拆包:'.$v['name'].'('.$v['hash'].')');
            }
        }
    }

    public function limitspeed($data){


    }


    /**
     * 自动删种
     */
    public function autoRemoveTorrents(){
        $info = $this->maindata();
        $free_space_on_disk = $info['server_state']['free_space_on_disk'];
        $data = $this->getTorrentsInfo();
        /*根据upspeed,num_leechs,num_incomplete升序排序*/
        $sort = new sort();
        usort($data,  array($sort, 'customSort'));
        $this->saveInfo($data);

        /*计数*/
        $sznum = 0;
        foreach ($data as $k => $v){
            if($v['state'] == 'error'){
                $this->deleteTorrents($v['hash']);
                $this->echoString('删除错误种子:'.$v['name'].'('.$v['hash'].')');
            }
            if(strpos($v['tags'], '已拆包') !== false && ( $v['size']> $this->util->toByte($partconfig['max_size']+2) || $v['size'] == 0 ) ){
                $this->deleteTorrents($v['hash']);
                $this->echoString('删除拆包不正确的种子:'.$v['name'].'('.$v['hash'].')');
            }

            if($this->util->toGB($free_space_on_disk) < $this->config['removetorrents']['free_size']){
                if($v['progress']>0.3 && $v['upspeed']<500*1024 && $sznum < $this->config['removetorrents']['deletenum'] ){
                    $sznum+=1;
                    $this->deleteTorrents($v['hash']);
                    $this->echoString('空间不足30G，删除较为低速的种子:'.$v['name'].'('.$v['hash'].')');
                }

            }else{
                if($v['progress'] == 1 && ($v['num_incomplete']+$v['num_leechs'])<6 && $v['upspeed']<50*1024 ){
                    $sznum+=1;
                    $this->deleteTorrents($v['hash']);
                    $this->echoString('删除较为低速的种子:'.$v['name'].'('.$v['hash'].')');
                }
            }


        }

    }

    public function saveInfo($data = array()){
        if(count($data)>0){
            foreach ($data as $k => $v){
                $_added_on = date('Y-m-d H:i:s',$v['added_on']);
                $_completion_on = date('Y-m-d H:i:s',$v['completion_on']);
                $sql = "INSERT INTO `mysql`.`torrent_info`(`name`, `size`, `hash`, `category`, `upload_speed`, `download_speed`, `eta`, `share_ratio`, `progress`, `num_incomplete`, `num_leechs`, `tags`, `status`, `added_on`, `completion_on`) VALUES ('{$v['name']}', {$v['size']}, '{$v['hash']}', '{$v['category']}', {$v['upspeed']}, {$v['dlspeed']}, {$v['eta']}, {$v['ratio']}, {$v['progress']}, {$v['num_incomplete']}, {$v['num_leechs']}, '{$v['tags']}', '{$v['state']}', '{$_added_on}', '{$_completion_on}')";
                MySQL::query($sql);
            }
        }else{
            return false;
        }

    }


    public function echoString($str,$savelog=true,$sendiyuu = true){
        echo $str.PHP_EOL;
        echo "<br/>";
        if($savelog){
            $this->savelog($str);
        }
        if($sendiyuu){
            $this->sendiyuu(substr($str, 0, strpos($str, ":")),$str);
        }
    }

    public function savelog($message){
        $time = date('Y-m-d',time());
        $log_path = __DIR__.'/../log/'.$time;
        $logfile = __DIR__.'/../log/'.$time.'/logfile.log';
        if (!is_dir($log_path))  mkdir($log_path,0777,true);
        error_log($message.PHP_EOL, 3, $logfile);
    }

    public function sendiyuu($text,$torrentName){
        if($this->config['iyuu']['token'] && $this->config['iyuu']['token'] !='' && preg_match('/^IYUU/', $this->config['iyuu']['token']) ){
            $url = 'https://iyuu.cn/'.$this->config['iyuu']['token'].'.send?text='.$text.'&desp='.$torrentName;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,

            ));
            $response = curl_exec($curl);
            curl_close($curl);
            echo $response;
        }else{
            echo '未配置iyuutoken';
        }

    }

    public function test(){
        $this->echoString($this->config['iyuu']['token']);
        echo PHP_EOL;
        $data = $this->getTorrentsInfo();
        $this->saveInfo($data);
        foreach ($data as $k => $v){
            echo $v['name'];
            echo PHP_EOL;
        }
    }

}