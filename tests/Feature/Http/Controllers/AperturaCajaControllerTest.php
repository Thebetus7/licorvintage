<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\AperturaCajaController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see AperturaCajaController
 */
final class AperturaCajaControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(AperturaCajaController::class));
    }
}
