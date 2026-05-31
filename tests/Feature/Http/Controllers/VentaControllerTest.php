<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\VentaController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see VentaController
 */
final class VentaControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(VentaController::class));
    }
}
