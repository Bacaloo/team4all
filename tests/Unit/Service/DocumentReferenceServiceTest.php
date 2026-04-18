<?php

declare(strict_types=1);

namespace OCA\Team4All\Tests\Unit\Service;

use DateTimeImmutable;
use OCA\Team4All\Db\DocumentReference;
use OCA\Team4All\Db\DocumentReferenceMapper;
use OCA\Team4All\Service\DocumentReferenceService;
use PHPUnit\Framework\TestCase;

class DocumentReferenceServiceTest extends TestCase {
	public function testGetDocumentsByContactUidsGroupsRowsByContactUid(): void {
		$leaderDocument = new DocumentReference();
		$leaderDocument->setContactUid('leader-uid');
		$leaderDocument->setFileName('DOC-1001');
		$leaderDocument->setSubject('Vertrag');
		$leaderDocument->setDocumentCreatedAt(new DateTimeImmutable('2026-04-18T07:00:00+00:00'));

		$memberDocument = new DocumentReference();
		$memberDocument->setContactUid('member-uid');
		$memberDocument->setFileName('DOC-1002');
		$memberDocument->setSubject('');
		$memberDocument->setDocumentCreatedAt(new DateTimeImmutable('2026-04-17T07:00:00+00:00'));

		$mapper = $this->createMock(DocumentReferenceMapper::class);
		$mapper->expects(self::once())
			->method('findByContactUids')
			->with(['leader-uid', 'member-uid'])
			->willReturn([$leaderDocument, $memberDocument]);

		$service = new DocumentReferenceService($mapper);
		$payload = $service->getDocumentsByContactUids(['leader-uid', 'member-uid']);

		self::assertSame('Vertrag', $payload['leader-uid'][0]['subject']);
		self::assertSame('DOC-1001', $payload['leader-uid'][0]['fileName']);
		self::assertSame('DOC-1002', $payload['member-uid'][0]['subject']);
		self::assertSame('2026-04-17T07:00:00+00:00', $payload['member-uid'][0]['documentCreatedAt']);
	}
}
