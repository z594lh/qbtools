<?php

class qbApi
{
    public $config;
    public $apiUrl;

    public function __construct()
    {
        $this->config = parse_ini_file(__DIR__.'/../config/config.ini', true);
        $this->apiUrl = 'http://'.$this->config['qb']['host'].':'.$this->config['qb']['port'].'/api/v2/';

    }


    /**
     * 登录保存cookie
     * @return bool|string
     */
    public function login(){
        $cookieFile = __DIR__.'/../config/response_cookies.txt';  // 指定保存 Cookie 的文件路径

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl.'auth/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('username' => $this->config['qb']['username'], 'password' => $this->config['qb']['password']),
            CURLOPT_COOKIEJAR => $cookieFile,  // 保存返回的 Cookie
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;


    }

    /**
     * 获取保存的cookie
     * @return bool|string
     */
    public function getCookie(){
        $cookieFile = __DIR__.'/../config/response_cookies.txt';  // 指定保存 Cookie 的文件路径
        // 读取保存的 Cookie
        $cookieContent = file_get_contents($cookieFile);

        // 使用 strpos 和 substr 提取 SID 的值
        $sidIndex = strpos($cookieContent, 'SID');
        if ($sidIndex !== false) {
            $sidValue = substr($cookieContent, $sidIndex + 4);
            $sidValue = trim($sidValue); // 去除可能的空格、换行等
            return "SID=".$sidValue;
        } else {
            return false;
        }
    }

    /**
     * 请求curl
     * @param $api
     * @param string $type
     * @param array $param
     * @return bool|mixed|string
     */
    public function curlapi($api,$type = 'GET',$param=array()){
        $cookie = $this->getCookie();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl.$api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HEADER => false,  // 包含响应头
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                'Cookie: '.$cookie,
            ),
        ));
        $response = curl_exec($curl);

        // 获取 HTTP 状态码
        $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($response == 'Forbidden'){
            $login_rst = $this->login();
            if($login_rst == 'Ok.'){
                return $this->curlapi($api,$type,$param);
            }
        }

        curl_close($curl);
        if(!$response){
            return $httpStatusCode;
        }
        return $response;
    }

    /**
     * 获取种子列表详细信息
     * @return mixed
     */
    public function getTorrentsInfo(){
        $info = $this->curlapi('torrents/info','GET');
        return json_decode($info,true);
    }

    /**
     * 获取maindata 剩余磁盘空间等全局参数
     * @param int $rid
     * @return mixed
     */
    public function maindata($rid=0){
        $info = $this->curlapi('sync/maindata','POST',array('rid'=>$rid));
        return json_decode($info,true);
    }

    /**
     * 根据种子hash获取文件列表
     * @param $hash
     * @return bool|mixed
     */
    public function torrents_files($hash){
        if(!$hash){
            return false;
        }
        $info = $this->curlapi('torrents/files?hash='.$hash,'GET');
        return json_decode($info,true);
    }

    /**
     * 设置种子文件优先级
     * $arr = array('hash'=>'xx','id'=>'2|5', 'priority'=>'1');
     * hash:种子hash
     * id:torrents_files接口返回的index
     * priority: 0:Do not download 1:Normal priority 6:High priority 7:Maximal priority
     * @param $arr
     * @return bool
     */
    public function setFilePriority($arr){
        if(!$arr || !is_array($arr)){
            return false;
        }
        $queryString = http_build_query($arr);
        $info = $this->curlapi('torrents/filePrio?'.$queryString,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加种子标签
     * $arr = array('hashes'=>'xx|xx','tags'=>'TagName1,TagName2');
     * hashes:种子hash,|隔开
     * tags:逗号隔开的标签
     * @param $arr
     * @return bool
     */
    public function addTorrentTags($arr){
        if(!$arr || !is_array($arr)){
            return false;
        }
        $queryString = http_build_query($arr);
        $info = $this->curlapi('torrents/addTags?'.$queryString,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 移除种子标签
     * $arr = array('hashes'=>'xx|xx','tags'=>'TagName1,TagName2');
     * hashes:种子hash,|隔开
     * tags:逗号隔开的标签
     * @param $arr
     * @return bool
     */
    public function removeTorrentTags($arr){
        if(!$arr || !is_array($arr)){
            return false;
        }
        $queryString = http_build_query($arr);
        $info = $this->curlapi('torrents/removeTags?'.$queryString,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除种子
     * hashes:种子hash,|隔开
     * deleteFiles:true/false
     * @param $arr
     * @return bool
     */
    public function deleteTorrents($hashes){
        if(!$hashes){
            return false;
        }
        $arr = array('hashes'=>$hashes,'deleteFiles'=>'true');
        $queryString = http_build_query($arr);
        $info = $this->curlapi('torrents/delete?'.$queryString,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 种子暂停
     * @param $hashes 'xx|xx'
     * @return bool
     */
    public function pauseTorrents($hashes){
        if(!$hashes){
            return false;
        }
        $info = $this->curlapi('torrents/pause?hashes='.$hashes,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 种子继续
     * @param $hashes 'xx|xx'
     * @return bool
     */
    public function resumeTorrents($hashes){
        if(!$hashes){
            return false;
        }
        $info = $this->curlapi('torrents/resume?hashes='.$hashes,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 限制下载上传速度
     * $arr = array('hashes'=>'xx|xx','limit'=>123456);
     * hashes:种子hash,|隔开
     * limit:单位(byte)
     * @param $arr
     * @return bool
     */
    public function setTorrentDownloadLimit($arr){
        if(!$arr || !is_array($arr)){
            return false;
        }
        $queryString = http_build_query($arr);
        $info = $this->curlapi('torrents/setDownloadLimit?'.$queryString,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 限制种子上传速度
     * $arr = array('hashes'=>'xx|xx','limit'=>123456);
     * hashes:种子hash,|隔开
     * limit:单位(byte)
     * @param $arr
     * @return bool
     */
    public function setTorrentUploadLimit($arr){
        if(!$arr || !is_array($arr)){
            return false;
        }
        $queryString = http_build_query($arr);
        $info = $this->curlapi('torrents/setUploadLimit?'.$queryString,'GET');
        if (strpos($info, '200') !== false) {
            return true;
        } else {
            return false;
        }
    }


}