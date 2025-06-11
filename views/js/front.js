/**
 * JavaScript funkcionalita pro front office modul Technologie potisku
 * Kompatibilní s PrestaShop 8.2.0 a moderními prohlížeči
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Inicializace modulu
    const TechnologieModule = {
        
        /**
         * Inicializace všech funkcí
         */
        init: function() {
            this.initImageLazyLoading();
            this.initImageErrorHandling();
            this.initCardAnimations();
            this.initAccessibility();
            this.initAnalytics();
            this.initDetailButtons();
            this.initAOSAnimations();
            this.initSmoothScrolling();
            this.initPerformanceOptimizations();
        },

        /**
         * Lazy loading obrázků pro lepší výkon
         */
        initImageLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            const src = img.dataset.src;
                            
                            if (src) {
                                img.src = src;
                                img.classList.remove('lazy');
                                img.classList.add('loaded');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },

        /**
         * Zpracování chyb při načítání obrázků
         */
        initImageErrorHandling: function() {
            document.querySelectorAll('.technologie-image img').forEach(img => {
                img.addEventListener('error', function() {
                    const card = this.closest('.technologie-card');
                    const title = card.querySelector('.technologie-title')?.textContent || 'Technologie';
                    
                    // Vytvoření placeholder elementu
                    const placeholder = document.createElement('div');
                    placeholder.className = 'technologie-placeholder';
                    placeholder.setAttribute('data-name', title);
                    placeholder.innerHTML = '<i class="fas fa-image"></i>';
                    
                    // Nahrazení obrázku placeholder
                    this.parentNode.replaceChild(placeholder, this);
                });
            });
        },

        /**
         * Animace karet při scrollování
         */
        initCardAnimations: function() {
            if ('IntersectionObserver' in window) {
                const cardObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });

                document.querySelectorAll('.technologie-card').forEach(card => {
                    cardObserver.observe(card);
                });
            }
        },

        /**
         * Vylepšení přístupnosti
         */
        initAccessibility: function() {
            // Přidání ARIA labelů
            document.querySelectorAll('.technologie-card').forEach((card, index) => {
                const title = card.querySelector('.technologie-title')?.textContent;
                if (title) {
                    card.setAttribute('aria-label', `Technologie: ${title}`);
                    card.setAttribute('role', 'article');
                }
            });

            // Keyboard navigace
            document.querySelectorAll('.technologie-card').forEach(card => {
                card.setAttribute('tabindex', '0');
                
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
        },

        /**
         * Základní analytics tracking
         */
        initAnalytics: function() {
            document.querySelectorAll('.technologie-card').forEach(card => {
                card.addEventListener('click', function() {
                    const title = this.querySelector('.technologie-title')?.textContent;
                    
                    // Google Analytics tracking (pokud je dostupné)
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'technologie_view', {
                            'event_category': 'technologie',
                            'event_label': title,
                            'value': 1
                        });
                    }
                    
                    // PrestaShop analytics (pokud je dostupné)
                    if (typeof prestashop !== 'undefined' && prestashop.emit) {
                        prestashop.emit('technologie_card_click', {
                            technologie: title
                        });
                    }
                });
            });
        },

        /**
         * AJAX načítání technologií (pro budoucí použití)
         */
        loadTechnologieAjax: function() {
            const container = document.querySelector('.technologie-grid');
            if (!container) return;

            // Zobrazení loading stavu
            container.innerHTML = '<div class="technologie-loading"><div class="loading-spinner"></div></div>';

            fetch(window.location.origin + '/reklamni-potisk/ajax/get-technologie', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderTechnologie(data.data);
                } else {
                    this.showError('Chyba při načítání technologií');
                }
            })
            .catch(error => {
                console.error('Error loading technologie:', error);
                this.showError('Chyba při načítání technologií');
            });
        },

        /**
         * Vykreslení technologií z AJAX dat
         */
        renderTechnologie: function(technologie) {
            const container = document.querySelector('.technologie-grid');
            if (!container) return;

            if (technologie.length === 0) {
                container.innerHTML = `
                    <div class="technologie-empty">
                        <h2>Žádné technologie</h2>
                        <p>Momentálně nejsou k dispozici žádné technologie potisku.</p>
                    </div>
                `;
                return;
            }

            const html = technologie.map(tech => `
                <div class="technologie-card" aria-label="Technologie: ${this.escapeHtml(tech.name)}">
                    <div class="technologie-image">
                        ${tech.image_url ? 
                            `<img src="${this.escapeHtml(tech.image_url)}" alt="${this.escapeHtml(tech.name)}" loading="lazy">` :
                            `<div class="technologie-placeholder" data-name="${this.escapeHtml(tech.name)}"><i class="fas fa-image"></i></div>`
                        }
                    </div>
                    <div class="technologie-content">
                        <h3 class="technologie-title">${this.escapeHtml(tech.name)}</h3>
                        <p class="technologie-description">${this.escapeHtml(tech.description)}</p>
                    </div>
                </div>
            `).join('');

            container.innerHTML = html;
            
            // Reinicializace funkcí pro nové elementy
            this.initImageErrorHandling();
            this.initCardAnimations();
            this.initAccessibility();
        },

        /**
         * Zobrazení chybové zprávy
         */
        showError: function(message) {
            const container = document.querySelector('.technologie-grid');
            if (!container) return;

            container.innerHTML = `
                <div class="technologie-error">
                    <h2>Chyba</h2>
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;
        },

        /**
         * Escape HTML pro bezpečnost
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Utility funkce pro debounce
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        /**
         * Inicializace tlačítek pro detail technologií
         */
        initDetailButtons: function() {
            document.querySelectorAll('.technologie-detail-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const techId = this.dataset.techId;
                    const techName = this.dataset.techName;

                    // Zobrazení modalu s detailem (připraveno pro budoucí rozšíření)
                    const modal = document.getElementById('technologieDetailModal');
                    if (modal) {
                        const modalTitle = modal.querySelector('.modal-title');
                        const modalBody = modal.querySelector('.technologie-detail-content');

                        if (modalTitle) modalTitle.textContent = techName;
                        if (modalBody) {
                            modalBody.innerHTML = `
                                <div class="text-center">
                                    <div class="loading-spinner"></div>
                                    <p class="mt-3">Načítání detailu technologie...</p>
                                </div>
                            `;
                        }

                        // Zobrazení modalu (Bootstrap 5)
                        if (typeof bootstrap !== 'undefined') {
                            const bsModal = new bootstrap.Modal(modal);
                            bsModal.show();
                        }

                        // Simulace načítání dat (pro budoucí AJAX implementaci)
                        setTimeout(() => {
                            if (modalBody) {
                                modalBody.innerHTML = `
                                    <p>Detail technologie <strong>${TechnologieModule.escapeHtml(techName)}</strong> bude dostupný v budoucí verzi modulu.</p>
                                    <p>Zde bude zobrazena podrobná specifikace, příklady použití a další informace.</p>
                                `;
                            }
                        }, 1000);
                    }
                });
            });
        },

        /**
         * Inicializace AOS animací (pokud je knihovna dostupná)
         */
        initAOSAnimations: function() {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    easing: 'ease-in-out',
                    once: true,
                    offset: 100,
                    disable: 'mobile' // Vypnutí na mobilních zařízeních pro lepší výkon
                });
            }
        },

        /**
         * Inicializace smooth scrollingu
         */
        initSmoothScrolling: function() {
            // Smooth scroll pro anchor odkazy
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        },

        /**
         * Optimalizace výkonu
         */
        initPerformanceOptimizations: function() {
            // Preload kritických obrázků
            this.preloadCriticalImages();

            // Optimalizace scroll eventů
            this.optimizeScrollEvents();

            // Lazy loading pro nekritické elementy
            this.initLazyElements();
        },

        /**
         * Preload kritických obrázků
         */
        preloadCriticalImages: function() {
            const criticalImages = document.querySelectorAll('.technologie-card:nth-child(-n+3) .technologie-img');
            criticalImages.forEach(img => {
                if (img.dataset.src) {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.as = 'image';
                    link.href = img.dataset.src;
                    document.head.appendChild(link);
                }
            });
        },

        /**
         * Optimalizace scroll eventů
         */
        optimizeScrollEvents: function() {
            let ticking = false;

            const updateScrollPosition = () => {
                const scrolled = window.pageYOffset;
                const rate = scrolled * -0.5;

                // Parallax efekt pro intro sekci
                const intro = document.querySelector('.technologie-intro');
                if (intro && scrolled < intro.offsetHeight) {
                    intro.style.transform = `translateY(${rate}px)`;
                }

                ticking = false;
            };

            const requestTick = () => {
                if (!ticking) {
                    requestAnimationFrame(updateScrollPosition);
                    ticking = true;
                }
            };

            window.addEventListener('scroll', requestTick, { passive: true });
        },

        /**
         * Lazy loading pro nekritické elementy
         */
        initLazyElements: function() {
            if ('IntersectionObserver' in window) {
                const lazyObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('fade-in');
                            lazyObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    rootMargin: '50px'
                });

                document.querySelectorAll('.technologie-contact, .empty-state, .error-state').forEach(el => {
                    lazyObserver.observe(el);
                });
            }
        }
    };

    // Inicializace modulu
    TechnologieModule.init();

    // Export pro globální použití
    window.TechnologieModule = TechnologieModule;

    // Performance monitoring (pokud je dostupné)
    if (typeof performance !== 'undefined' && performance.mark) {
        performance.mark('technologie-module-loaded');
    }
});

// CSS animace pro karty (přidáno dynamicky)
const style = document.createElement('style');
style.textContent = `
    .technologie-card {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }

    .technologie-card.animate-in {
        opacity: 1;
        transform: translateY(0);
    }

    .technologie-image img.lazy {
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .technologie-image img.loaded {
        opacity: 1;
    }

    /* Optimalizace pro prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .technologie-card,
        .technologie-image img {
            transition: none;
        }
    }
`;
document.head.appendChild(style);

// Service Worker registrace pro cache (pokud je podporován)
if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/modules/technologie/sw.js')
            .then(function(registration) {
                console.log('SW registered: ', registration);
            })
            .catch(function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
    });
}
