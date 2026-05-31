<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\MetodoPagoController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see MetodoPagoController
 */
final class MetodoPagoControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(MetodoPagoController::class));
    }
}
