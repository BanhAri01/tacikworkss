<?php

namespace Tests;

use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Roles must exist for registration and role checks; seeded after RefreshDatabase migrates. */
    protected bool $seed = true;

    protected string $seeder = RolesSeeder::class;
}
