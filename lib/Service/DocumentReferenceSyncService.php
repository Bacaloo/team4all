<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use OCA\Team4All\Db\DocumentReference;
use OCA\Team4All\Db\DocumentReferenceMapper;
use OCP\Files\File;
use OCP\Files\Folder;

class DocumentReferenceSyncService {
	private const UNASSIGNED_CONTACT_UID = '';

	public function __construct(
		private DocumentReferenceMapper $documentReferenceMapper,
		private TeamFolderProvisioningService $teamFolderProvisioningService,
	) {
	}

	/**
	 * @return array{added:int,deleted:int,total:int}
	 */
	public function syncFromDocumentsFolder(): array {
		$documentsFolder = $this->teamFolderProvisioningService->getDocumentsFolderForUserContext();
		$currentFileNames = $documentsFolder instanceof Folder
			? $this->collectFileNames($documentsFolder)
			: [];

		$existingReferences = $this->documentReferenceMapper->findUnassigned();
		$existingByFileName = [];
		foreach ($existingReferences as $reference) {
			$fileName = trim($reference->getFileName());
			if ($fileName === '') {
				continue;
			}

			$existingByFileName[$fileName] = $reference;
		}

		$added = 0;
		foreach ($currentFileNames as $fileName) {
			if (isset($existingByFileName[$fileName])) {
				unset($existingByFileName[$fileName]);
				continue;
			}

			$reference = new DocumentReference();
			$reference->setContactUid(self::UNASSIGNED_CONTACT_UID);
			$reference->setFileName($fileName);
			$reference->setSubject(null);
			$reference->setDocumentCreatedAt(null);
			$this->documentReferenceMapper->insert($reference);
			$added++;
		}

		$deleted = 0;
		foreach ($existingByFileName as $reference) {
			$this->documentReferenceMapper->delete($reference);
			$deleted++;
		}

		return [
			'added' => $added,
			'deleted' => $deleted,
			'total' => count($currentFileNames),
		];
	}

	/**
	 * @return list<string>
	 */
	public function getUnassignedFileNames(): array {
		return array_map(
			static fn(DocumentReference $reference): string => $reference->getFileName(),
			$this->documentReferenceMapper->findUnassigned()
		);
	}

	/**
	 * @return list<string>
	 */
	private function collectFileNames(Folder $documentsFolder): array {
		$fileNames = [];

		foreach ($documentsFolder->getDirectoryListing() as $node) {
			if (!$node instanceof File) {
				continue;
			}

			$fileName = trim($node->getName());
			if ($fileName === '') {
				continue;
			}

			$fileNames[] = $fileName;
		}

		sort($fileNames, SORT_NATURAL | SORT_FLAG_CASE);

		return array_values(array_unique($fileNames));
	}
}
