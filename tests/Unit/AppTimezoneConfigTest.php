<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AppTimezoneConfigTest extends TestCase
{
    public function test_app_timezone_is_configured_via_environment_variable(): void
    {
        $configPath = realpath(__DIR__.'/../../config/app.php');

        $this->assertNotFalse($configPath);
        $this->assertStringContainsString("'timezone' => env('APP_TIMEZONE', 'UTC')", file_get_contents($configPath));
    }
}
