<?php

declare(strict_types=1);

namespace App\Middleware;

use GiocoPlus\PrismPlus\Helper\ApiResponse;
use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Service\CacheService;
use GiocoPlus\JWTAuth\JWT;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 後台IP白名單過濾 角色為supervisor不判斷
 * Class IPCheckMiddleware
 * @package App\Middleware
 */
class BoIPCheckMiddleware implements MiddlewareInterface
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


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->hasHeader('x-forwarded-for')
            ? $request->getHeader('x-forwarded-for')
            : $request->getServerParams()['remote_addr'];

        if (stripos($request->getUri()->getPath(), '/api/v1/') !== false) {
            $comp = null;
            $userInfo = $this->jwt->getParserData();
            if (trim(strtolower($userInfo['role'])) === 'supervisor') {
                return $handler->handle($request);
            }
            $compCode =  $userInfo['company_code'] ?? "";
            if ($compCode) {
                $comp = $this->cache->company($compCode);
            }
            // 檢查來源IP
            if (!Tool::IpWhitelistCheck($ip, $comp['bo_whitelist'])) {
                $response = $handler->handle($request);
                return $response->withBody(new SwooleStream(
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