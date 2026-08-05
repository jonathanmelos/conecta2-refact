{{-- Modal para registrar impresiones --}}
<div class="modal fade" id="impresionesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Registrar Impresiones</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.registro.impresion') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="client_id" id="modal_client_id">
                    <input type="hidden" name="usage_record_id" id="modal_usage_record_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente:</label>
                        <p class="form-control-plaintext" id="modal_client_name"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cantidad_impresiones" class="form-label">Cantidad de impresiones:</label>
                        <input type="number" class="form-control form-control-lg" 
                               id="cantidad_impresiones" name="cantidad" 
                               min="1" required autofocus
                               placeholder="Ingrese la cantidad">
                    </div>

                    <div class="mb-3">
                        <label for="fecha_impresion" class="form-label">Fecha y hora:</label>
                        <input type="datetime-local" class="form-control"
                               id="fecha_impresion" name="fecha_impresion">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Agregar Impresiones</button>
                </div>
            </form>
        </div>
    </div>
</div>
