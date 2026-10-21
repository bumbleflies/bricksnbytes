<?php
declare(strict_types=1);

namespace Backoffice\Modules\Newsletter;

use Backoffice\Config;
use Backoffice\Modules\Kunden\KundenRepository;
use Backoffice\Modules\Kunden\KundenValidator;
use Backoffice\Router;

// Newsletter unsubscribe from the website (bricksnbytes.de/newsletter-abmelden): the website mailer posts
// the address here with a shared token (header X-Abmelde-Token = config newsletter.abmelde_token).
// No login, no CSRF – the token is the only key; without a configured token the endpoint is off.
final class AbmeldungController
{
    private const MAX_BODY = 4096;

    public static function register(Router $router): void
    {
        $router->post('/api/newsletter-abmelden', [self::class, 'abmelden'], public: true, csrf: false);
    }

    public static function abmelden(): void
    {
        if (!self::tokenValid($_SERVER['HTTP_X_ABMELDE_TOKEN'] ?? null, Config::get('newsletter.abmelde_token'))) {
            self::json(403, ['ok' => false]);
            return;
        }

        $email = self::email((string) file_get_contents('php://input', length: self::MAX_BODY + 1));
        if ($email === null) {
            self::json(422, ['ok' => false]);
            return;
        }

        // Shown as the user in the change log (no session on this endpoint)
        $_SESSION['user_name'] = 'Website (Selbst-Abmeldung)';
        self::json(200, ['ok' => true] + KundenRepository::withdrawNewsletterByEmail($email));
    }

    /** The configured token must be long enough to count as a secret, and the sent one must match it */
    public static function tokenValid(mixed $sent, mixed $token): bool
    {
        return is_string($token) && strlen($token) >= 32 && is_string($sent) && hash_equals($token, $sent);
    }

    /** The address from the JSON body {"email": "…"}, or null when missing, invalid or too large */
    public static function email(string $body): ?string
    {
        if (strlen($body) > self::MAX_BODY) {
            return null;
        }
        $data = json_decode($body, true);
        $email = is_array($data) && is_string($data['email'] ?? null) ? trim($data['email']) : '';
        return $email !== '' && KundenValidator::validEmail($email) ? $email : null;
    }

    private static function json(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
