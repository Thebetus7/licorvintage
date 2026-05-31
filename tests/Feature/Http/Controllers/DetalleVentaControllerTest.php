<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\DetalleVentaController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see DetalleVentaController
 */
final class DetalleVentaControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(DetalleVentaController::class));
    }
}
