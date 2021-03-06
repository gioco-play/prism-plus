<?php

declare(strict_types=1);

namespace App\Middleware\Bo;

use GiocoPlus\PrismConst\State\ApiState;
use GiocoPlus\PrismConst\Tool\ApiResponse;
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
 * 權限檢查
 * Class PermissionCheckerMiddleware
 * @package App\Middleware
 */
class PermissionCheckerMiddleware implements MiddlewareInterface
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
        if (in_array($request->getUri()->getPath(), [
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/auth/refresh_token',
            '/api/v1/auth/change_password',
            '/api/v1/auth/user_env'
        ])) {
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
                    return $this->customResponse([], ApiState::ACT_PERMIT_DENY);
                }
                $permit = json_decode($permit, true);
                $check = $this->permitCheck($userInfo['role'], $permit['menu']??"", $permit['permit']??"");
                if ($check === false) {
                    return $this->customResponse([], ApiState::ACT_PERMIT_DENY);
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
        $stream = new SwooleStream(json_encode(ApiResponse::result($data, $msg)));
        return $this->response->withBody($stream)->withHeader('content-type', 'application/json');
    }
}