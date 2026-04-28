<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PolicyStatus;
use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\Client;
use App\Models\InsuranceOffer;
use App\Models\LeadRequest;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DiplomaDemoSeeder extends Seeder
{
    protected array $operatorCodes = [
        39, 50, 63, 66, 67, 68, 73, 91, 92, 93, 94, 95, 96, 97, 98, 99,
    ];

    public function run(): void
    {
        Policy::$suppressAutoDraft = true;

        try {
            $admin = $this->ensureUser(
                email: 'demo.admin@insurance.local',
                name: 'Demo Admin',
                role: 'admin',
                password: 'admin123',
            );

            $supervisor = $this->ensureUser(
                email: 'demo.supervisor@insurance.local',
                name: 'Demo Supervisor',
                role: 'supervisor',
                password: 'supervisor123',
            );

            $manager = $this->ensureUser(
                email: 'demo.manager@insurance.local',
                name: 'Demo Manager',
                role: 'manager',
                password: 'manager123',
            );

            $secondaryManager = $this->ensureUser(
                email: 'demo.manager2@insurance.local',
                name: 'Demo Manager 2',
                role: 'manager',
                password: 'manager123',
            );

            $offer = InsuranceOffer::query()->first();

            if (! $offer) {
                $this->command?->warn('Diploma demo skipped: no insurance offers found. Run base seeders first.');

                return;
            }

            $individualClient = $this->createOrUpdateClient([
                'type' => 'individual',
                'status' => 'active',
                'first_name' => 'Олена',
                'last_name' => 'Демченко',
                'middle_name' => 'Ігорівна',
                'company_name' => null,
                'primary_email' => 'demo.client.individual@example.com',
                'primary_phone' => '+380671112233',
                'document_number' => 'AA123456',
                'tax_id' => '1234567890',
                'date_of_birth' => Carbon::create(1992, 5, 14),
                'preferred_contact_method' => 'phone',
                'city' => 'Київ',
                'address_line' => 'вул. Демонстраційна, 10',
                'source' => 'landing',
                'assigned_user_id' => $manager->id,
                'notes' => 'Демо-клієнт для показу повного циклу: заявка, поліс, оплата, страховий випадок.',
            ]);

            $companyClient = $this->createOrUpdateClient([
                'type' => 'company',
                'status' => 'active',
                'first_name' => 'Андрій',
                'last_name' => 'Коваленко',
                'middle_name' => 'Петрович',
                'company_name' => 'ТОВ Демо Логістика',
                'primary_email' => 'demo.company@example.com',
                'primary_phone' => '+380501112244',
                'document_number' => 'BB654321',
                'tax_id' => '40123456',
                'date_of_birth' => Carbon::create(1987, 3, 21),
                'preferred_contact_method' => 'email',
                'city' => 'Львів',
                'address_line' => 'вул. Бізнесова, 5',
                'source' => 'recommendation',
                'assigned_user_id' => $manager->id,
                'notes' => 'Демо-компанія для показу корпоративного клієнта.',
            ]);

            $this->createLeadRequests($manager, $secondaryManager, $individualClient);

            $draftPolicy = $this->createPolicyWithPayment(
                client: $individualClient,
                offer: $offer,
                manager: $manager,
                number: 'DEMO-DRAFT-001',
                effective: Carbon::today()->addDays(5),
                expiration: Carbon::today()->addMonths((int) $offer->duration_months),
                paymentMethod: PaymentMethod::NoMethod,
                paymentStatus: PaymentStatus::Draft,
                paidAt: null,
                initiatedAt: null,
                dueDate: Carbon::today()->addDays(7),
                policyStatus: PolicyStatus::Draft,
                notes: 'Демо: чернетка поліса без вибраного способу оплати.',
            );

            $futurePaidPolicy = $this->createPolicyWithPayment(
                client: $companyClient,
                offer: $offer,
                manager: $manager,
                number: 'DEMO-FUTURE-PAID-001',
                effective: Carbon::today()->addDay(),
                expiration: Carbon::today()->addMonths((int) $offer->duration_months),
                paymentMethod: PaymentMethod::Card,
                paymentStatus: PaymentStatus::Paid,
                paidAt: now()->subHour(),
                initiatedAt: null,
                dueDate: Carbon::today()->addDays(7),
                policyStatus: PolicyStatus::Draft,
                notes: 'Демо: поліс уже оплачений, але ще не активний, бо дата початку завтра.',
            );

            $activePolicy = $this->createPolicyWithPayment(
                client: $individualClient,
                offer: $offer,
                manager: $manager,
                number: 'DEMO-ACTIVE-001',
                effective: Carbon::today()->subDays(3),
                expiration: Carbon::today()->addMonths((int) $offer->duration_months),
                paymentMethod: PaymentMethod::Cash,
                paymentStatus: PaymentStatus::Paid,
                paidAt: now()->subDays(3),
                initiatedAt: null,
                dueDate: Carbon::today()->subDay(),
                policyStatus: PolicyStatus::Active,
                notes: 'Демо: активний поліс для створення страхового випадку.',
            );

            $completedPolicy = $this->createPolicyWithPayment(
                client: $companyClient,
                offer: $offer,
                manager: $manager,
                number: 'DEMO-COMPLETED-001',
                effective: Carbon::today()->subMonths(14),
                expiration: Carbon::today()->subDays(7),
                paymentMethod: PaymentMethod::Transfer,
                paymentStatus: PaymentStatus::Paid,
                paidAt: now()->subMonths(13),
                initiatedAt: now()->subMonths(13)->subDay(),
                dueDate: Carbon::today()->subMonths(13),
                policyStatus: PolicyStatus::Completed,
                notes: 'Демо: завершений поліс, по якому ще можна редагувати страховий випадок.',
            );

            $overduePolicy = $this->createPolicyWithPayment(
                client: $individualClient,
                offer: $offer,
                manager: $manager,
                number: 'DEMO-OVERDUE-001',
                effective: Carbon::today()->subDays(12),
                expiration: Carbon::today()->addMonths((int) $offer->duration_months),
                paymentMethod: PaymentMethod::Transfer,
                paymentStatus: PaymentStatus::Overdue,
                paidAt: null,
                initiatedAt: now()->subDays(12),
                dueDate: Carbon::today()->subDays(4),
                policyStatus: PolicyStatus::Canceled,
                notes: 'Демо: поліс зі простроченою оплатою.',
            );

            $scheduledPolicy = $this->createPolicyWithPayment(
                client: $companyClient,
                offer: $offer,
                manager: $manager,
                number: 'DEMO-SCHEDULED-001',
                effective: Carbon::today(),
                expiration: Carbon::today()->addMonths((int) $offer->duration_months),
                paymentMethod: PaymentMethod::Transfer,
                paymentStatus: PaymentStatus::Scheduled,
                paidAt: null,
                initiatedAt: now(),
                dueDate: Carbon::today(),
                policyStatus: PolicyStatus::Draft,
                notes: 'Демо: запланований банківський переказ для daily sweep.',
            );

            $this->createClaims($activePolicy, $completedPolicy, $manager);

            $this->createActivityLogExamples($admin, $manager, $individualClient, $draftPolicy);

            $draftPolicy->refresh()->recomputeStatus();
            $futurePaidPolicy->refresh()->recomputeStatus();
            $activePolicy->refresh()->recomputeStatus();
            $completedPolicy->refresh()->recomputeStatus();
            $overduePolicy->refresh()->recomputeStatus();
            $scheduledPolicy->refresh()->recomputeStatus();

            $this->command?->info('Diploma demo data created:');
            $this->command?->info(' - demo.admin@insurance.local / admin123');
            $this->command?->info(' - demo.supervisor@insurance.local / supervisor123');
            $this->command?->info(' - demo.manager@insurance.local / manager123');
            $this->command?->info(' - demo.manager2@insurance.local / manager123');
        } finally {
            Policy::$suppressAutoDraft = false;
        }
    }

    protected function ensureUser(string $email, string $name, string $role, string $password): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'is_active' => true,
            ]
        );
    }

    protected function createOrUpdateClient(array $data): Client
    {
        return Client::query()->withTrashed()->updateOrCreate(
            ['primary_email' => $data['primary_email']],
            $data
        );
    }

    protected function phone(int $index): string
    {
        $code = $this->operatorCodes[$index % count($this->operatorCodes)];

        return '+380' . $code . str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT);
    }

    protected function createLeadRequests(User $manager, User $secondaryManager, Client $convertedClient): void
    {
        LeadRequest::query()->updateOrCreate(
            ['email' => 'demo.lead.new@example.com'],
            [
                'type' => 'individual',
                'first_name' => 'Марина',
                'last_name' => 'Нова',
                'middle_name' => 'Олегівна',
                'company_name' => null,
                'phone' => $this->phone(1),
                'interest' => 'Автострахування',
                'source' => 'landing',
                'status' => 'new',
                'comment' => 'Демо: нова заявка з лендінгу, яку можна взяти в роботу.',
                'assigned_user_id' => $manager->id,
                'converted_client_id' => null,
            ]
        );

        LeadRequest::query()->updateOrCreate(
            ['email' => 'demo.lead.progress@example.com'],
            [
                'type' => 'company',
                'first_name' => 'Ірина',
                'last_name' => 'Менеджерська',
                'middle_name' => 'Сергіївна',
                'company_name' => 'ТОВ Очікує Пропозицію',
                'phone' => $this->phone(2),
                'interest' => 'Корпоративні програми',
                'source' => 'office',
                'status' => 'in_progress',
                'comment' => 'Демо: менеджер уже зв’язався з клієнтом, очікуються документи.',
                'assigned_user_id' => $manager->id,
                'converted_client_id' => null,
            ]
        );

        LeadRequest::query()->updateOrCreate(
            ['email' => 'demo.lead.rejected@example.com'],
            [
                'type' => 'individual',
                'first_name' => 'Петро',
                'last_name' => 'Відмовний',
                'middle_name' => 'Іванович',
                'company_name' => null,
                'phone' => $this->phone(3),
                'interest' => 'Страхування подорожей',
                'source' => 'recommendation',
                'status' => 'rejected',
                'comment' => 'Демо: клієнт відмовився через вартість.',
                'assigned_user_id' => $secondaryManager->id,
                'converted_client_id' => null,
            ]
        );

        LeadRequest::query()->updateOrCreate(
            ['email' => 'demo.client.individual@example.com'],
            [
                'type' => $convertedClient->type,
                'first_name' => $convertedClient->first_name,
                'last_name' => $convertedClient->last_name,
                'middle_name' => $convertedClient->middle_name,
                'company_name' => $convertedClient->company_name,
                'phone' => $convertedClient->primary_phone,
                'interest' => 'Здоров’я та життя',
                'source' => 'landing',
                'status' => 'converted',
                'comment' => 'Демо: заявку вже конвертовано у клієнта.',
                'assigned_user_id' => $convertedClient->assigned_user_id,
                'converted_client_id' => $convertedClient->id,
            ]
        );
    }

    protected function createPolicyWithPayment(
        Client $client,
        InsuranceOffer $offer,
        User $manager,
        string $number,
        Carbon $effective,
        Carbon $expiration,
        PaymentMethod $paymentMethod,
        PaymentStatus $paymentStatus,
        mixed $paidAt,
        mixed $initiatedAt,
        Carbon $dueDate,
        PolicyStatus $policyStatus,
        string $notes,
    ): Policy {
        $premiumAmount = $this->resolvePremiumAmount($offer);
        $commissionRate = $this->resolveCommissionRate($offer);

        $policy = Policy::query()->updateOrCreate(
            ['policy_number' => $number],
            [
                'client_id' => $client->id,
                'insurance_offer_id' => $offer->id,
                'agent_id' => $manager->id,
                'status' => $policyStatus,
                'effective_date' => $effective->toDateString(),
                'expiration_date' => $expiration->toDateString(),
                'premium_amount' => $premiumAmount,
                'coverage_amount' => $offer->coverage_amount,
                'payment_frequency' => 'once',
                'commission_rate' => $commissionRate,
                'payment_due_at' => $effective->copy()->addDays(7)->toDateString(),
                'notes' => $notes,
            ]
        );

        PolicyPayment::withoutEvents(function () use ($policy): void {
            $policy->payments()->delete();
        });

        PolicyPayment::query()->create([
            'policy_id' => $policy->id,
            'due_date' => $dueDate->toDateString(),
            'initiated_at' => $initiatedAt,
            'paid_at' => $paidAt,
            'amount' => $premiumAmount,
            'status' => $paymentStatus,
            'method' => $paymentMethod,
            'transaction_reference' => null,
            'notes' => $notes,
        ]);

        $policy = $policy->refresh();

        $policy->forceFill([
            'status' => $policyStatus,
        ])->saveQuietly();

        return $policy->refresh();
    }

    protected function resolveCommissionRate(InsuranceOffer $offer): float
    {
        return match (mb_strtolower(trim((string) $offer->offer_name))) {
            'базовий', 'базовый', 'basic' => 3.00,
            'комфорт+', 'комфорт плюс', 'comfort+' => 1.50,
            'преміум', 'премиум', 'premium' => 0.00,
            default => 2.00,
        };
    }

    protected function resolvePremiumAmount(InsuranceOffer $offer): float
    {
        $rate = $this->resolveCommissionRate($offer);
        $base = (float) $offer->price * (int) $offer->duration_months;

        return round($base + ($base * ($rate / 100)), 2);
    }

    protected function createClaims(Policy $activePolicy, Policy $completedPolicy, User $manager): void
    {
        $claimInReview = Claim::query()->updateOrCreate(
            ['claim_number' => 'DEMO-CLAIM-REVIEW-001'],
            [
                'policy_id' => $activePolicy->id,
                'reported_by_id' => $manager->id,
                'status' => 'на розгляді',
                'reported_at' => now()->subDay(),
                'loss_occurred_at' => Carbon::today()->subDay()->toDateString(),
                'loss_location' => 'м. Київ, вул. Демонстраційна, 10',
                'cause' => 'ДТП',
                'amount_claimed' => 15000.00,
                'amount_reserve' => 12000.00,
                'amount_paid' => 0.00,
                'description' => 'Демо: новий страховий випадок на розгляді.',
                'metadata' => null,
            ]
        );

        $claimInReview->notes()->delete();
        $claimInReview->notes()->create([
            'user_id' => $manager->id,
            'visibility' => 'внутрішня',
            'note' => 'Демо: потрібно перевірити документи та фото з місця події.',
        ]);

        $claimApproved = Claim::query()->updateOrCreate(
            ['claim_number' => 'DEMO-CLAIM-APPROVED-001'],
            [
                'policy_id' => $activePolicy->id,
                'reported_by_id' => $manager->id,
                'status' => 'схвалено',
                'reported_at' => now()->subDays(4),
                'loss_occurred_at' => Carbon::today()->subDays(5)->toDateString(),
                'loss_location' => 'м. Київ, просп. Перемоги, 50',
                'cause' => 'Пошкодження майна',
                'amount_claimed' => 20000.00,
                'amount_reserve' => 18000.00,
                'amount_paid' => 0.00,
                'description' => 'Демо: заяву схвалено, очікується виплата.',
                'metadata' => null,
            ]
        );

        $claimApproved->notes()->delete();
        $claimApproved->notes()->create([
            'user_id' => $manager->id,
            'visibility' => 'внутрішня',
            'note' => 'Демо: погоджено резерв, передано на виплату.',
        ]);

        $claimPaidAfterPolicyEnd = Claim::query()->updateOrCreate(
            ['claim_number' => 'DEMO-CLAIM-PAID-COMPLETED-POLICY-001'],
            [
                'policy_id' => $completedPolicy->id,
                'reported_by_id' => $manager->id,
                'status' => 'виплачено',
                'reported_at' => now()->subDays(20),
                'loss_occurred_at' => $completedPolicy->expiration_date->copy()->subDays(10)->toDateString(),
                'loss_location' => 'м. Львів, вул. Бізнесова, 5',
                'cause' => 'Пошкодження вантажу',
                'amount_claimed' => 30000.00,
                'amount_reserve' => 25000.00,
                'amount_paid' => 22000.00,
                'description' => 'Демо: виплата проведена вже після завершення строку дії поліса.',
                'metadata' => null,
            ]
        );

        $claimPaidAfterPolicyEnd->notes()->delete();
        $claimPaidAfterPolicyEnd->notes()->create([
            'user_id' => $manager->id,
            'visibility' => 'внутрішня',
            'note' => 'Демо: поліс завершено, але страховий випадок продовжував оброблятися до виплати.',
        ]);
    }

    protected function createActivityLogExamples(User $admin, User $manager, Client $client, Policy $policy): void
    {
        ActivityLog::query()->create([
            'actor_id' => $manager->id,
            'actor_name' => $manager->name,
            'actor_role' => $manager->role,
            'action' => 'updated',
            'subject_type' => Client::class,
            'subject_id' => $client->id,
            'subject_type_label' => 'Клієнт',
            'subject_label' => $client->display_label,
            'description' => 'Демо: очищено нотатку клієнта',
            'before' => [
                'notes' => 'Було службове примітка.',
            ],
            'after' => [
                'notes' => null,
            ],
        ]);

        ActivityLog::query()->create([
            'actor_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_role' => $admin->role,
            'action' => 'updated',
            'subject_type' => Policy::class,
            'subject_id' => $policy->id,
            'subject_type_label' => 'Поліс',
            'subject_label' => $policy->policy_number,
            'description' => 'Демо: змінено дату початку поліса',
            'before' => [
                'effective_date' => Carbon::today()->addDays(4)->toDateString(),
            ],
            'after' => [
                'effective_date' => Carbon::today()->addDays(5)->toDateString(),
            ],
        ]);
    }
}