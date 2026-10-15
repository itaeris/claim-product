<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_claim_page_renders(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('Claimly', false)
            ->assertSee('id="app"', false);
    }
}
