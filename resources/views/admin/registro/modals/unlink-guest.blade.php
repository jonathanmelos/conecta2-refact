{{-- Modal para confirmar desvinculación --}}
<div class="modal fade" id="unlinkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Desvinculación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.clientes.unlinkGuest') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="unlink_guest_id" name="guest_id">
                    
                    <div class="alert alert-warning">
                        <strong><i class="bi bi-info-circle"></i> ¿Estás seguro?</strong>
                        <p class="mb-0 mt-2">Vas a desvincular a:</p>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <h5 id="unlink_guest_name" class="mb-2"></h5>
                            <p class="mb-0 text-muted">
                                <small>Vinculado al plan de: <strong id="unlink_master_name"></strong></small>
                            </p>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <strong>Nota:</strong> El cliente quedará como <strong>Cliente Ocasional</strong> y deberá pagar por uso o vincularse a otro plan.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-unlink"></i> Sí, Desvincular
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>