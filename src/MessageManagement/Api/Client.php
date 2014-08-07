<?php

namespace Dyn\MessageManagement\Api;

use Zend\Http\Client as HttpClient;
use Zend\Http\Request;
use Dyn\MessageManagement\Api\Response;
use Dyn\MessageManagement\Api\Http\Response as HttpResponse;

class Client
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * This property holds the most recent HTTP response sent by the API.
     * Can be useful when debugging issues.
     *
     * @var Zend\Http\Response
     */
    protected $lastHttpResponse;

    /**
     * This property holds the most recent API response. Can be useful when
     * debugging issues.
     *
     * @var Response
     */
    protected $lastResponse;


    /**
     * Constructor
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Getter for the HTTP client instance
     *
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $config = array(
                'useragent' => 'Dyn PHP SDK v0.2.0',
                'maxredirects' => 5
            );

            // use curl if available
            if (extension_loaded('curl')) {
                 $config['adapter'] = 'Zend\Http\Client\Adapter\Curl';
            }

            $this->httpClient = new HttpClient(null, $config);
        }

        return $this->httpClient;
    }

    /**
     * Builds a request object for the given API path
     *
     * @param  string  $path
     * @return Zend\Http\Request
     */
    protected function buildRequest($path)
    {
        $request = new Request();
        $request->setUri('https://emailapi.dynect.net/rest/json'.$path);

        return $request;
    }

    /**
     * Setter for API key
     *
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Getter for API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Removes the existing API key
     *
     * @return void
     */
    public function clearToken()
    {
        $this->apiKey = null;
    }

    /**
     * Perform an API request.
     *
     * Returns a Response object if the API HTTP request return a valid
     * response, false otherwise.
     *
     * @param  Request $request
     * @return Response|false
     */
    protected function apiRequest(Request $request)
    {
        $httpClient = $this->getHttpClient();

        // ensure our custom HTTP response class is used instead of the
        // normal HTTP response class
        $httpResponse = new HttpResponse();
        $httpClient->setResponse($httpResponse);

        $this->lastHttpResponse = $httpClient->dispatch($request);
        if ($this->lastHttpResponse->isSuccess()) {
            $json = json_decode($this->lastHttpResponse->getBody());
            if (!$json) {
                return false;
            }

            // parse response and store
            $this->lastResponse = Response::fromJson($json);

            return $this->lastResponse;
        }

        return false;
    }

    /**
     * Returns the last HTTP response received from the API
     *
     * @return Zend\Http\Response
     */
    public function getLastHttpResponse()
    {
        return $this->lastHttpResponse;
    }

    /**
     * Returns the last API response received
     *
     * @return Response
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Perform a GET request to the API
     *
     * @param  string $path  API path (excluding the /REST part)
     * @param  array  $data  Optional array of GET parameters
     * @return Response|false
     */
    public function get($path, array $data = null)
    {
        $request = $this->buildRequest($path);
        $request->setMethod(Request::METHOD_GET);

        $params = array(
            'apikey' => $this->getApiKey()
        );
        if ($data) {
            $params = array_merge($params, $data);
        }
        $request->getQuery()->fromArray($params);

        return $this->apiRequest($request);
    }

    /**
     * Perform a POST request to the API
     *
     * @param  string $path  API path (excluding the /REST part)
     * @param  array  $data  Optional array of POST data
     * @return Response|false
     */
    public function post($path, array $data = null)
    {
        $request = $this->buildRequest($path);
        $request->setMethod(Request::METHOD_POST);

        $params = array(
            'apikey' => $this->getApiKey()
        );
        if ($data) {
            $params = array_merge($params, $data);
        }

        $this->getHttpClient()->setEncType(HttpClient::ENC_FORMDATA);
        $request->getPost()->fromArray($params);

        return $this->apiRequest($request);
    }
}
