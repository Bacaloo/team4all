<?php

declare(strict_types=1);

namespace OCA\Team4All\Controller;

use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppManager $appManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 */
	public function index(): TemplateResponse {
		if (!$this->appManager->isEnabledForAnyone('contacts')) {
			return new TemplateResponse('team4all', 'contacts_missing');
		}

		return new TemplateResponse('team4all', 'main');
	}
}