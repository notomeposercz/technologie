{**
 * Admin šablona pro formulář technologie - OPRAVENÁ VERZE
 * Kompatibilní s PrestaShop 8.2.0
 *}

<div class="panel technologie-admin">
    <div class="panel-heading">
        <i class="icon-{if $is_edit}edit{else}plus{/if}"></i>
        {if $is_edit}
            {l s='Upravit technologii' mod='technologie'}: {if isset($technologie) && $technologie && isset($technologie.name) && $technologie.name}{$technologie.name|escape:'html':'UTF-8'}{/if}
        {else}
            {l s='Přidat novou technologii' mod='technologie'}
        {/if}
    </div>

    <div class="panel-body">
        {if isset($errors) && is_array($errors) && count($errors) > 0}
            <div class="alert alert-danger">
                <ul class="mb-0">
                    {foreach from=$errors item=error}
                        <li>{$error|escape:'html':'UTF-8'}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        {* KRITICKÉ: Správný action URL a enctype *}
        <form action="{$smarty.server.REQUEST_URI|escape:'html':'UTF-8'}" 
              method="post" 
              enctype="multipart/form-data" 
              class="form-horizontal"
              id="technologie-form">

            {* Název technologie *}
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Název technologie' mod='technologie'}
                </label>
                <div class="col-lg-9">
                    <input type="text"
                           name="name"
                           value="{if isset($technologie) && $technologie && isset($technologie.name)}{$technologie.name|escape:'html':'UTF-8'}{/if}"
                           class="form-control"
                           placeholder="{l s='Zadejte název technologie' mod='technologie'}"
                           maxlength="255"
                           required />
                    <p class="help-block">{l s='Povinné pole. Maximálně 255 znaků.' mod='technologie'}</p>
                </div>
            </div>

            {* Popis technologie *}
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Popis technologie' mod='technologie'}
                </label>
                <div class="col-lg-9">
                    <textarea name="description"
                              class="form-control"
                              rows="4"
                              placeholder="{l s='Zadejte popis technologie' mod='technologie'}"
                              maxlength="1000">{if isset($technologie) && $technologie && isset($technologie.description)}{$technologie.description|escape:'html':'UTF-8'}{/if}</textarea>
                    <p class="help-block">{l s='Nepovinné pole. Maximálně 1000 znaků.' mod='technologie'}</p>
                </div>
            </div>

            {* Obrázek *}
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Obrázek technologie' mod='technologie'}
                </label>
                <div class="col-lg-9">
                    {if $is_edit && isset($technologie) && $technologie && isset($technologie.image) && $technologie.image && isset($technologie.image_url) && $technologie.image_url}
                        <div class="current-image mb-3">
                            <p><strong>{l s='Aktuální obrázek:' mod='technologie'}</strong></p>
                            <img src="{$technologie.image_url|escape:'html':'UTF-8'}"
                                 alt="{if isset($technologie.name)}{$technologie.name|escape:'html':'UTF-8'}{else}Obrázek technologie{/if}"
                                 class="img-thumbnail current-tech-image"
                                 style="max-width: 200px; max-height: 200px;" />
                            <p class="text-muted mt-2">
                                <small>{l s='Nahrajte nový obrázek pro nahrazení současného' mod='technologie'}</small>
                            </p>
                        </div>
                    {/if}
                    
                    {* KRITICKÉ: Správný name="image" *}
                    <input type="file"
                           name="image"
                           class="form-control"
                           accept="image/*"
                           id="technologie-image-input" />
                    <p class="help-block">
                        {l s='Podporované formáty: JPG, PNG, GIF, WebP. Maximální velikost: 2MB.' mod='technologie'}
                    </p>
                    
                    {* Debug info - ukáže co se odesílá *}
                    <div id="debug-info" style="margin-top: 10px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; font-family: monospace; font-size: 12px; display: none;">
                        <strong>Debug informace:</strong><br>
                        <span id="debug-content"></span>
                    </div>
                </div>
            </div>

            {* Pořadí *}
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Pořadí' mod='technologie'}
                </label>
                <div class="col-lg-9">
                    <input type="number"
                           name="position"
                           value="{if isset($technologie) && $technologie && isset($technologie.position) && $technologie.position > 0}{$technologie.position}{/if}"
                           class="form-control"
                           min="0"
                           placeholder="{l s='Zadejte pořadí (nechte prázdné pro automatické)' mod='technologie'}" />
                    <p class="help-block">{l s='Čím nižší číslo, tím výše se technologie zobrazí. Pokud nevyplníte, bude automaticky přiřazeno.' mod='technologie'}</p>
                </div>
            </div>

            {* Aktivní *}
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Stav' mod='technologie'}
                </label>
                <div class="col-lg-9">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox"
                                   name="active"
                                   value="1"
                                   {if !isset($technologie) || !$technologie || !isset($technologie.active) || $technologie.active == 1}checked{/if} />
                            {l s='Aktivní (zobrazí se na webu)' mod='technologie'}
                        </label>
                    </div>
                    <p class="help-block">{l s='Pouze aktivní technologie se zobrazují návštěvníkům webu.' mod='technologie'}</p>
                </div>
            </div>

            {* Tlačítka *}
            <div class="form-group">
                <div class="col-lg-9 col-lg-offset-3">
                    {* KRITICKÉ: Správný name="submitAddtechnologie" *}
                    <button type="submit" 
                            name="submitAdd{$table}" value="1" 
                            class="btn btn-primary btn-technologie"
                            id="submit-btn">
                        <i class="icon-save"></i>
                        {if $is_edit}
                            {l s='Aktualizovat technologii' mod='technologie'}
                        {else}
                            {l s='Přidat technologii' mod='technologie'}
                        {/if}
                    </button>
                    
                    <a href="{$back_url|escape:'html':'UTF-8'}" class="btn btn-default">
                        <i class="icon-arrow-left"></i>
                        {l s='Zpět na seznam' mod='technologie'}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{* Enhanced JavaScript s debugging *}
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Form script loaded');
    
    const form = document.getElementById('technologie-form');
    const imageInput = document.getElementById('technologie-image-input');
    const submitBtn = document.getElementById('submit-btn');
    const debugInfo = document.getElementById('debug-info');
    const debugContent = document.getElementById('debug-content');

    // Debug function
    function showDebug(message) {
        debugContent.innerHTML += message + '<br>';
        debugInfo.style.display = 'block';
        console.log('DEBUG: ' + message);
    }

    // Image preview functionality
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            showDebug('File selected: ' + (file ? file.name + ' (' + file.size + ' bytes)' : 'none'));
            
            if (file) {
                // Validace typu souboru
                if (!file.type.match('image.*')) {
                    alert('{l s="Vyberte prosím obrázek" mod="technologie"}');
                    this.value = '';
                    return;
                }

                // Validace velikosti (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('{l s="Obrázek je příliš velký. Maximální velikost je 2MB" mod="technologie"}');
                    this.value = '';
                    return;
                }

                showDebug('File validation passed');

                // Preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Odstranění starého preview
                    const oldPreview = document.querySelector('.image-preview');
                    if (oldPreview) {
                        oldPreview.remove();
                    }
                    
                    // Vytvoření nového preview
                    const preview = document.createElement('div');
                    preview.className = 'image-preview mt-3';
                    preview.innerHTML = '<p><strong>{l s="Náhled nového obrázku:" mod="technologie"}</strong></p>' +
                                      '<img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 200px; max-height: 200px;" />';
                    
                    imageInput.parentNode.appendChild(preview);
                    showDebug('Preview created');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Form submission debugging
    if (form) {
        form.addEventListener('submit', function(e) {
            showDebug('Form submission started');
            
            const nameInput = document.querySelector('input[name="name"]');
            if (!nameInput.value.trim()) {
                alert('{l s="Název technologie je povinný" mod="technologie"}');
                e.preventDefault();
                nameInput.focus();
                return false;
            }

            // Debug form data
            const formData = new FormData(form);
            let debugData = 'Form data:<br>';
            for (let [key, value] of formData.entries()) {
                if (key === 'image' && value instanceof File) {
                    debugData += key + ': FILE - ' + value.name + ' (' + value.size + ' bytes)<br>';
                } else {
                    debugData += key + ': ' + value + '<br>';
                }
            }
            showDebug(debugData);

            // Change button text to indicate processing
            submitBtn.innerHTML = '<i class="icon-spinner icon-spin"></i> Ukládání...';
            submitBtn.disabled = true;
            
            showDebug('Form submitted successfully');
        });
    }

    showDebug('JavaScript initialization complete');
});
</script>

<style>
.image-preview {
    margin-top: 15px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.image-preview img {
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.current-tech-image {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-technologie {
    background-color: #007cff;
    border-color: #007cff;
    color: white;
    transition: all 0.2s ease;
}

.btn-technologie:hover {
    background-color: #0056b3;
    border-color: #0056b3;
    color: white;
}

#debug-info {
    max-height: 200px;
    overflow-y: auto;
}
</style>