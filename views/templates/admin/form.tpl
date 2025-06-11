{**
 * Admin šablona pro formulář technologie - KRITICKY OPRAVENÁ VERZE
 * Problém: Nesprávné jméno submit tlačítka
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

        {* KRITICKÁ OPRAVA: Přidán debug panel *}
        <div id="debug-panel" style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
            <strong>🔧 DEBUG INFORMACE:</strong><br>
            Table name: {$table|default:'NENÍ NASTAVENO'}<br>
            Submit button name: submitAdd{$table|default:'technologie'}<br>
            Is edit: {if $is_edit}ANO{else}NE{/if}<br>
            <div id="form-debug-info"></div>
        </div>

        {* KRITICKY DŮLEŽITÉ: Správný action URL a enctype *}
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
                    
                    {* KRITICKY DŮLEŽITÉ: Správný name="image" *}
                    <input type="file"
                           name="image"
                           class="form-control"
                           accept="image/*"
                           id="technologie-image-input" />
                    <p class="help-block">
                        {l s='Podporované formáty: JPG, PNG, GIF, WebP. Maximální velikost: 2MB.' mod='technologie'}
                    </p>
                    
                    {* Live upload info *}
                    <div id="upload-info" style="margin-top: 10px; padding: 10px; background: #e8f4f8; border: 1px solid #bee5eb; border-radius: 4px; display: none;">
                        <strong>📁 Informace o souboru:</strong><br>
                        <span id="file-info"></span>
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
                    {* KRITICKÁ OPRAVA: Správné jméno submit tlačítka *}
                    <button type="submit" name="submitAddtechnologie" value="1"
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

{* Vylepšený JavaScript s kompletním debuggingem *}
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Form script loaded');
    
    const form = document.getElementById('technologie-form');
    const imageInput = document.getElementById('technologie-image-input');
    const submitBtn = document.getElementById('submit-btn');
    const debugInfo = document.getElementById('form-debug-info');
    const uploadInfo = document.getElementById('upload-info');
    const fileInfo = document.getElementById('file-info');

    function updateDebug(message) {
        const timestamp = new Date().toLocaleTimeString();
        debugInfo.innerHTML += `[${timestamp}] ${message}<br>`;
        console.log('🔧 DEBUG: ' + message);
    }

    updateDebug('JavaScript inicializován');
    updateDebug('Submit button name: ' + (submitBtn ? submitBtn.name : 'NENALEZEN'));

    // Enhanced image handling
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                updateDebug(`Soubor vybrán: ${file.name} (${file.size} bytes, ${file.type})`);
                
                // Zobrazení file info
                fileInfo.innerHTML = `
                    <strong>Název:</strong> ${file.name}<br>
                    <strong>Typ:</strong> ${file.type}<br>
                    <strong>Velikost:</strong> ${(file.size / 1024).toFixed(1)} KB<br>
                    <strong>Poslední změna:</strong> ${new Date(file.lastModified).toLocaleString()}
                `;
                uploadInfo.style.display = 'block';
                
                // Validace typu
                if (!file.type.match('image.*')) {
                    alert('{l s="Vyberte prosím obrázek" mod="technologie"}');
                    this.value = '';
                    uploadInfo.style.display = 'none';
                    updateDebug('CHYBA: Neplatný typ souboru');
                    return;
                }

                // Validace velikosti (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('{l s="Obrázek je příliš velký. Maximální velikost je 2MB" mod="technologie"}');
                    this.value = '';
                    uploadInfo.style.display = 'none';
                    updateDebug('CHYBA: Soubor příliš velký');
                    return;
                }

                updateDebug('Validace souboru prošla');

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
                    preview.innerHTML = `
                        <p><strong>{l s="Náhled nového obrázku:" mod="technologie"}</strong></p>
                        <img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px; max-height: 200px;" />
                    `;
                    
                    imageInput.parentNode.appendChild(preview);
                    updateDebug('Preview vytvořen');
                };
                reader.readAsDataURL(file);
            } else {
                uploadInfo.style.display = 'none';
                updateDebug('Soubor odstraněn');
            }
        });
    }

    // Enhanced form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            updateDebug('=== FORMULÁŘ SE ODESÍLÁ ===');
            
            const nameInput = document.querySelector('input[name="name"]');
            if (!nameInput.value.trim()) {
                alert('{l s="Název technologie je povinný" mod="technologie"}');
                e.preventDefault();
                nameInput.focus();
                updateDebug('CHYBA: Prázdný název - formulář zastaven');
                return false;
            }

            // Detailní debug form data
            const formData = new FormData(form);
            updateDebug('Form Data obsah:');
            let hasFile = false;
            for (let [key, value] of formData.entries()) {
                if (key === 'image' && value instanceof File && value.size > 0) {
                    updateDebug(`  ${key}: FILE - ${value.name} (${value.size} bytes)`);
                    hasFile = true;
                } else if (key === 'image') {
                    updateDebug(`  ${key}: NO FILE`);
                } else {
                    updateDebug(`  ${key}: ${value}`);
                }
            }
            
            if (hasFile) {
                updateDebug('✅ FORMULÁŘ OBSAHUJE SOUBOR K UPLOADU');
            } else {
                updateDebug('ℹ️ Formulář neobsahuje soubor k uploadu');
            }

            // Change button
            submitBtn.innerHTML = '<i class="icon-spinner icon-spin"></i> Ukládání...';
            submitBtn.disabled = true;
            
            updateDebug('✅ Formulář úspěšně odeslán');
        });
    }

    updateDebug('JavaScript plně načten a připraven');
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

#debug-panel {
    max-height: 200px;
    overflow-y: auto;
}

#upload-info {
    font-size: 13px;
}
</style>