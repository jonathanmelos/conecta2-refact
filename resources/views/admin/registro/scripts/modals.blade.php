<script>
// Modal de impresiones
function openImpresionesModal(clientId, clientName, usageRecordId) {
    document.getElementById('modal_client_id').value = clientId;
    document.getElementById('modal_client_name').textContent = clientName;
    document.getElementById('modal_usage_record_id').value = usageRecordId;
    document.getElementById('cantidad_impresiones').value = '';
    const fechaImpresionInput = document.getElementById('fecha_impresion');
    if (fechaImpresionInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const currentDateTime = now.toISOString().slice(0, 16);
        fechaImpresionInput.value = currentDateTime;
        fechaImpresionInput.max = currentDateTime;
    }
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
                                   onclick="selectMaster(${client.id}, '${client.first_name} ${client.last_name}', '${client.current_subscription.plan.name}${client.current_subscription.plan.is_pilot ? ' (Piloto)' : ''}'); return false;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${client.first_name} ${client.last_name}</strong>
                                            <br><small class="text-muted">${client.document_number}</small>
                                        </div>
                                        <span class="badge bg-success">${client.current_subscription.plan.name}${client.current_subscription.plan.is_pilot ? ' (Piloto)' : ''}</span>
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

// Funciones de desvinculación
function confirmUnlinkSelf(guestId, guestName, masterName) {
    console.log('confirmUnlinkSelf llamado:', guestId, guestName, masterName);
    
    document.getElementById('unlink_guest_name').textContent = guestName;
    document.getElementById('unlink_master_name').textContent = masterName;
    
    const form = document.getElementById('unlinkGuestForm');
    form.action = `{{ url('admin/clientes/unlink-guest') }}/${guestId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('unlinkModal'));
    modal.show();
}

function confirmUnlink(guestId, guestName, masterId) {
    console.log('confirmUnlink llamado:', guestId, guestName, masterId);
    
    const masterName = document.querySelector('#clientInfoCard h5')?.textContent || '{{ $selectedClient->full_name ?? "" }}';
    
    document.getElementById('unlink_guest_name').textContent = guestName;
    document.getElementById('unlink_master_name').textContent = masterName;
    
    const form = document.getElementById('unlinkGuestForm');
    form.action = `{{ url('admin/clientes/unlink-guest') }}/${guestId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('unlinkModal'));
    modal.show();
}

// ⭐ Abrir modal de edición de registro
function openEditModal(recordId, serviceType, clientName, checkIn, checkOut, subscriptionId, planName) {
    console.log('=== openEditModal LLAMADO ===');
    console.log('Record ID:', recordId);
    console.log('Service Type:', serviceType);
    console.log('Check In:', checkIn);
    console.log('Check Out:', checkOut);
    console.log('Subscription ID:', subscriptionId);
    console.log('Plan Name:', planName);
    
    document.getElementById('edit_record_id').value = recordId;
    document.getElementById('edit_client_name').textContent = clientName;
    
    const serviceLabel = serviceType === 'cowork' ? 'Cowork' : (serviceType === 'meeting_room' ? 'Sala de Reuniones' : 'Impresión');
    document.getElementById('edit_service_type').textContent = serviceLabel;
    
    // Fechas
    document.getElementById('edit_check_in').value = checkIn;
    document.getElementById('edit_check_out').value = checkOut || '';
    
    // ⭐ Mostrar plan actual
    const planInfo = document.getElementById('edit_current_plan_info');
    if (subscriptionId && subscriptionId !== 'null' && subscriptionId !== null && planName) {
        planInfo.innerHTML = `<span class="badge bg-primary">${planName}</span>`;
    } else {
        planInfo.innerHTML = `<span class="badge bg-warning text-dark">Sin plan (Ocasional)</span>`;
    }
    
    // Resetear opciones
    document.getElementById('plan_option_keep').checked = true;
    document.getElementById('edit_master_search_container').classList.add('d-none');
    document.getElementById('edit_new_subscription_id').value = '';
    document.getElementById('edit_selected_master').classList.add('d-none');
    document.getElementById('edit_master_search').value = '';
    document.getElementById('edit_master_search_results').innerHTML = '';
    
    // Calcular duración
    setupDurationCalculator();
    
    new bootstrap.Modal(document.getElementById('editRecordModal')).show();
}

// Calcular duración en tiempo real
function setupDurationCalculator() {
    const checkInInput = document.getElementById('edit_check_in');
    const checkOutInput = document.getElementById('edit_check_out');
    const durationPreview = document.getElementById('edit_duration_preview');
    const durationText = document.getElementById('duration_text');
    const timeWarning = document.getElementById('edit_time_warning');
    const submitButton = document.getElementById('edit_record_submit');
    const checkInPreview = document.getElementById('edit_check_in_preview');
    const checkOutPreview = document.getElementById('edit_check_out_preview');

    function formatTo12h(value) {
        if (!value) {
            return '';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        let hours = date.getHours();
        const minutes = date.getMinutes();
        const suffix = hours >= 12 ? 'p.m.' : 'a.m.';
        hours = hours % 12;
        hours = hours === 0 ? 12 : hours;
        return `${hours}:${String(minutes).padStart(2, '0')} ${suffix}`;
    }

    function updatePreview() {
        if (checkInPreview) {
            checkInPreview.textContent = checkInInput.value
                ? `Hora interpretada: ${formatTo12h(checkInInput.value)}`
                : '';
        }
        if (checkOutPreview) {
            checkOutPreview.textContent = checkOutInput.value
                ? `Hora interpretada: ${formatTo12h(checkOutInput.value)}`
                : '';
        }
    }
    
    function calculateDuration() {
        const checkIn = new Date(checkInInput.value);
        const checkOut = new Date(checkOutInput.value);

        updatePreview();
        if (timeWarning) {
            timeWarning.classList.add('d-none');
        }
        if (submitButton) {
            submitButton.disabled = false;
        }
        
        if (checkIn && checkOut && checkOut > checkIn) {
            const diffMs = checkOut - checkIn;
            const diffHours = diffMs / (1000 * 60 * 60);
            const hours = Math.floor(diffHours);
            const minutes = Math.round((diffHours - hours) * 60);
            
            durationText.textContent = `${hours} horas y ${minutes} minutos (${diffHours.toFixed(2)} horas)`;
            durationPreview.classList.remove('d-none');
        } else if (checkInInput.value && checkOutInput.value) {
            durationPreview.classList.add('d-none');
            if (timeWarning) {
                timeWarning.classList.remove('d-none');
            }
            if (submitButton) {
                submitButton.disabled = true;
            }
        } else {
            durationPreview.classList.add('d-none');
        }
    }
    
    checkInInput.removeEventListener('change', calculateDuration);
    checkOutInput.removeEventListener('change', calculateDuration);
    checkInInput.removeEventListener('input', calculateDuration);
    checkOutInput.removeEventListener('input', calculateDuration);
    
    checkInInput.addEventListener('change', calculateDuration);
    checkOutInput.addEventListener('change', calculateDuration);
    checkInInput.addEventListener('input', calculateDuration);
    checkOutInput.addEventListener('input', calculateDuration);
    
    calculateDuration();
}

// ⭐ Gestión del selector de plan en edición
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="plan_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const searchContainer = document.getElementById('edit_master_search_container');
            if (this.value === 'link') {
                searchContainer.classList.remove('d-none');
            } else {
                searchContainer.classList.add('d-none');
                document.getElementById('edit_new_subscription_id').value = '';
                document.getElementById('edit_selected_master').classList.add('d-none');
                document.getElementById('edit_master_search').value = '';
                document.getElementById('edit_master_search_results').innerHTML = '';
            }
        });
    });
});

// Búsqueda de cliente master para vincular
let editMasterSearchTimeout;
const editMasterSearchInput = document.getElementById('edit_master_search');
if (editMasterSearchInput) {
    editMasterSearchInput.addEventListener('input', function() {
        clearTimeout(editMasterSearchTimeout);
        
        const search = this.value.trim();
        
        if (search.length < 2) {
            document.getElementById('edit_master_search_results').innerHTML = '';
            return;
        }
        
        editMasterSearchTimeout = setTimeout(() => {
            fetch('{{ route("admin.registro.buscar") }}?search=' + encodeURIComponent(search))
                .then(response => response.json())
                .then(clients => {
                    let html = '';
                    
                    clients.forEach(client => {
                        if (client.current_subscription && client.current_subscription.plan) {
                            html += `
                                <a href="#" class="list-group-item list-group-item-action" 
                                   onclick="selectEditMaster(${client.current_subscription.id}, '${client.first_name} ${client.last_name}', '${client.current_subscription.plan.name}${client.current_subscription.plan.is_pilot ? ' (Piloto)' : ''}'); return false;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${client.first_name} ${client.last_name}</strong>
                                            <br><small class="text-muted">${client.document_number}</small>
                                        </div>
                                        <span class="badge bg-success">${client.current_subscription.plan.name}${client.current_subscription.plan.is_pilot ? ' (Piloto)' : ''}</span>
                                    </div>
                                </a>
                            `;
                        }
                    });
                    
                    if (html === '') {
                        html = '<div class="list-group-item text-center text-muted">No se encontraron clientes con plan activo</div>';
                    }
                    
                    document.getElementById('edit_master_search_results').innerHTML = html;
                });
        }, 300);
    });
}

function selectEditMaster(subscriptionId, masterName, masterPlan) {
    console.log('selectEditMaster:', subscriptionId, masterName, masterPlan);
    document.getElementById('edit_new_subscription_id').value = subscriptionId;
    document.getElementById('edit_selected_master_name').textContent = masterName;
    document.getElementById('edit_selected_master_plan').textContent = 'Plan: ' + masterPlan;
    document.getElementById('edit_selected_master').classList.remove('d-none');
    document.getElementById('edit_master_search_results').innerHTML = '';
    document.getElementById('edit_master_search').value = '';
}
</script>
