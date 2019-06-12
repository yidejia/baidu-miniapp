<?php
/**
 * Created by PhpStorm.
 * User: f-oris
 * Date: 2019/6/10
 * Time: 3:50 PM
 */

namespace EasySmartProgram\Support\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use EasySmartProgram\Support\Http\Middleware\MiddlewareInterface;

/**
 * Class HttpClient
 * @package EasySmartProgram\Support\Http
 * @author  f-oris <us@f-oris.me>
 * @version 1.0.0
 */
class HttpClient
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var HandlerStack
     */
    protected $handlerStack;

    /**
     * @var array
     */
    protected $middleware = [];

    /**
     * @var
     */
    protected $baseUri;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected static $defaults = [
        'curl' => [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ],
    ];

    /**
     * HttpClient constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config = [])
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function pushMiddleware(MiddlewareInterface $middleware)
    {
        $this->middleware[$middleware->name()] = $middleware->callable();
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function removeMiddleware(string $name)
    {
        unset($this->middleware[$name]);
        return $this;
    }

    /**
     * @param \GuzzleHttp\HandlerStack $handlerStack
     *
     * @return $this
     */
    public function setHandlerStack(HandlerStack $handlerStack)
    {
        $this->handlerStack = $handlerStack;

        return $this;
    }

    /**
     * Build a handler stack.
     *
     * @return \GuzzleHttp\HandlerStack
     */
    public function getHandlerStack(): HandlerStack
    {
        if ($this->handlerStack) {
            return $this->handlerStack;
        }

        $this->handlerStack = HandlerStack::create();

        foreach ($this->middleware as $name => $middleware) {
            $this->handlerStack->push($middleware, $name);
        }

        return $this->handlerStack;
    }

    /**
     * @return Client|ClientInterface
     */
    protected function client()
    {
        if (!($this->client instanceof ClientInterface)) {
            $this->client = new Client($this->config);
        }
        return $this->client;
    }

    /**
     * @param string $url
     * @param array  $query
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(string $url, array $query = [])
    {
        return $this->request($url,'GET', ['query' => $query]);
    }

    /**
     * @param string $url
     * @param array  $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(string $url, array $data = [])
    {
        return $this->request($url, 'POST', ['form_params' => $data]);
    }

    /**
     * @param string $url
     * @param array  $data
     * @param array  $query
     * @return ResponseInterface|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function postJson(string $url, array $data = [], array $query = [])
    {
        return $this->request($url, 'POST', ['query' => $query, 'json' => $data]);
    }

    /**
     * @param string $url
     * @param array  $files
     * @param array  $form
     * @param array  $query
     * @return ResponseInterface|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function upload(string $url, array $files = [], array $form = [], array $query = [])
    {
        $multipart = [];

        foreach ($files as $name => $path) {
            $multipart[] = [
                'name' => $name,
                'contents' => fopen($path, 'r'),
            ];
        }

        foreach ($form as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->request($url, 'POST', [
            'query' => $query, 'multipart' => $multipart, 'connect_timeout' => 30, 'timeout' => 30, 'read_timeout' => 30
        ]);
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $options
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $url, $method = 'GET', $options = []) : Response
    {
        $method = strtoupper($method);

        $options = array_merge(self::$defaults, $options, ['handler' => $this->getHandlerStack()]);

        $options = $this->fixJsonIssue($options);

        return $this->client()->request($method, $url, $options);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function fixJsonIssue(array $options): array
    {
        if (isset($options['json']) && is_array($options['json'])) {
            $options['headers'] = array_merge($options['headers'] ?? [], ['Content-Type' => 'application/json']);

            if (empty($options['json'])) {
                $options['body'] = \GuzzleHttp\json_encode($options['json'], JSON_FORCE_OBJECT);
            } else {
                $options['body'] = \GuzzleHttp\json_encode($options['json'], JSON_UNESCAPED_UNICODE);
            }

            unset($options['json']);
        }

        return $options;
    }
}