<?php

namespace app\service;

// use Laf\Cache;
use mon\env\Config;
use mon\util\Common;
use mon\util\Network;
use mon\util\Instance;

/**
 * 微信SDK工具类
 *
 * @author Mon <985558837@qq.com>
 * @version 2.0.0 支持h5支付
 * @version 2.1.0 增加服务号模板消息推送
 */
class WechatService
{
    use Instance;

    /**
     * AppID
     * 
     * @var string
     */
    protected $appid;

    /**
     * 秘钥
     * 
     * @var string
     */
    protected $secret;

    /**
     * 商户ID
     *
     * @var string
     */
    protected $mchid;

    /**
     * 商户API_KEY
     *
     * @var string
     */
    protected $mch_key;

    /**
     * 相关接口
     *
     * @var array
     */
    public $api = [
        'openid'            => 'https://api.weixin.qq.com/sns/jscode2session',
        'access_token'      => 'https://api.weixin.qq.com/cgi-bin/token',
        'user_access_token' => 'https://api.weixin.qq.com/sns/oauth2/access_token',
        'userinfo'          => 'https://api.weixin.qq.com/sns/userinfo',
        'msg_sec_check'     => 'https://api.weixin.qq.com/wxa/img_sec_check',
        'prepay'            => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
        'query_order'       => 'https://api.mch.weixin.qq.com/pay/orderquery',
        'jsapi_ticket'      => 'https://api.weixin.qq.com/cgi-bin/ticket/getticket',
        'send_template'     => 'https://api.weixin.qq.com/cgi-bin/message/template/send',
    ];

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error;

    /**
     * 构筑方法
     *
     * @param string $appid     APPID
     * @param string $secret    SECRET
     * @param string $mchid     商户ID
     * @param string $mch_key   商户KEY
     */
    public function __construct($config = [])
    {
        if (empty($config)) {
            $config = Config::instance()->get('wechat');
        }

        $this->appid = $config['appid'];
        $this->secret = $config['secret'];
        $this->mch_key = $config['mch_key'];
        $this->mchid = $config['mchid'];
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = '';

        return $error;
    }

    /**
     * 获取用户OpenID
     *
     * @param  string $code 小程序返回的code码
     * @return mixed
     */
    public function getOpenid($code)
    {
        $data = [
            'appid'         => $this->appid,
            'secret'        => $this->secret,
            'grant_type'    => 'authorization_code',
            'js_code'       => $code
        ];

        $res = Network::instance()->sendHTTP($this->api['openid'], $data, 'get', true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            // 获取失败
            $this->error = $res['errmsg'];
            return false;
        }

        return $res;
    }

    /**
     * 获取小程序全局唯一后台接口调用凭据
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        // 先判断是否存在缓存
        // $cache = Cache::instance()->get('access_token');
        // if ($cache) {
        //     return $cache;
        // }

        // 不存在缓存，发起请求获取
        $data = [
            'grant_type'    => 'client_credential',
            'appid'         => $this->appid,
            'secret'        => $this->secret,
        ];

        $res = Network::instance()->sendHTTP($this->api['access_token'], $data, 'get', true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            // 获取失败
            $this->error = $res['errmsg'];
            return false;
        }

        // 缓存
        // Cache::instance()->set('access_token', $res['access_token'], $res['expires_in']);
        return $res['access_token'];
    }

    /**
     * 获取用户Access_Token
     *
     * @param  string $code 小程序返回的code码
     * @return mixed
     */
    public function getUserAccessToken($code)
    {
        $data = [
            'appid'      => $this->appid,
            'secret'     => $this->secret,
            'code'       => $code,
            'grant_type' => 'authorization_code'
        ];

        $res = Network::instance()->sendHTTP($this->api['user_access_token'], $data, 'get', true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            // 获取失败
            $this->error = $res['errmsg'];
            return false;
        }

        // {
        //     "access_token":"ACCESS_TOKEN",
        //     "expires_in":7200,
        //     "refresh_token":"REFRESH_TOKEN",
        //     "openid":"OPENID",
        //     "scope":"SCOPE" 
        // }
        return $res;
    }

    /**
     * 获取jsapi_ticket，公众号用于调用微信JS接口的临时票据
     *
     * @return mixed
     */
    public function getJsApiTicket()
    {
        // 先判断是否存在缓存
        // $cache = Cache::instance()->get('jsapi_ticket');
        // if ($cache) {
        //     return $cache;
        // }

        $data = [
            'type'          => 'jsapi',
            'access_token'  => $this->getAccessToken()
        ];
        $res = Network::instance()->sendHTTP($this->api['jsapi_ticket'], $data, 'get', true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            // 获取失败
            $this->error = $res['errmsg'];
            return false;
        }

        // 缓存
        // Cache::instance()->set('ticket', $res['ticket'], $res['expires_in']);
        return $res['ticket'];
    }

    /**
     * 获取js-sdk使用签名
     *
     * @param string $url 执行js的URL
     * @return mixed
     */
    public function getJsSign($url = '')
    {
        // 获取jsapi_ticket
        $ticket = $this->getJsApiTicket();
        // 随机字符串
        $nonce_str = Common::instance()->randString(32);
        // 当前时间
        $time = time();
        // 签名
        $string = "jsapi_ticket={$ticket}&noncestr={$nonce_str}&timestamp={$time}&url={$url}";
        $signature = sha1($string);

        // 返回签名包
        $signPackage = [
            'appid'     => $this->appid,
            'nonceStr'  => $nonce_str,
            'timestamp' => $time,
            'url'       => $url,
            'signature' => $signature,
            'rawString' => $string
        ];
        return $signPackage;
    }

    /**
     * 获取用户信息
     *
     * @param  string $code 小程序返回的code码
     * @param  string $lang 语言类型
     * @return mixed
     */
    public function getUserInfo($code, $lang = 'zh_CN')
    {
        $user_access_token = $this->getUserAccessToken($code);
        if (!$user_access_token) {
            return false;
        }

        $data = [
            'access_token'  => $user_access_token['access_token'],
            'openid'        => $user_access_token['openid'],
            'lang'          => $lang
        ];

        $res = Network::instance()->sendHTTP($this->api['userinfo'], $data, 'get', true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            // 获取失败
            $this->error = $res['errmsg'];
            return false;
        }

        // {   
        //     "openid":" OPENID",
        //     "nickname": NICKNAME,
        //     "sex":"1",
        //     "province":"PROVINCE"
        //     "city":"CITY",
        //     "country":"COUNTRY",
        //     "headimgurl":"http://thirdwx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
        //     "privilege":[ "PRIVILEGE1" "PRIVILEGE2"     ],
        //     "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
        // }

        return $res;
    }

    /**
     * 检查一段文本是否含有违法违规内容。
     *
     * @see   频率限制：单个 appId 调用上限为 4000 次/分钟，2,000,000 次/天
     * @param  string $content 文本内容
     * @return boolean
     */
    public function msgSecCheck($content)
    {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }
        $url = $this->api['msg_sec_check'] . '?access_token=' . $access_token;
        $data = [
            'content' => $content
        ];
        $res = Network::instance()->sendHTTP($url, $data, 'post', true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            // 获取失败
            $this->error = $res['errmsg'];
            return false;
        }

        return true;
    }

    /**
     * JSApi发起支付请求
     *
     * @param  string  $body             内容
     * @param  integer $total_fee        价格，单位分
     * @param  string  $order_id         订单ID
     * @param  string  $openid           用户openid
     * @param  string  $notify_url       通知回调路径 
     * @return mixed
     */
    public function jsApiPay($body, $total_fee, $order_id, $openid, $notify_url = '')
    {
        // 随机字符串
        $nonce_str = Common::instance()->randString(32);
        //服务器终端的ip
        $spbill_create_ip = $_SERVER['SERVER_ADDR'];
        // 统一下单
        $orders = $this->orders($spbill_create_ip, $body, $total_fee, $order_id, 'JSAPI', $openid, $nonce_str, $notify_url);
        if (!$orders) {
            return false;
        }

        // 结果集
        $data = [];
        //临时数组用于签名
        $tmp = [];
        $time = time();
        $tmp['appId'] = $this->appid;
        $tmp['nonceStr'] = $nonce_str;
        $tmp['package'] = 'prepay_id=' . $orders['PREPAY_ID'];
        $tmp['signType'] = 'MD5';
        $tmp['timeStamp'] = (string) $time;

        $data['state'] = 1;
        $data['timeStamp'] = (string) $time;
        $data['nonceStr'] = $nonce_str;
        $data['signType'] = 'MD5';
        $data['package'] = 'prepay_id=' . $orders['PREPAY_ID'];
        $data['paySign'] = $this->makeSign($tmp);
        $data['out_trade_no'] = $order_id;

        return $data;
    }

    /**
     * h5支付
     *
     * @param  string  $body             内容
     * @param  integer $total_fee        价格，单位分
     * @param  string  $order_id         订单ID
     * @param  string  $notify_url       通知回调路径 
     * @return mixed
     */
    public function h5Pay($body, $total_fee, $order_id, $notify_url = '')
    {
        // 随机字符串
        $nonce_str = Common::instance()->randString(32);
        //客户终端的ip
        $spbill_create_ip = $_SERVER['REMOTE_ADDR'];
        // 统一下单
        $orders = $this->orders($spbill_create_ip, $body, $total_fee, $order_id, 'MWEB', '', $nonce_str, $notify_url);
        if (!$orders) {
            return false;
        }

        return $orders;
    }

    /**
     * 统一下单
     *
     * @param  string  $spbill_create_ip 发起支付请求的IP，h5支付为客户端IP,即[REMOTE_ADDR],其他为服务端IP,即[SERVER_ADDR]
     * @param  string  $body             内容
     * @param  integer $total_fee        价格，单位分
     * @param  string  $order_id         商户订单号
     * @param  string  $trade_type       交易类型
     * @param  string  $openid           用户openid
     * @param  string  $nonce_str        随机字符串
     * @param  string  $notify_url       回调通知路径
     * @return mixed
     */
    public function orders($spbill_create_ip, $body, $total_fee, $order_id, $trade_type, $openid, $nonce_str, $notify_url)
    {
        // 这里是按照顺序的 因为下面的签名是按照(字典序)顺序 排序错误 肯定出错
        $post['appid'] = $this->appid;
        $post['body'] = $body;
        $post['mch_id'] = $this->mchid;
        $post['nonce_str'] = $nonce_str;
        $post['notify_url'] = $notify_url;
        if (!empty($openid)) {
            $post['openid'] = $openid;
        }
        $post['out_trade_no'] = $order_id;
        $post['spbill_create_ip'] = $spbill_create_ip;
        // 总金额, 最低为一分钱, 必须是整数
        $post['total_fee'] = intval($total_fee);
        $post['trade_type'] = $trade_type;
        // 签名
        $sign = $this->makeSign($post);

        $post_xml = '<xml>
               <appid>' . $this->appid . '</appid>
               <body>' . $body . '</body>
               <mch_id>' . $this->mchid . '</mch_id>
               <nonce_str>' . $nonce_str . '</nonce_str>
               <notify_url>' . $notify_url . '</notify_url>
               <openid>' . $openid . '</openid>
               <out_trade_no>' . $order_id . '</out_trade_no>
               <spbill_create_ip>' . $spbill_create_ip . '</spbill_create_ip>
               <total_fee>' . $total_fee . '</total_fee>
               <trade_type>' . $trade_type . '</trade_type>
               <sign>' . $sign . '</sign>
            </xml> ';

        // 统一下单接口prepay_id
        $url = $this->api['prepay'];
        $xml = Network::instance()->sendHTTP($url, $post_xml, 'post');
        // 将【统一下单】api返回xml数据转换成数组，全要大写
        $array = Common::instance()->xml2array($xml);
        if ($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS') {
            // 成功，返回结果集
            return $array;
        } else {
            $this->error = $array['RETURN_MSG'];
            return false;
        }
    }

    /**
     * 查询订单状态(是否已支付)
     *
     * @param string $out_trade_no      系统订单号
     * @param string $transaction_id    微信订单号
     * @return boolean
     */
    public function queryOrder($out_trade_no = null, $transaction_id = null)
    {
        $nonce_str = Common::instance()->randString(32);
        $post['appid'] = $this->appid;
        $post['mch_id'] = $this->mchid;
        // 随机字符串
        $post['nonce_str'] = $nonce_str;
        // 使用wx订单ID或自服务系统订单ID进行查询，优先使用系统订单号
        if (!is_null($out_trade_no)) {
            $post['out_trade_no'] = $out_trade_no;
        } else {
            $post['transaction_id'] = $transaction_id;
        }
        $sign = $this->makeSign($post);
        $post_xml = '<xml>
               <appid>' . $this->appid . '</appid>
               <mch_id>' . $this->mchid . '</mch_id>
               <nonce_str>' . $nonce_str . '</nonce_str>';
        if (isset($post['out_trade_no'])) {
            $post_xml .= '<out_trade_no>' . $out_trade_no . '</out_trade_no>';
        } else {
            $post_xml .= '<transaction_id>' . $transaction_id . '</transaction_id>';
        }
        $post_xml .= '<sign>' . $sign . '</sign></xml>';
        $xml = Network::instance()->sendHTTP($this->api['query_order'], $post_xml, 'post');
        // 将【统一下单】api返回xml数据转换成数组，全要大写
        $array = Common::instance()->xml2array($xml);
        if (array_key_exists("RETURN_CODE", (array) $array) && array_key_exists("RESULT_CODE", (array) $array) && $array["RETURN_CODE"] == "SUCCESS" && $array["RESULT_CODE"] == "SUCCESS") {
            return true;
        }

        $this->error = '未支付或不存在订单';
        return false;
    }

    /**
     * 发送服务号模板消息
     *
     * @param string $temp_id 模板ID
     * @param string $toUser 接收用户openID
     * @param array $data 模板数据
     * @return boolean
     */
    public function sendTemplateMessage($temp_id, $toUser, $data, $link = '')
    {
        $access_token = $this->getAccessToken();
        $url = $this->api['send_template'] . '?access_token=' . $access_token;

        $sendData = [
            'touser' => $toUser,
            'template_id' => $temp_id,
            'topcolor' => '#FF0000',
            'data' => $data,
            'url' => $link
        ];

        $json = json_encode($sendData, JSON_UNESCAPED_UNICODE);
        $result = Network::instance()->sendHTTP($url, $json, 'post', true);
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            // 获取失败
            $this->error = $result['errmsg'];
            return false;
        }

        return $result;
    }

    /**
     * 生成签名
     *
     * @return string 签名
     */
    protected function makeSign($params)
    {
        // 签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = http_build_query($params);
        // 签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->mch_key;
        // 签名步骤三：MD5加密
        $string = md5($string);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
}
