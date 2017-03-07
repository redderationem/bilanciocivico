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
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \ReddeRationem\BilancioCivico\Queries\SelectSubdivision;
use \Twig_Environment as Environment;
class GetSubdivisionAsHtml{
	private $environment = null;
	private $selectSubdivision = null;
	public function __construct(Environment $environment, SelectSubdivision $selectSubdivision){
		$this->environment = $environment;
		$this->selectSubdivision = $selectSubdivision;
	}
	public function __invoke(Request $request, Response $response, callable $next) : Response{
		$response = $next($request, $response);
		$attributes = $request->getAttributes();
		$arguments = [
			'code' => $attributes['code'],
			'cycleCode' => (int)$attributes['cycleCode'],
			'operator' => strpos($attributes['code'], '~E') !== false ? '>' : '<'
		];
		$arguments['subdivision'] = $this->selectSubdivision->submit($arguments);
		$response->getBody()->write($this->environment->render('subdivision.html', $arguments));
		return $response;
	}
}