<script>
// Modal de impresiones
function openImpresionesModal(clientId, clientName, usageRecordId) {
    document.getElementById('modal_client_id').value = clientId;
    document.getElementById('modal_client_name').textContent = clientName;
    document.getElementById('modal_usage_record_id').value = usageRecordId;
    document.getElementById('cantidad_impresiones').value = '';
    new bootstrap.Modal(document.getElementById('impresionesModal')).show();
    setTimeout(() => document.getElementById('cantidad_impresiones').focus(), 500);
}

// Modal crear cliente
function openCreateClientModal() {
    new bootstrap.Modal(document.getElementById('createClientModal')).show();
}

// Modal vincular invitado desde el panel
function openInviteModalFromPanel(guestId, guestName) {
    document.getElementById('invite_guest_id').value = guestId;
    document.getElementById('invite_guest_name').textContent = guestName;
    document.getElementById('invite_master_id').value = '';
    document.getElementById('selectedMaster').classList.add('d-none');
    document.getElementById('masterSearchResults').innerHTML = '';
    document.getElementById('invite_master_search').value = '';
    document.getElementById('btnVincular').disabled = true;
    
    new bootstrap.Modal(document.getElementById('inviteModal')).show();
    
    setTimeout(() => {
        document.getElementById('invite_master_search').focus();
    }, 500);
}

// Búsqueda de cliente master
let masterSearchTimeout;
const masterSearchInput = document.getElementById('invite_master_search');
if (masterSearchInput) {
    masterSearchInput.addEventListener('input', function() {
        clearTimeout(masterSearchTimeout);
        
        const search = this.value.trim();
        
        if (search.length < 2) {
            document.getElementById('masterSearchResults').innerHTML = '';
            return;
        }
        
        masterSearchTimeout = setTimeout(() => {
            fetch('{{ route("admin.registro.buscar") }}?search=' + encodeURIComponent(search))
                .then(response => response.json())
                .then(clients => {
                    let html = '';
                    
                    clients.forEach(client => {
                        if (client.current_subscription && client.current_subscription.plan) {
                            html += `
                                <a href="#" class="list-group-item list-group-item-action" 
                                   onclick="selectMaster(${client.id}, '${client.first_name} ${client.last_name}', '${client.current_subscription.plan.name}'); return false;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${client.first_name} ${client.last_name}</strong>
                                            <br><small class="text-muted">${client.document_number}</small>
                                        </div>
                                        <span class="badge bg-success">${client.current_subscription.plan.name}</span>
                                    </div>
                                </a>
                            `;
                        }
                    });
                    
                    if (html === '') {
                        html = '<div class="list-group-item text-center text-muted">No se encontraron clientes con plan activo</div>';
                    }
                    
                    document.getElementById('masterSearchResults').innerHTML = html;
                });
        }, 300);
    });
}

function selectMaster(masterId, masterName, masterPlan) {
    document.getElementById('invite_master_id').value = masterId;
    document.getElementById('selectedMasterName').textContent = masterName;
    document.getElementById('selectedMasterPlan').textContent = 'Plan: ' + masterPlan;
    document.getElementById('selectedMaster').classList.remove('d-none');
    document.getElementById('masterSearchResults').innerHTML = '';
    document.getElementById('invite_master_search').value = '';
    document.getElementById('btnVincular').disabled = false;
}
// Confirmar desvinculación
function confirmUnlink(guestId, guestName, masterId) {
    document.getElementById('unlink_guest_id').value = guestId;
    document.getElementById('unlink_guest_name').textContent = guestName;
    
    // Obtener el nombre del master desde el DOM
    const masterNameElement = document.querySelector('.card-body h5');
    if (masterNameElement) {
        document.getElementById('unlink_master_name').textContent = masterNameElement.textContent;
    }
    
    new bootstrap.Modal(document.getElementById('unlinkModal')).show();
}
// Confirmar desvinculación desde el panel del propio cliente
function confirmUnlinkSelf(guestId, guestName, masterName) {
    document.getElementById('unlink_guest_id').value = guestId;
    document.getElementById('unlink_guest_name').textContent = guestName;
    document.getElementById('unlink_master_name').textContent = masterName;
    
    new bootstrap.Modal(document.getElementById('unlinkModal')).show();
}
</script>