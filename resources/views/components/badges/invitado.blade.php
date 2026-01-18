@if(isset($client) && $client && $client->invited_by_client_id)
    <span class="badge bg-secondary ms-1 align-middle" title="Cliente invitado">
        <i class="bi bi-person-plus"></i> Invitado
    </span>
@endif
