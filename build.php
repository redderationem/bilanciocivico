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
ini_set('phar.readonly', 'Off');
$phar = new Phar(__DIR__ . '/build/application.phar', 0, 'application.phar');
$phar->buildFromDirectory(__DIR__);
$phar->setSignatureAlgorithm(Phar::OPENSSL, file_get_contents($argv[2]));
$stub = <<<'STUB'
<?php
declare(strict_types=1);
use \Auryn\Injector;
use \ReddeRationem\BilancioCivico\Application;
include 'phar://application.phar/library/autoload.php';
$application = new Application();
$application->run(
	__DIR__, 
	(isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/',
	new Injector()
);
__HALT_COMPILER();
STUB;
$phar->setStub($stub);
file_put_contents(
	__DIR__ . '/build/application.phar.pubkey', 
	openssl_pkey_get_details(openssl_pkey_get_public(file_get_contents($argv[1])))['key']
);