<script>
let searchTimeout;
let searchClientsData = {}; // Guardar clientes en memoria

function doSearch() {
    clearTimeout(searchTimeout);
    
    const search = document.getElementById('searchCliente').value.trim();
    
    if (search.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        const createBtn = document.getElementById('createClientButton');
        if (createBtn) createBtn.classList.add('d-none');
        return;
    }
    
    const clientInfoCard = document.getElementById('clientInfoCard');
    const sidebarDefault = document.getElementById('sidebarDefault');
    
    if (clientInfoCard) clientInfoCard.style.display = 'none';
    if (sidebarDefault) sidebarDefault.style.display = 'block';
    
    const createBtn = document.getElementById('createClientButton');
    if (createBtn) createBtn.classList.remove('d-none');
    
    searchTimeout = setTimeout(() => {
        fetch('{{ route("admin.registro.buscar") }}?search=' + encodeURIComponent(search))
            .then(response => response.json())
            .then(clients => {
                console.log('Clientes recibidos:', clients); // ✅ LOG
                
                let html = '';
                
                if (clients.length === 0) {
                    html = '<div class="alert alert-warning mt-3">No se encontraron clientes</div>';
                } else {
                    html = `<h5 class="mt-3">Resultados de la búsqueda</h5>
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr><th>Documento</th><th>Nombre</th><th>Apellido</th><th>Plan</th><th>Seleccionar</th></tr>
                            </thead><tbody>`;
                    
                    clients.forEach(client => {
                        // ✅ Guardar en memoria con ID como clave
                        searchClientsData[client.id] = client;
                        console.log('Guardado cliente ID:', client.id, client); // ✅ LOG
                        
                        let planInfo;
                        if (client.current_subscription && client.current_subscription.plan) {
                            planInfo = `<span class="badge bg-success">${client.current_subscription.plan.name}</span>`;
                        } else {
                            planInfo = `<span class="badge bg-warning text-dark">Cliente Ocasional</span>`;
                        }
                        
                        html += `<tr>
                            <td>${client.document_number}</td>
                            <td>${client.first_name}</td>
                            <td>${client.last_name}</td>
                            <td>${planInfo}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick="selectClientFromSearch(${client.id})">
                                    <i class="bi bi-arrow-right-circle"></i> Elegir
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                }
                
                document.getElementById('searchResults').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('searchResults').innerHTML = '<div class="alert alert-danger mt-3">Error al buscar</div>';
            });
    }, 300);
}

// ✅ Nueva función para seleccionar cliente desde búsqueda
function selectClientFromSearch(clientId) {
    console.log('=== selectClientFromSearch llamado ===');
    console.log('Client ID:', clientId);
    console.log('searchClientsData:', searchClientsData);
    
    const client = searchClientsData[clientId];
    console.log('Cliente encontrado:', client);
    
    if (!client) {
        alert('Error: No se pudo cargar la información del cliente. ID: ' + clientId);
        console.error('Cliente no encontrado en searchClientsData');
        return;
    }
    
    const clientName = client.first_name + ' ' + client.last_name;
    const clientDoc = client.document_number;
    
    console.log('Llamando a openServiceModal con:', {
        id: clientId,
        name: clientName,
        doc: clientDoc,
        data: client
    });
    
    // Verificar que la función existe
    if (typeof openServiceModal === 'function') {
        openServiceModal(clientId, clientName, clientDoc, client);
    } else {
        console.error('openServiceModal no está definida');
        alert('Error: La función openServiceModal no está disponible');
    }
}

document.getElementById('searchCliente').addEventListener('input', doSearch);
</script>