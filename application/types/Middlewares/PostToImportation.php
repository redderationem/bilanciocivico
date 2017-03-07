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
use \ReddeRationem\BilancioCivico\Commands\Clear;
use \ReddeRationem\BilancioCivico\Commands\Import;
class PostToImportation{
	private $clear = null;
	private $import = null;
	public function __construct(Clear $clear, Import $import){
		$this->clear = $clear;
		$this->import = $import;
	}
	public function __invoke(Request $request, Response $response, callable $next) : Response{
		$response = $next($request, $response);
		$uploadedFiles = $request->getUploadedFiles();
		if (isset($uploadedFiles['file'])){
			$arguments = [
				'path' => $uploadedFiles['file']->getStream()->getMetadata('uri')
			];
			$this->import->submit($arguments);
			$this->clear->submit();
		}
		$response = $response->withStatus(303)->withHeader('Location', $request->getRequestTarget());
		return $response;
	}
}