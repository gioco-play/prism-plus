<?php

declare(strict_types=1);

namespace App\Middleware;

use GiocoPlus\PrismConst\State\ApiState;
use GiocoPlus\PrismConst\Tool\ApiResponse;
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
 * 封鎖IP
 * Class IPCheckMiddleware
 * @package App\Middleware
 */
class GlobalIPBlockMiddleware implements MiddlewareInterface
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

        $blockIP = $this->cache->globalIPBlock();
        // 檢查來源IP
        if (Tool::IpContainChecker($ip, $blockIP)) {
            return $this->response->withBody(new SwooleStream(
                    json_encode(ApiResponse::result([
                        'ip' => $ip
                    ], ApiState::IP_BLOCKED))
                )
            );
        }

        return $handler->handle($request);
    }
}