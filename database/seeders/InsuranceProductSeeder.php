<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceCategory;
use App\Models\InsuranceProduct;

class InsuranceProductSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Категорії ----
        $categories = [
            ['code' => 'AUTO', 'name' => 'Транспорт', 'description' => 'Авто, мото, велосипеди, електротранспорт.'],
            ['code' => 'HOME', 'name' => 'Майно', 'description' => 'Житло, гаджети, зброя, будівельні ризики.'],
            ['code' => 'HEALTH', 'name' => 'Медицина', 'description' => 'Медичне покриття, стоматологія, нещасні випадки.'],
            ['code' => 'TRAVEL', 'name' => 'Подорожі', 'description' => 'Подорожі Україною та за кордон.'],
            ['code' => 'LIFE', 'name' => 'Життя і пенсія', 'description' => 'Похорон, пенсійне, непрацездатність.'],
            ['code' => 'PET', 'name' => 'Тварини', 'description' => 'Медичне страхування і відповідальність власників.'],
            ['code' => 'LIABILITY', 'name' => 'Відповідальність', 'description' => 'Професійна, адвокатська, договірна.'],
        ];

        foreach ($categories as $c) {
            InsuranceCategory::firstOrCreate(['code' => $c['code']], $c);
        }

        // ---- Отримуємо ID категорій ----
        $auto = InsuranceCategory::where('code', 'AUTO')->first();
        $home = InsuranceCategory::where('code', 'HOME')->first();
        $health = InsuranceCategory::where('code', 'HEALTH')->first();
        $travel = InsuranceCategory::where('code', 'TRAVEL')->first();
        $life = InsuranceCategory::where('code', 'LIFE')->first();
        $pet = InsuranceCategory::where('code', 'PET')->first();
        $liability = InsuranceCategory::where('code', 'LIABILITY')->first();

        // ---- Продукти ----
        $products = [

            // ТРАНСПОРТ
            ['category_id' => $auto->id, 'code' => 'AUTO_CIVIL', 'name' => 'Автоцивілка', 'description' => 'Обов’язкове страхування відповідальності власників авто.'],
            ['category_id' => $auto->id, 'code' => 'AUTO_CASCO_FULL', 'name' => 'КАСКО повне', 'description' => 'Повне покриття пошкоджень, викрадення, стихійні лиха.'],
            ['category_id' => $auto->id, 'code' => 'AUTO_CASCO_MINI', 'name' => 'КАСКО міні', 'description' => 'Часткове покриття основних ризиків (ДТП, пожежа, стихія).'],
            ['category_id' => $auto->id, 'code' => 'AUTO_GREEN_CARD', 'name' => 'Зелена картка', 'description' => 'Страхування відповідальності для виїзду за кордон.'],
            ['category_id' => $auto->id, 'code' => 'AUTO_MOTO', 'name' => 'Мото/мопед', 'description' => 'Страхування двоколісного транспорту.'],
            ['category_id' => $auto->id, 'code' => 'AUTO_ELECTRO', 'name' => 'Електросамокати/велосипеди', 'description' => 'Відповідальність за збитки третім особам.'],

            // МАЙНО
            ['category_id' => $home->id, 'code' => 'HOME_APARTMENT', 'name' => 'Квартира/будинок', 'description' => 'Покриття пожежі, затоплення, крадіжки.'],
            ['category_id' => $home->id, 'code' => 'HOME_RENT', 'name' => 'Оренда житла', 'description' => 'Захист майна орендодавця або орендаря.'],
            ['category_id' => $home->id, 'code' => 'HOME_WEAPON', 'name' => 'Страхування зброї', 'description' => 'Втрата, крадіжка, пошкодження зброї.'],
            ['category_id' => $home->id, 'code' => 'HOME_GADGET', 'name' => 'Мобільні пристрої', 'description' => 'Страхування смартфонів, ноутбуків, планшетів.'],
            ['category_id' => $home->id, 'code' => 'HOME_CONSTRUCTION', 'name' => 'Будівельно-монтажні роботи', 'description' => 'Ризики під час будівництва або ремонту.'],

            // МЕДИЦИНА
            ['category_id' => $health->id, 'code' => 'HEALTH_PRIVATE', 'name' => 'Приватне медичне страхування', 'description' => 'Покриття лікування у приватних клініках.'],
            ['category_id' => $health->id, 'code' => 'HEALTH_SUPPLEMENTARY', 'name' => 'Додаткове медичне', 'description' => 'Доповнення до державного медичного полісу.'],
            ['category_id' => $health->id, 'code' => 'HEALTH_DENTAL', 'name' => 'Стоматологічне страхування', 'description' => 'Покриття лікування зубів, імплантів.'],
            ['category_id' => $health->id, 'code' => 'HEALTH_ACCIDENT', 'name' => 'Нещасні випадки', 'description' => 'Виплати у разі травм, госпіталізації чи інвалідності.'],
            ['category_id' => $health->id, 'code' => 'HEALTH_TRAFFIC', 'name' => 'Медичне при ДТП', 'description' => 'Покриття витрат на лікування після ДТП.'],
            ['category_id' => $health->id, 'code' => 'HEALTH_REHAB', 'name' => 'Догляд за хворими', 'description' => 'Компенсація витрат на догляд і реабілітацію.'],

            // ПОДОРОЖІ
            ['category_id' => $travel->id, 'code' => 'TRAVEL_ABROAD', 'name' => 'Подорожі за кордон', 'description' => 'Медичне покриття, багаж, відміна рейсів.'],
            ['category_id' => $travel->id, 'code' => 'TRAVEL_DOMESTIC', 'name' => 'Подорожі Україною', 'description' => 'Страхування під час подорожей країною.'],

            // ЖИТТЯ І ПЕНСІЯ
            ['category_id' => $life->id, 'code' => 'LIFE_FUNERAL', 'name' => 'Похоронне страхування', 'description' => 'Допомога родині у випадку смерті застрахованого.'],
            ['category_id' => $life->id, 'code' => 'LIFE_PENSION', 'name' => 'Пенсійне страхування', 'description' => 'Формування власного пенсійного капіталу.'],
            ['category_id' => $life->id, 'code' => 'LIFE_DISABILITY', 'name' => 'Професійна непрацездатність', 'description' => 'Компенсація у разі втрати працездатності.'],
            ['category_id' => $life->id, 'code' => 'LIFE_CRITICAL', 'name' => 'Критичні захворювання', 'description' => 'Виплати при діагностуванні важких хвороб.'],

            // ТВАРИНИ
            ['category_id' => $pet->id, 'code' => 'PET_MEDICAL', 'name' => 'Медичне страхування тварин', 'description' => 'Компенсація витрат на лікування домашніх тварин.'],
            ['category_id' => $pet->id, 'code' => 'PET_LIABILITY', 'name' => 'Відповідальність власника тварини', 'description' => 'Збитки, спричинені тваринами третім особам.'],

            // ВІДПОВІДАЛЬНІСТЬ
            ['category_id' => $liability->id, 'code' => 'LIABILITY_PROFESSIONAL', 'name' => 'Професійна відповідальність', 'description' => 'Для лікарів, юристів, аудиторів тощо.'],
            ['category_id' => $liability->id, 'code' => 'LIABILITY_LAWYER', 'name' => 'Адвокатська відповідальність', 'description' => 'Страхування юридичних ризиків адвоката.'],
            ['category_id' => $liability->id, 'code' => 'LIABILITY_EMPLOYER', 'name' => 'Відповідальність роботодавця', 'description' => 'Компенсація у разі травм працівників.'],
            ['category_id' => $liability->id, 'code' => 'LIABILITY_CONTRACT', 'name' => 'Договірна відповідальність', 'description' => 'Захист при порушенні договірних зобов’язань.'],
        ];

        foreach ($products as $p) {
            InsuranceProduct::firstOrCreate(['code' => $p['code']], $p);
        }
    }
}
