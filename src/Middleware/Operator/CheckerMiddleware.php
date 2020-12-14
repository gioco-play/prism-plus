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
 * 商戶狀態、IP過濾
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

        $operatorToken = $request->getParsedBody()["operator_token"];
        $secretKey = $request->getParsedBody()["secret_key"];

        $op = $this->cache->operatorByToken($operatorToken);

        // 密鑰錯誤
        if (empty($op) || $op['secret_key'] !== $secretKey) {
            return $this->response->withBody($this->customResponse([], ApiResponse::OPERATOR_NOT_FOUND));
        }

        // 狀態
        switch ($op['status']) {
            case GlobalConst::MAINTAIN :
                return $this->response->withBody($this->customResponse([], ApiResponse::MAINTAIN));
            case GlobalConst::DECOMMISSION :
                return $this->response->withBody($this->customResponse([], ApiResponse::DECOMMISSION));
        }

        // 檢查來源IP
        if (!Tool::IpContainChecker($ip, $op['api_whitelist'])) {
            return $this->response->withBody($this->customResponse(['ip' => $ip], ApiResponse::IP_NOT_ALLOWED));
        }

        return $handler->handle($request);
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