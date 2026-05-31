<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ProveedorController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ProveedorController
 */
final class ProveedorControllerTest extends TestCase
{
    #[Test]
    public function controller_class_exists(): void
    {
        $this->assertTrue(class_exists(ProveedorController::class));
    }
}
