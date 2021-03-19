<?php
/**
 * @copyright Copyright (c) 2018 Larva Information Technology Co., Ltd.
 * @link http://www.larvacent.com/
 * @license http://www.larvacent.com/license/
 */

namespace Larva\AMAP;

use Larva\Support\HttpClient;
use Psr\Http\Message\RequestInterface;

/**
 * 高德
 */
class AMAPClient extends HttpClient
{
    /**
     * @var string Api Key
     */
    protected $apiKey;

    /**
     * The base URL for the request.
     *
     * @var string
     */
    protected $baseUrl = 'https://restapi.amap.com';

    /**
     * @return \Closure
     */
    public function buildBeforeSendingHandler()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if ($request->getMethod() == 'POST') {
                    $params = [];
                    parse_str($request->getBody()->getContents(), $params);
                } else {
                    $params = \GuzzleHttp\Psr7\Query::parse($request->getUri()->getQuery());
                }
                $params['key'] =  $this->apiKey;
                $params['output'] = 'JSON';
                $body = http_build_query($params, '', '&');
                if ($request->getMethod() == 'POST') {
                    $request = \GuzzleHttp\Psr7\Utils::modifyRequest($request, ['body' => $body]);
                } else {
                    $request = \GuzzleHttp\Psr7\Utils::modifyRequest($request, ['query' => $body]);
                }
                return $handler($request, $options);
            };
        };
    }

    /**
     * 坐标转换
     * @param string $locations 经度和纬度用","分割，经度在前，纬度在后，经纬度小数点后不得超过6位。多个坐标对之间用”|”进行分隔最多支持40对坐标。
     * @param string $coordSys 输入坐标系。可选值： gps、mapbar、baidu、autonavi(不进行转换)
     * @return bool|array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Larva\Support\Exception\ConnectionException
     */
    public function coordinateConvert($locations, $coordSys)
    {
        $parameters = ['locations' => $locations, 'coordsys' => $coordSys];
        $response = $this->getJSON('/v3/assistant/coordinate/convert', $parameters);
        if (is_array($response) && $response['status'] == 1 && $response['locations']) {
            return array_shift($response['locations']);
        }
        return false;
    }

    /**
     * 地理编码
     * @param string $address 结构化地址信息
     * @param null $city 指定查询的城市
     * @param string $callback 回调函数
     * @return array|false
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Larva\Support\Exception\ConnectionException
     */
    public function geo($address, $city = null, $callback = '')
    {
        $parameters = ['address' => $address, 'batch' => 'false'];
        if ($city) $parameters['city'] = $city;
        if ($callback) $parameters['callback'] = $callback;
        $response = $this->getJSON('/v3/geocode/geo', $parameters);
        if (is_array($response) && $response['status'] == 1 && $response['geocodes']) {
            return array_shift($response['geocodes']);
        }
        return false;
    }

    /**
     * 行政区查询
     * @param string $keywords 规则：只支持单个关键词语搜索关键词支持：行政区名称、citycode、adcode    例如，在subdistrict=2，搜索省份（例如山东），能够显示市（例如济南），区（例如历下区）
     * @param int $subdistrict
     * @param string $extensions
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Larva\Support\Exception\ConnectionException
     */
    public function district($keywords, $subdistrict = 1, $extensions = 'base')
    {
        $parameters = ['keywords' => $keywords, 'subdistrict' => $subdistrict, 'extensions' => $extensions, 'offset' => 200];
        $response = $this->getJSON('/v3/config/district', $parameters);
        if (is_array($response) && $response['status'] == 1 && $response['districts']) {
            return $response['districts'];
        }
        return false;
    }

    /**
     * IP定位
     * @param string $ip
     * @return array|false 返回的经纬度是 GCJ02
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Larva\Support\Exception\ConnectionException
     */
    public function ip(string $ip)
    {
        $response = $this->getJSON('/v3/ip', ['ip' => $ip]);
        if (is_array($response) && $response['status'] == 1 && $response['rectangle']) {
            return $response;
        }
        return false;
    }

    /**
     * 逆地理位置编码 接收 GCJ02 坐标
     * @param float $longitude
     * @param float $latitude
     * @param string $extensions
     * @return array|false
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Larva\Support\Exception\ConnectionException
     */
    public function regeo($longitude, $latitude, $extensions = 'base')
    {
        $response = $this->getJSON('/v3/geocode/regeo', ['location' => $longitude . ',' . $latitude, 'extensions' => $extensions]);
        if (is_array($response) && $response['status'] == 1 && is_array($response['regeocode']['addressComponent'])) {
            if ($response['regeocode']['addressComponent']) {
                return $response['regeocode']['addressComponent'];
            }
        }
        return false;
    }
}
