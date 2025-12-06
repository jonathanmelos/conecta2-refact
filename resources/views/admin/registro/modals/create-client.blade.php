{{-- Modal para crear nuevo cliente ocasional --}}
<div class="modal fade" id="createClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Crear Nuevo Cliente Ocasional</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.clientes.storeQuick') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong><i class="bi bi-info-circle"></i> Cliente Ocasional</strong>
                        <p class="mb-0 mt-2"><small>Este cliente será registrado como <strong>cliente ocasional</strong> sin plan activo. Podrá usar los servicios con pago por uso, o podrás vincularlo como invitado de otro cliente con plan.</small></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_document_number" class="form-label">Documento: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_document_number" name="document_number" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_first_name" class="form-label">Nombre: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_first_name" name="first_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_last_name" class="form-label">Apellido: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_last_name" name="last_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_phone" class="form-label">Teléfono:</label>
                        <input type="text" class="form-control" id="new_phone" name="phone" placeholder="Ej: 0999999999">
                        <small class="text-muted">Opcional</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_email" class="form-label">Correo Electrónico:</label>
                        <input type="email" class="form-control" id="new_email" name="email" placeholder="ejemplo@correo.com">
                        <small class="text-muted">Opcional</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cliente Ocasional</button>
                </div>
            </form>
        </div>
    </div>
</div>