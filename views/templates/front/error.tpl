{**
 * Chybová šablona pro technologie
 * Kompatibilní s PrestaShop 8.2.0
 *}

{extends file='page.tpl'}

{block name='page_title'}
    <h1 class="page-title">{l s='Chyba při načítání technologií' mod='technologie'}</h1>
{/block}

{block name='page_content'}
<div class="technologie-error">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mx-auto text-center">
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle error-icon"></i>
                    <h2 class="error-title">{l s='Omlouváme se' mod='technologie'}</h2>
                    <p class="error-message">
                        {if isset($error_message)}
                            {$error_message|escape:'html':'UTF-8'}
                        {else}
                            {l s='Došlo k neočekávané chybě při načítání technologií potisku.' mod='technologie'}
                        {/if}
                    </p>
                    <div class="error-actions">
                        <button type="button" class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-redo"></i>
                            {l s='Zkusit znovu' mod='technologie'}
                        </button>
                        <a href="{$urls.pages.index}" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i>
                            {l s='Zpět na hlavní stránku' mod='technologie'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{/block}
