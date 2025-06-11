{**
 * Admin šablona pro formulář technologie - PRODUKČNÍ VERZE BEZ DEBUG
 * Soubor: views/templates/admin/form.tpl
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

        {* FORMULÁŘ *}
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
                    
                    <input type="file"
                           name="image"
                           class="form-control"
                           accept="image/*"
                           id="technologie-image-input" />
                    <p class="help-block">
                        {l s='Podporované formáty: JPG, PNG, GIF, WebP. Maximální velikost: 2MB.' mod='technologie'}
                    </p>
                    
                    {* Upload info *}
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

{* Minimální JavaScript pro preview obrázků *}
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    var imageInput = document.getElementById('technologie-image-input');
    var uploadInfo = document.getElementById('upload-info');
    var fileInfo = document.getElementById('file-info');
    var submitBtn = document.getElementById('submit-btn');
    var form = document.getElementById('technologie-form');

    // Image preview
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            
            if (file) {
                // Zobrazení file info
                fileInfo.innerHTML = 
                    '<strong>Název:</strong> ' + file.name + '<br>' +
                    '<strong>Typ:</strong> ' + file.type + '<br>' +
                    '<strong>Velikost:</strong> ' + (file.size / 1024).toFixed(1) + ' KB';
                uploadInfo.style.display = 'block';
                
                // Validace
                if (!file.type.match('image.*')) {
                    alert('{l s="Vyberte prosím obrázek" mod="technologie" js=1}');
                    this.value = '';
                    uploadInfo.style.display = 'none';
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    alert('{l s="Obrázek je příliš velký. Maximální velikost je 2MB" mod="technologie" js=1}');
                    this.value = '';
                    uploadInfo.style.display = 'none';
                    return;
                }

                // Preview
                var reader = new FileReader();
                reader.onload = function(e) {
                    var oldPreview = document.querySelector('.image-preview');
                    if (oldPreview) {
                        oldPreview.remove();
                    }
                    
                    var preview = document.createElement('div');
                    preview.className = 'image-preview mt-3';
                    preview.innerHTML = 
                        '<p><strong>{l s="Náhled:" mod="technologie" js=1}</strong></p>' +
                        '<img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 200px; max-height: 200px;" />';
                    
                    imageInput.parentNode.appendChild(preview);
                };
                reader.readAsDataURL(file);
            } else {
                uploadInfo.style.display = 'none';
            }
        });
    }

    // Form submission
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            var nameInput = document.querySelector('input[name="name"]');
            if (!nameInput.value.trim()) {
                alert('{l s="Název technologie je povinný" mod="technologie" js=1}');
                e.preventDefault();
                nameInput.focus();
                return false;
            }

            submitBtn.innerHTML = '<i class="icon-spinner icon-spin"></i> Ukládání...';
            submitBtn.disabled = true;
        });
    }
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
</style>