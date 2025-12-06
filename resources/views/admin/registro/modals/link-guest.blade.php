{{-- Modal para vincular cliente como invitado --}}
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
    <h5 class="modal-title"><i class="bi bi-person-plus-fill"></i> Vincular Cliente como Invitado</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
            <form method="POST" action="{{ route('admin.clientes.linkGuest') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="invite_guest_id" name="guest_id">
                    
                    {{-- Cliente que será invitado --}}
                    <div class="alert alert-info mb-4">
                        <strong>Cliente a vincular:</strong>
                        <h5 class="mb-0 mt-2" id="invite_guest_name"></h5>
                        <small class="text-muted">Este cliente podrá usar las horas del plan del cliente master</small>
                    </div>
                    
                    {{-- Búsqueda de cliente master --}}
                    <div class="mb-3">
                        <label for="invite_master_search" class="form-label">
                            <strong>Buscar Cliente Master (con plan activo):</strong>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="invite_master_search" 
                               placeholder="Escribe nombre o documento del cliente con plan..." 
                               autocomplete="off">
                        <small class="text-muted">El cliente master debe tener un plan activo</small>
                    </div>
                    
                    {{-- Resultados de búsqueda --}}
                    <div id="masterSearchResults" class="list-group mb-3"></div>
                    
                    {{-- Cliente master seleccionado --}}
                    <input type="hidden" id="invite_master_id" name="master_id" required>
                    <div id="selectedMaster" class="alert alert-success d-none">
                        <strong><i class="bi bi-check-circle-fill"></i> Cliente Master Seleccionado:</strong>
                        <h5 class="mb-0 mt-2" id="selectedMasterName"></h5>
                        <small id="selectedMasterPlan"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnVincular" disabled>
                        <i class="bi bi-link-45deg"></i> Confirmar Vinculación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>