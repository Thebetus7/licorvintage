<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\MovimientoCajaController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see MovimientoCajaController
 */
final class MovimientoCajaControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(MovimientoCajaController::class));
    }
}
