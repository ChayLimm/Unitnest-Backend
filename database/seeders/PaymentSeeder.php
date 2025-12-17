<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Contract;
use App\Models\User;
use App\Models\Service;
use App\Models\Transaction;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contracts = Contract::with(['room.building', 'tenant'])->get();

        if ($contracts->isEmpty()) {
            // If no contracts, we can't create realistic payments based on contracts
            // But we can create standalone payments
             Payment::factory()->count(10)->create();
             return;
        }

        foreach ($contracts as $contract) {
            $room = $contract->room;
            $landlordId = $room->building->landlord_id;
            
            // Payment tenant_id expects a User. The Contract has a Tenant model.
            // This is a schema discrepancy. The Tenant model is independent of User table (mostly).
            // However, the payments table defines tenant_id as foreign key to users.
            // For now, let's create a User for the tenant if one doesn't exist matching the email,
            // or just create a dummy user to satisfy the Key constraint.
            
            // Try to find a user with the tenant's email
            $tenantUser = User::where('email', $contract->tenant->email)->first();
            
            if (!$tenantUser && $contract->tenant->email) {
                 $tenantUser = User::factory()->create([
                     'email' => $contract->tenant->email,
                     'name' => $contract->tenant->first_name . ' ' . $contract->tenant->last_name,
                     'role_id' => \App\Models\Role::where('role_name', 'Tenant')->first()->id ?? 3
                 ]);
            } elseif (!$tenantUser) {
                 $tenantUser = User::factory()->create([
                      'role_id' => \App\Models\Role::where('role_name', 'Tenant')->first()->id ?? 3
                 ]);
            }

            // Create a few payments for this contract/room
            $payments = Payment::factory()->count(3)->create([
                'tenant_id' => $tenantUser->id,
                'landlord_id' => $landlordId,
                'room_id' => $room->id,
                'transaction_id' => Transaction::factory(),
            ]);

            // Add Payment Items
            $services = Service::where('landlord_id', $landlordId)->get();
            if ($services->isEmpty()) {
                 $services = Service::factory()->count(3)->create(['landlord_id' => $landlordId]);
            }

            foreach ($payments as $payment) {
                foreach ($services as $service) {
                    PaymentItem::factory()->create([
                        'payment_id' => $payment->id,
                        'service_id' => $service->id,
                    ]);
                }
            }
        }
    }
}
