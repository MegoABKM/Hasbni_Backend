<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_admin_login_page_is_available(): void
    {
        $this->get(route('filament.admin.auth.login'))
            ->assertOk();
    }
}
