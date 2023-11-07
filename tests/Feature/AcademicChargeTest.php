<?php

namespace Tests\Feature;

use App\Models\AcademicCharge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\Traits\UseRolesTable;
use Tests\Traits\UseFirebaseUser;

class AcademicChargeTest extends TestCase
{
    private string $CSV_PATH = 'database/data/SAN-JOAQUIN 2023-1.csv';

    use RefreshDatabase, UseRolesTable, UseFirebaseUser;

    public function test_index_route_is_public(): void
    {
        AcademicCharge::factory()->count(10)->create();
        $response = $this->getJson(route('academic-charges.index'));

        $response->assertStatus(200);
    }

    public function test_show_route_is_public(): void
    {
        $academicCharge = AcademicCharge::factory()->create();

        $response = $this->getJson(route('academic-charges.show', $academicCharge));

        $response->assertStatus(200);
    }

    public function test_it_shows_available_charges_by_default(): void
    {
        AcademicCharge::factory()->create(['is_hidden' => true]);
        $availableCharge = AcademicCharge::factory()->create(['is_hidden' => false]);

        $response = $this->getJson(route('academic-charges.index'));

        // It will only send one charge, the one that is not hidden
        $response->assertJsonCount(1, 'charges');
        $response->assertJsonFragment(['id' => $availableCharge->id]);
    }

    public function test_it_allows_admin_to_upload_academic_charge_file(): void
    {
        $user = $this->createUserWithRoles(['duoc']);
        $this->actingAsFirebaseUser($user);

        // get a file from database/data folder
        $file = new UploadedFile($this->CSV_PATH, 'SAN-JOAQUIN 2023-1.csv', 'text/csv', null, true);

        $response = $this->postJson(
            route('academic-charges.store'),
            ['file' => $file], 
            // ['Content-Type' => 'multipart/form-data']
        );

        $response->assertStatus(201);
    }

    public function test_it_allows_admin_to_update_academic_charges(): void
    {
        $academicCharge = AcademicCharge::factory()->create();
        $user = $this->createUserWithRoles(['duoc']);
        $this->actingAsFirebaseUser($user);
        
        $response = $this->putJson(route('academic-charges.update', $academicCharge), [
            'name' => 'New name',
            'is_hidden' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('academic_charges', [
            'id' => $academicCharge->id,
            'name' => 'New name',
            'is_hidden' => true,
        ]);
    }

    public function test_it_allows_admin_to_delete_academic_charges(): void
    {
        $academicCharge = AcademicCharge::factory()->create();
        $user = $this->createUserWithRoles(['duoc']);
        $this->actingAsFirebaseUser($user);

        $response = $this->deleteJson(route('academic-charges.destroy', $academicCharge));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('academic_charges', [
            'id' => $academicCharge->id,
        ]);
    }

    public function test_it_denies_common_users_to_create_academic_charges(): void
    {
        $academicCharge = AcademicCharge::factory()->make();
        $user = $this->createUserWithRoles(['common']);
        $this->actingAsFirebaseUser($user);

        $response = $this->postJson(route('academic-charges.store'), $academicCharge->toArray());

        $response->assertStatus(403);
        $this->assertDatabaseMissing('academic_charges', $academicCharge->toArray());
    }

    public function test_it_denies_common_users_to_update_academic_charges(): void
    {
        $academicCharge = AcademicCharge::factory()->create();
        $user = $this->createUserWithRoles(['common']);
        $this->actingAsFirebaseUser($user);

        $response = $this->putJson(route('academic-charges.update', $academicCharge), [
            'name' => 'New name',
            'is_hidden' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_it_denies_common_users_to_delete_academic_charges(): void
    {
        $academicCharge = AcademicCharge::factory()->create();
        $user = $this->createUserWithRoles(['common']);
        $this->actingAsFirebaseUser($user);

        $response = $this->deleteJson(route('academic-charges.destroy', $academicCharge));

        $response->assertStatus(403);
    }


}
