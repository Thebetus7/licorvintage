<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ProductoController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ProductoController
 */
final class ProductoControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductoController::class));
    }
}
