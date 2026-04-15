use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;

#[NoAdminRequired]
public function getData(): DataResponse {
    return new DataResponse(['ok' => true]);
}