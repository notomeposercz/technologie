{**
 * Front Office šablona pro zobrazení technologií potisku
 * Kompatibilní s PrestaShop 8.2.0 a moderními tématy
 *}

{extends file='page.tpl'}

{block name='page_title'}
    <h1 class="page-title">{$page_title|escape:'html':'UTF-8'}</h1>
{/block}

{block name='page_content'}
<div class="technologie-page">

    {* Breadcrumb navigace *}
    <nav aria-label="breadcrumb" class="technologie-breadcrumb">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{$urls.pages.index}">{l s='Domů' mod='technologie'}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {l s='Technologie potisku' mod='technologie'}
                </li>
            </ol>
        </div>
    </nav>

    {* Úvodní sekce *}
    <div class="technologie-intro">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h2 class="section-title">{l s='Naše technologie potisku' mod='technologie'}</h2>
                    <p class="section-description">
                        {l s='Nabízíme širokou škálu moderních technologií potisku pro všechny typy materiálů a požadavků. Každá technologie má své specifické výhody a oblasti použití.' mod='technologie'}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {* Seznam technologií *}
    {if $technologie && count($technologie) > 0}
    <div class="technologie-grid">
        <div class="container">
            <div class="row g-4">
                {foreach from=$technologie item=tech}
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <article class="technologie-card fade-in"
                             data-aos="fade-up"
                             data-aos-delay="{($tech@iteration - 1) * 100}"
                             role="article"
                             aria-labelledby="tech-title-{$tech->getId()}"
                             tabindex="0">

                        {* Obrázek technologie *}
                        <div class="technologie-image">
                            {if $tech->getImage()}
                                <img src="{$tech->getImageUrl()}"
                                     alt="{$tech->getName()|escape:'html':'UTF-8'}"
                                     class="technologie-img"
                                     loading="lazy"
                                     decoding="async" />
                            {else}
                                <div class="technologie-placeholder">
                                    <i class="fas fa-print" aria-hidden="true"></i>
                                    <span>{l s='Bez obrázku' mod='technologie'}</span>
                                </div>
                            {/if}


                        </div>

                        {* Obsah karty *}
                        <div class="technologie-content">
                            <h3 class="technologie-title" id="tech-title-{$tech->getId()}">
                                {$tech->getName()|escape:'html':'UTF-8'}
                            </h3>

                            {if $tech->getDescription()}
                                <p class="technologie-description">
                                    {$tech->getDescription()|escape:'html':'UTF-8'|nl2br}
                                </p>
                            {/if}

                            {* Tlačítko pro více informací (pro budoucí rozšíření) *}
                            <div class="technologie-actions">
                                <button type="button"
                                        class="technologie-detail-btn"
                                        data-tech-id="{$tech->getId()}"
                                        data-tech-name="{$tech->getName()|escape:'html':'UTF-8'}"
                                        aria-label="{l s='Zobrazit více informací o technologii' mod='technologie'} {$tech->getName()|escape:'html':'UTF-8'}">
                                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                                    <span>{l s='Více informací' mod='technologie'}</span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                {/foreach}
            </div>
        </div>
    </div>

    {* Kontaktní sekce *}
    <div class="technologie-contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h3 class="contact-title">{l s='Potřebujete poradit s výběrem technologie?' mod='technologie'}</h3>
                    <p class="contact-description">
                        {l s='Naši odborníci vám pomohou vybrat nejvhodnější technologii potisku pro váš projekt.' mod='technologie'}
                    </p>
                    <div class="contact-buttons">
                        <a href="{$urls.pages.contact}" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope"></i>
                            {l s='Kontaktujte nás' mod='technologie'}
                        </a>
                        {if isset($urls.pages.stores)}
                        <a href="{$urls.pages.stores}" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-map-marker-alt"></i>
                            {l s='Naše pobočky' mod='technologie'}
                        </a>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {else}
    {* Prázdný stav *}
    <div class="technologie-empty">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mx-auto text-center">
                    <div class="empty-state">
                        <i class="fas fa-print empty-icon"></i>
                        <h3 class="empty-title">{l s='Žádné technologie' mod='technologie'}</h3>
                        <p class="empty-description">
                            {l s='Momentálně nemáme k dispozici žádné technologie potisku. Zkuste to prosím později.' mod='technologie'}
                        </p>
                        <a href="{$urls.pages.index}" class="btn btn-primary">
                            <i class="fas fa-home"></i>
                            {l s='Zpět na hlavní stránku' mod='technologie'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {/if}
</div>

{* Modal pro detail technologie (pro budoucí rozšíření) *}
<div class="modal fade" id="technologieDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="technologieDetailModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="technologie-detail-content">
                    {* Obsah se načte přes AJAX *}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {l s='Zavřít' mod='technologie'}
                </button>
            </div>
        </div>
    </div>
</div>
{/block}

{block name='page_footer'}
    {* Structured data pro SEO *}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "{$page_title|escape:'html':'UTF-8'}",
        "description": "{$page_description|escape:'html':'UTF-8'}",
        "numberOfItems": {if $technologie}{count($technologie)}{else}0{/if},
        "itemListElement": [
            {if $technologie}
            {foreach from=$technologie item=tech}
            {
                "@type": "ListItem",
                "position": {$tech@iteration},
                "name": "{$tech->getName()|escape:'html':'UTF-8'}",
                "description": "{if $tech->getDescription()}{$tech->getDescription()|escape:'html':'UTF-8'}{/if}"
            }{if !$tech@last},{/if}
            {/foreach}
            {/if}
        ]
    }
    </script>
{/block}
