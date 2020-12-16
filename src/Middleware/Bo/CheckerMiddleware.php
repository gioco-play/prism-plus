<?php

declare(strict_types=1);

namespace App\Middleware\Bo;

use GiocoPlus\PrismPlus\Helper\ApiResponse;
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
 * 後台IP白名單過濾
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
        $ip = $request->hasHeader('x-forwarded-for')
            ? $request->getHeader('x-forwarded-for')
            : $request->getServerParams()['remote_addr'];

        if ($request->getUri()->getPath() === '/api/v1/auth/login') {
            return $handler->handle($request);
        }

        if (stripos($request->getUri()->getPath(), '/api/v1/') !== false) {
            $comp = null;
            $userInfo = $this->jwt->getParserData();
            if (in_array(trim(strtolower($userInfo['role'])), $this->cache->fullAccessRoles())) {
                return $handler->handle($request);
            }
            $compCode =  $userInfo['company'] ?? "";
            if ($compCode) {
                $comp = $this->cache->company($compCode);
            }
            // 檢查來源IP
            if (!Tool::IpContainChecker($ip, $comp['bo_whitelist'])) {
                return $this->response->withBody(new SwooleStream(
                        json_encode(ApiResponse::result([
                            'ip' => $ip
                        ], ApiResponse::IP_NOT_ALLOWED))
                    )
                );
            }
        }

        return $handler->handle($request);
    }
}