<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_cashier_account_views_use_modern_corporate_layout(): void
    {
        $user = \App\Models\User::where('email', 'kodzogadzekpo@gmail.com')->first();
        $this->actingAs($user);

        // Test accounts/2 (show)
        $responseShow = $this->get('/caissier/accounts/2');
        $responseShow->assertStatus(200);
        $responseShow->assertDontSee('glass-card');
        $responseShow->assertSee('kpi-pastel-card');
        $responseShow->assertSee('FSD YAYRA');

        // Test accounts/6/deposit (deposit)
        $responseDep6 = $this->get('/caissier/accounts/6/deposit');
        $responseDep6->assertStatus(200);
        $responseDep6->assertDontSee('glass-card');
        $responseDep6->assertSee('Valider', false);
        $responseDep6->assertSee('FSD YAYRA');

        // Test accounts/2/deposit
        $responseDep2 = $this->get('/caissier/accounts/2/deposit');
        $responseDep2->assertStatus(200);
        $responseDep2->assertDontSee('glass-card');
        $responseDep2->assertSee('FSD YAYRA');

        // Test inscription express
        $responseReg = $this->get('/caissier/clients/register-with-tontine');
        $responseReg->assertStatus(200);
        $responseReg->assertDontSee('glass-card');
        $responseReg->assertSee('FSD YAYRA');
    }

    public function test_cashier_loan_repayment(): void
    {
        $user = \App\Models\User::where('email', 'kodzogadzekpo@gmail.com')->first();
        $this->actingAs($user);

        // Test loan 2 repay
        $response = $this->post('/caissier/loans/2/repay', [
            'amount' => 18083,
            'payment_method' => 'cash',
            'notes' => 'Test Remboursement'
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/caissier/loans/2');

        // Test API mobile endpoint for repayment
        $responseApi = $this->actingAs($user, 'sanctum')->postJson('/api/v1/agent/loans/2/repay', [
            'amount' => 18083,
            'payment_method' => 'cash',
        ]);
        $responseApi->assertStatus(200);
        $responseApi->assertJson(['success' => true]);

        // Test depot and retrait search
        $responseDepotSearch = $this->actingAs($user)->getJson('/caissier/depot/search?query=Sewoda');
        $responseDepotSearch->assertStatus(200);
        $responseDepotSearch->assertJson(['success' => true]);
        $this->assertNotEmpty($responseDepotSearch->json('data'));

        $responseRetraitSearch = $this->actingAs($user)->getJson('/caissier/retrait/search?query=Sewoda');
        $responseRetraitSearch->assertStatus(200);
        $responseRetraitSearch->assertJson(['success' => true]);
        $this->assertNotEmpty($responseRetraitSearch->json('data'));
    }
}
