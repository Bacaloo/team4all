<?php

declare(strict_types=1);

namespace OCA\Team4All\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Team4All\Db\ContactMeta;
use OCA\Team4All\Db\ContactMetaMapper;
use OCA\Team4All\Service\ContactMetaService;
use PHPUnit\Framework\TestCase;

class ContactMetaServiceTest extends TestCase {
	public function testGetMetaReturnsEmptyPayloadWhenNoSharedRowExists(): void {
		$mapper = $this->createMock(ContactMetaMapper::class);
		$mapper->expects(self::once())
			->method('findOneByNcUserIdAndContactUid')
			->with('__shared__', 'contact-uid')
			->willReturn(null);
		$mapper->expects(self::once())
			->method('findOneByContactUid')
			->willReturn(null);

		$service = new ContactMetaService($mapper);
		$payload = $service->getMetaByContactUid('contact-uid');

		self::assertSame('__shared__', $payload['ncUserId']);
		self::assertSame('contact-uid', $payload['contactUid']);
		self::assertNull($payload['anrede']);
		self::assertNull($payload['briefanrede']);
	}

	public function testSaveMetaCreatesSharedRowWhenMissing(): void {
		$mapper = $this->createMock(ContactMetaMapper::class);
		$mapper->expects(self::once())
			->method('findOneByNcUserIdAndContactUid')
			->with('__shared__', 'contact-uid')
			->willReturn(null);
		$mapper->expects(self::once())
			->method('findOneByContactUid')
			->willReturn(null);
		$mapper->expects(self::once())
			->method('insert')
			->with(self::callback(function (ContactMeta $meta): bool {
				return $meta->getNcUserId() === '__shared__'
					&& $meta->getContactUid() === 'contact-uid'
					&& $meta->getAnrede() === 'Herr'
					&& $meta->getBriefanrede() === 'Sehr geehrter Herr Muster';
			}));
		$mapper->expects(self::never())->method('update');

		$service = new ContactMetaService($mapper);
		$payload = $service->saveMetaByContactUid('contact-uid', 'Herr', 'Sehr geehrter Herr Muster');

		self::assertSame('Herr', $payload['anrede']);
		self::assertSame('Sehr geehrter Herr Muster', $payload['briefanrede']);
		self::assertNotNull($payload['createdAt']);
		self::assertNotNull($payload['updatedAt']);
	}

	public function testSaveMetaPromotesLegacyRowToSharedScope(): void {
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
			->with('__shared__', 'contact-uid')
			->willReturn(null);
		$mapper->expects(self::once())
			->method('findOneByContactUid')
			->willReturn($existing);
		$mapper->expects(self::never())->method('insert');
		$mapper->expects(self::once())
			->method('update')
			->with(self::callback(function (ContactMeta $meta): bool {
				return $meta->getId() === 9
					&& $meta->getNcUserId() === '__shared__'
					&& $meta->getAnrede() === null
					&& $meta->getBriefanrede() === 'Neu';
			}));

		$service = new ContactMetaService($mapper);
		$payload = $service->saveMetaByContactUid('contact-uid', '', 'Neu');

		self::assertSame('__shared__', $payload['ncUserId']);
		self::assertNull($payload['anrede']);
		self::assertSame('Neu', $payload['briefanrede']);
	}
}
