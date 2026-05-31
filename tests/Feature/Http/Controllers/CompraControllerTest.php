<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\CompraController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see CompraController
 */
final class CompraControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(CompraController::class));
    }
}
