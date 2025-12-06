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
    
    // Verificar plan y horas disponibles
    if (clientData.current_subscription && clientData.current_subscription.plan) {
        const plan = clientData.current_subscription.plan;
        const sub = clientData.current_subscription;
        
        // Calcular horas usadas y disponibles
        const coworkUsed = clientData.cowork_hours_used || 0;
        const salaUsed = clientData.sala_hours_used || 0;
        const coworkTotal = plan.cowork_hours || 0;
        const salaTotal = plan.meeting_room_hours || 0;
        const coworkAvailable = Math.max(0, coworkTotal - coworkUsed);
        const salaAvailable = Math.max(0, salaTotal - salaUsed);
        const coworkPercent = coworkTotal > 0 ? (coworkUsed / coworkTotal * 100) : 0;
        const salaPercent = salaTotal > 0 ? (salaUsed / salaTotal * 100) : 0;
        
        html = `
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-success mb-0"><i class="bi bi-check-circle"></i> Plan Activo</h6>
                        <span class="badge bg-success">${sub.days_remaining || 0} días restantes</span>
                    </div>
                    
                    <p class="mb-2"><strong>Plan:</strong> ${plan.name}</p>
                    <p class="mb-3"><strong>Vigencia:</strong> ${clientData.subscription_dates || ''}</p>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted"><strong>Horas Cowork</strong></small>
                            <small class="text-muted">${coworkUsed.toFixed(2)} / ${coworkTotal} hrs</small>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar ${coworkPercent > 80 ? 'bg-danger' : 'bg-primary'}" 
                                 role="progressbar" 
                                 style="width: ${Math.min(coworkPercent, 100)}%"
                                 aria-valuenow="${coworkPercent}"
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                ${coworkPercent.toFixed(1)}%
                            </div>
                        </div>
                        <small class="text-success"><strong>Disponible: ${coworkAvailable.toFixed(2)} hrs</strong></small>
                    </div>
                    
                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted"><strong>Horas Sala</strong></small>
                            <small class="text-muted">${salaUsed.toFixed(2)} / ${salaTotal} hrs</small>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar ${salaPercent > 80 ? 'bg-danger' : 'bg-info'}" 
                                 role="progressbar" 
                                 style="width: ${Math.min(salaPercent, 100)}%"
                                 aria-valuenow="${salaPercent}"
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                ${salaPercent.toFixed(1)}%
                            </div>
                        </div>
                        <small class="text-success"><strong>Disponible: ${salaAvailable.toFixed(2)} hrs</strong></small>
                    </div>
                </div>
            </div>
        `;
        
        // Validar disponibilidad según el plan
        if (!plan.cowork_hours || plan.cowork_hours <= 0) {
            coworkDisabled = true;
            coworkInfo = '<i class="bi bi-x-circle text-danger"></i> Plan sin horas de cowork';
        } else if (coworkAvailable <= 0) {
            coworkDisabled = true;
            coworkInfo = '<i class="bi bi-x-circle text-danger"></i> No hay horas disponibles';
        } else if (!coworkDisabled) {
            coworkInfo = `${coworkAvailable.toFixed(2)} horas disponibles`;
        }
        
        if (!plan.meeting_room_hours || plan.meeting_room_hours <= 0) {
            salaDisabled = true;
            salaInfo = '<i class="bi bi-x-circle text-danger"></i> Plan sin horas de sala';
        } else if (salaAvailable <= 0) {
            salaDisabled = true;
            salaInfo = '<i class="bi bi-x-circle text-danger"></i> No hay horas disponibles';
        } else if (!salaDisabled) {
            salaInfo = `${salaAvailable.toFixed(2)} horas disponibles`;
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
                               onclick="selectServiceMaster(${client.id}, '${client.first_name} ${client.last_name}', '${client.current_subscription.plan.name}'); return false;">
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