<script>
function toggleHistorial(clientId) {
    const historial = document.getElementById('historial-' + clientId);
    const isShowing = historial.classList.contains('show');
    
    if (isShowing) {
        // Cerrar con animación
        historial.style.maxHeight = historial.scrollHeight + 'px';
        setTimeout(() => {
            historial.style.maxHeight = '0';
            setTimeout(() => {
                historial.classList.remove('show');
            }, 300);
        }, 10);
    } else {
        // Abrir con animación
        historial.classList.add('show');
        historial.style.maxHeight = '0';
        setTimeout(() => {
            historial.style.maxHeight = historial.scrollHeight + 'px';
            setTimeout(() => {
                historial.style.maxHeight = 'none';
            }, 300);
        }, 10);
    }
}
</script>

<style>
.collapse {
    transition: max-height 0.3s ease;
    overflow: hidden;
}

.collapse.show {
    max-height: none !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8f9fa;
}
</style>


<script>
function toggleHistorial(clientId) {
    const historial = document.getElementById('historial-' + clientId);
    const isShowing = historial.classList.contains('show');
    
    if (isShowing) {
        historial.style.maxHeight = historial.scrollHeight + 'px';
        setTimeout(() => {
            historial.style.maxHeight = '0';
            setTimeout(() => {
                historial.classList.remove('show');
            }, 300);
        }, 10);
    } else {
        historial.classList.add('show');
        historial.style.maxHeight = '0';
        setTimeout(() => {
            historial.style.maxHeight = historial.scrollHeight + 'px';
            setTimeout(() => {
                historial.style.maxHeight = 'none';
            }, 300);
        }, 10);
    }
}
</script>

<style>
.collapse {
    transition: max-height 0.3s ease;
    overflow: hidden;
}

.collapse.show {
    max-height: none !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8f9fa;
}

/* ⚠️ ANIMACIÓN PARA REGISTROS PELIGROSOS (SIN CERRAR DE DÍAS ANTERIORES) */
.pulse-danger {
    animation: pulse-danger-animation 2s infinite;
}

@keyframes pulse-danger-animation {
    0%, 100% {
        background-color: #dc3545;
        transform: scale(1);
    }
    50% {
        background-color: #ff6b6b;
        transform: scale(1.05);
    }
}

.table-danger {
    background-color: #f8d7da !important;
}

.alert-warning {
    animation: subtle-warning 3s infinite;
}

@keyframes subtle-warning {
    0%, 100% {
        background-color: #fff3cd;
    }
    50% {
        background-color: #ffe8a1;
    }
}
</style>
