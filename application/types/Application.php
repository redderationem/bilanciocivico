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
namespace ReddeRationem\BilancioCivico;
use \Auryn\Injector;
use \Exception;
use \FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use \FastRoute\RouteCollector;
use \Less_Parser as Parser;
use \Pdo;
use \Phar;
use \Twig_Environment as Environment;
use \Twig_SimpleFilter as SimpleFilter;
use \Whoops\Run;
use const \PHP_URL_HOST;
use const \PHP_URL_PATH;
use function \FastRoute\simpleDispatcher;
class Application{
	public function run(string $baseDirectory, string $baseUrl, Injector $injector){	
		// ////////////////////////////////////////////////////////////////////
		$configuration = [
			'baseUrl' => $baseUrl,
			'cacheDirectory' => $baseDirectory . '/cache',
			'databaseFile' => $baseDirectory . '/data.sqlite',
			'dataManager' => '[Responsabile/i dei dati]',
			'debug' => true,
			'defaultCycleCode' => date('Y'),
			'email' => 'contatti@' . parse_url($baseUrl, PHP_URL_HOST),
			'imageDirectory' => $baseDirectory,
			'municipality' => '[Nome del comune]',
			'pageDirectory' => $baseDirectory,
			'password' => '$2y$10$r5Exv.sJrpgcLulkXD2Qo.1EcvMW7GqhkD5a0svlzU/IttiGchKSi',
			'phone' => '[Numero di telefono]',
			'siteManager' => '[Responsabile/i del sito]',
			'siteTitle' => '[Titolo del sito]',
			'stylesheetDirectory' => $baseDirectory,
			'user' => 'admin'
		];
		$configurationFile = $baseDirectory . '/configuration.json';
		if (file_exists($configurationFile)){
			$configuration = array_replace($configuration, json_decode(file_get_contents($configurationFile), true));
		}
		// ////////////////////////////////////////////////////////////////////
		$injector->alias('FastRoute\\Dispatcher', 'FastRoute\\Dispatcher\\GroupCountBased');
		$injector->alias('FastRoute\\DataGenerator', 'FastRoute\\DataGenerator\\GroupCountBased');
		$injector->alias('FastRoute\\RouteParser', 'FastRoute\\RouteParser\\Std');
		$injector->alias('Psr\\Http\\Message\\RequestInterface', 'Zend\\Diactoros\\ServerRequest');
		$injector->alias('Psr\\Http\\Message\\ResponseInterface', 'Zend\\Diactoros\\Response');
		$injector->alias('Psr\\Http\\Message\\ServerRequestInterface', 'Zend\\Diactoros\\ServerRequest');
		$injector->alias('Psr\\Http\\Message\\ServerRequestInterface', 'Zend\\Diactoros\\ServerRequest');
		$injector->alias('Twig_LoaderInterface', 'Twig_Loader_Filesystem');
		$injector->alias('Whoops\\Handler\\HandlerInterface', 'Whoops\\Handler\\PrettyPageHandler');
		$injector->alias('Zend\\Diactoros\\Response\\EmitterInterface', 'Zend\\Diactoros\\Response\\SapiEmitter');
		// ////////////////////////////////////////////////////////////////////
		$injector->define(
			'Less_Parser',
			[
				':env' => [
					'compress' => true,
					'import_callback' => function ($import) use ($configuration){
						foreach ([$configuration['stylesheetDirectory'], __DIR__ . '/../stylesheets'] as $v){
							$v = $v . '/' . trim($import->getPath(), '/');
							if (file_exists($v)){
								return [$v, null];
							}
						}
						throw new Exception();
					},
					'relativeUrls' => false
				]
			]
		);
		$injector->define(
			'PDO',
			[
				':dsn' => 'sqlite:' . $configuration['databaseFile'],
				':options' => [
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
				]
			]
		);
		$injector->define('ReddeRationem\\BilancioCivico\\Commands\\Clear', [':cacheDirectory' => $configuration['cacheDirectory']]);
		$injector->define('ReddeRationem\\BilancioCivico\\Commands\\Configure', [':configurationFile' => $configurationFile]);
		$injector->define('ReddeRationem\\BilancioCivico\\Commands\\Mail', [':email' => $configuration['email']]);
		$injector->define('ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate', [':password' => $configuration['password'], ':user' => $configuration['user']]);
		$injector->define('ReddeRationem\\BilancioCivico\\Middlewares\\Cache', [':directory' => $configuration['cacheDirectory']]);
		$injector->define('ReddeRationem\\BilancioCivico\\Middlewares\\GetHomeAsHtml', [':cycleCode' => $configuration['defaultCycleCode']]);
		$injector->define(
			'ReddeRationem\\BilancioCivico\\Middlewares\\GetImageAsJpg', 
			[':directories' => [$configuration['imageDirectory'], __DIR__ . '/../images']]
		);
		$injector->define(
			'ReddeRationem\\BilancioCivico\\Middlewares\\GetImageAsPng', 
			[':directories' => [$configuration['imageDirectory'], __DIR__ . '/../images']]
		);
		$injector->define(
			'ReddeRationem\\BilancioCivico\\Middlewares\\GetStylesheetAsCss', 
			[':directories' => [$configuration['stylesheetDirectory'], __DIR__ . '/../stylesheets'], ':variables' => []]
		);
		$injector->define(
			'ReddeRationem\\BilancioCivico\\Middlewares\\PostToContacts', 
			[':email' => $configuration['email']]
		);
		$injector->define(
			'Relay\\RelayBuilder',
			[
				':resolver' => function ($middleware) use ($injector){
					return $injector->make(...(array)$middleware);
				}
			]
		);
		$injector->define('Twig_Loader_Filesystem', [':paths' => [$configuration['pageDirectory'], __DIR__ . '/../pages']]);
		// ////////////////////////////////////////////////////////////////////
		$injector->delegate(
			'FastRoute\\Dispatcher\\GroupCountBased',
			function (RouteCollector $routeCollector){
				return new Dispatcher($routeCollector->getData());
			}
		);
		$injector->delegate('Zend\\Diactoros\\ServerRequest', 'Zend\\Diactoros\\ServerRequestFactory::fromGlobals');
		// ////////////////////////////////////////////////////////////////////
		$injector->prepare(
			'FastRoute\\RouteCollector',
			function (RouteCollector $routeCollector) use ($configuration){
				$basePath = rtrim(parse_url($configuration['baseUrl'], PHP_URL_PATH), '/') . '/';
				$authenticateMiddleware = $configuration['debug'] ? ['ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate'] : [];
				$cacheMiddleware = $configuration['debug'] ? [] : ['ReddeRationem\\BilancioCivico\\Middlewares\\Cache'];
				$routeCollector->addRoute('GET', $basePath, array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetHomeAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/atti/{code}.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetActAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/atti.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetActsAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/capitoli/{code}.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetSubdivisionAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/capitoli.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetSubdivisionsAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/sezioni/{code}.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetDivisionAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/sezioni.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetDivisionsAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/soggetti/{code}.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetSubjectAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/soggetti.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetSubjectsAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/operazioni/{code}.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetTransactionAsHtml']));
				$routeCollector->addRoute('GET', $basePath . '{cycleCode}/operazioni.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetTransactionsAsHtml']));
				$routeCollector->addRoute('GET', $basePath . 'contatti.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetContactsAsHtml']));
				$routeCollector->addRoute('GET', $basePath . 'images/{name}.jpg', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetImageAsJpg']));
				$routeCollector->addRoute('GET', $basePath . 'images/{name}.png', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetImageAsPng']));
				$routeCollector->addRoute('GET', $basePath . 'nota-sui-dati.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetDisclaimerAsHtml']));
				$routeCollector->addRoute('GET', $basePath . 'progetto.html', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetIntroductionAsHtml']));
				$routeCollector->addRoute('GET', $basePath . 'amministrazione/configurazione.html', ['ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate', 'ReddeRationem\\BilancioCivico\\Middlewares\\GetConfigurationAsHtml']);
				$routeCollector->addRoute('GET', $basePath . 'amministrazione/importazione.html', ['ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate', 'ReddeRationem\\BilancioCivico\\Middlewares\\GetImportationAsHtml']);
				$routeCollector->addRoute('GET', $basePath . 'amministrazione/riparazione.html', ['ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate', 'ReddeRationem\\BilancioCivico\\Middlewares\\GetReparationAsHtml']);
				$routeCollector->addRoute('GET', $basePath . 'stylesheets/{name}.css', array_merge($authenticateMiddleware, $cacheMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\GetStylesheetAsCss']));
				$routeCollector->addRoute('POST', $basePath . 'contatti.html', array_merge($authenticateMiddleware, ['ReddeRationem\\BilancioCivico\\Middlewares\\PostToContacts']));
				$routeCollector->addRoute('POST', $basePath . 'amministrazione/configurazione.html', ['ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate', 'ReddeRationem\\BilancioCivico\\Middlewares\\PostToConfiguration']);
				$routeCollector->addRoute('POST', $basePath . 'amministrazione/importazione.html', ['ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate', 'ReddeRationem\\BilancioCivico\\Middlewares\\PostToImportation']);
				$routeCollector->addRoute('POST', $basePath . 'amministrazione/riparazione.html', ['ReddeRationem\\BilancioCivico\\Middlewares\\Authenticate', 'ReddeRationem\\BilancioCivico\\Middlewares\\PostToReparation']);
			}
		);
		$injector->prepare(
			'PDO',
			function (PDO $pdo){
				$sql = <<<SQL
				PRAGMA foreign_keys = On
SQL;
				$pdo->exec($sql);
			}
		);
		$injector->prepare(
			'Twig_Environment',
			function (Environment $environment) use ($configuration){
				$environment->addFilter(new SimpleFilter(
					'double_encode_slashes',
					function ($value){
						return str_replace('%2F', '%252F', $value);
					}
				));
				foreach ($configuration as $k => $v){
					$environment->addGlobal($k, $v);
				}
			}
		);
		$injector->prepare(
			'Whoops\\Run',
			function (Run $run) use ($injector){
				$run->pushHandler($injector->make('Whoops\\Handler\\HandlerInterface'));
			}
		);
		// ////////////////////////////////////////////////////////////////////
		$injector->share($injector);
		$injector->share('Relay\\RelayBuilder');
		// ////////////////////////////////////////////////////////////////////
		if (!file_exists($configuration['cacheDirectory'])){
			mkdir($configuration['cacheDirectory'], 0755, true);
		}
		if (!file_exists($configuration['databaseFile'])){
			copy(__DIR__ . '/../databases/database.sqlite', $configuration['databaseFile']);
		}
		// ////////////////////////////////////////////////////////////////////
		$injector->execute($injector->make('Relay\\RelayBuilder')->newInstance([
			'ReddeRationem\\BilancioCivico\\Middlewares\\Emit', 
			'ReddeRationem\\BilancioCivico\\Middlewares\\Debug',
			'ReddeRationem\\BilancioCivico\\Middlewares\\Route'
		]));
	}
}