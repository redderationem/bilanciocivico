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
namespace ReddeRationem\BilancioCivico\Commands;
use \Exception;
use \PDO;
use \XMLReader;
class Import{
	private $pdo = null;
	private $xmlReader = null;
	public function __construct(PDO $pdo, XMLReader $xmlReader){
		$this->pdo = $pdo;
		$this->xmlReader = $xmlReader;
	}
	public function submit(array $arguments = []){
		if (!isset($arguments['path']) || !strlen($arguments['path'])){
			throw new Exception('Invalid $arguments');
		}
		$this->pdo->beginTransaction();
		$sql = <<<SQL
		INSERT OR REPLACE INTO acts
		VALUES (
			(SELECT id FROM actTypes WHERE code = ?), 
			?, 
			?, 
			?, 
			(SELECT id FROM acts WHERE code = ?)
		)
SQL;
		$importAct = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO actsCycles
		VALUES (
			(SELECT id FROM acts WHERE code = ?),
			(SELECT id FROM cycles WHERE code = ?),
			(
				SELECT actsCycles.id 
				FROM actsCycles 
				JOIN acts ON acts.id = actsCycles.actId 
				JOIN cycles ON cycles.id = actsCycles.cycleId 
				WHERE acts.code = ? AND cycles.code = ?
			)
		)
SQL;
		$importActCycle = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO actsDivisions 
		VALUES (
			(SELECT id FROM acts WHERE code = ?), 
			(
				SELECT actsDivisions.id 
				FROM actsDivisions 
				JOIN acts ON acts.id = actsDivisions.actId 
				JOIN divisions ON divisions.id = actsDivisions.divisionId 
				WHERE acts.code = ? AND divisions.code = ?
			), 
			(SELECT id FROM divisions WHERE code = ?)
		)
SQL;
		$importActDivision = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO actsSubdivisions 
		VALUES (
			(SELECT id FROM acts WHERE code = ?), 
			(
				SELECT actsSubdivisions.id 
				FROM actsSubdivisions 
				JOIN acts ON acts.id = actsSubdivisions.actId 
				JOIN subdivisions ON subdivisions.id = actsSubdivisions.subdivisionId 
				WHERE acts.code = ? AND subdivisions.code = ?
			), 
			(SELECT id FROM subdivisions WHERE code = ?)
		)
SQL;
		$importActSubdivision = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO actsSubjects 
		VALUES (
			(SELECT id FROM acts WHERE code = ?), 
			(
				SELECT actsSubjects.id 
				FROM actsSubjects 
				JOIN acts ON acts.id = actsSubjects.actId 
				JOIN subjects ON subjects.id = actsSubjects.subjectId 
				WHERE acts.code = ? AND subjects.code = ?
			), 
			(SELECT id FROM subjects WHERE code = ?)
		)
SQL;
		$importActSubject = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO actsTransactions 
		VALUES (
			(SELECT id FROM acts WHERE code = ?), 
			(
				SELECT actsTransactions.id 
				FROM actsTransactions 
				JOIN acts ON acts.id = actsTransactions.actId 
				JOIN transactions ON transactions.id = actsTransactions.transactionId 
				WHERE acts.code = ? AND transactions.code = ?
			), 
			(SELECT id FROM transactions WHERE code = ?)
		)
SQL;
		$importActTransaction = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO actTypes VALUES (?, ?, (SELECT id FROM actTypes WHERE code = ?))
SQL;
		$importActType = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO amounts 
		VALUES (
			CAST(? AS INTEGER), 
			(
				SELECT amounts.id 
				FROM amounts 
				JOIN subdivisions ON subdivisions.id = amounts.subdivisionId 
				WHERE subdivisions.code = ?
			), 
			(SELECT id FROM subdivisions WHERE code = ?)
		)
SQL;
		$importAmount = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO cigCodes VALUES (?, (SELECT id FROM cigCodes WHERE code = ?))
SQL;
		$importCigCode = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO cigCodesTransactions 
		VALUES (
			(SELECT id FROM cigCodes WHERE code = ?), 
			(
				SELECT cigCodesTransactions.id 
				FROM cigCodesTransactions 
				JOIN cigCodes ON cigCodes.id = cigCodesTransactions.cigCodeId 
				JOIN transactions ON transactions.id = cigCodesTransactions.transactionId 
				WHERE cigCodes.code = ? AND transactions.code = ?
			), 
			(SELECT id FROM transactions WHERE code = ?)
		)
SQL;
		$importCigCodeTransaction = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO cupCodes VALUES (?, (SELECT id FROM cupCodes WHERE code = ?))
SQL;
		$importCupCode = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO cupCodesTransactions
		VALUES (
			(SELECT id FROM cupCodes WHERE code = ?), 
			(
				SELECT cupCodesTransactions.id 
				FROM cupCodesTransactions 
				JOIN cupCodes ON cupCodes.id = cupCodesTransactions.cupCodeId 
				JOIN transactions ON transactions.id = cupCodesTransactions.transactionId 
				WHERE cupCodes.code = ? AND transactions.code = ?
			), 
			(SELECT id FROM transactions WHERE code = ?)
		)
SQL;
		$importCupCodeTransaction = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO cycles 
		VALUES (
			?, 
			(SELECT id FROM cycleTypes WHERE code = ?), 
			?, 
			(SELECT id FROM cycles WHERE code = ?)
		)
SQL;
		$importCycle = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO cyclesSubjects
		VALUES (
			(SELECT id FROM cycles WHERE code = ?),
			(
				SELECT cyclesSubjects.id 
				FROM cyclesSubjects 
				JOIN cycles ON cycles.id = cyclesSubjects.cycleId 
				JOIN subjects ON subjects.id = cyclesSubjects.subjectId 
				WHERE cycles.code = ? AND subjects.code = ?
			),
			(SELECT id FROM subjects WHERE code = ?) 
		)
SQL;
		$importCycleSubject = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO cycleTypes VALUES (?, ?, (SELECT id FROM cycleTypes WHERE code = ?))
SQL;
		$importCycleType = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO divisions 
		VALUES (
			?, 
			(SELECT id FROM cycles WHERE code = ?), 
			?, 
			(SELECT id FROM divisionTypes WHERE code = ?), 
			(SELECT id FROM divisions WHERE code = ?)
		)
SQL;
		$importDivision = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO divisionsSubdivisions 
		VALUES (
			(SELECT id FROM divisions WHERE code = ?), 
			(
				SELECT divisionsSubdivisions.id 
				FROM divisionsSubdivisions 
				JOIN divisions ON divisions.id = divisionsSubdivisions.divisionId 
				JOIN subdivisions ON subdivisions.id = divisionsSubdivisions.subdivisionId 
				WHERE divisions.code = ? AND subdivisions.code = ?
			), 
			(SELECT id FROM subdivisions WHERE code = ?)
		)
SQL;
		$importDivisionSubdivision = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO divisionsSubjects 
		VALUES (
			(SELECT id FROM divisions WHERE code = ?), 
			(
				SELECT divisionsSubjects.id 
				FROM divisionsSubjects 
				JOIN divisions ON divisions.id = divisionsSubjects.divisionId 
				JOIN subjects ON subjects.id = divisionsSubjects.subjectId 
				WHERE divisions.code = ? AND subjects.code = ?
			), 
			(SELECT id FROM subjects WHERE code = ?)
		)
SQL;
		$importDivisionSubject = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO divisionsTransactions 
		VALUES (
			(
				SELECT divisionsTransactions.id 
				FROM divisionsTransactions 
				JOIN divisions ON divisions.id = divisionsTransactions.divisionId 
				JOIN transactions ON transactions.id = divisionsTransactions.transactionId 
				WHERE divisions.code = ? AND transactions.code = ?
			), 
			(SELECT id FROM divisions WHERE code = ?), 
			(SELECT id FROM transactions WHERE code = ?)
		)
SQL;
		$importDivisionTransaction = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO divisionTypes VALUES (?, ?, (SELECT id FROM divisionTypes WHERE code = ?))
SQL;
		$importDivisionType = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO parentDivisions 
		VALUES (
			(SELECT id FROM divisions WHERE code = ?), 
			(
				SELECT parentDivisions.id 
				FROM parentDivisions 
				JOIN divisions AS divisions1 ON divisions1.id = parentDivisions.divisionId 
				JOIN divisions AS divisions2 ON divisions2.id = parentDivisions.parentDivisionId 
				WHERE divisions1.code = ? AND divisions2.code = ?
			), 
			(SELECT id FROM divisions WHERE code = ?)
		)
SQL;
		$importParentDivision = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO parentSubdivisions 
		VALUES (
			(SELECT id FROM subdivisions WHERE code = ?), 
			(
				SELECT parentSubdivisions.id 
				FROM parentSubdivisions 
				JOIN subdivisions AS subdivisions1 ON subdivisions1.id = parentSubdivisions.subdivisionId 
				JOIN subdivisions AS subdivisions2 ON subdivisions2.id = parentSubdivisions.parentSubdivisionId 
				WHERE subdivisions1.code = ? AND subdivisions2.code = ?
			), 
			(SELECT id FROM subdivisions WHERE code = ?)
		)
SQL;
		$importParentSubdivision = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO subdivisions 
		VALUES (
			?, 
			(SELECT id FROM cycles WHERE code = ?), 
			?, 
			(SELECT id FROM subdivisions WHERE code = ?), 
			(SELECT id FROM subdivisionTypes WHERE code = ?)
		)
SQL;
		$importSubdivision = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO subdivisionsSubjects 
		VALUES (
			(
				SELECT subdivisionsSubjects.id 
				FROM subdivisionsSubjects 
				JOIN subdivisions ON subdivisions.id = subdivisionsSubjects.subdivisionId 
				JOIN subjects ON subjects.id = subdivisionsSubjects.subjectId 
				WHERE subdivisions.code = ? AND subjects.code = ?
			), 
			(SELECT id FROM subdivisions WHERE code = ?), 
			(SELECT id FROM subjects WHERE code = ?)
		)
SQL;
		$importSubdivisionSubject = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO subdivisionsTransactions 
		VALUES (
			(
				SELECT subdivisionsTransactions.id 
				FROM subdivisionsTransactions 
				JOIN subdivisions ON subdivisions.id = subdivisionsTransactions.subdivisionId 
				JOIN transactions ON transactions.id = subdivisionsTransactions.transactionId 
				WHERE subdivisions.code = ? AND transactions.code = ?
			), 
			(SELECT id FROM subdivisions WHERE code = ?), 
			(SELECT id FROM transactions WHERE code = ?)
		)
SQL;
		$importSubdivisionTransaction = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO subdivisionTypes VALUES (?, ?, (SELECT id FROM subdivisionTypes WHERE code = ?))
SQL;
		$importSubdivisionType = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO subjects 
		VALUES (
			?, 
			(SELECT id FROM subjects WHERE code = ?), 
			?, 
			(SELECT id FROM subjectTypes WHERE code = ?)
		)
SQL;
		$importSubject = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO subjectsTransactions 
		VALUES (
			(
				SELECT subjectsTransactions.id 
				FROM subjectsTransactions 
				JOIN subjects ON subjects.id = subjectsTransactions.subjectId 
				JOIN transactions ON transactions.id = subjectsTransactions.transactionId 
				WHERE subjects.code = ? AND transactions.code = ?
			), 
			(SELECT id FROM subjects WHERE code = ?), 
			(SELECT id FROM transactions WHERE code = ?)
		)
SQL;
		$importSubjectTransaction = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO subjectTypes VALUES (?, ?, (SELECT id FROM subjectTypes WHERE code = ?))
SQL;
		$importSubjectType = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO transactions 
		VALUES (
			CAST(? AS INTEGER), 
			?, 
			(SELECT id FROM cycles WHERE code = ?), 
			?, 
			?, 
			(SELECT id FROM transactions WHERE code = ?), 
			(SELECT id FROM transactionTypes WHERE code = ?)
		)
SQL;
		$importTransaction = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO transactionTypes VALUES (?, ?, (SELECT id FROM transactionTypes WHERE code = ?))
SQL;
		$importTransactionType = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO taxCodes 
		VALUES (
			?, 
			(SELECT id FROM taxCodes WHERE code = ?), 
			(SELECT id FROM subjects WHERE code = ?)
		)
SQL;
		$importTaxCode = $this->pdo->prepare($sql);
		$sql = <<<SQL
		INSERT OR REPLACE INTO vatNumbers 
		VALUES (
			(SELECT id FROM vatNumbers WHERE "number" = ?), 
			?, 
			(SELECT id FROM subjects WHERE code = ?)
		)
SQL;
		$importVatNumber = $this->pdo->prepare($sql);
		$this->xmlReader->open($arguments['path']);
		while ($this->xmlReader->read()){
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT){
				$attributes = [];
				while ($this->xmlReader->moveToNextAttribute()){
					$attributes[$this->xmlReader->localName] = $this->xmlReader->value;
				}
				$this->xmlReader->moveToElement();
				switch ($this->xmlReader->localName){
					case 'actType':
						$importActType->execute([$attributes['code'], $attributes['description'], $attributes['code']]);
						break;
					case 'cigCode':
						$importCigCode->execute([$attributes['code'], $attributes['code']]);
						break;
					case 'cupCode':
						$importCupCode->execute([$attributes['code'], $attributes['code']]);
						break;
					case 'cycleType':
						$importCycleType->execute([$attributes['code'], $attributes['description'], $attributes['code']]);
						break;
					case 'divisionType':
						$importDivisionType->execute([$attributes['code'], $attributes['description'], $attributes['code']]);
						break;
					case 'subdivisionType':
						$importSubdivisionType->execute([$attributes['code'], $attributes['description'], $attributes['code']]);
						break;
					case 'subjectType':
						$importSubjectType->execute([$attributes['code'], $attributes['description'], $attributes['code']]);
						break;
					case 'transactionType':
						$importTransactionType->execute([$attributes['code'], $attributes['description'], $attributes['code']]);
						break;
				}
			}
		}
		$this->xmlReader->close();
		$this->xmlReader->open($arguments['path']);
		while ($this->xmlReader->read()){
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT){
				$attributes = [];
				while ($this->xmlReader->moveToNextAttribute()){
					$attributes[$this->xmlReader->localName] = $this->xmlReader->value;
				}
				$this->xmlReader->moveToElement();
				switch ($this->xmlReader->localName){
					case 'cycle':
						$importCycle->execute([$attributes['code'], $attributes['cycleTypeCode'], $attributes['description'], $attributes['code']]);
						break;
				}
			}
		}
		$this->xmlReader->close();
		$this->xmlReader->open($arguments['path']);
		while ($this->xmlReader->read()){
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT){
				$attributes = [];
				while ($this->xmlReader->moveToNextAttribute()){
					$attributes[$this->xmlReader->localName] = $this->xmlReader->value;
				}
				$this->xmlReader->moveToElement();
				switch ($this->xmlReader->localName){
					case 'act':
						$importAct->execute([$attributes['actTypeCode'], $attributes['code'], $attributes['date'], $attributes['description'], $attributes['code']]);
						foreach (explode(' ', $attributes['cycleCodes']) as $vv){
							$importActCycle->execute([$attributes['code'], $vv, $attributes['code'], $vv]);
						}
						break;
					case 'division':
						$importDivision->execute([$attributes['code'], $attributes['cycleCode'], $attributes['description'], $attributes['divisionTypeCode'], $attributes['code']]);
						break;
					case 'subdivision':
						$importSubdivision->execute([$attributes['code'], $attributes['cycleCode'], $attributes['description'], $attributes['code'], $attributes['subdivisionTypeCode']]);
						if (isset($attributes['amount']) && strlen($attributes['amount'])){
							$importAmount->execute([(int)$attributes['amount'], $attributes['code'], $attributes['code']]);
						}
						if (strlen($attributes['divisionCode']) && strlen($attributes['divisionCode'])){
							$importDivisionSubdivision->execute([$attributes['divisionCode'], $attributes['divisionCode'], $attributes['code'], $attributes['code']]);
						}
						break;
					case 'subject':
						$importSubject->execute([$attributes['code'], $attributes['code'], $attributes['name'], $attributes['subjectTypeCode']]);
						foreach (explode(' ', $attributes['cycleCodes']) as $vv){
							$importCycleSubject->execute([$vv, $vv, $attributes['code'], $attributes['code']]);
						}
						if (strlen($attributes['taxCode']) && strlen($attributes['taxCode'])){
							$importTaxCode->execute([$attributes['taxCode'], $attributes['taxCode'], $attributes['code']]);
						}
						if (isset($attributes['vatNumber']) && strlen($attributes['vatNumber'])){
							$importVatNumber->execute([$attributes['vatNumber'], $attributes['vatNumber'], $attributes['code']]);
						}
						break;
					case 'transaction':
						$importTransaction->execute([(int)$attributes['amount'], $attributes['code'], $attributes['cycleCode'], $attributes['date'], $attributes['description'], $attributes['code'], $attributes['transactionTypeCode']]);
						if (isset($attributes['cigCode']) && strlen($attributes['cigCode'])){
							$importCigCodeTransaction->execute([$attributes['cigCode'], $attributes['cigCode'], $attributes['code'], $attributes['code']]);
						}
						if (isset($attributes['cupCode']) && strlen($attributes['cupCode'])){
							$importCupCodeTransaction->execute([$attributes['cupCode'], $attributes['cupCode'], $attributes['code'], $attributes['code']]);
						}
						break;
				}
			}
		}
		$this->xmlReader->close();
		$this->xmlReader->open($arguments['path']);
		while ($this->xmlReader->read()){
			if ($this->xmlReader->nodeType == XMLReader::ELEMENT){
				$attributes = [];
				while ($this->xmlReader->moveToNextAttribute()){
					$attributes[$this->xmlReader->localName] = $this->xmlReader->value;
				}
				$this->xmlReader->moveToElement();
				switch ($this->xmlReader->localName){
					case 'actDivision':
						$importActDivision->execute([$attributes['actCode'], $attributes['actCode'], $attributes['divisionCode'], $attributes['divisionCode']]);
						break;
					case 'actSubdivision':
						$importActSubdivision->execute([$attributes['actCode'], $attributes['actCode'], $attributes['subdivisionCode'], $attributes['subdivisionCode']]);
						break;
					case 'actSubject':
						$importActSubject->execute([$attributes['actCode'], $attributes['actCode'], $attributes['subjectCode'], $attributes['subjectCode']]);
						break;
					case 'division':
						if (isset($attributes['parentDivisionCode']) && strlen($attributes['parentDivisionCode'])){
							$importParentDivision->execute([$attributes['code'], $attributes['code'], $attributes['parentDivisionCode'], $attributes['parentDivisionCode']]);
						}
						break;
					case 'divisionSubject':
						$importDivisionSubject->execute([$attributes['divisionCode'], $attributes['subjectCode'], $attributes['divisionCode'], $attributes['subjectCode']]);
						break;
					case 'subdivision':
						if (isset($attributes['divisionCode']) && strlen($attributes['divisionCode'])){
							$importDivisionSubdivision->execute([$attributes['divisionCode'], $attributes['divisionCode'], $attributes['code'], $attributes['code']]);
						}
						if (isset($attributes['parentSubdivisionCode']) && strlen($attributes['parentSubdivisionCode'])){
							$importParentSubdivision->execute([$attributes['code'], $attributes['code'], $attributes['parentSubdivisionCode'], $attributes['parentSubdivisionCode']]);
						}
						break;
					case 'subdivisionSubject':
						$importSubdivisionSubject->execute([$attributes['subdivisionCode'], $attributes['subjectCode'], $attributes['subdivisionCode'], $attributes['subjectCode']]);
						break;
					case 'transaction':
						if (isset($attributes['actCode']) && strlen($attributes['actCode'])){
							$importActTransaction->execute([$attributes['actCode'], $attributes['actCode'], $attributes['code'], $attributes['code']]);
						}
						if (isset($attributes['divisionCode']) && strlen($attributes['divisionCode'])){
							$importDivisionTransaction->execute([$attributes['divisionCode'], $attributes['code'], $attributes['divisionCode'], $attributes['code']]);
						}
						if (isset($attributes['subdivisionCode']) && strlen($attributes['subdivisionCode'])){
							$importSubdivisionTransaction->execute([$attributes['subdivisionCode'], $attributes['code'], $attributes['subdivisionCode'], $attributes['code']]);
						}
						if (isset($attributes['subjectCode']) && strlen($attributes['subjectCode'])){
							$importSubjectTransaction->execute([$attributes['subjectCode'], $attributes['code'], $attributes['subjectCode'], $attributes['code']]);
						}
						break;
				}
			}
		}
		$this->xmlReader->close();
		$sql = <<<SQL
		DELETE FROM denormalizedActs
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		DELETE FROM denormalizedParentDivisions
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		DELETE FROM denormalizedParentSubdivisions
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		DELETE FROM denormalizedDivisions
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		DELETE FROM denormalizedSubdivisions
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		DELETE FROM denormalizedSubjects
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		DELETE FROM denormalizedTransactions
SQL;
		$this->pdo->exec($sql);	
		$sql = <<<SQL
		DELETE FROM indexedDenormalizedActs
SQL;
		$this->pdo->exec($sql);	
		$sql = <<<SQL
		DELETE FROM indexedDenormalizedDivisions
SQL;
		$this->pdo->exec($sql);	
		$sql = <<<SQL
		DELETE FROM indexedDenormalizedSubdivisions
SQL;
		$this->pdo->exec($sql);	
		$sql = <<<SQL
		DELETE FROM indexedDenormalizedSubjects
SQL;
		$this->pdo->exec($sql);	
		$sql = <<<SQL
		DELETE FROM indexedDenormalizedTransactions
SQL;
		$this->pdo->exec($sql);	
		$sql = <<<SQL
		INSERT INTO denormalizedActs 
		SELECT 
			actTypes.code AS actTypeCode,
			actTypes.description AS actTypeDescription,	
			SUM(COALESCE(transactions.amount, 0)) AS amount,
			acts.code, 
			COUNT(transactions.id) AS "count",
			cycles.code AS cycleCode,
			cycles.id AS cycleId,
			acts."date", 
			acts.description, 
			acts.id
		FROM acts 
		JOIN actsCycles ON actsCycles.actId = acts.id
		JOIN cycles ON cycles.id = actsCycles.cycleId
		JOIN actTypes On actTypes.id = acts.actTypeId
		LEFT JOIN actsTransactions ON actsTransactions.actId = acts.id
		LEFT JOIN transactions ON transactions.id = actsTransactions.transactionId
		GROUP BY acts.id, cycles.id
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH RECURSIVE window (ancestorDivisionId, depth, divisionId, path) AS (
			SELECT 
				parentDivisions1.parentDivisionId, 
				1 AS depth, 
				parentDivisions1.divisionId, 
				parentDivisions1.divisionId || ' ' || parentDivisions1.parentDivisionId || ' ' AS path 
			FROM parentDivisions AS parentDivisions1 
			UNION ALL 
			SELECT 
				parentDivisions2.parentDivisionId, 
				window.depth + 1, 
				window.divisionId, 
				window.path || parentDivisions2.parentDivisionId || ' ' 
			FROM parentDivisions AS parentDivisions2 
			JOIN window ON parentDivisions2.divisionId = window.ancestorDivisionId 
--			WHERE window.path NOT LIKE '%' || parentDivisions2.parentDivisionId || ' %' 
		) 
		INSERT INTO denormalizedParentDivisions 
		(ancestorDivisionId, depth, divisionId, id)
		SELECT ancestorDivisionId, depth, divisionId, NULL AS id 
		FROM window
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH RECURSIVE window (ancestorSubdivisionId, depth, subdivisionId, path) AS (
			SELECT 
				parentSubdivisions1.parentSubdivisionId, 
				1 AS depth, 
				parentSubdivisions1.subdivisionId, 
				parentSubdivisions1.subdivisionId || ' ' || parentSubdivisions1.parentSubdivisionId || ' ' AS path 
			FROM parentSubdivisions AS parentSubdivisions1 
			UNION ALL 
			SELECT 
				parentSubdivisions2.parentSubdivisionId, 
				window.depth + 1, 
				window.subdivisionId, 
				window.path || parentSubdivisions2.parentSubdivisionId || ' ' 
			FROM parentSubdivisions AS parentSubdivisions2 
			JOIN window ON parentSubdivisions2.subdivisionId = window.ancestorSubdivisionId 
--			WHERE window.path NOT LIKE '%' || parentSubdivisions2.parentSubdivisionId || ' %' 
		) 
		INSERT INTO denormalizedParentSubdivisions 
		(ancestorSubdivisionId, depth, subdivisionId, id)
		SELECT ancestorSubdivisionId, depth, subdivisionId, NULL AS id 
		FROM window
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH
			window1 (amount, divisionId, id) AS (
				SELECT SUM(amounts.amount), divisionsSubdivisions.divisionId, subdivisions.id
				FROM subdivisions
				JOIN amounts ON amounts.subdivisionId = subdivisions.id
				JOIN divisionsSubdivisions ON divisionsSubdivisions.subdivisionId = subdivisions.id
				GROUP BY divisionsSubdivisions.divisionId
			),
			window2 (amount, divisionId, id) AS (
				SELECT SUM(transactions.amount), divisionsTransactions.divisionId, transactions.id
				FROM transactions
				JOIN divisionsTransactions ON divisionsTransactions.transactionId = transactions.id
				GROUP BY divisionsTransactions.divisionId
			)
		INSERT INTO denormalizedDivisions
		SELECT
			SUM(COALESCE(window1.amount, 0)) AS amount,
			divisions.code,
			cycles.code AS cycleCode,
			cycles.id AS cycleId,
			divisions.description,
			divisionTypes.code AS divisionTypeCode,
			divisionTypes.description AS divisionTypeDescription,
			divisions.id, 
			SUM(COALESCE(window2.amount, 0)) AS usedAmount
		FROM divisions
		JOIN cycles ON cycles.id = divisions.cycleId
		LEFT JOIN denormalizedParentDivisions ON denormalizedParentDivisions.ancestorDivisionId = divisions.id
		JOIN divisionTypes ON divisionTypes.id = divisions.divisionTypeId
		LEFT JOIN window1 ON window1.divisionId IN (divisions.id, denormalizedParentDivisions.divisionId)
		LEFT JOIN window2 ON window2.divisionId IN (divisions.id, denormalizedParentDivisions.divisionId)
		GROUP BY divisions.id
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH window (amount, id, subdivisionId) AS (
			SELECT SUM(transactions.amount), transactions.id, subdivisionsTransactions.subdivisionId
			FROM transactions
			JOIN subdivisionsTransactions ON subdivisionsTransactions.transactionId = transactions.id
			GROUP BY subdivisionsTransactions.subdivisionId
		)
		INSERT INTO denormalizedSubdivisions
		SELECT 
			SUM(COALESCE(amounts.amount, 0)) AS "amount",
			subdivisions.code,
			cycles.code AS cycleCode,
			cycles.id AS cycleId,
			subdivisions.description,
			divisions.code AS divisionCode,
			divisions.description AS divisionDescription,
			divisions.id AS divisionId,
			subdivisions.id, 
			subdivisionTypes.code AS subdivisionTypeCode,
			subdivisionTypes.description AS subdivisionTypeDescription,
			SUM(COALESCE(window.amount, 0)) AS "usedAmount"
		FROM subdivisions
		JOIN cycles ON cycles.id = subdivisions.cycleId
		LEFT JOIN denormalizedParentSubdivisions ON denormalizedParentSubdivisions.ancestorSubdivisionId = subdivisions.id
		LEFT JOIN amounts ON amounts.subdivisionId IN (subdivisions.id, denormalizedParentSubdivisions.subdivisionId)
		LEFT JOIN divisionsSubdivisions ON divisionsSubdivisions.subdivisionId = subdivisions.id
		LEFT JOIN divisions ON divisions.id = divisionsSubdivisions.divisionId
		JOIN subdivisionTypes ON subdivisionTypes.id = subdivisions.subdivisionTypeId
		LEFT JOIN window ON window.subdivisionId IN (subdivisions.id, denormalizedParentSubdivisions.subdivisionId)
		GROUP BY subdivisions.id		
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		INSERT INTO denormalizedSubjects 
		SELECT 
			SUM(COALESCE(transactions.amount, 0)) AS amount,
			subjects.code, 
			COUNT(transactions.id) AS "count",
			cycles.code AS cycleCode,
			cycles.id AS cycleId,
			subjectTypes.code AS subjectTypeCode, 
			subjectTypes.description AS subjectTypeDescription, 
			subjects.id, 
			subjects.name, 
			taxCodes.code AS taxCode, 
			vatNumbers."number" AS "number" 
		FROM subjects 
		JOIN cyclesSubjects ON cyclesSubjects.subjectId = subjects.id
		JOIN cycles ON cycles.id = cyclesSubjects.cycleId
		LEFT JOIN subjectsTransactions ON subjectsTransactions.subjectId = subjects.id
		LEFT JOIN transactions ON transactions.id = subjectsTransactions.transactionId
		JOIN subjectTypes On subjectTypes.id = subjects.subjectTypeId 
		LEFT JOIN taxCodes ON taxCodes.subjectId = subjects.id 
		LEFT JOIN vatNumbers ON vatNumbers.subjectId = subjects.id
		GROUP BY cycles.id, subjects.id
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		INSERT INTO denormalizedTransactions
		SELECT 
			acts.code AS actCode,
			acts.description AS actDescription,
			acts.id AS actId,
			transactions.amount,
			transactions.code,
			transactions."date",
			cycles.code AS cycleCode,
			cycles.id AS cycleId,
			transactions.description,
			divisions.code AS divisionCode,
			divisions.description AS divisionDescription,
			divisions.id AS divisionId,
			transactions.id,
			subdivisions.code AS subdivisionCode,
			subdivisions.description AS subdivisionDescription,
			subdivisions.id AS subdivisionId,
			subjects.code AS subjectCode,
			subjects.id AS subjectId,
			subjects.name AS subjectName,
			transactionTypes.code AS transactionTypeCode,
			transactionTypes.description AS transactionTypeDescription
		FROM transactions
		LEFT JOIN actsTransactions ON actsTransactions.transactionId = transactions.id
		LEFT JOIN acts ON acts.id = actsTransactions.actId
		JOIN cycles ON cycles.id = transactions.cycleId
		LEFT JOIN divisionsTransactions ON divisionsTransactions.transactionId = transactions.id
		LEFT JOIN divisions ON divisions.id = divisionsTransactions.divisionId
		LEFT JOIN subdivisionsTransactions ON subdivisionsTransactions.transactionId = transactions.id
		LEFT JOIN subdivisions ON subdivisions.id = subdivisionsTransactions.subdivisionId
		LEFT JOIN subjectsTransactions ON subjectsTransactions.transactionId = transactions.id
		LEFT JOIN subjects ON subjects.id = subjectsTransactions.subjectId
		JOIN transactionTypes ON transactionTypes.id = transactions.transactionTypeId
SQL;
		$this->pdo->exec($sql);	
		$sql = <<<SQL
		WITH
			windowA (id, path) AS (
				SELECT divisions.id, divisions.code || ' ' || GROUP_CONCAT(DISTINCT divisions2.code) || ' ' || divisions.description || ' ' || GROUP_CONCAT(DISTINCT divisions2.description)
				FROM divisions
				LEFT JOIN denormalizedParentDivisions ON denormalizedParentDivisions.divisionId = divisions.id
				LEFT JOIN divisions AS divisions2 ON divisions2.id = denormalizedParentDivisions.ancestorDivisionId
				GROUP BY divisions.id
			),
			windowB (id, path) AS (
				SELECT subdivisions.id, subdivisions.code || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.code) || ' ' || subdivisions.description || ' ' || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.description)
				FROM subdivisions
				LEFT JOIN denormalizedParentSubdivisions ON denormalizedParentSubdivisions.subdivisionId = subdivisions.id
				LEFT JOIN subdivisions AS subdivisions2 ON subdivisions2.id = denormalizedParentSubdivisions.ancestorSubdivisionId
				GROUP BY subdivisions.id
			),
			window1 (id, divisions) AS (
				SELECT actsDivisions.actId, GROUP_CONCAT(divisions.code || ' ' || divisions.description)  || ' ' || GROUP_CONCAT(windowA.path)
				FROM divisions
				JOIN actsDivisions ON actsDivisions.divisionId = divisions.id
				LEFT JOIN windowA ON windowA.id = divisions.id
				GROUP BY actsDivisions.actId
			),
			window2 (id, subdivisions) AS (
				SELECT actsSubdivisions.actId, GROUP_CONCAT(subdivisions.code || ' ' || subdivisions.description) || ' ' || GROUP_CONCAT(windowB.path)
				FROM subdivisions
				JOIN actsSubdivisions ON actsSubdivisions.subdivisionId = subdivisions.id
				LEFT JOIN windowB ON windowB.id = subdivisions.id
				GROUP BY actsSubdivisions.actId
			),
			window3 (id, subjects) AS (
				SELECT actsSubjects.actId, GROUP_CONCAT(subjects.code || ' ' || subjects.name)
				FROM subjects
				JOIN actsSubjects ON actsSubjects.subjectId = subjects.id
				GROUP BY actsSubjects.actId
			),
			window4 (id, transactions) AS (
				SELECT actsTransactions.actId, GROUP_CONCAT(transactions.code || ' ' || transactions.description)
				FROM transactions
				JOIN actsTransactions ON actsTransactions.transactionId = transactions.id
				GROUP BY actsTransactions.actId
			)
		INSERT INTO indexedDenormalizedActs
		(rowid, acts, divisions, subdivisions, subjects, transactions)
		SELECT acts.id, acts.code || ' ' || acts.description AS acts, window1.divisions, window2.subdivisions, window3.subjects, window4.transactions
		FROM acts
		LEFT JOIN window1 ON window1.id = acts.id
		LEFT JOIN window2 ON window2.id = acts.id
		LEFT JOIN window3 ON window3.id = acts.id
		LEFT JOIN window4 ON window4.id = acts.id
		GROUP BY acts.id
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH
			windowA (id, path) AS (
				SELECT divisions.id, divisions.code || ' ' || GROUP_CONCAT(DISTINCT divisions2.code) || ' ' || divisions.description || ' ' || GROUP_CONCAT(DISTINCT divisions2.description)
				FROM divisions
				LEFT JOIN denormalizedParentDivisions ON denormalizedParentDivisions.divisionId = divisions.id
				LEFT JOIN divisions AS divisions2 ON divisions2.id = denormalizedParentDivisions.ancestorDivisionId
				GROUP BY divisions.id
			),
			windowB (id, path) AS (
				SELECT subdivisions.id, subdivisions.code || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.code) || ' ' || subdivisions.description || ' ' || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.description)
				FROM subdivisions
				LEFT JOIN denormalizedParentSubdivisions ON denormalizedParentSubdivisions.subdivisionId = subdivisions.id
				LEFT JOIN subdivisions AS subdivisions2 ON subdivisions2.id = denormalizedParentSubdivisions.ancestorSubdivisionId
				GROUP BY subdivisions.id
			),
			window1 (id, acts) AS (
				SELECT actsDivisions.divisionId, GROUP_CONCAT(acts.code || ' ' || acts.description)
				FROM acts
				JOIN actsDivisions ON actsDivisions.actId = acts.id
				GROUP BY actsDivisions.divisionId
			),
			window2 (id, subdivisions) AS (
				SELECT divisionsSubdivisions.divisionId, GROUP_CONCAT(subdivisions.code || ' ' || subdivisions.description) || ' ' || windowB.path
				FROM subdivisions
				JOIN divisionsSubdivisions ON divisionsSubdivisions.subdivisionId = subdivisions.id
				LEFT JOIN windowB ON windowB.id = subdivisions.id
				GROUP BY divisionsSubdivisions.divisionId
			),
			window3 (id, subjects) AS (
				SELECT divisionsSubjects.divisionId, GROUP_CONCAT(subjects.code || ' ' || subjects.name)
				FROM subjects
				JOIN divisionsSubjects ON divisionsSubjects.subjectId = subjects.id
				GROUP BY divisionsSubjects.divisionId
			),
			window4 (id, transactions) AS (
				SELECT divisionsTransactions.divisionId, GROUP_CONCAT(transactions.code || ' ' || transactions.description)
				FROM transactions
				JOIN divisionsTransactions ON divisionsTransactions.transactionId = transactions.id
				GROUP BY divisionsTransactions.divisionId
			)
		INSERT INTO indexedDenormalizedDivisions
		(rowid, acts, divisions, subdivisions, subjects, transactions)
		SELECT divisions.id, window1.acts, divisions.code || ' ' || divisions.description || ' ' || windowA.path AS divisions, window2.subdivisions, window3.subjects, window4.transactions
		FROM divisions
		LEFT JOIN windowA ON windowA.id = divisions.id
		LEFT JOIN window1 ON window1.id = divisions.id
		LEFT JOIN window2 ON window2.id = divisions.id
		LEFT JOIN window3 ON window3.id = divisions.id
		LEFT JOIN window4 ON window4.id = divisions.id
		GROUP BY divisions.id
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH
			windowA (id, path) AS (
				SELECT divisions.id, divisions.code || ' ' || GROUP_CONCAT(DISTINCT divisions2.code) || ' ' || divisions.description || ' ' || GROUP_CONCAT(DISTINCT divisions2.description)
				FROM divisions
				LEFT JOIN denormalizedParentDivisions ON denormalizedParentDivisions.divisionId = divisions.id
				LEFT JOIN divisions AS divisions2 ON divisions2.id = denormalizedParentDivisions.ancestorDivisionId
				GROUP BY divisions.id
			),
			windowB (id, path) AS (
				SELECT subdivisions.id, subdivisions.code || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.code) || ' ' || subdivisions.description || ' ' || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.description)
				FROM subdivisions
				LEFT JOIN denormalizedParentSubdivisions ON denormalizedParentSubdivisions.subdivisionId = subdivisions.id
				LEFT JOIN subdivisions AS subdivisions2 ON subdivisions2.id = denormalizedParentSubdivisions.ancestorSubdivisionId
				GROUP BY subdivisions.id
			),
			window1 (id, acts) AS (
				SELECT actsSubdivisions.subdivisionId, GROUP_CONCAT(acts.code || ' ' || acts.description)
				FROM acts
				JOIN actsSubdivisions ON actsSubdivisions.actId = acts.id
				GROUP BY actsSubdivisions.subdivisionId
			),
			window2 (id, divisions) AS (
				SELECT divisionsSubdivisions.subdivisionId, GROUP_CONCAT(divisions.code || ' ' || divisions.description)  || ' ' || windowA.path
				FROM divisions
				JOIN divisionsSubdivisions ON divisionsSubdivisions.divisionId = divisions.id
				LEFT JOIN windowA ON windowA.id = divisions.id
				GROUP BY divisionsSubdivisions.subdivisionId
			),
			window3 (id, subjects) AS (
				SELECT subdivisionsSubjects.subdivisionId, GROUP_CONCAT(subjects.code || ' ' || subjects.name)
				FROM subjects
				JOIN subdivisionsSubjects ON subdivisionsSubjects.subjectId = subjects.id
				GROUP BY subdivisionsSubjects.subdivisionId
			),
			window4 (id, transactions) AS (
				SELECT subdivisionsTransactions.subdivisionId, GROUP_CONCAT(transactions.code || ' ' || transactions.description)
				FROM transactions
				JOIN subdivisionsTransactions ON subdivisionsTransactions.transactionId = transactions.id
				GROUP BY subdivisionsTransactions.subdivisionId
			)
		INSERT INTO indexedDenormalizedSubdivisions
		(rowid, acts, divisions, subdivisions, subjects, transactions)
		SELECT subdivisions.id, window1.acts, window2.divisions, subdivisions.code || ' ' || subdivisions.description || ' ' || windowB.path AS subdivisions, window3.subjects, window4.transactions
		FROM subdivisions
		LEFT JOIN windowB ON windowB.id = subdivisions.id
		LEFT JOIN window1 ON window1.id = subdivisions.id
		LEFT JOIN window2 ON window2.id = subdivisions.id
		LEFT JOIN window3 ON window3.id = subdivisions.id
		LEFT JOIN window4 ON window4.id = subdivisions.id
		GROUP BY subdivisions.id
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH
			windowA (id, path) AS (
				SELECT divisions.id, divisions.code || ' ' || GROUP_CONCAT(DISTINCT divisions2.code) || ' ' || divisions.description || ' ' || GROUP_CONCAT(DISTINCT divisions2.description)
				FROM divisions
				LEFT JOIN denormalizedParentDivisions ON denormalizedParentDivisions.divisionId = divisions.id
				LEFT JOIN divisions AS divisions2 ON divisions2.id = denormalizedParentDivisions.ancestorDivisionId
				GROUP BY divisions.id
			),
			windowB (id, path) AS (
				SELECT subdivisions.id, subdivisions.code || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.code) || ' ' || subdivisions.description || ' ' || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.description)
				FROM subdivisions
				LEFT JOIN denormalizedParentSubdivisions ON denormalizedParentSubdivisions.subdivisionId = subdivisions.id
				LEFT JOIN subdivisions AS subdivisions2 ON subdivisions2.id = denormalizedParentSubdivisions.ancestorSubdivisionId
				GROUP BY subdivisions.id
			),
			window1 (id, acts) AS (
				SELECT actsSubjects.subjectId, GROUP_CONCAT(acts.code || ' ' || acts.description)
				FROM acts
				JOIN actsSubjects ON actsSubjects.actId = acts.id
				GROUP BY actsSubjects.subjectId
			),
			window2 (id, divisions) AS (
				SELECT divisionsSubjects.subjectId, GROUP_CONCAT(divisions.code || ' ' || divisions.description)  || ' ' || GROUP_CONCAT(windowA.path)
				FROM divisions
				JOIN divisionsSubjects ON divisionsSubjects.divisionId = divisions.id
				LEFT JOIN windowA ON windowA.id = divisions.id
				GROUP BY divisionsSubjects.subjectId
			),
			window3 (id, subdivisions) AS (
				SELECT subdivisionsSubjects.subjectId, GROUP_CONCAT(subdivisions.code || ' ' || subdivisions.description) || ' ' || GROUP_CONCAT(windowB.path)
				FROM subdivisions
				JOIN subdivisionsSubjects ON subdivisionsSubjects.subdivisionId = subdivisions.id
				LEFT JOIN windowB ON windowB.id = subdivisions.id
				GROUP BY subdivisionsSubjects.subjectId
			),
			window4 (id, transactions) AS (
				SELECT subjectsTransactions.subjectId, GROUP_CONCAT(transactions.code || ' ' || transactions.description)
				FROM transactions
				JOIN subjectsTransactions ON subjectsTransactions.transactionId = transactions.id
				GROUP BY subjectsTransactions.subjectId
			)
		INSERT INTO indexedDenormalizedSubjects
		(rowid, acts, divisions, subdivisions, subjects, transactions)
		SELECT subjects.id, window1.acts, window2.divisions, window3.subdivisions, subjects.code || ' ' || subjects.name AS subjects, window4.transactions
		FROM subjects
		LEFT JOIN window1 ON window1.id = subjects.id
		LEFT JOIN window2 ON window2.id = subjects.id
		LEFT JOIN window3 ON window3.id = subjects.id
		LEFT JOIN window4 ON window4.id = subjects.id
		GROUP BY subjects.id
SQL;
		$this->pdo->exec($sql);
		$sql = <<<SQL
		WITH
			windowA (id, path) AS (
				SELECT divisions.id, divisions.code || ' ' || GROUP_CONCAT(DISTINCT divisions2.code) || ' ' || divisions.description || ' ' || GROUP_CONCAT(DISTINCT divisions2.description)
				FROM divisions
				LEFT JOIN denormalizedParentDivisions ON denormalizedParentDivisions.divisionId = divisions.id
				LEFT JOIN divisions AS divisions2 ON divisions2.id = denormalizedParentDivisions.ancestorDivisionId
				GROUP BY divisions.id
			),
			windowB (id, path) AS (
				SELECT subdivisions.id, subdivisions.code || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.code) || ' ' || subdivisions.description || ' ' || ' ' || GROUP_CONCAT(DISTINCT subdivisions2.description)
				FROM subdivisions
				LEFT JOIN denormalizedParentSubdivisions ON denormalizedParentSubdivisions.subdivisionId = subdivisions.id
				LEFT JOIN subdivisions AS subdivisions2 ON subdivisions2.id = denormalizedParentSubdivisions.ancestorSubdivisionId
				GROUP BY subdivisions.id
			),
			window1 (id, acts) AS (
				SELECT actsTransactions.transactionId, GROUP_CONCAT(acts.code || ' ' || acts.description)
				FROM acts
				JOIN actsTransactions ON actsTransactions.actId = acts.id
				GROUP BY actsTransactions.transactionId
			),
			window2 (id, divisions) AS (
				SELECT divisionsTransactions.transactionId, GROUP_CONCAT(divisions.code || ' ' || divisions.description)  || ' ' || windowA.path
				FROM divisions
				JOIN divisionsTransactions ON divisionsTransactions.divisionId = divisions.id
				LEFT JOIN windowA ON windowA.id = divisions.id
				GROUP BY divisionsTransactions.transactionId
			),
			window3 (id, subdivisions) AS (
				SELECT subdivisionsTransactions.transactionId, GROUP_CONCAT(subdivisions.code || ' ' || subdivisions.description) || ' ' || windowB.path
				FROM subdivisions
				JOIN subdivisionsTransactions ON subdivisionsTransactions.subdivisionId = subdivisions.id
				LEFT JOIN windowB ON windowB.id = subdivisions.id
				GROUP BY subdivisionsTransactions.transactionId
			),
			window4 (id, subjects) AS (
				SELECT subjectsTransactions.transactionId, GROUP_CONCAT(subjects.code || ' ' || subjects.name)
				FROM subjects
				JOIN subjectsTransactions ON subjectsTransactions.subjectId = subjects.id
				GROUP BY subjectsTransactions.transactionId
			)
		INSERT INTO indexedDenormalizedTransactions
		(rowid, acts, divisions, subdivisions, subjects, transactions)
		SELECT transactions.id, window1.acts, window2.divisions, window3.subdivisions, window4.subjects, transactions.code || ' ' || transactions.description AS transactions
		FROM transactions
		LEFT JOIN window1 ON window1.id = transactions.id
		LEFT JOIN window2 ON window2.id = transactions.id
		LEFT JOIN window3 ON window3.id = transactions.id
		LEFT JOIN window4 ON window4.id = transactions.id
		GROUP BY transactions.id
SQL;
		$this->pdo->exec($sql);		
		$this->pdo->commit();
	}
}