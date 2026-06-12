<?php

namespace Tests\Feature\Api\Pos;

use App\Models\PaymentBank;

class PaymentBankTest extends PosApiTestCase
{
    public function test_can_list_payment_banks(): void
    {
        $this->withHeaders($this->posHeaders())
            ->getJson('/api/pos/payment-banks')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'bankName', 'accountNumber', 'accountName', 'isActive']],
            ]);
    }

    public function test_can_create_payment_bank_with_manage_permission(): void
    {
        $this->withHeaders($this->posHeaders())
            ->postJson('/api/pos/payment-banks', [
                'bank_name' => 'Mandiri',
                'account_number' => '9876543210',
                'account_name' => 'Toko yClothes',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.bankName', 'Mandiri');

        $this->assertDatabaseHas('payment_banks', [
            'bank_name' => 'Mandiri',
            'account_number' => '9876543210',
        ]);
    }

    public function test_can_update_payment_bank(): void
    {
        $bank = PaymentBank::query()->firstOrFail();

        $this->withHeaders($this->posHeaders())
            ->patchJson('/api/pos/payment-banks/'.$bank->id, [
                'bank_name' => 'BCA Updated',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.bankName', 'BCA Updated')
            ->assertJsonPath('data.isActive', false);
    }

    public function test_can_delete_payment_bank(): void
    {
        $bank = PaymentBank::query()->create([
            'bank_name' => 'Hapus',
            'account_number' => '111',
            'account_name' => 'Test',
            'is_active' => true,
        ]);

        $this->withHeaders($this->posHeaders())
            ->deleteJson('/api/pos/payment-banks/'.$bank->id)
            ->assertOk();

        $this->assertDatabaseMissing('payment_banks', ['id' => $bank->id]);
    }
}
