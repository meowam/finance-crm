<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\ClaimNote;
use App\Models\Client;
use App\Models\InsuranceOffer;
use App\Models\LeadRequest;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use App\Services\Policies\PolicyDailyService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DefenseHappyPathSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            InsuranceCategorySeeder::class,
            InsuranceCompanySeeder::class,
            InsuranceProductSeeder::class,
            InsuranceOfferSeeder::class,
        ]);

        $this->resetDomainTables();

        $admin = User::query()->where('email', 'admin@insurance.local')->firstOrFail();
        $supervisor = User::query()->where('email', 'supervisor1@insurance.local')->firstOrFail();
        $manager = User::query()->where('email', 'manager1@insurance.local')->firstOrFail();

        $basicOffer = $this->resolveOffer('Базовий');
        $comfortOffer = $this->resolveOffer('Комфорт+');

        $today = Carbon::today();

        $newLandingLead = LeadRequest::create([
            'type' => 'individual',
            'first_name' => 'Марія',
            'last_name' => 'Петренко',
            'middle_name' => 'Олександрівна',
            'company_name' => null,
            'phone' => '+380671234501',
            'email' => 'landing.lead@demo.local',
            'interest' => 'Автострахування',
            'source' => 'landing',
            'status' => 'new',
            'comment' => 'Нова заявка з лендінгу для демонстрації.',
            'assigned_user_id' => $manager->id,
            'converted_client_id' => null,
        ]);

        $inProgressLead = LeadRequest::create([
            'type' => 'company',
            'first_name' => 'Ірина',
            'last_name' => 'Коваль',
            'middle_name' => null,
            'company_name' => 'ТОВ Орбіта Груп',
            'phone' => '+380671234502',
            'email' => 'orbit.demo@demo.local',
            'interest' => 'Медичне страхування для компанії',
            'source' => 'online',
            'status' => 'in_progress',
            'comment' => 'Менеджер уже зв’язався, очікуємо уточнення по кількості співробітників.',
            'assigned_user_id' => $manager->id,
            'converted_client_id' => null,
        ]);

        $convertedClient = Client::create([
            'type' => 'individual',
            'status' => 'active',
            'first_name' => 'Олена',
            'last_name' => 'Іваненко',
            'middle_name' => 'Сергіївна',
            'company_name' => null,
            'primary_email' => 'olena.demo@demo.local',
            'primary_phone' => '+380671234503',
            'document_number' => 'AA100001',
            'tax_id' => '1000000001',
            'date_of_birth' => '1993-05-14',
            'preferred_contact_method' => 'phone',
            'city' => 'Київ',
            'address_line' => 'вул. Саксаганського, 15',
            'source' => 'landing',
            'assigned_user_id' => $manager->id,
            'notes' => 'Клієнт створений із заявки для happy path демонстрації.',
        ]);

        $convertedLead = LeadRequest::create([
            'type' => 'individual',
            'first_name' => 'Олена',
            'last_name' => 'Іваненко',
            'middle_name' => 'Сергіївна',
            'company_name' => null,
            'phone' => '+380671234503',
            'email' => 'olena.demo@demo.local',
            'interest' => 'КАСКО',
            'source' => 'landing',
            'status' => 'converted',
            'comment' => 'Заявка успішно конвертована у клієнта.',
            'assigned_user_id' => $manager->id,
            'converted_client_id' => $convertedClient->id,
        ]);

        $overdueClient = Client::create([
            'type' => 'company',
            'status' => 'active',
            'first_name' => 'Андрій',
            'last_name' => 'Мельник',
            'middle_name' => null,
            'company_name' => 'ТОВ Демо Логістик',
            'primary_email' => 'logistic.demo@demo.local',
            'primary_phone' => '+380671234504',
            'document_number' => 'AA100002',
            'tax_id' => '2000000002',
            'date_of_birth' => '1990-01-01',
            'preferred_contact_method' => 'email',
            'city' => 'Львів',
            'address_line' => 'вул. Наукова, 7',
            'source' => 'recommendation',
            'assigned_user_id' => $manager->id,
            'notes' => 'Клієнт для демонстрації простроченої оплати та сповіщень.',
        ]);

        $activePolicy = $this->createPolicyWithPayment(
            client: $convertedClient,
            offer: $basicOffer,
            manager: $manager,
            effectiveDate: $today->copy()->subDays(20),
            expirationDate: $today->copy()->addDays(5),
            paymentDueAt: $today->copy()->subDays(13),
            paymentMethod: 'cash',
            paymentStatus: 'paid',
            paymentDueDate: $today->copy()->subDays(13),
            paidAt: $today->copy()->subDays(18),
            commissionRate: 3.00,
            notes: 'Активний поліс для демонстрації картки клієнта, дашборда та страхового випадку.'
        );

        $claim = Claim::create([
            'claim_number' => 'CLM-DEMO-0001',
            'policy_id' => $activePolicy->id,
            'reported_by_id' => $manager->id,
            'status' => 'на розгляді',
            'reported_at' => $today->copy()->subDays(2)->setTime(11, 30),
            'loss_occurred_at' => $today->copy()->subDays(3),
            'loss_location' => 'Київ, вул. Велика Васильківська, 21',
            'cause' => 'ДТП',
            'amount_claimed' => 25000.00,
            'amount_reserve' => 18000.00,
            'amount_paid' => 0.00,
            'description' => 'Демонстраційний страховий випадок для захисту диплома.',
            'metadata' => [],
        ]);

        ClaimNote::create([
            'claim_id' => $claim->id,
            'user_id' => $manager->id,
            'visibility' => 'внутрішня',
            'note' => 'Потрібно перевірити фото пошкоджень та погодити резерв із supervisor.',
        ]);

        $overduePolicy = $this->createPolicyWithPayment(
            client: $overdueClient,
            offer: $comfortOffer,
            manager: $manager,
            effectiveDate: $today->copy()->subDays(10),
            expirationDate: $today->copy()->addDays(40),
            paymentDueAt: $today->copy()->subDay(),
            paymentMethod: 'transfer',
            paymentStatus: 'scheduled',
            paymentDueDate: $today->copy()->subDay(),
            paidAt: null,
            commissionRate: 1.50,
            notes: 'Поліс для демонстрації прострочення, автоматичного оновлення статусу та сповіщень.'
        );

        app(PolicyDailyService::class)->run(0.0);

        $this->command?->info('Defense happy path seeded successfully.');
        $this->command?->info('Demo users:');
        $this->command?->info(" - Admin: {$admin->email} / admin123");
        $this->command?->info(" - Supervisor: {$supervisor->email} / supervisor123");
        $this->command?->info(" - Manager: {$manager->email} / manager123");
        $this->command?->info("Lead from landing: #{$newLandingLead->id}");
        $this->command?->info("Lead in progress: #{$inProgressLead->id}");
        $this->command?->info("Converted lead: #{$convertedLead->id}");
        $this->command?->info("Active policy: {$activePolicy->policy_number}");
        $this->command?->info("Overdue policy: {$overduePolicy->policy_number}");
        $this->command?->info("Claim: {$claim->claim_number}");
    }

    protected function resetDomainTables(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('notifications')->delete();
        DB::table('activity_logs')->delete();
        DB::table('claim_notes')->delete();
        DB::table('claims')->delete();
        DB::table('lead_requests')->delete();
        DB::table('policy_payments')->delete();
        DB::table('policies')->delete();
        DB::table('client_contacts')->delete();
        DB::table('clients')->delete();

        Schema::enableForeignKeyConstraints();
    }

    protected function resolveOffer(string $offerName): InsuranceOffer
    {
        return InsuranceOffer::query()
            ->where('offer_name', $offerName)
            ->orderBy('id')
            ->firstOrFail();
    }

    protected function createPolicyWithPayment(
        Client $client,
        InsuranceOffer $offer,
        User $manager,
        Carbon $effectiveDate,
        Carbon $expirationDate,
        Carbon $paymentDueAt,
        string $paymentMethod,
        string $paymentStatus,
        Carbon $paymentDueDate,
        ?Carbon $paidAt,
        float $commissionRate,
        ?string $notes = null,
    ): Policy {
        $base = (float) $offer->price * (int) $offer->duration_months;
        $premium = round($base + ($base * ($commissionRate / 100)), 2);

        Policy::$suppressAutoDraft = true;

        try {
            $policy = Policy::create([
                'client_id' => $client->id,
                'insurance_offer_id' => $offer->id,
                'agent_id' => $manager->id,
                'status' => 'draft',
                'effective_date' => $effectiveDate->toDateString(),
                'expiration_date' => $expirationDate->toDateString(),
                'premium_amount' => $premium,
                'coverage_amount' => $offer->coverage_amount,
                'payment_frequency' => 'once',
                'commission_rate' => $commissionRate,
                'notes' => $notes,
                'payment_due_at' => $paymentDueAt->toDateString(),
            ]);
        } finally {
            Policy::$suppressAutoDraft = false;
        }

        PolicyPayment::create([
            'policy_id' => $policy->id,
            'due_date' => $paymentDueDate->toDateString(),
            'initiated_at' => $paymentMethod === 'transfer'
                ? $paymentDueDate->copy()->subDays(2)->setTime(10, 0)
                : null,
            'paid_at' => $paidAt,
            'amount' => $premium,
            'status' => $paymentStatus,
            'method' => $paymentMethod,
            'transaction_reference' => null,
            'notes' => null,
        ]);

        $policy->refresh()->recomputeStatus();

        return $policy->fresh();
    }
}