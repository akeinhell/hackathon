<?php

namespace App\API;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class YandexConnector {

    /**
     * Yandex API url
     */
    const API_URL = 'https://search-maps.yandex.ru/v1/';
    const LANG    = 'ru_RU';
    const API_KEY = 'YANDEX_API_KEY';
    const CACHE_TIME = 120;

    /**
     * Organization types
     */
    const BIZ_TYPE = 'biz';
    const GEO_TYPE = 'geo';

    /**
     * @var Client
     */
    private $client;

    /**
     * Type of organization
     *
     * @var string
     */
    private $type;

    /**
     * Query string
     *
     * @var string
     */
    private $query;

    /**
     * Singleton class instance
     *
     * @var YandexConnector
     */
    private static $instance;

    public function __construct()
    {
        if (!getenv(static::API_KEY)) {
            throw new \Exception(static::API_KEY.' must be defined');
        }

        $this->client = new Client([
            'base_uri' => static::API_URL,
        ]);
    }

    /**
     * @return YandexConnector
     */
    public static function init()
    {
        if (static::$instance === null) {
            static::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * @return \Psr\Http\Message\StreamInterface
     * @throws \HttpResponseException
     */
    public function makeRequest()
    {
        $response = $this->client->get([], [
            'query' => [
                'apikey' => getenv(static::API_KEY),
                'lang'   => static::LANG,
                'text'   => $this->query,
                'type'   => $this->type,
                'results'=> 1
            ]
        ]);

        $cacheKey = sprintf('api_yandex_%s_%s_%s_%s', getenv(static::API_KEY), static::LANG, $this->query, $this->type);

        if (!Cache::has($cacheKey)) {
            if ($response->getStatusCode() !== 200) {
                throw new \HttpResponseException('Wrong request sended');
            }

            Cache::put($cacheKey, $this->formatResponse($response->getBody()), static::CACHE_TIME);

            return $this->formatResponse($response->getBody());
        }

        return Cache::get($cacheKey);
    }

    /**
     * @param $response
     *
     * @return array
     * @throws \Exception
     */
    private function formatResponse($response)
    {
        $data = json_decode($response);

        if ($data === null) {
            throw  new \Exception('Incorrect response format');
        }

        return $data;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $query
     *
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }
}