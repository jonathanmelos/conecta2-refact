{{-- Modal para editar registro --}}
<div class="modal fade" id="editRecordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Editar Registro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="POST" action="{{ route('admin.registro.actualizar') }}">
                @csrf
                <input type="hidden" id="edit_record_id" name="record_id">

                <div class="modal-body">
                    {{-- Mostrar errores de validación --}}
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    {{-- INFORMACIÓN DEL CLIENTE Y SERVICIO --}}
                    <div class="alert alert-info">
                        <strong>Cliente:</strong> <span id="edit_client_name"></span><br>
                        <strong>Servicio:</strong> <span id="edit_service_type"></span>
                    </div>
                    
                    <div class="row">
                        {{-- HORA DE ENTRADA --}}
                        <div class="col-md-6 mb-3">
                            <label for="edit_check_in" class="form-label">
                                <i class="bi bi-box-arrow-in-right"></i> Hora de Entrada: <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   id="edit_check_in" 
                                   name="check_in" 
                                   required>
                            <small class="text-muted d-block">
                                Se interpreta en formato 12h (a.m./p.m.). Si escribes 24h, lo convertimos.
                            </small>
                            <small id="edit_check_in_preview" class="text-muted"></small>
                        </div>
                        
                        {{-- HORA DE SALIDA --}}
                        <div class="col-md-6 mb-3">
                            <label for="edit_check_out" class="form-label">
                                <i class="bi bi-box-arrow-left"></i> Hora de Salida: <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" 
                                   class="form-control" 
                                   id="edit_check_out" 
                                   name="check_out" 
                                   required>
                            <small class="text-muted d-block">
                                Se interpreta en formato 12h (a.m./p.m.). Si escribes 24h, lo convertimos.
                            </small>
                            <small id="edit_check_out_preview" class="text-muted"></small>
                        </div>
                    </div>
                    
                    {{-- PREVISUALIZACIÓN DE DURACIÓN --}}
                    <div id="edit_duration_preview" class="alert alert-success d-none mb-3">
                        <strong><i class="bi bi-clock-history"></i> Duración:</strong> 
                        <span id="duration_text"></span>
                    </div>

                    <div id="edit_time_warning" class="alert alert-danger d-none mb-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        La hora de salida debe ser posterior a la hora de entrada.
                    </div>
                    
                    <hr>
                    
                    {{-- ⭐ NUEVA SECCIÓN: PLAN VINCULADO --}}
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-link-45deg"></i> Plan Vinculado
                        </label>
                        
                        <div class="card">
                            <div class="card-body">
                                <div id="edit_current_plan_display" class="mb-3">
                                    <small class="text-muted">Plan actual:</small>
                                    <div id="edit_current_plan_info"></div>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="plan_option" 
                                           id="plan_option_keep" 
                                           value="keep" 
                                           checked>
                                    <label class="form-check-label" for="plan_option_keep">
                                        <i class="bi bi-check-circle"></i> Mantener plan actual
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="plan_option" 
                                           id="plan_option_link" 
                                           value="link">
                                    <label class="form-check-label" for="plan_option_link">
                                        <i class="bi bi-person-plus"></i> Vincular a plan de otro cliente
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="plan_option" 
                                           id="plan_option_remove" 
                                           value="remove">
                                    <label class="form-check-label" for="plan_option_remove">
                                        <i class="bi bi-x-circle"></i> Quitar vinculación (registro ocasional)
                                    </label>
                                </div>
                                
                                {{-- BUSCADOR DE CLIENTE MASTER (solo visible si selecciona "link") --}}
                                <div id="edit_master_search_container" class="d-none">
                                    <label for="edit_master_search" class="form-label">
                                        Buscar cliente con plan:
                                    </label>
                                    <input type="text" 
                                           class="form-control mb-2" 
                                           id="edit_master_search" 
                                           placeholder="Escribe nombre o documento...">
                                    <div id="edit_master_search_results" class="list-group mb-2"></div>
                                    <input type="hidden" id="edit_new_subscription_id" name="new_subscription_id">
                                    
                                    <div id="edit_selected_master" class="alert alert-success d-none">
                                        <strong>Cliente seleccionado:</strong><br>
                                        <span id="edit_selected_master_name"></span><br>
                                        <small id="edit_selected_master_plan"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-info-circle"></i> <strong>Nota importante:</strong><br>
                        Al guardar los cambios, se recalculará la duración y se actualizarán las horas consumidas del plan automáticamente.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" id="edit_record_submit">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
