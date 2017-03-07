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
namespace ReddeRationem\BilancioCivico\Commands;
use \Exception;
use \PDO;
class Mail{
	private $email = null;
	private $pdo = null;
	public function __construct(string $email, PDO $pdo){
		$this->email = $email;
		$this->pdo = $pdo;
	}
	public function submit(array $arguments = []){
		if (!isset($arguments['contact']) || !is_string($arguments['contact']) || !strlen($arguments['contact'])){
			throw new Exception('Invalid $arguments');
		}
		$contact = $arguments['contact'];
		if (!isset($arguments['message']) || !is_string($arguments['message']) || !strlen($arguments['message'])){
			throw new Exception('Invalid $arguments');
		}
		$message = $arguments['message'];
		if (!isset($arguments['subject']) || !is_string($arguments['subject']) || !strlen($arguments['subject'])){
			throw new Exception('Invalid $arguments');
		}
		$subject = $arguments['subject'];
		mail(
			$this->email,
			$subject, 
			'Messaggio da: ' . $contact .  "\r\nMessaggio:\r\n" . $message,
			'From: ' . $this->email
		);
	}
}