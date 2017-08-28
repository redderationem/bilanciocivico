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
class SelectDivisionsAtAGlance{
	private $pdo = null;
	private $queries = null;
	public function __construct(PDO $pdo){
		$this->pdo = $pdo;
	}
	public function submit(array $arguments = []){
		if (!isset($arguments['cycleCode']) || !is_int($arguments['cycleCode'])){
			throw new Exception('Invalid $arguments');
		}
		$cycleCode = $arguments['cycleCode'];
		$sql = <<<SQL
		SELECT ABS("denormalizedDivisions"."amount") AS "amount"
		FROM "denormalizedDivisions" 
		WHERE 
			"denormalizedDivisions"."cycleCode" = ? 
			AND
			"denormalizedDivisions"."divisionTypeCode" IN ('uscite', 'U')
		LIMIT 1
		OFFSET 0
SQL;
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$cycleCode]);
		$amount = null;
		foreach ($statement as $v){
			$amount = $v['amount'];
		}
		$sql = <<<SQL
		SELECT
			ABS("denormalizedDivisions"."amount") AS "amount", 
			"denormalizedDivisions"."code",
			"denormalizedDivisions"."description",
			"denormalizedDivisions"."id",
			ABS("denormalizedDivisions"."usedAmount") AS "usedAmount"
		FROM "denormalizedDivisions" 
		WHERE 
			"denormalizedDivisions"."cycleCode" = ? 
			AND
			"denormalizedDivisions"."divisionTypeCode" IN ('titoloentrate', 'T')
			AND
			"denormalizedDivisions"."amount" > 0
		ORDER BY "denormalizedDivisions"."amount" DESC
SQL;
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$cycleCode]);
		$inRows = [];
		foreach ($statement as $v){
			$v['amountPercent'] = $amount ? ($v['amount'] / $amount) * 100 : 0;
			$v['amountUsedAmountPercent'] = $amount ? ($v['usedAmount'] / $v['amount']) * 100 : 0;
			$v['usedAmountPercent'] = $amount ? ($v['usedAmount'] / $v['amount']) * 100 * ($v['amountPercent'] / 100) : 0;
			$inRows[] = $v;
		}
		$sql = <<<SQL
		SELECT
			ABS(SUM("denormalizedDivisions"."amount")) AS "amount", 
			"denormalizedDivisions"."code",
			"denormalizedDivisions"."description",
			"denormalizedDivisions"."id",
			ABS(SUM("denormalizedDivisions"."usedAmount")) AS "usedAmount"
		FROM "denormalizedDivisions" 
		WHERE 
			"denormalizedDivisions"."cycleCode" = ? 
			AND
			"denormalizedDivisions"."divisionTypeCode" IN ('missione', 'F')
			AND
			"denormalizedDivisions"."amount" < 0
		GROUP BY "denormalizedDivisions"."description"
		ORDER BY "amount" DESC
SQL;
		$statement = $this->pdo->prepare($sql);
		$statement->execute([$cycleCode]);
		$outRows = [];
		foreach ($statement as $v){
			$v['amountPercent'] = $amount ? ($v['amount'] / $amount) * 100 : 0;
			$v['amountUsedAmountPercent'] = $amount ? ($v['usedAmount'] / $v['amount']) * 100 : 0;
			$v['usedAmountPercent'] = $amount ? ($v['usedAmount'] / $v['amount']) * 100 * ($v['amountPercent'] / 100) : 0;
			$outRows[] = $v;
		}
		return [
			'inRows' => $inRows,
			'outRows' => $outRows
		];
	}
}