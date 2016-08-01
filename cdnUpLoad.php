<?php
// +----------------------------------------------------------------------
// | 网宿CDN SDK
// +----------------------------------------------------------------------
// | Author: niuxiaokang <363849474@qq.com>
// +----------------------------------------------------------------------
namespace Think;
class CdnUpload {
    /**
     * 生成网宿CDN token上传凭证
     */
    private function createToken() {

        // 构建上传策略数据
        $SecretKey  = C('SECRETKEY');
        $AccessKey  = C('ACCESSKEY');
        $deadline   = $this->getMillisecond() + C('DEADLINE');
        $scope      = C('SCOPE');
        $fsizeLimit = C('FSIZELIMIT');
        $overwrite  = C('OVERWRITE');
        $instant    = C('INSTANT');
        $separate   = C('SEPARATE');
        $data = array(
            'scope'      => $scope,
            'deadline'   => $deadline,
            'overwrite'  => $overwrite,
            'fsizeLimit' => $fsizeLimit,
            'instant'    => $instant,
            'separate'   => $separate
        );

        // 生成json数据
        $putPolicy = json_encode($data);

        // url安全的base64编码上传策略
        $encodePutPolicy = base64_encode($putPolicy);

        // hmac-sha1签名数据
        $sign = hash_hmac("sha1", $encodePutPolicy, $SecretKey);

        // url安全的base64编码签名数据
        $encodeSign = base64_encode($sign);

        // 生成上传凭证
        $uploadToken = $AccessKey . ':' . $encodeSign . ':' . $encodePutPolicy;
        return $uploadToken;
    }


    /**
     * 上传文件
     */
    public function upload() {

        // 构建上传表单
        $file = $_FILES['files']['name'];
        $tmpFile = $_FILES['files']['tmp_name'];
        
        $fsize = filesize($tmpFile);
        $fileBinaryData = fread(fopen($tmpFile,r),$fsize);
        
        $token = $this->createToken();
        $host = C('HOST');
        $year  = date('Y');
        $month = date('m');
        $day   = date('d');
        $path = 'php/'.$year.'/'.$month.'/'.$day.'/'.$file;
        $data = array (
            'token'          => $token,           // 上传凭证
            'key'            => $path,            // 自定义文件名
            'file'           => "@$tmpFile",      // 原文件名
            'fileBinaryData' => $fileBinaryData,  // 文件的二进制流
        );

        // 构建头部信息
        $header = array(
            'Host:' . $host,
            'Accept:*/*',
            'Content-Type: multipart/form-data;',
            //'Content-Length: ' . strlen($data)
        );
        
        $url = C('REQUESTURL');
        $result = $this->curl($url, $data, $header);
        if (!isset($result['code'])) {
        	$result = array('apath' => C('DOMIAN').$path, 'rpath' => $path);
        } else {
        	$result;
        }
        return json_encode($result);
    }


    /**
     *
     * @param $url
     * @param string $method
     * @param null $postFields
     * @param null $header
     *
     * @return mixed
     * @throws Exception
     */
    public function curl($url, $postFields = null, $header = null) {

		$ch = curl_init();
		
		//加@符号curl就会把它当成是文件上传处理
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postFields);
		
        if (!empty($header) && is_array($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $result = curl_exec($ch);
		curl_close($ch);
        return $result;
    }


    private function object2array(&$object) {
        return json_decode(json_encode($object), true);
    }


    /**
     * 获取当前毫秒值
     */
    private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }


    /**
     * @param base64把+替换-把/替换成_
     * @return string
     */
    private function urlsafe_base64_encode( $str ){
        return strtr(base64_encode($str), '+/', '-_');
    }
}