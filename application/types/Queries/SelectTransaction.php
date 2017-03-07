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
namespace ReddeRationem\BilancioCivico\Queries;
use \Exception;
use \PDO;
class SelectTransaction{
	private $pdo = null;
	private $queries = null;
	public function __construct(PDO $pdo){
		$this->pdo = $pdo;
	}
	public function submit(array $arguments = []){
		if (!isset($arguments['code']) || !strlen($arguments['code'])){
			throw new Exception('Invalid $arguments');
		}
		$code = $arguments['code'];
		$sql = <<<SQL
		SELECT 
			"denormalizedTransactions".*,
			ABS("denormalizedTransactions"."amount") AS "amount"
		FROM "denormalizedTransactions"
		WHERE "denormalizedTransactions"."code" = ?
		LIMIT 1
		OFFSET 0
SQL;
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$code]);
		$transaction = null;
		foreach ($statement->fetchAll() as $v){
			$transaction = $v;
		}
		if (!$transaction){
			throw new Exception('Invalid $arguments');
		}
		return $transaction;
	}
}