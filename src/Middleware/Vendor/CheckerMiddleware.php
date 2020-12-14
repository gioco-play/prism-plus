<?php

declare(strict_types=1);

namespace App\Middleware;

use GiocoPlus\PrismPlus\Helper\ApiResponse;
use GiocoPlus\PrismPlus\Helper\GlobalConst;
use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Service\CacheService;
use GiocoPlus\JWTAuth\JWT;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Vendor 狀態 \ IP 檢查
 * Class IPCheckMiddleware
 * @package App\Middleware
 */
class CheckerMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject()
     * @var CacheService
     */
    protected $cache;

    /**
     * @Inject()
     * @var JWT
     */
    protected $jwt;

    /**
     * @var HttpResponse
     */
    protected $response;


    public function __construct(ContainerInterface $container, HttpResponse $response)
    {
        $this->container = $container;
        $this->response = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 請求的url http://{vendor}.playbox.co

        $ip = $request->hasHeader('x-forwarded-for')
            ? $request->getHeader('x-forwarded-for')
            : $request->getServerParams()['remote_addr'];

        list($vendorCode, $domain) = explode('.', $request->getUri()->getHost());

        if ($vendorCode) {
            $vendor = $this->cache->vendor(strtolower($vendorCode));
            switch ($vendor['status']) {
                case GlobalConst::MAINTAIN :
                    return $this->response->withBody($this->customResponse([], ApiResponse::MAINTAIN));
                case GlobalConst::DECOMMISSION :
                    return $this->response->withBody($this->customResponse([], ApiResponse::DECOMMISSION));
            }
            // 檢查來源IP
            if ($vendor['filter_ip'] && !Tool::IpContainChecker($ip, $vendor['ip_whitelist'])) {
                return $this->response->withBody($this->customResponse([
                    'ip' => $ip
                ], ApiResponse::IP_NOT_ALLOWED));
            }

            return $handler->handle($request);
        }

        return $this->response->withBody($this->customResponse([], ApiResponse::VENDOR_REQUEST_FAIL));
    }

    /**
     * @param array $data
     * @param $msg
     * @return SwooleStream
     */
    private function customResponse($data = [], $msg) {
        return new SwooleStream(json_encode(ApiResponse::result($data, $msg)));
    }
}