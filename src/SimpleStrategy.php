<?php
namespace xorik\cms;


use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;


class SimpleStrategy implements InvocationStrategyInterface
{
	/** @var Container */
	protected $ci;

	public function __construct($ci)
	{
		$this->ci = $ci;
	}

	public function __invoke(
		callable $callable,
		ServerRequestInterface $request,
		ResponseInterface $response,
		array $routeArguments
	) {
		// Set route for controller
		$this->ci['route'] = $this->ci->protect($request->getAttribute('route'));

		return call_user_func_array($callable, $routeArguments);
	}
}
