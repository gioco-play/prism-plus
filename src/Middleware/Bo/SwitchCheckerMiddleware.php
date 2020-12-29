<?php

declare(strict_types=1);

namespace App\Middleware\Bo;

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
 * BO 後台開關 / 商戶開關
 * Class IPCheckMiddleware
 * @package App\Middleware
 */
class SwitchCheckerMiddleware implements MiddlewareInterface
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

        if ($request->getUri()->getPath() === '/api/v1/auth/login') {
            return $handler->handle($request);
        }

        if (stripos($request->getUri()->getPath(), '/api/v1/') !== false) {
            $comp = null;
            $userInfo = $this->jwt->getParserData();
            if (in_array(trim(strtolower($userInfo['role'])), $this->cache->fullAccessRoles())) {
                return $handler->handle($request);
            }
            // 後台開關
            $status = $this->cache->platformSwitch('bo');
            switch ($status) {
                case GlobalConst::MAINTAIN :
                    return $this->customResponse([], ApiResponse::MAINTAIN);
            }
            // 商戶開關
            $comp = $this->cache->company($userInfo['company']);
            switch ($comp['status']) {
                case GlobalConst::MAINTAIN :
                    return $this->customResponse([], ApiResponse::MAINTAIN);
                case GlobalConst::DECOMMISSION :
                    return $this->customResponse([], ApiResponse::DECOMMISSION);
            }
        }

        return $handler->handle($request);
    }

    /**
     * @param array $data
     * @param $msg
     * @return SwooleStream
     */
    private function customResponse($data = [], $msg) {
        $stream = new SwooleStream(json_encode(ApiResponse::result($data, $msg)));
        return $this->response->withBody($stream)->withHeader('content-type', 'application/json');
    }
}