<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\LoyaltyCard\CardAssign;
use App\Models\LoyaltyCard\CardCategory;
use App\Models\PassengerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class MobileTestPassengerSeeder extends Seeder
{
    public function run()
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The mobile test passenger may only be seeded locally or during tests.');
        }

        $companyId = (int) env('MOBILE_TEST_COMPANY_ID', config('mobile.company_id'));
        $mobile = preg_replace('/\D+/', '', (string) env('MOBILE_TEST_PASSENGER_MOBILE', '03111119999'));
        $password = (string) env('MOBILE_TEST_PASSENGER_PASSWORD', 'Kainat@123');

        if ($companyId < 1) {
            throw new RuntimeException('Set MOBILE_COMPANY_ID or MOBILE_TEST_COMPANY_ID before running this seeder.');
        }

        DB::transaction(function () use ($companyId, $mobile, $password) {
            $customer = Customer::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'contact' => $mobile,
                ],
                [
                    'name' => 'Mobile Test Passenger',
                    'cnic' => '00000-0000000-0',
                ]
            );

            $account = PassengerAccount::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'mobile' => $mobile,
                ],
                [
                    'customer_id' => $customer->id,
                    'email' => 'mobile.test@kainattravels.local',
                    'password' => Hash::make($password),
                    'gender' => 'male',
                    'mobile_verified_at' => now(),
                ]
            );

            $category = CardCategory::query()
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->first();
            if ($category) {
                CardAssign::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'customer_id' => $customer->id,
                    ],
                    [
                        'cnic' => $customer->cnic,
                        'phone' => $mobile,
                        'name' => $customer->name,
                        'card_category_id' => $category->id,
                        'starting_points' => (int) env('MOBILE_TEST_PASSENGER_POINTS', 3),
                        'expiry_date' => now()->addYear()->toDateString(),
                    ]
                );
            }

        });

        if ($this->command) {
            $this->command->info("Mobile test passenger ready: {$mobile}");
        }
    }
}
