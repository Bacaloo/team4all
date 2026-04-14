<?php

declare(strict_types=1);

namespace OCA\Team4All\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Team4All\Db\ContactMeta;
use OCA\Team4All\Db\ContactMetaMapper;
use OCA\Team4All\Service\ContactMetaService;
use PHPUnit\Framework\TestCase;

class ContactMetaServiceTest extends TestCase {
	public function testGetMetaReturnsEmptyPayloadWhenNoRowExists(): void {
		$mapper = $this->createMock(ContactMetaMapper::class);
		$mapper->expects(self::once())
			->method('findOneByNcUserIdAndContactUid')
			->with('mark', 'contact-uid')
			->willReturn(null);

		$service = new ContactMetaService($mapper);
		$payload = $service->getMetaByNcUserIdAndContactUid('mark', 'contact-uid');

		self::assertSame('mark', $payload['ncUserId']);
		self::assertSame('contact-uid', $payload['contactUid']);
		self::assertNull($payload['anrede']);
		self::assertNull($payload['briefanrede']);
	}

	public function testSaveMetaCreatesNewRowWhenMissing(): void {
		$mapper = $this->createMock(ContactMetaMapper::class);
		$mapper->expects(self::once())
			->method('findOneByNcUserIdAndContactUid')
			->willReturn(null);
		$mapper->expects(self::once())
			->method('insert')
			->with(self::callback(function (ContactMeta $meta): bool {
				return $meta->getNcUserId() === 'mark'
					&& $meta->getContactUid() === 'contact-uid'
					&& $meta->getAnrede() === 'Herr'
					&& $meta->getBriefanrede() === 'Sehr geehrter Herr Muster';
			}));
		$mapper->expects(self::never())->method('update');

		$service = new ContactMetaService($mapper);
		$payload = $service->saveMetaByNcUserIdAndContactUid(
			'mark',
			'contact-uid',
			'Herr',
			'Sehr geehrter Herr Muster',
		);

		self::assertSame('Herr', $payload['anrede']);
		self::assertSame('Sehr geehrter Herr Muster', $payload['briefanrede']);
		self::assertNotNull($payload['createdAt']);
		self::assertNotNull($payload['updatedAt']);
	}

	public function testSaveMetaUpdatesExistingRowWithoutCreatingDuplicate(): void {
		$existing = new ContactMeta();
		$existing->setId(9);
		$existing->setNcUserId('mark');
		$existing->setContactUid('contact-uid');
		$existing->setAnrede('Herr');
		$existing->setBriefanrede('Alt');
		$existing->setCreatedAt(new DateTimeImmutable('2026-04-14T08:00:00+00:00'));
		$existing->setUpdatedAt(new DateTimeImmutable('2026-04-14T08:00:00+00:00'));

		$mapper = $this->createMock(ContactMetaMapper::class);
		$mapper->expects(self::once())
			->method('findOneByNcUserIdAndContactUid')
			->willReturn($existing);
		$mapper->expects(self::never())->method('insert');
		$mapper->expects(self::once())
			->method('update')
			->with(self::callback(function (ContactMeta $meta): bool {
				return $meta->getId() === 9
					&& $meta->getAnrede() === null
					&& $meta->getBriefanrede() === 'Neu';
			}));

		$service = new ContactMetaService($mapper);
		$payload = $service->saveMetaByNcUserIdAndContactUid(
			'mark',
			'contact-uid',
			'',
			'Neu',
		);

		self::assertNull($payload['anrede']);
		self::assertSame('Neu', $payload['briefanrede']);
	}
}
