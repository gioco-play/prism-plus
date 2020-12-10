<?php

declare(strict_types=1);

namespace App\Middleware;

use GiocoPlus\PrismPlus\Helper\ApiResponse;
use GiocoPlus\PrismPlus\Helper\Tool;
use GiocoPlus\PrismPlus\Service\CacheFlushService;
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
 * Class PermissionCheckMiddleware
 * @package App\Middleware
 */
class PermissionCheckMiddleware implements MiddlewareInterface
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
            if ($this->jwt) {
                $userInfo = $this->jwt->getParserData();
                if (in_array(trim(strtolower($userInfo['role'])), $this->cache->fullAccessRoles())) {
                    return $handler->handle($request);
                }
                $permit = current($request->getHeader("action"));
                if ($permit === false) {
                    return $this->response->withBody($this->customResponse([], ApiResponse::ACT_PERMIT_DENY));
                }
                $permit = json_decode($permit, true);
                $check = $this->permitCheck($userInfo['role'], $permit['menu']??"", $permit['permit']??"");
                if ($check === false) {
                    return $this->response->withBody($this->customResponse([], ApiResponse::ACT_PERMIT_DENY));
                }
            }
        }

        return $handler->handle($request);
    }

    /**
     * 檢查權限
     * @param string $role
     * @param string $menu
     * @param string $permit
     * @return false
     */
    private function permitCheck(string $role, string $menu, string $permit) {
        $permits = $this->cache->roleMenuPermit($role, $menu)['permits'];
        $permits = array_values(collect($permits)->pluck('permit')->toArray());
        return array_search($permit, $permits) !== false;
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