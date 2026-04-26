document.addEventListener('DOMContentLoaded', () => {
    const burgerBtn = document.getElementById('burgerBtn');
    const mobileNav = document.getElementById('mobileNav');

    if (burgerBtn && mobileNav) {
        burgerBtn.addEventListener('click', () => {
            mobileNav.classList.toggle('open');
            burgerBtn.setAttribute(
                'aria-expanded',
                mobileNav.classList.contains('open') ? 'true' : 'false'
            );
        });
    }

    document.querySelectorAll('[data-mobile-link]').forEach((link) => {
        link.addEventListener('click', () => {
            if (mobileNav) {
                mobileNav.classList.remove('open');
            }

            if (burgerBtn) {
                burgerBtn.setAttribute('aria-expanded', 'false');
            }
        });
    });

    const revealEls = document.querySelectorAll('.reveal');

    if (revealEls.length) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealEls.forEach((el) => observer.observe(el));
    }

    const typeButtons = document.querySelectorAll('.type-btn');
    const applicantTypeInput = document.getElementById('applicantType');
    const companyGroup = document.getElementById('companyGroup');

    function updateApplicantType(type) {
        if (applicantTypeInput) {
            applicantTypeInput.value = type;
        }

        typeButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });

        if (companyGroup) {
            if (type === 'company') {
                companyGroup.classList.remove('hidden');
            } else {
                companyGroup.classList.add('hidden');
            }
        }
    }

    typeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            updateApplicantType(btn.dataset.type);
        });
    });

    updateApplicantType(applicantTypeInput?.value || 'individual');
});