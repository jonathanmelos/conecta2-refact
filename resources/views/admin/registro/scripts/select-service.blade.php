<script>
let currentClientData = null;

// Abrir modal de selección de servicio
function openServiceModal(clientId, clientName, clientDoc, clientData) {
    currentClientData = clientData;
    
    document.getElementById('service_client_id').value = clientId;
    document.getElementById('service_client_name').textContent = clientName;
    document.getElementById('service_client_doc').textContent = clientDoc;
    
    // Limpiar vinculación previa
    clearMasterSelection();
    
    // Cargar info del plan
    loadClientPlanInfo(clientData);
    
    // Mostrar modal
    new bootstrap.Modal(document.getElementById('selectServiceModal')).show();
}

// Cargar información del plan del cliente
function loadClientPlanInfo(clientData) {
    let html = '';
    let coworkDisabled = false;
    let salaDisabled = false;
    let coworkInfo = '';
    let salaInfo = '';
    const coworkData = clientData.service_plans?.cowork || clientData.current_subscription || null;
    const salaData = clientData.service_plans?.meeting_room || clientData.current_subscription || null;
    
    // ✅ SOLO verificar si el CLIENTE (no sus invitados) está usando servicio
    if (clientData.active_cowork_today) {
        coworkDisabled = true;
        coworkInfo = '<i class="bi bi-exclamation-triangle text-warning"></i> Ya está usando cowork';
        salaDisabled = true;
        salaInfo = '<i class="bi bi-exclamation-triangle text-warning"></i> No puede usar sala mientras está en cowork';
    } else if (clientData.active_sala_today) {
        salaDisabled = true;
        salaInfo = '<i class="bi bi-exclamation-triangle text-warning"></i> Ya está usando sala';
        coworkDisabled = true;
        coworkInfo = '<i class="bi bi-exclamation-triangle text-warning"></i> No puede usar cowork mientras está en sala';
    }
    
    // Verificar plan y horas disponibles por servicio
    if ((coworkData && coworkData.plan) || (salaData && salaData.plan)) {
        const renderServicePlan = (label, data, serviceKey) => {
            if (!data || data.status === 'billable' || !data.plan) {
                return `<div class="alert alert-warning py-2 mb-2">${label}: pago por uso</div>`;
            }

            if (data.status === 'ambiguous') {
                return `<div class="alert alert-danger py-2 mb-2">${label}: ${data.message || 'Seleccione un plan preferido'}</div>`;
            }

            const plan = data.plan;
            const used = Number(data.hours_used || 0);
            const total = serviceKey === 'cowork'
                ? Number(plan.cowork_hours || data.hours_total || 0)
                : Number(plan.meeting_room_hours || data.hours_total || 0);
            const isPilot = !!plan.is_pilot;
            const available = isPilot ? null : Math.max(0, total - used);
            const percent = !isPilot && total > 0 ? (used / total * 100) : 0;
            const barColor = serviceKey === 'cowork' ? 'bg-primary' : 'bg-info';

            return `
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted"><strong>${label}</strong></small>
                        <span class="badge bg-success">Plan #${data.subscription_id}</span>
                    </div>
                    <div class="small mb-1">
                        ${plan.name}${isPilot ? ' (Piloto)' : ''} · ${data.dates_label || ''}
                        ${data.source === 'member_assignment' ? ' · asignado' : ''}
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">${used.toFixed(2)} / ${isPilot ? 'Ilimitadas' : total + ' hrs'}</small>
                        <small class="text-success"><strong>${isPilot ? 'Ilimitado' : available.toFixed(2) + ' hrs disponibles'}</strong></small>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar ${percent > 80 ? 'bg-danger' : barColor}"
                             role="progressbar"
                             style="width: ${Math.min(percent, 100)}%">
                            ${isPilot ? 'Sin limite' : percent.toFixed(1) + '%'}
                        </div>
                    </div>
                </div>
            `;
        };
        
        html = `
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-success mb-0"><i class="bi bi-check-circle"></i> Plan Activo</h6>
                        <span class="badge bg-success">Por servicio</span>
                    </div>
                    ${renderServicePlan('Horas Cowork', coworkData, 'cowork')}
                    ${renderServicePlan('Horas Sala', salaData, 'meeting_room')}
                </div>
            </div>
        `;
        
        // Validar disponibilidad según el plan
        if (coworkData && coworkData.status === 'ambiguous') {
            coworkDisabled = true;
            coworkInfo = '<i class="bi bi-x-circle text-danger"></i> ' + (coworkData.message || 'Plan ambiguo');
        } else if (coworkData && coworkData.plan && !coworkData.is_pilot && (!coworkData.plan.cowork_hours || coworkData.plan.cowork_hours <= 0)) {
            coworkDisabled = true;
            coworkInfo = '<i class="bi bi-x-circle text-danger"></i> Plan sin horas de cowork';
        } else if (coworkData && coworkData.plan && !coworkData.is_pilot && Number(coworkData.hours_available || 0) <= 0) {
            coworkDisabled = true;
            coworkInfo = '<i class="bi bi-x-circle text-danger"></i> No hay horas disponibles';
        } else if (!coworkDisabled) {
            coworkInfo = coworkData && coworkData.is_pilot ? 'Uso ilimitado (plan piloto)' : `${Number(coworkData?.hours_available || 0).toFixed(2)} horas disponibles`;
        }
        
        if (salaData && salaData.status === 'ambiguous') {
            salaDisabled = true;
            salaInfo = '<i class="bi bi-x-circle text-danger"></i> ' + (salaData.message || 'Plan ambiguo');
        } else if (salaData && salaData.plan && !salaData.is_pilot && (!salaData.plan.meeting_room_hours || salaData.plan.meeting_room_hours <= 0)) {
            salaDisabled = true;
            salaInfo = '<i class="bi bi-x-circle text-danger"></i> Plan sin horas de sala';
        } else if (salaData && salaData.plan && !salaData.is_pilot && Number(salaData.hours_available || 0) <= 0) {
            salaDisabled = true;
            salaInfo = '<i class="bi bi-x-circle text-danger"></i> No hay horas disponibles';
        } else if (!salaDisabled) {
            salaInfo = salaData && salaData.is_pilot ? 'Uso ilimitado (plan piloto)' : `${Number(salaData?.hours_available || 0).toFixed(2)} horas disponibles`;
        }
        
    } else {
        // Cliente ocasional
        html = `
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-warning mb-2"><i class="bi bi-info-circle"></i> Cliente Ocasional</h6>
                    <p class="mb-0 small">Este cliente no tiene plan activo. Puede usar servicios con pago por uso, o vincularlo como invitado.</p>
                </div>
            </div>
        `;
        
        if (!coworkDisabled) coworkInfo = 'Pago por uso';
        if (!salaDisabled) salaInfo = 'Pago por uso';
    }
    
    document.getElementById('clientPlanInfo').innerHTML = html;
    document.getElementById('coworkInfo').innerHTML = coworkInfo;
    document.getElementById('salaInfo').innerHTML = salaInfo;
    
    // Habilitar/deshabilitar botones
    document.getElementById('btnSelectCowork').disabled = coworkDisabled;
    document.getElementById('btnSelectSala').disabled = salaDisabled;
}

// Búsqueda de cliente master en el modal
let serviceMasterTimeout;
document.getElementById('service_master_search')?.addEventListener('input', function() {
    clearTimeout(serviceMasterTimeout);
    
    const search = this.value.trim();
    
    if (search.length < 2) {
        document.getElementById('serviceMasterResults').innerHTML = '';
        return;
    }
    
    serviceMasterTimeout = setTimeout(() => {
        fetch('{{ route("admin.registro.buscar") }}?search=' + encodeURIComponent(search))
            .then(response => response.json())
            .then(clients => {
                let html = '';
                
                clients.forEach(client => {
                    if (client.current_subscription && client.current_subscription.plan) {
                        html += `
                            <a href="#" class="list-group-item list-group-item-action" 
                               onclick="selectServiceMaster(${client.id}, '${client.first_name} ${client.last_name}', '${client.current_subscription.plan.name}${client.current_subscription.plan.is_pilot ? ' (Piloto)' : ''}'); return false;">
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
                
                document.getElementById('serviceMasterResults').innerHTML = html;
            });
    }, 300);
});

function selectServiceMaster(masterId, masterName, masterPlan) {
    document.getElementById('service_master_id').value = masterId;
    document.getElementById('serviceMasterName').innerHTML = `<strong>${masterName}</strong> (${masterPlan})`;
    document.getElementById('serviceMasterSelected').classList.remove('d-none');
    document.getElementById('serviceMasterResults').innerHTML = '';
    document.getElementById('service_master_search').value = '';
}

function clearMasterSelection() {
    document.getElementById('service_master_id').value = '';
    document.getElementById('serviceMasterSelected').classList.add('d-none');
    document.getElementById('serviceMasterResults').innerHTML = '';
    document.getElementById('service_master_search').value = '';
}

// Confirmar selección de servicio
function confirmServiceSelection(serviceType) {
    const clientId = document.getElementById('service_client_id').value;
    const masterId = document.getElementById('service_master_id').value;
    
    if (serviceType === 'cowork') {
        document.getElementById('form_client_id_cowork').value = clientId;
        document.getElementById('form_master_id_cowork').value = masterId;
        document.getElementById('formCowork').submit();
    } else if (serviceType === 'sala') {
        document.getElementById('form_client_id_sala').value = clientId;
        document.getElementById('form_master_id_sala').value = masterId;
        document.getElementById('formSala').submit();
    }
}
</script>
