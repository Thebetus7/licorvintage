<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\DetallePromoController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see DetallePromoController
 */
final class DetallePromoControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(DetallePromoController::class));
    }
}
