<?php

use Workerman\Protocols\Http\Response;


class Service
{


    public function loopStock()
    {
        global $global;
        $hi = (int)date('Hi');

        //没开盘就没必要拉取了
        if(($hi>900 && $hi<1130) || ($hi>1300 && $hi<1500)){
            $global->rest = 0;
        }else{
            $global->rest = 1;
        }

        if($global->rest){
            $this->log("time:".$hi);
            return;
        }
        $stock = $this->getConfig('stock');
        $url   = $this->getConfig('url');
        $data  = [];
        foreach ($stock as $stockCode) {
            $result     = file_get_contents($url . $stockCode);
            $result     = mb_convert_encoding($result, "utf8", "gb2312");
            $start      = strpos($result, '"');   //第一次出现的位置
            $last       = strripos($result, '"'); //最后一次出现的位置
            $stockStr   = substr($result, $start + 1, $last - $start - 1);
            $stockArray = explode(",", $stockStr);
            if (count($stockArray) !== 34 && count($stockArray) !== 33) {
                $this->log("不存在的代码？" . $stockCode);
                continue;
            }
            do {
                $old_value             = $new_value = $global->stockData;
                $new_value[$stockCode] = [
                    'title' => $stockArray[0],
                    'zx'    => $stockArray[3],
                    'zdf'   => round(($stockArray[3] - $stockArray[2]) / $stockArray[2] * 100, 3),
                ];
            } while (!$global->cas('stockData', $old_value, $new_value));


            /*            $stockTitle = $stockArray[0]."[".$stockCode."]";
                        $stockInfo = "最新：".$stockArray[3]."\n".
                                     "涨跌：".round($stockArray[3]-$stockArray[2], 3)."\n".
                                     "涨幅：".round(($stockArray[3]-$stockArray[2])/$stockArray[2]*100, 3)."%%\n".
                                     "今开：".$stockArray[1]."\n".
                                     "昨收：".$stockArray[2]."\n".
                                     "最高：".$stockArray[4]."\n".
                                     "最低：".$stockArray[5]."\n".
                                     "总手：".
                                     ((substr($stockCode,0,1) != 3)?
                                         (array_key_exists($stockCode, $stockIndex)?round(($stockArray[8]/100000000),3)."亿":round(($stockArray[8]/1000000),3)."万")
                                         :(array_key_exists($stockCode, $stockIndex)?round(($stockArray[8]/10000000000),3)."亿":round(($stockArray[8]/1000000),3)."万"))
                                     ."\n".
                                     "金额：".(array_key_exists($stockCode, $stockIndex)?round(($stockArray[9]/100000000),3)."亿":round(($stockArray[9]/10000),3)."万")."\n".
                                     "更新：".$stockArray[30]." ".$stockArray[31];*/
        }


    }

    /**
     * 日志记录
     *
     * @param        $message
     * @param string $type
     *
     * @author xietaotao
     */
    public function log($message, $type = 'info')
    {

        $logPath = __DIR__ . DIRECTORY_SEPARATOR . 'logs/' . date('Ymd') . '_' . $type . '.log';
        file_put_contents($logPath, 'Time:' . date('Y-m-d H:i:s') . ',Message:' . $message . "\r\n", FILE_APPEND);

        echo($message . "\r\n");

    }

    /**
     * get
     *
     * @param $url
     *
     * @return mixed
     * @author xietaotao
     */
    public function get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getConfig('curlTimeOut')); //设置请求超时时间 秒
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result     = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        if (false === $result) {
            return [
                'result'     => false,
                'http_code'  => $http_code,
                'curl_errno' => $curl_errno,
            ];
        } else {
            return [
                'result' => $result,
            ];
        }
    }

    /**
     * 获取配置
     *
     * @param $id
     * @param $key
     *
     * @return string
     * @author xietaotao
     */
    public function getConfig($id, $key = '')
    {

        global $global;
        $config = $global->config;
        if ($id && $key) {
            return isset($config[$id][$key]) ? $config[$id][$key] : false;
        } elseif ($id) {
            return isset($config[$id]) ? $config[$id] : false;
        } else {
            return $config;
        }

    }


    /**
     * 获取请求结果集
     * @param string $message
     * @param array  $data
     *
     * @return Response
     * @author xietaotao
     */
    public function getResponse($message = 'success', $data = [])
    {

        if ($message == 'success') {
            $code = 1;
        } else {
            $code = 0;
        }
        $respond = [
            'code'    => $code,
            'message' => $message,
        ];
        if ($data) {
            $respond['data'] = $data;
        }
        $response = new Response(200, [
            'Content-Type' => 'application/json',
        ], json_encode($respond));

        return $response;
    }
}
