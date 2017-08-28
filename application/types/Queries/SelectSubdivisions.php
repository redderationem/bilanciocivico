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
class SelectSubdivisions{
	private $pdo = null;
	private $queries = null;
	public function __construct(PDO $pdo){
		$this->pdo = $pdo;
	}
	public function submit(array $arguments = []){
		if (!isset($arguments['by']) || !in_array($arguments['by'], ['amount', 'code', 'description', 'usedAmount'])){
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
		SELECT
			ABS(SUM("denormalizedSubdivisions"."amount")) AS "amount", 
			COUNT("denormalizedSubdivisions"."id") AS "count", 
			ABS(SUM("denormalizedSubdivisions"."usedAmount")) AS "usedAmount"
		FROM "denormalizedSubdivisions"
		WHERE 
			(
				(
					? IS NULL 
					AND 
					NOT EXISTS (
						SELECT "denormalizedParentSubdivisions"."subdivisionId"
						FROM "denormalizedParentSubdivisions"
						WHERE "denormalizedParentSubdivisions"."ancestorSubdivisionId" = "denormalizedSubdivisions"."id"
					)
				)
				OR
				"denormalizedSubdivisions"."id" IN (
					SELECT "denormalizedTransactions"."subdivisionId"
					FROM "denormalizedTransactions"
					WHERE "denormalizedTransactions"."id" IN (
						SELECT "indexedDenormalizedTransactions"."rowid"
						FROM "indexedDenormalizedTransactions"
						WHERE "indexedDenormalizedTransactions" MATCH ?
					)
					GROUP BY "denormalizedTransactions"."subdivisionId"
				)
			)
			AND
			"denormalizedSubdivisions"."cycleCode" = ?
			AND
			REPLACE("code", 'U', 'S') LIKE '%$part%'
SQL;
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$match, $match, $cycleCode]);
		$amount = null;
		$count = null;
		$usedAmount = null;
		foreach ($statement as $v){
			$amount = $v['amount'];
			$count = $v['count'];
			$usedAmount = $v['usedAmount'];
		}
		$sql = <<<SQL
		WITH 
			"window" AS (
				SELECT "denormalizedTransactions"."subdivisionId" AS "id"
				FROM "denormalizedTransactions"
				WHERE (
					? IS NULL
					OR
					"denormalizedTransactions"."id" IN (
						SELECT "indexedDenormalizedTransactions"."rowid"
						FROM "indexedDenormalizedTransactions"
						WHERE "indexedDenormalizedTransactions" MATCH ?
					)
				)
			),
			"window2" AS (
				SELECT "denormalizedParentSubdivisions"."ancestorSubdivisionId" AS "id"
				FROM "denormalizedParentSubdivisions"
				WHERE "denormalizedParentSubdivisions"."subdivisionId" IN (SELECT "window"."id" FROM "window")
				UNION
				SELECT "window"."id" FROM "window"
			),
			"window3" AS (
				SELECT "denormalizedSubdivisions"."id"
				FROM "denormalizedSubdivisions"
				WHERE NOT EXISTS(
					SELECT "denormalizedParentSubdivisions"."id"
					FROM "denormalizedParentSubdivisions"
					WHERE "denormalizedParentSubdivisions"."subdivisionId" = "denormalizedSubdivisions"."id"
				)
			)
		SELECT 
			ABS("denormalizedSubdivisions"."amount") AS "amount",
			"denormalizedSubdivisions"."code", 
			"denormalizedParentSubdivisions2"."depth",
			"denormalizedSubdivisions"."description", 
			"denormalizedSubdivisions"."divisionDescription", 
			"denormalizedSubdivisions"."divisionId", 
			"denormalizedSubdivisions"."id", 
			"denormalizedParentSubdivisions"."ancestorSubdivisionId" AS "parentSubdivisionId",
			ABS("denormalizedSubdivisions"."usedAmount") AS "usedAmount"
		FROM "denormalizedSubdivisions"
		JOIN "denormalizedParentSubdivisions" ON "denormalizedParentSubdivisions"."subdivisionId" = "denormalizedSubdivisions"."id"
		JOIN "denormalizedParentSubdivisions" AS "denormalizedParentSubdivisions2" ON "denormalizedParentSubdivisions2"."subdivisionId" = "denormalizedSubdivisions"."id"
		WHERE
			"denormalizedSubdivisions"."cycleCode" = ?
			AND
			REPLACE("code", 'U', 'S') LIKE '%$part%'
			AND
			"denormalizedParentSubdivisions"."depth" = 1
			AND
			"denormalizedSubdivisions"."id" IN (SELECT "window2"."id" FROM "window2")
			AND
			"denormalizedParentSubdivisions2"."ancestorSubdivisionId" IN (SELECT "window3"."id" FROM "window3")
		ORDER BY "denormalizedParentSubdivisions2"."depth" ASC, $by $direction		
SQL;
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$match, $match, $cycleCode]);
		$rows = [];
		foreach ($statement->fetchAll() as $v){
			$rows[$v['id']] = $v;
			$rows[$v['id']]['rows'] = [];
			if (isset($rows[$v['parentSubdivisionId']])){
				$rows[$v['id']]['amountPercent'] = $rows[$v['parentSubdivisionId']]['amount'] ? ($rows[$v['id']]['amount'] / $rows[$v['parentSubdivisionId']]['amount']) * 100 : 0;
				$rows[$v['id']]['amountUsedAmountPercent'] = $rows[$v['id']]['amount'] ? ($v['usedAmount'] / $rows[$v['id']]['amount']) * 100 : 0;
				$rows[$v['id']]['usedAmountPercent'] = $rows[$v['id']]['amount'] ? ($v['usedAmount'] / $rows[$v['id']]['amount']) * 100 * ($rows[$v['id']]['amountPercent'] / 100) : 0;
				$rows[$v['parentSubdivisionId']]['rows'][] = &$rows[$v['id']];
			}
			else {
				$rows[$v['id']]['amountPercent'] = 100;
				$rows[$v['id']]['amountUsedAmountPercent'] = $rows[$v['id']]['amount'] ? ($v['usedAmount'] / $rows[$v['id']]['amount']) * 100 : 0;
				$rows[$v['id']]['usedAmountPercent'] = $rows[$v['id']]['amount'] ? ($v['usedAmount'] / $rows[$v['id']]['amount']) * 100 * ($rows[$v['id']]['amountPercent'] / 100) : 0;
			}
		}
		$rows = ['rows' => reset($rows)];
		return [
			'amount' => $amount,
			'count' => $count,
			'rows' => $rows,
			'usedAmount' => $usedAmount
		];
	}
}