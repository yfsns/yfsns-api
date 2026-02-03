<?php

namespace Plugins\Location\Example;

use App\Modules\Location\Drivers\BaseDriver;
use App\Modules\Location\DTOs\LocationRequest;
use App\Modules\Location\DTOs\LocationResponse;
use Exception;

/**
 * 示例定位驱动.
 *
 * 这是一个演示如何创建自定义定位插件的示例
 */
class ExampleLocationDriver extends BaseDriver
{
    /**
     * 您的API基础URL.
     */
    protected string $baseUrl = 'https://api.example.com';

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'example';
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * @inheritDoc
     */
    public function reverseGeocode(LocationRequest $request): LocationResponse
    {
        // TODO: 实现逆地理编码逻辑
        // 1. 构建API请求URL和参数
        // 2. 发送HTTP请求
        // 3. 解析响应数据
        // 4. 返回LocationResponse

        try {
            // 示例实现
            $url = $this->baseUrl . '/reverse';
            $params = [
                'key' => $this->apiKey,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
            ];

            $data = $this->httpRequest($url, $params);

            // 根据您的API响应格式解析数据
            return LocationResponse::success()
                ->setDriver($this->getName())
                ->setCoordinate($request->latitude, $request->longitude)
                ->setRawData($data);

            // 设置地址信息
            // $response->setAddress(...);
            // $response->setFormattedAddress(...);
        } catch (Exception $e) {
            return LocationResponse::fail($e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     */
    public function geocode(string $address, ?string $city = null): LocationResponse
    {
        // TODO: 实现地理编码逻辑

        try {
            $url = $this->baseUrl . '/geocode';
            $params = [
                'key' => $this->apiKey,
                'address' => $address,
            ];

            if ($city) {
                $params['city'] = $city;
            }

            $data = $this->httpRequest($url, $params);

            return LocationResponse::success()
                ->setDriver($this->getName())
                ->setRawData($data);
        } catch (Exception $e) {
            return LocationResponse::fail($e->getMessage())
                ->setDriver($this->getName());
        }
    }

    /**
     * @inheritDoc
     */
    public function getLocationByIp(string $ip): LocationResponse
    {
        // TODO: 实现IP定位逻辑

        try {
            $url = $this->baseUrl . '/ip';
            $params = [
                'key' => $this->apiKey,
                'ip' => $ip,
            ];

            $data = $this->httpRequest($url, $params);

            return LocationResponse::success()
                ->setDriver($this->getName())
                ->setRawData($data);
        } catch (Exception $e) {
            return LocationResponse::fail($e->getMessage())
                ->setDriver($this->getName());
        }
    }
}
