<?php

declare(strict_types=1);

namespace OCA\Team4All\Service;

use DateTime;
use DateTimeZone;
use OCA\Team4All\Db\ContactMeta;
use OCA\Team4All\Db\ContactMetaMapper;

class ContactMetaService {
	private const SHARED_SCOPE = '__shared__';

	public function __construct(
		private ContactMetaMapper $contactMetaMapper,
	) {
	}

	/**
	 * @return array{ncUserId:string,contactUid:string,anrede:?string,briefanrede:?string,createdAt:?string,updatedAt:?string}
	 */
	public function getMetaByContactUid(string $contactUid): array {
		$meta = $this->resolveSharedMeta($contactUid);

		return $meta instanceof ContactMeta
			? $this->toPayload($meta)
			: [
				'ncUserId' => self::SHARED_SCOPE,
				'contactUid' => $contactUid,
				'anrede' => null,
				'briefanrede' => null,
				'createdAt' => null,
				'updatedAt' => null,
			];
	}

	/**
	 * @return array{ncUserId:string,contactUid:string,anrede:?string,briefanrede:?string,createdAt:?string,updatedAt:?string}
	 */
	public function saveMetaByContactUid(string $contactUid, ?string $anrede, ?string $briefanrede): array {
		$meta = $this->resolveSharedMeta($contactUid);
		$now = new DateTime('now', new DateTimeZone('UTC'));

		if (!$meta instanceof ContactMeta) {
			$meta = new ContactMeta();
			$meta->setContactUid($contactUid);
			$meta->setCreatedAt($now);
		}

		$meta->setNcUserId(self::SHARED_SCOPE);
		$meta->setAnrede($this->normalizeNullableString($anrede));
		$meta->setBriefanrede($this->normalizeNullableString($briefanrede));
		$meta->setUpdatedAt($now);

		if ($meta->getId() === null) {
			$this->contactMetaMapper->insert($meta);
		} else {
			$this->contactMetaMapper->update($meta);
		}

		return $this->toPayload($meta);
	}

	private function resolveSharedMeta(string $contactUid): ?ContactMeta {
		$sharedMeta = $this->contactMetaMapper->findOneByNcUserIdAndContactUid(self::SHARED_SCOPE, $contactUid);
		if ($sharedMeta instanceof ContactMeta) {
			return $sharedMeta;
		}

		return $this->contactMetaMapper->findOneByContactUid($contactUid);
	}

	private function normalizeNullableString(?string $value): ?string {
		if ($value === null) {
			return null;
		}

		$value = trim($value);

		return $value === '' ? null : $value;
	}

	/**
	 * @return array{ncUserId:string,contactUid:string,anrede:?string,briefanrede:?string,createdAt:?string,updatedAt:?string}
	 */
	private function toPayload(ContactMeta $meta): array {
		return [
			'ncUserId' => $meta->getNcUserId(),
			'contactUid' => $meta->getContactUid(),
			'anrede' => $meta->getAnrede(),
			'briefanrede' => $meta->getBriefanrede(),
			'createdAt' => $meta->getCreatedAt()?->format(DATE_ATOM),
			'updatedAt' => $meta->getUpdatedAt()?->format(DATE_ATOM),
		];
	}
}
