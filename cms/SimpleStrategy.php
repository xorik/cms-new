<?php
namespace xorik\cms;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;


class SimpleStrategy implements InvocationStrategyInterface
{
	public function __invoke(
		callable $callable,
		ServerRequestInterface $request,
		ResponseInterface $response,
		array $routeArguments
	) {
		return call_user_func_array($callable, $routeArguments);
	}
}
