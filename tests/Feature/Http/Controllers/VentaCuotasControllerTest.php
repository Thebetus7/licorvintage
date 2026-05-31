<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\VentaCuotasController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see VentaCuotasController
 */
final class VentaCuotasControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(VentaCuotasController::class));
    }
}
