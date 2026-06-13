<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('passport')]
class PassportInstallationTest extends TestCase
{
    private string $basePath;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__, 2);
        $this->storagePath = $this->basePath.'/storage';
    }

    #[Test]
    public function passport_package_is_installed(): void
    {
        $composerLockPath = $this->basePath.'/composer.lock';

        $this->assertFileExists(
            $composerLockPath,
            'composer.lock not found in the project root.'
        );

        $composerLock = json_decode(file_get_contents($composerLockPath), associative: true);
        $installedPackages = array_column($composerLock['packages'] ?? [], 'name');

        $this->assertContains(
            'laravel/passport',
            $installedPackages,
            'laravel/passport is not installed. Run: composer require laravel/passport'
        );
    }

    #[Test]
    public function passport_private_key_exists(): void
    {
        $keyFile = $this->storagePath.'/oauth-private.key';
        $envKey = $this->resolveEnvVariable('PASSPORT_PRIVATE_KEY');

        $this->assertTrue(
            file_exists($keyFile) || ! empty($envKey),
            'Passport private key not found (file or environment variable). Run: php artisan passport:keys'
        );
    }

    #[Test]
    public function passport_public_key_exists(): void
    {
        $keyFile = $this->storagePath.'/oauth-public.key';
        $envKey = $this->resolveEnvVariable('PASSPORT_PUBLIC_KEY');

        $this->assertTrue(
            file_exists($keyFile) || ! empty($envKey),
            'Passport public key not found (file or environment variable). Run: php artisan passport:keys'
        );
    }

    private function resolveEnvVariable(string $key): string|false
    {
        $systemValue = getenv($key);

        if ($systemValue !== false && $systemValue !== '') {
            return $systemValue;
        }

        $envFile = $this->basePath.'/.env';

        if (! file_exists($envFile)) {
            return false;
        }

        $content = file_get_contents($envFile);

        if (preg_match('/^'.preg_quote($key, '/').'=(.+)$/m', $content, $matches)) {
            return trim($matches[1], " \t\"'");
        }

        return false;
    }
}
