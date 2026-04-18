<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCA\Team4All\Db\DocumentReference;
use OCA\Team4All\Db\DocumentReferenceMapper;

class DocumentReferenceService {
	public function __construct(
		private DocumentReferenceMapper $documentReferenceMapper,
	) {
	}

	/**
	 * @param list<string> $contactUids
	 * @return array<string, list<array{contactUid:string,fileName:string,subject:string,documentCreatedAt:?string}>>
	 */
	public function getDocumentsByContactUids(array $contactUids): array {
		$documentsByContactUid = [];

		foreach ($this->documentReferenceMapper->findByContactUids($contactUids) as $documentReference) {
			$contactUid = $documentReference->getContactUid();
			if ($contactUid === '') {
				continue;
			}

			$documentsByContactUid[$contactUid] ??= [];
			$documentsByContactUid[$contactUid][] = $this->toPayload($documentReference);
		}

		return $documentsByContactUid;
	}

	/**
	 * @return array{contactUid:string,fileName:string,subject:string,documentCreatedAt:?string}
	 */
	private function toPayload(DocumentReference $documentReference): array {
		$subject = trim((string)$documentReference->getSubject());
		$fileName = trim($documentReference->getFileName());

		return [
			'contactUid' => $documentReference->getContactUid(),
			'fileName' => $fileName,
			'subject' => $subject !== '' ? $subject : $fileName,
			'documentCreatedAt' => $documentReference->getDocumentCreatedAt()?->format(DATE_ATOM),
		];
	}
}
