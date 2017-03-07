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
class Cache{
	private $directory = null;
	public function __construct(string $directory){
		$this->directory = $directory;
	}
	public function __invoke(Request $request, Response $response, callable $next) : Response{
		$directory = $this->directory . '/' . md5($request->getRequestTarget());
		if (file_exists($directory)){
			list($headers, $status) = include $directory . '/headersandstatus.php';
			if (!isset($headers['Expires']) || time() < strtotime($headers['Expires'])){
				$response = $response->withStatus($status);
				foreach ($headers as $k => $v){
					$response = $response->withHeader($k, $v);
				}
				$response->getBody()->attach($directory . '/body');
				return $response;
			}
		}
		$response = $next($request, $response);
//		$response = $response->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
//		$response = $response->withHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 60));
		$headers = [];
		foreach ($response->getHeaders() as $k => $v){
			$headers[$k] = implode(', ', $v);
		}
		if (!file_exists($directory)){
			mkdir($directory, 0755);
		}
		file_put_contents(
			$directory . '/headersandstatus.php', 
			'<?php return ' . var_export([$headers, $response->getStatusCode()], true) . ';'
		);
		$handle = fopen($directory . '/body', 'w');
		$response->getBody()->rewind();
		while (!($response->getBody()->eof())){
			fwrite($handle, $response->getBody()->read(1024));
		}
		fclose($handle);
		return $response;
	}
}