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
class SelectTransactions{
	private $pdo = null;
	private $queries = null;
	public function __construct(PDO $pdo){
		$this->pdo = $pdo;
	}
	public function submit(array $arguments = []){
		if (!isset($arguments['by']) || !in_array($arguments['by'], ['amount', 'code', 'date', 'description'])){
			throw new Exception('Invalid $arguments');
		}
		$by = $arguments['by'];
		if (!isset($arguments['cycleCode']) || !is_int($arguments['cycleCode'])){
			throw new Exception('Invalid $arguments');
		}
		$cycleCode = $arguments['cycleCode'];
		if (!isset($arguments['direction']) || !in_array($arguments['direction'], ['ASC', 'DESC'])){
			throw new Exception('Invalid $arguments');
		}
		$direction = $arguments['direction'];
		if (!isset($arguments['limit']) || !is_int($arguments['limit'])){
			throw new Exception('Invalid $arguments');
		}
		$limit = $arguments['limit'];
		if (!array_key_exists('match', $arguments) || ($arguments['match'] !== null && !is_string($arguments['match']))){
			throw new Exception('Invalid $arguments');
		}
		$match = $arguments['match'];
		if (!isset($arguments['offset']) || !is_int($arguments['offset'])){
			throw new Exception('Invalid $arguments');
		}
		$offset = $arguments['offset'];
		if (!isset($arguments['operator']) || !in_array($arguments['operator'], ['<', '>'])){
			throw new Exception('Invalid $arguments');
		}
		$operator = $arguments['operator'];
		$part = $operator == '>' ? '~E' : '~S';
		$sql = <<<SQL
		SELECT COUNT("denormalizedTransactions"."id") AS "count", ABS(SUM("denormalizedTransactions"."amount")) AS "amount"
		FROM "denormalizedTransactions"
		WHERE 
			(
				? IS NULL 
				OR
				"denormalizedTransactions"."id" IN (
					SELECT "indexedDenormalizedTransactions"."rowid"
					FROM "indexedDenormalizedTransactions"
					WHERE "indexedDenormalizedTransactions" MATCH ?
				)
			)
			AND 
			"denormalizedTransactions"."cycleCode" = ?
			AND 
			REPLACE("code", 'U', 'S') LIKE '%$part%'
SQL;
		
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$match, $match, $cycleCode]);
		$amount = null;
		$count = null;
		foreach ($statement as $v){
			$amount = $v['amount'];
			$count = $v['count'];
		}
		$sql = <<<SQL
		SELECT 
			"denormalizedTransactions"."actCode",
			"denormalizedTransactions"."actDescription",
			"denormalizedTransactions"."actId",
			ABS("denormalizedTransactions"."amount") AS "amount",
			"denormalizedTransactions"."code",
			"denormalizedTransactions"."date",
			"denormalizedTransactions"."description",
			"denormalizedTransactions"."divisionCode",
			"denormalizedTransactions"."divisionDescription",
			"denormalizedTransactions"."divisionId",
			"denormalizedTransactions"."id",
			"denormalizedTransactions"."subdivisionCode",
			"denormalizedTransactions"."subdivisionDescription",
			"denormalizedTransactions"."subdivisionId",
			"denormalizedTransactions"."subjectCode",
			"denormalizedTransactions"."subjectId",
			"denormalizedTransactions"."subjectName"
		FROM "denormalizedTransactions"
		WHERE
			(
				? IS NULL 
				OR 
				(
					"denormalizedTransactions"."id" IN (
						SELECT "indexedDenormalizedTransactions"."rowid"
						FROM "indexedDenormalizedTransactions"
						WHERE "indexedDenormalizedTransactions" MATCH ?
					)
				)
			)
			AND
			"denormalizedTransactions"."cycleCode" = ?
			AND
			REPLACE("code", 'U', 'S') LIKE '%$part%'
		ORDER BY $by $direction
		LIMIT $limit
		OFFSET $offset
SQL;
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$match, $match, $cycleCode]);
		$rows = $statement->fetchAll();
		return [
			'amount' => $amount,
			'count' => $count,
			'rows' => $rows
		];
	}
}