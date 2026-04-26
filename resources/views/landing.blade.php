<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Захист — Страховий брокер</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>
<body>

<header class="header" id="top">
    <a href="#top" class="logo">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L3 7v7c0 5 4 9 9 10 5-1 9-5 9-10V7L12 2z"/>
            </svg>
        </div>
        <span class="logo-text">Захист<span>Груп</span></span>
    </a>

    <nav class="nav">
        <a href="#services">Послуги</a>
        <a href="#benefits">Переваги</a>
        <a href="#how">Як ми працюємо</a>
        <a href="#form">Залишити заявку</a>
    </nav>

    <a href="#form" class="header-cta desktop-only">Залишити заявку</a>

    <button class="burger" id="burgerBtn" aria-label="Меню" aria-expanded="false" aria-controls="mobileNav" type="button">
        <span></span><span></span><span></span>
    </button>
</header>

<nav class="mobile-nav" id="mobileNav">
    <a href="#services" data-mobile-link>Послуги</a>
    <a href="#benefits" data-mobile-link>Переваги</a>
    <a href="#how" data-mobile-link>Як ми працюємо</a>
    <a href="#form" data-mobile-link class="header-cta">Залишити заявку</a>
</nav>

<section class="hero" id="hero">
    <div class="hero-grid">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                Страховий брокер
            </div>
            <h1>Надійний захист для вас та вашого бізнесу</h1>
            <p class="hero-desc">
                Підбираємо страхові рішення під конкретну ситуацію — без зайвих ускладнень,
                зрозумілою мовою та з повним супроводом на всіх етапах.
            </p>
            <div class="hero-actions">
                <a href="#form" class="btn-primary">✉ Залишити заявку</a>
                <a href="#services" class="btn-secondary">Переглянути послуги →</a>
            </div>
        </div>

        <div class="hero-cards">
            <div class="hero-card">
                <div class="hero-card-icon">🛡️</div>
                <div>
                    <div class="hero-card-title">Індивідуальний підбір</div>
                    <div class="hero-card-text">
                        Аналізуємо вашу ситуацію і пропонуємо найкращий варіант серед перевірених страховиків.
                    </div>
                </div>
            </div>

            <div class="hero-card">
                <div class="hero-card-icon">⚡</div>
                <div>
                    <div class="hero-card-title">Швидко та зручно</div>
                    <div class="hero-card-text">
                        Консультація і підбір полісу без черг, бюрократії і зайвих поїздок до офісу.
                    </div>
                </div>
            </div>

            <div class="hero-card">
                <div class="hero-card-icon">🤝</div>
                <div>
                    <div class="hero-card-title">Для фізосіб і бізнесу</div>
                    <div class="hero-card-text">
                        Працюємо як із приватними клієнтами, так і з компаніями будь-якого масштабу.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section services" id="services">
    <div class="section-inner">
        <div class="services-header">
            <div>
                <div class="section-label reveal">Послуги</div>
                <h2 class="section-title reveal reveal-delay-1">Що ми страхуємо</h2>
            </div>
            <p class="section-desc reveal reveal-delay-2">
                Працюємо з усіма основними напрямками страхування — для приватних осіб і для бізнесу.
            </p>
        </div>

        <div class="services-grid">
            <div class="service-card reveal">
                <span class="service-icon">🚗</span>
                <div class="service-title">Автострахування</div>
                <p class="service-text">
                    ОСЦПВ, КАСКО, «Зелена карта» — підберемо оптимальний поліс під ваш автомобіль і умови використання.
                </p>
            </div>

            <div class="service-card reveal reveal-delay-1">
                <span class="service-icon">🏠</span>
                <div class="service-title">Страхування майна</div>
                <p class="service-text">
                    Захист квартири, будинку або комерційної нерухомості від пожежі, затоплення, крадіжки та інших ризиків.
                </p>
            </div>

            <div class="service-card reveal reveal-delay-2">
                <span class="service-icon">❤️</span>
                <div class="service-title">Здоров'я та життя</div>
                <p class="service-text">
                    Медичне страхування та страхування від нещасних випадків — для вас, родини або колективу компанії.
                </p>
            </div>

            <div class="service-card reveal">
                <span class="service-icon">✈️</span>
                <div class="service-title">Страхування подорожей</div>
                <p class="service-text">
                    Поліс туриста на будь-яку країну: медичні витрати, скасування поїздки, втрата багажу та інше.
                </p>
            </div>

            <div class="service-card reveal reveal-delay-1">
                <span class="service-icon">🏢</span>
                <div class="service-title">Корпоративні програми</div>
                <p class="service-text">
                    Комплексні рішення для бізнесу: страхування відповідальності, майна, вантажів і персоналу.
                </p>
            </div>

            <div class="service-card reveal reveal-delay-2">
                <span class="service-icon">⚙️</span>
                <div class="service-title">Індивідуальні рішення</div>
                <p class="service-text">
                    Нестандартний запит? Розберемось. Підберемо рішення навіть у нетипових ситуаціях.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section benefits" id="benefits">
    <div class="section-inner">
        <div style="max-width: 560px;">
            <div class="section-label reveal">Переваги</div>
            <h2 class="section-title reveal reveal-delay-1">Чому клієнти обирають нас</h2>
            <p class="section-desc reveal reveal-delay-2">
                Наша мета — щоб ви розуміли, що саме купуєте, і відчували підтримку при настанні страхового випадку.
            </p>
        </div>

        <div class="benefits-grid">
            <div class="benefit-card reveal">
                <div class="benefit-num">01</div>
                <div class="benefit-content">
                    <div class="benefit-title">Незалежна позиція</div>
                    <p class="benefit-text">
                        Ми не прив'язані до одного страховика — обираємо кращий варіант серед десятків перевірених компаній ринку.
                    </p>
                </div>
            </div>

            <div class="benefit-card reveal reveal-delay-1">
                <div class="benefit-num">02</div>
                <div class="benefit-content">
                    <div class="benefit-title">Зрозуміла мова</div>
                    <p class="benefit-text">
                        Пояснюємо умови без стандартної страхової «каші» — чітко, що покривається, а що ні, і в яких випадках.
                    </p>
                </div>
            </div>

            <div class="benefit-card reveal reveal-delay-2">
                <div class="benefit-num">03</div>
                <div class="benefit-content">
                    <div class="benefit-title">Підтримка при страховому випадку</div>
                    <p class="benefit-text">
                        Не кидаємо після продажу полісу. Допомагаємо правильно оформити документи та отримати виплату.
                    </p>
                </div>
            </div>

            <div class="benefit-card reveal reveal-delay-3">
                <div class="benefit-num">04</div>
                <div class="benefit-content">
                    <div class="benefit-title">Для всіх типів клієнтів</div>
                    <p class="benefit-text">
                        Фізичні особи, ФОП, невеликі бізнеси та великі корпорації — у кожного своє рішення і свій менеджер.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section howwework" id="how">
    <div class="section-inner">
        <div class="steps-header">
            <div class="section-label reveal">Як ми працюємо</div>
            <h2 class="section-title reveal reveal-delay-1">Чотири кроки до вашого захисту</h2>
            <p class="section-desc reveal reveal-delay-2">
                Простий і прозорий шлях — від першого запиту до оформленого полісу.
            </p>
        </div>

        <div class="steps-row">
            <div class="step reveal">
                <div class="step-circle">📝</div>
                <div class="step-title">Залишаєте заявку</div>
                <p class="step-text">Заповнюєте коротку форму або телефонуєте нам. Жодних складних анкет.</p>
            </div>

            <div class="step reveal reveal-delay-1">
                <div class="step-circle">📞</div>
                <div class="step-title">Менеджер зв'язується</div>
                <p class="step-text">Ваш особистий менеджер зв'яжеться протягом одного робочого дня.</p>
            </div>

            <div class="step reveal reveal-delay-2">
                <div class="step-circle">🔍</div>
                <div class="step-title">Підбираємо рішення</div>
                <p class="step-text">Аналізуємо ваш запит та пропонуємо варіанти з детальним поясненням умов.</p>
            </div>

            <div class="step reveal reveal-delay-3">
                <div class="step-circle">✅</div>
                <div class="step-title">Оформляємо і супроводжуємо</div>
                <p class="step-text">Допомагаємо з оформленням документів і залишаємося на зв'язку весь строк дії полісу.</p>
            </div>
        </div>
    </div>
</section>

<section class="section form-section" id="form">
    <div class="section-inner">
        <div class="form-grid">
            <div class="form-info">
                <div class="section-label">Залишити заявку</div>
                <h2 class="section-title">Розкажіть нам про ваш запит</h2>
                <p class="section-desc">
                    Заповніть форму — менеджер зв'яжеться з вами протягом одного робочого дня та відповість на всі питання.
                </p>

                <div class="form-checklist">
                    <div class="form-check-item">
                        <div class="form-check-icon">✓</div>
                        Підходить і для фізичних осіб, і для компаній
                    </div>
                    <div class="form-check-item">
                        <div class="form-check-icon">✓</div>
                        Безкоштовна консультація без зобов'язань
                    </div>
                    <div class="form-check-item">
                        <div class="form-check-icon">✓</div>
                        Відповімо протягом одного робочого дня
                    </div>
                    <div class="form-check-item">
                        <div class="form-check-icon">✓</div>
                        Персональний менеджер на весь час співпраці
                    </div>
                </div>

                <div class="form-contact-info">
                    <span style="font-size:28px;">📞</span>
                    <div>
                        <strong>+38 (044) 000-00-00</strong>
                        Пн–Пт, 9:00–18:00
                    </div>
                </div>
            </div>

            <div class="form-box" id="formBox">
                <div class="form-box-title">Заявка на консультацію</div>

                @if (session('lead_success'))
                    <div class="form-success show" id="formSuccess">
                        <div class="success-icon">✓</div>
                        <div class="success-title">Заявку отримано!</div>
                        <p class="success-text">
                            Менеджер зв'яжеться з вами протягом одного робочого дня. Дякуємо за звернення.
                        </p>
                    </div>
                @endif

                @if (! session('lead_success'))
                    <form method="POST" action="{{ route('landing.store') }}" id="applicationForm" novalidate>
                        @csrf

                        <div class="global-error {{ $errors->any() ? 'show' : '' }}" id="globalError">
                            ⚠ Виникла помилка. Перевірте заповнені поля та спробуйте ще раз.
                        </div>

                        @if ($errors->has('form'))
                            <div class="global-error show">
                                {{ $errors->first('form') }}
                            </div>
                        @endif

                        <div class="type-toggle">
                            <button
                                type="button"
                                class="type-btn {{ old('type', 'individual') === 'individual' ? 'active' : '' }}"
                                data-type="individual"
                            >
                                Фізична особа
                            </button>

                            <button
                                type="button"
                                class="type-btn {{ old('type') === 'company' ? 'active' : '' }}"
                                data-type="company"
                            >
                                Компанія
                            </button>
                        </div>

                        <input type="hidden" name="type" id="applicantType" value="{{ old('type', 'individual') }}">

                        <div class="form-group {{ old('type') === 'company' ? '' : 'hidden' }}" id="companyGroup">
                            <label class="form-label" for="companyName">
                                Назва компанії <span class="req">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-input @error('company_name') error @enderror"
                                id="companyName"
                                name="company_name"
                                value="{{ old('company_name') }}"
                                placeholder="ТОВ «Назва компанії»"
                                maxlength="200"
                            >
                            <span class="field-error {{ $errors->has('company_name') ? 'visible' : '' }}" id="companyNameError">
                                {{ $errors->first('company_name') ?: 'Вкажіть назву компанії' }}
                            </span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="lastName">
                                    Прізвище <span class="req">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-input @error('last_name') error @enderror"
                                    id="lastName"
                                    name="last_name"
                                    value="{{ old('last_name') }}"
                                    placeholder="Іваненко"
                                    maxlength="100"
                                >
                                <span class="field-error {{ $errors->has('last_name') ? 'visible' : '' }}" id="lastNameError">
                                    {{ $errors->first('last_name') ?: 'Вкажіть прізвище' }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="firstName">
                                    Ім'я <span class="req">*</span>
                                </label>
                                <input
                                    type="text"
                                    class="form-input @error('first_name') error @enderror"
                                    id="firstName"
                                    name="first_name"
                                    value="{{ old('first_name') }}"
                                    placeholder="Іван"
                                    maxlength="100"
                                >
                                <span class="field-error {{ $errors->has('first_name') ? 'visible' : '' }}" id="firstNameError">
                                    {{ $errors->first('first_name') ?: 'Вкажіть ім\'я' }}
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="patronymic">По батькові</label>
                            <input
                                type="text"
                                class="form-input @error('middle_name') error @enderror"
                                id="patronymic"
                                name="middle_name"
                                value="{{ old('middle_name') }}"
                                placeholder="Іванович"
                                maxlength="100"
                            >
                            @if ($errors->has('middle_name'))
                                <span class="field-error visible">{{ $errors->first('middle_name') }}</span>
                            @endif
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="phone">
                                    Телефон <span class="req">*</span>
                                </label>
                                <input
                                    type="tel"
                                    class="form-input @error('phone') error @enderror"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="+380 XX XXX XX XX"
                                >
                                <span class="field-error {{ $errors->has('phone') ? 'visible' : '' }}" id="phoneError">
                                    {{ $errors->first('phone') ?: 'Введіть коректний номер' }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input
                                    type="email"
                                    class="form-input @error('email') error @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="mail@example.com"
                                >
                                <span class="field-error {{ $errors->has('email') ? 'visible' : '' }}" id="emailError">
                                    {{ $errors->first('email') ?: 'Введіть коректний email' }}
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="interest">
                                Що вас цікавить <span class="req">*</span>
                            </label>
                            <select class="form-select @error('interest') error @enderror" id="interest" name="interest" required>
                                <option value="" {{ old('interest') === null || old('interest') === '' ? 'selected' : '' }}>
                                    Оберіть напрямок
                                </option>
                                <option value="auto" {{ old('interest') === 'auto' ? 'selected' : '' }}>Автострахування</option>
                                <option value="property" {{ old('interest') === 'property' ? 'selected' : '' }}>Страхування майна</option>
                                <option value="health" {{ old('interest') === 'health' ? 'selected' : '' }}>Здоров’я та життя</option>
                                <option value="travel" {{ old('interest') === 'travel' ? 'selected' : '' }}>Страхування подорожей</option>
                                <option value="corporate" {{ old('interest') === 'corporate' ? 'selected' : '' }}>Корпоративні програми</option>
                                <option value="individual" {{ old('interest') === 'individual' ? 'selected' : '' }}>Індивідуальне рішення</option>
                                <option value="other" {{ old('interest') === 'other' ? 'selected' : '' }}>Інше</option>
                            </select>
                            <span class="field-error {{ $errors->has('interest') ? 'visible' : '' }}" id="interestError">
                                {{ $errors->first('interest') ?: 'Оберіть напрямок' }}
                            </span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="comment">Коментар</label>
                            <textarea
                                class="form-textarea @error('comment') error @enderror"
                                id="comment"
                                name="comment"
                                placeholder="Розкажіть детальніше про ваш запит..."
                                maxlength="1000"
                            >{{ old('comment') }}</textarea>
                            @if ($errors->has('comment'))
                                <span class="field-error visible">{{ $errors->first('comment') }}</span>
                            @endif
                        </div>

                        <button type="submit" class="form-submit" id="submitBtn">Надіслати заявку</button>
                        <p class="form-note">Натискаючи кнопку, ви погоджуєтесь з обробкою персональних даних</p>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="footer-inner">
        <div>
            <a href="#top" class="logo" style="margin-bottom:10px; display:inline-flex;">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L3 7v7c0 5 4 9 9 10 5-1 9-5 9-10V7L12 2z"/>
                    </svg>
                </div>
                <span class="logo-text">Захист<span>Груп</span></span>
            </a>
            <p class="footer-text" style="margin-top: 8px;">© 2025 ЗахистГруп. Усі права захищені.</p>
        </div>

        <div class="footer-links">
            <a href="#">Політика конфіденційності</a>
            <a href="#">Умови використання</a>
            <a href="#form">Залишити заявку</a>
        </div>
    </div>
</footer>

</body>
</html>