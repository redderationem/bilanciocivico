<?php
/**
 * © Copyright 2016 Associazione di promozione sociale Redde Rationem
 * 
 * This file was authored by:
 *
 * - Gianmarco Tuccini <gianmarcotuccini@redderationem.org>
 * - Paolo Landi <paololandi@redderationem.org>
 * - Marco Santini <marcosantini@redderationem.org>
 *
 * This file is part of BilancioCivico.
 * 
 * BilancioCivico is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BilancioCivico is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with BilancioCivico.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Supplemental term under GNU Affero General Public License version 3 section 7
 *
 * You must retain the whole attribution (both the copyright line and the actual authors' list)
 */
declare(strict_types=1);
namespace ReddeRationem\BilancioCivico\Middlewares;
use \FastRoute\Dispatcher;
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Relay\RelayBuilder;
use const \PHP_URL_PATH;
class Route{
	private $dispatcher = null;
	private $relayBuilder = null;
	public function __construct(Dispatcher $dispatcher, RelayBuilder $relayBuilder){
		$this->dispatcher = $dispatcher;
		$this->relayBuilder = $relayBuilder;
	}
	public function __invoke(Request $request, Response $response, callable $next) : Response{
		$dispatch = $this->dispatcher->dispatch($request->getMethod(), parse_url($request->getRequestTarget(), PHP_URL_PATH));
		if ($dispatch[0] == $this->dispatcher::FOUND){
			foreach ($dispatch[2] as $k => $v){
				$request = $request->withAttribute($k, $v);
			}
			return ($this->relayBuilder->newInstance($dispatch[1]))($request, $next($request, $response));
		}
		if ($dispatch[0] == $this->dispatcher::FOUND){
			return $response->withStatus(405)->withHeader('Allow', implode(', ', $dispatch[1]));
		}
		return $response->withStatus(404);
	}
}