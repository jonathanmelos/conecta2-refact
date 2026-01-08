<script>
let closeOldRecordId = null;

function openCloseOldRecordModal(recordId, clientName, serviceType, checkIn) {
    closeOldRecordId = recordId;
    
    // Llenar datos del modal
    document.getElementById('closeOldClientName').textContent = clientName;
    
    // Traducir el tipo de servicio
    const serviceNames = {
        'cowork': 'Cowork',
        'meeting_room': 'Sala de Reuniones',
        'print': 'Impresión'
    };
    document.getElementById('closeOldServiceType').textContent = serviceNames[serviceType] || serviceType;
    
    // Establecer fecha/hora de entrada (readonly)
    document.getElementById('closeOldCheckIn').value = checkIn;
    
    // Limpiar y enfocar fecha de salida
    document.getElementById('closeOldCheckOut').value = '';
    document.getElementById('closeOldDuration').style.display = 'none';
    document.getElementById('closeOldSubmitBtn').disabled = true;
    
    // Establecer fecha/hora mínima (debe ser después de la entrada)
    document.getElementById('closeOldCheckOut').min = checkIn;
    
    // Establecer fecha/hora máxima (no puede ser en el futuro)
    const now = new Date();
    const maxDateTime = now.toISOString().slice(0, 16);
    document.getElementById('closeOldCheckOut').max = maxDateTime;
    
    // Establecer la acción del formulario
    document.getElementById('closeOldRecordForm').action = `/admin/registro/finalizar-con-fecha/${recordId}`;
    
    // Abrir el modal
    const modal = new bootstrap.Modal(document.getElementById('closeOldRecordModal'));
    modal.show();
    
    // Enfocar el campo de salida después de que se abra el modal
    setTimeout(() => {
        document.getElementById('closeOldCheckOut').focus();
    }, 500);
}

// Calcular duración cuando se cambia la fecha de salida
document.addEventListener('DOMContentLoaded', function() {
    const checkOutInput = document.getElementById('closeOldCheckOut');
    
    if (checkOutInput) {
        checkOutInput.addEventListener('change', function() {
            const checkIn = document.getElementById('closeOldCheckIn').value;
            const checkOut = this.value;
            
            if (checkIn && checkOut) {
                const start = new Date(checkIn);
                const end = new Date(checkOut);
                
                // Validar que la salida sea después de la entrada
                if (end <= start) {
                    alert('La fecha de salida debe ser posterior a la fecha de entrada');
                    this.value = '';
                    document.getElementById('closeOldSubmitBtn').disabled = true;
                    document.getElementById('closeOldDuration').style.display = 'none';
                    return;
                }
                
                // Calcular diferencia en horas
                const diffMs = end - start;
                const diffHours = diffMs / (1000 * 60 * 60);
                const diffDays = Math.floor(diffHours / 24);
                const remainingHours = diffHours % 24;
                
                // Mostrar duración
                let durationText = '';
                if (diffDays > 0) {
                    durationText = `${diffDays} día(s) y ${remainingHours.toFixed(2)} horas = ${diffHours.toFixed(2)} horas totales`;
                } else {
                    durationText = `${diffHours.toFixed(2)} horas`;
                }
                
                document.getElementById('closeOldDurationText').textContent = durationText;
                document.getElementById('closeOldDuration').style.display = 'block';
                document.getElementById('closeOldSubmitBtn').disabled = false;
                
                // Advertencia si es más de 24 horas
                if (diffHours > 24) {
                    const durDiv = document.getElementById('closeOldDuration');
                    durDiv.className = 'alert alert-warning';
                    durDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> <strong>Duración calculada:</strong> <span id="closeOldDurationText">' + durationText + '</span><br><small>⚠️ Esto es más de un día. Verifica que sea correcto.</small>';
                }
            } else {
                document.getElementById('closeOldSubmitBtn').disabled = true;
                document.getElementById('closeOldDuration').style.display = 'none';
            }
        });
    }
});
</script>