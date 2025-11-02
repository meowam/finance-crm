<?php

namespace App\Filament\Resources\InsuranceOffers\Concerns;

trait MutatesOfferData
{
    protected function normalizeOfferData(array $data): array
    {
        $price = isset($data['price']) ? (float) $data['price'] : 0.0;

        $map = [
            'Базовий'  => ['coverage' => 35, 'franchise' => 0.15],
            'Комфорт+' => ['coverage' => 60, 'franchise' => 0.07],
            'Преміум'  => ['coverage' => 90, 'franchise' => 0.00],
        ];

        if (isset($data['offer_name'], $map[$data['offer_name']])) {
            $t = $map[$data['offer_name']];
            $data['coverage_amount'] = number_format($price * $t['coverage'], 2, '.', '');
            $data['franchise']       = number_format($price * $t['franchise'], 2, '.', '');
        }

        if (!empty($data['benefits_json'])) {
            $data['benefits'] = $data['benefits_json'];
        }
        unset($data['benefits_json']);

        if (!empty($data['conditions_arr']) && is_array($data['conditions_arr'])) {
            $data['conditions'] = $data['conditions_arr'];
        }
        unset($data['conditions_arr']);

        unset(
            $data['sw_base'], $data['sw_fast_online'], $data['sw_e_policy'],
            $data['sw_road'], $data['sw_vip'], $data['sw_cashback'], $data['sw_no_franchise']
        );

        $data['price'] = number_format($price, 2, '.', '');

        return $data;
    }
}
