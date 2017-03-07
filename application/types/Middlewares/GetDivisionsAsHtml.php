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
use \ReddeRationem\BilancioCivico\Queries\SelectDivisions;
use \Twig_Environment as Environment;
class GetDivisionsAsHtml{
	private $environment = null;
	private $selectDivisions = null;
	public function __construct(Environment $environment, SelectDivisions $selectDivisions){
		$this->environment = $environment;
		$this->selectDivisions = $selectDivisions;
	}
	public function __invoke(Request $request, Response $response, callable $next) : Response{
		$response = $next($request, $response);
		$queryParams = array_filter($request->getQueryParams());
		if (isset($queryParams['match'])){
			$arguments = [
				'by' => $queryParams['by'] ?? 'code', 
				'cycleCode' => (int)$request->getAttribute('cycleCode'),
				'direction' => $queryParams['direction'] ?? 'ASC',
				'limit' => isset($queryParams['limit']) ? (int)$queryParams['limit'] : 25,
				'match' => $queryParams['match'] == 'Mostra tutto' ? null : $queryParams['match'],
				'offset' => isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0,
				'operator' => $queryParams['operator'] ?? '<'
			];
			$arguments['divisions'] = $this->selectDivisions->submit($arguments);
			$arguments['match'] = $queryParams['match'];
			$response->getBody()->write($this->environment->render('divisionindex.html', $arguments));
			return $response;
		}
		$arguments = [
			'cycleCode' => (int)$request->getAttribute('cycleCode'),
		];
		$response->getBody()->write($this->environment->render('divisionsummary.html', $arguments));
		return $response;
	}
}