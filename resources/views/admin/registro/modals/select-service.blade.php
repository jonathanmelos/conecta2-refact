{{-- Modal central para seleccionar servicio --}}
<div class="modal fade" id="selectServiceModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-person-check"></i> Registrar Cliente en Coworking</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Información del cliente seleccionado --}}
                <div class="alert alert-primary mb-4">
                    <h4 class="mb-1" id="service_client_name"></h4>
                    <small class="text-muted">Documento: <strong id="service_client_doc"></strong></small>
                </div>

                <input type="hidden" id="service_client_id">
                
                {{-- Estado del plan --}}
                <div id="clientPlanInfo" class="mb-4">
                    {{-- Se llenará dinámicamente --}}
                </div>

                {{-- Buscador para vincular (opcional) --}}
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Vincular a Plan de Otro Cliente (Opcional)</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3"><small>Si este cliente es invitado de otro, búscalo aquí. Las horas se descontarán del plan del cliente master.</small></p>
                        
                        <input type="text" class="form-control mb-2" id="service_master_search" 
                               placeholder="Buscar cliente master por nombre o documento..." 
                               autocomplete="off">
                        
                        <div id="serviceMasterResults" class="list-group mb-2"></div>
                        
                        <input type="hidden" id="service_master_id">
                        <div id="serviceMasterSelected" class="alert alert-success d-none">
                            <strong><i class="bi bi-check-circle"></i> Vinculado a:</strong>
                            <span id="serviceMasterName"></span>
                            <button type="button" class="btn btn-sm btn-outline-danger float-end" onclick="clearMasterSelection()">
                                <i class="bi bi-x"></i> Quitar
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Botones de acción --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-lg btn-info w-100" id="btnSelectCowork" onclick="confirmServiceSelection('cowork')">
                            <i class="bi bi-laptop"></i><br>
                            <strong>Registrar Cowork</strong>
                        </button>
                        <small class="text-muted d-block mt-2 text-center" id="coworkInfo"></small>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-lg btn-info w-100" id="btnSelectSala" onclick="confirmServiceSelection('sala')">
                            <i class="bi bi-door-open"></i><br>
                            <strong>Registrar Sala</strong>
                        </button>
                        <small class="text-muted d-block mt-2 text-center" id="salaInfo"></small>
                    </div>
                </div>

                {{-- Alertas dinámicas --}}
                <div id="serviceAlerts" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Formularios ocultos para enviar --}}
<form id="formCowork" method="POST" action="{{ route('admin.registro.cowork') }}" style="display: none;">
    @csrf
    <input type="hidden" name="client_id" id="form_client_id_cowork">
    <input type="hidden" name="master_id" id="form_master_id_cowork">
</form>

<form id="formSala" method="POST" action="{{ route('admin.registro.sala') }}" style="display: none;">
    @csrf
    <input type="hidden" name="client_id" id="form_client_id_sala">
    <input type="hidden" name="master_id" id="form_master_id_sala">
</form>