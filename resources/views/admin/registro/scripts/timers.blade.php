<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateTimers() {
        @foreach($registrosHoy->where('status', 'in_progress') as $registro)
            updateTimer({{ $registro->id }}, '{{ $registro->check_in->format('Y-m-d H:i:s') }}');
        @endforeach
    }
    
    function updateTimer(registroId, checkInTime) {
        const timerElement = document.getElementById('timer-' + registroId);
        if (!timerElement) return;
        
        const checkIn = new Date(checkInTime.replace(' ', 'T'));
        const diff = Math.floor((new Date() - checkIn) / 1000);
        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;
        
        timerElement.innerHTML = '<img src="{{ asset('images/total_horas.png') }}" width="16" height="16"> ' + 
            String(hours).padStart(2, '0') + ':' + 
            String(minutes).padStart(2, '0') + ':' + 
            String(seconds).padStart(2, '0');
    }
    
    updateTimers();
    setInterval(updateTimers, 1000);
    
    // Auto-hide client info after 60 seconds
    const clientInfoCard = document.getElementById('clientInfoCard');
    if (clientInfoCard) {
        const searchInput = document.getElementById('searchCliente');
        if (searchInput) searchInput.value = '';
        
        const createBtn = document.getElementById('createClientButton');
        if (createBtn) createBtn.classList.add('d-none');
        
        const sidebarDefault = document.getElementById('sidebarDefault');
        if (sidebarDefault) sidebarDefault.style.display = 'none';
        
        setTimeout(() => window.location.href = '{{ route("admin.registro.index") }}', 60000);
    }
});
</script>