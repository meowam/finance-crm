<?php

return [

    'required'        => 'Поле :attribute є обовʼязковим.',
    'email'           => 'Поле :attribute повинно містити коректну адресу.',
    'regex'           => 'Поле :attribute має некоректний формат.',
    'min'             => ['string' => 'Поле :attribute повинно містити щонайменше :min символи(ів).'],
    'max'             => ['string' => 'Поле :attribute повинно містити не більше :max символів.'],
    'in'              => 'Обране значення для :attribute некоректне.',
    'exists'          => 'Обраного значення :attribute не знайдено.',
    'date'            => 'Поле :attribute повинно бути коректною датою.',
    'before_or_equal' => 'Поле :attribute повинно бути датою не пізніше :date.',
    'after_or_equal'  => 'Поле :attribute повинно бути датою не раніше :date.',

    'custom' => [

    ],

    'attributes' => [
        'type'                     => 'Тип',
        'status'                   => 'Статус',
        'first_name'               => "Ім'я",
        'last_name'                => 'Прізвище',
        'middle_name'              => 'По батькові',
        'company_name'             => 'Назва компанії',
        'primary_email'            => 'Основна ел. пошта',
        'primary_phone'            => 'Основний телефон',
        'document_number'          => 'Номер документа',
        'tax_id'                   => 'ІПН / ЄДРПОУ',
        'date_of_birth'            => 'Дата народження',
        'preferred_contact_method' => 'Бажаний спосіб звʼязку',
        'city'                     => 'Місто',
        'address_line'             => 'Адреса',
        'source'                   => 'Канал звернення',
        'assigned_user_id'         => 'Менеджер',
        'notes'                    => 'Нотатки',
    ],
];
