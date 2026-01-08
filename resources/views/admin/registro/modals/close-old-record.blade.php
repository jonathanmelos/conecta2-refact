{{-- Modal para cerrar registros antiguos --}}
<div class="modal fade" id="closeOldRecordModal" tabindex="-1" aria-labelledby="closeOldRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="closeOldRecordModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i> Cerrar Registro Antiguo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="closeOldRecordForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Este registro lleva días sin cerrar.</strong>
                        <br>
                        Debes ingresar la fecha y hora de salida real para poder finalizarlo.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Cliente:</strong></label>
                        <p id="closeOldClientName" class="form-control-plaintext text-primary fw-bold"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Servicio:</strong></label>
                        <p id="closeOldServiceType" class="form-control-plaintext"></p>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><strong>Fecha/Hora Entrada:</strong></label>
                            <input type="datetime-local" id="closeOldCheckIn" class="form-control" readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">
                                <strong>Fecha/Hora Salida:</strong> 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" 
                                   id="closeOldCheckOut" 
                                   name="check_out" 
                                   class="form-control" 
                                   required>
                            <small class="text-muted">Obligatorio</small>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div id="closeOldDuration" class="alert alert-info" style="display: none;">
                            <strong>Duración calculada:</strong> <span id="closeOldDurationText">0 horas</span>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger" id="closeOldSubmitBtn" disabled>
                        <i class="bi bi-check-circle"></i> Cerrar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>