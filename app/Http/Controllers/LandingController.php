<?php

namespace App\Http\Controllers;

use App\Models\LeadRequest;
use App\Models\User;
use App\Notifications\NewLeadAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('landing');
    }

    public function store(Request $request): RedirectResponse
    {
        $interestOptions = [
            'auto' => 'Автострахування',
            'property' => 'Страхування майна',
            'health' => 'Здоров’я та життя',
            'travel' => 'Страхування подорожей',
            'corporate' => 'Корпоративні програми',
            'individual' => 'Індивідуальне рішення',
            'other' => 'Інше',
        ];

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['individual', 'company'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'company_name' => [
                Rule::requiredIf(fn () => $request->input('type') === 'company'),
                'nullable',
                'string',
                'max:200',
            ],
            'phone' => ['required', 'regex:/^\+[0-9\s\-\(\)]{8,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'interest' => ['required', Rule::in(array_keys($interestOptions))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'type.required' => 'Оберіть тип заявника.',
            'type.in' => 'Некоректний тип заявника.',

            'first_name.required' => 'Вкажіть імʼя.',
            'first_name.max' => 'Імʼя не повинно перевищувати 100 символів.',

            'last_name.required' => 'Вкажіть прізвище.',
            'last_name.max' => 'Прізвище не повинно перевищувати 100 символів.',

            'middle_name.max' => 'По батькові не повинно перевищувати 100 символів.',

            'company_name.required' => 'Вкажіть назву компанії.',
            'company_name.max' => 'Назва компанії не повинна перевищувати 200 символів.',

            'phone.required' => 'Вкажіть номер телефону.',
            'phone.regex' => 'Вкажіть коректний номер телефону у міжнародному форматі.',

            'email.email' => 'Вкажіть коректний email.',
            'email.max' => 'Email не повинен перевищувати 150 символів.',

            'interest.required' => 'Оберіть, що саме вас цікавить.',
            'interest.in' => 'Оберіть варіант зі списку.',

            'comment.max' => 'Коментар не повинен перевищувати 1000 символів.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->to(route('landing.index') . '#form')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $assignedManagerId = User::query()
            ->where('role', 'manager')
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if (! $assignedManagerId) {
            return redirect()
                ->to(route('landing.index') . '#form')
                ->withErrors([
                    'form' => 'Наразі неможливо прийняти заявку. У системі немає активного менеджера.',
                ])
                ->withInput();
        }

        $normalizedPhone = preg_replace('/[^\d+]/', '', (string) $validated['phone']);

        $leadRequest = LeadRequest::create([
            'type' => $validated['type'],
            'first_name' => trim((string) $validated['first_name']),
            'last_name' => trim((string) $validated['last_name']),
            'middle_name' => filled($validated['middle_name'] ?? null)
                ? trim((string) $validated['middle_name'])
                : null,
            'company_name' => filled($validated['company_name'] ?? null)
                ? trim((string) $validated['company_name'])
                : null,
            'phone' => $normalizedPhone,
            'email' => filled($validated['email'] ?? null)
                ? trim((string) $validated['email'])
                : null,
            'interest' => $interestOptions[$validated['interest']],
            'source' => 'landing',
            'status' => 'new',
            'comment' => filled($validated['comment'] ?? null)
                ? trim((string) $validated['comment'])
                : null,
            'assigned_user_id' => $assignedManagerId,
            'converted_client_id' => null,
        ]);

        $assignedManager = User::query()->find($assignedManagerId);

        if ($assignedManager instanceof User) {
            $assignedManager->notify(new NewLeadAssignedNotification($leadRequest));
        }

        return redirect()
            ->to(route('landing.index') . '#form')
            ->with('lead_success', 'Заявку успішно відправлено. Менеджер звʼяжеться з вами протягом одного робочого дня.');
    }
}