/**
 * JavaScript pro admin rozhraní technologií
 */

document.addEventListener('DOMContentLoaded', function() {

    // Inicializace sortable pro drag & drop
    initSortable();

    // Hromadné akce
    initBulkActions();

    // Potvrzení mazání
    initDeleteConfirmation();

    // Preview obrázků
    initImagePreview();
});

/**
 * Inicializace drag & drop řazení
 */
function initSortable() {
    const sortableTable = document.getElementById('sortable-technologie');
    if (!sortableTable) return;

    // Pokud je dostupná knihovna Sortable
    if (typeof Sortable !== 'undefined') {
        new Sortable(sortableTable, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                updatePositions();
            }
        });
    }
}

/**
 * Aktualizace pozic po drag & drop
 */
function updatePositions() {
    const rows = document.querySelectorAll('#sortable-technologie tr[data-id]');
    const positions = {};

    rows.forEach((row, index) => {
        const id = row.getAttribute('data-id');
        positions[id] = index + 1;

        // Aktualizace zobrazené pozice
        const badge = row.querySelector('.position-badge');
        if (badge) {
            badge.textContent = index + 1;
        }
    });

    // AJAX požadavek na server
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            'ajax': '1',
            'action': 'updatePositions',
            'positions': JSON.stringify(positions)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Pořadí bylo úspěšně aktualizováno', 'success');
        } else {
            showNotification('Chyba při aktualizaci pořadí: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Chyba při komunikaci se serverem', 'error');
        console.error('Error:', error);
    });
}

/**
 * Inicializace hromadných akcí
 */
function initBulkActions() {
    const selectAll = document.getElementById('select-all');
    const rowSelectors = document.querySelectorAll('.row-selector');
    const bulkActions = document.querySelector('.bulk-actions');
    const bulkActionBtn = document.getElementById('bulk-action-btn');

    if (!selectAll || !bulkActions) return;

    // Select all checkbox
    selectAll.addEventListener('change', function() {
        rowSelectors.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        toggleBulkActions();
    });

    // Individual checkboxes
    rowSelectors.forEach(checkbox => {
        checkbox.addEventListener('change', toggleBulkActions);
    });

    // Bulk action button
    if (bulkActionBtn) {
        bulkActionBtn.addEventListener('click', executeBulkAction);
    }

    function toggleBulkActions() {
        const checkedBoxes = document.querySelectorAll('.row-selector:checked');
        if (checkedBoxes.length > 0) {
            bulkActions.style.display = 'block';
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

/**
 * Provedení hromadné akce
 */
function executeBulkAction() {
    const actionSelect = document.getElementById('bulk-action-select');
    const checkedBoxes = document.querySelectorAll('.row-selector:checked');

    if (!actionSelect.value) {
        showNotification('Vyberte akci', 'warning');
        return;
    }

    if (checkedBoxes.length === 0) {
        showNotification('Nevybrali jste žádné položky', 'warning');
        return;
    }

    const action = actionSelect.value;
    const ids = Array.from(checkedBoxes).map(cb => cb.value);

    // Potvrzení pro mazání
    if (action === 'delete') {
        if (!confirm(`Opravdu chcete smazat ${ids.length} vybraných technologií?`)) {
            return;
        }
    }

    // Odeslání formuláře
    const form = document.getElementById('bulk-action-form');
    document.getElementById('bulk-action-type').value = action;
    document.getElementById('selected-ids').value = ids.join(',');
    form.submit();
}

/**
 * Inicializace potvrzení mazání
 */
function initDeleteConfirmation() {
    const deleteLinks = document.querySelectorAll('.delete-link');

    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Opravdu chcete smazat tuto technologii?')) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Inicializace preview obrázků
 */
function initImagePreview() {
    const imageInput = document.querySelector('input[type="file"][accept*="image"]');
    if (!imageInput) return;

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Kontrola typu souboru
        if (!file.type.startsWith('image/')) {
            showNotification('Vyberte platný obrázek', 'error');
            return;
        }

        // Kontrola velikosti (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showNotification('Obrázek je příliš velký (max 2MB)', 'error');
            return;
        }

        // Vytvoření preview
        const reader = new FileReader();
        reader.onload = function(e) {
            showImagePreview(e.target.result);
        };
        reader.readAsDataURL(file);
    });
}

/**
 * Zobrazení preview obrázku
 */
function showImagePreview(src) {
    // Odstranění starého preview
    const oldPreview = document.querySelector('.image-preview');
    if (oldPreview) {
        oldPreview.remove();
    }

    // Vytvoření nového preview
    const preview = document.createElement('div');
    preview.className = 'image-preview mt-3';
    preview.innerHTML = `
        <p><strong>Náhled nového obrázku:</strong></p>
        <img src="${src}" class="img-thumbnail" style="max-width: 200px; max-height: 200px;" />
    `;

    // Přidání za file input
    const fileInput = document.querySelector('input[type="file"][accept*="image"]');
    fileInput.parentNode.appendChild(preview);
}

/**
 * Zobrazení notifikace
 */
function showNotification(message, type = 'info') {
    // Pokud existuje PrestaShop notifikační systém
    if (typeof showSuccessMessage === 'function' && type === 'success') {
        showSuccessMessage(message);
        return;
    }

    if (typeof showErrorMessage === 'function' && type === 'error') {
        showErrorMessage(message);
        return;
    }

    // Fallback - jednoduchá notifikace
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Přidání na začátek stránky
    const container = document.querySelector('.content-wrapper') || document.body;
    container.insertBefore(notification, container.firstChild);

    // Automatické skrytí po 5 sekundách
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
