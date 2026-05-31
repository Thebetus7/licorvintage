<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\DetalleCompraController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see DetalleCompraController
 */
final class DetalleCompraControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(DetalleCompraController::class));
    }
}
