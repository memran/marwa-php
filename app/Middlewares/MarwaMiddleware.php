<?php
	
	namespace App\Middlewares;
	
	use Psr\Http\Message\ResponseInterface;
	use Psr\Http\Message\ServerRequestInterface;
	use Psr\Http\Server\MiddlewareInterface;
	use Psr\Http\Server\RequestHandlerInterface;
	
	class MarwaMiddleware implements MiddlewareInterface {
		
		/**
		 * @param ServerRequestInterface $request
		 * @param RequestHandlerInterface $handler
		 * @return ResponseInterface
		 */
		public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ) : ResponseInterface
		{
			
			return $handler->handle($request);
		}
		
	}
