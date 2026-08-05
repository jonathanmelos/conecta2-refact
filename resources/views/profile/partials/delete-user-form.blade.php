<section>
    <header class="mb-3">
        <h5 class="fw-bold mb-1 text-danger">{{ __('Eliminar cuenta') }}</h5>
        <p class="text-muted small mb-0">
            {{ __('Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán permanentemente. Antes de continuar, descarga cualquier información que quieras conservar.') }}
        </p>
    </header>

    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        {{ __('Eliminar cuenta') }}
    </button>

    <div class="modal fade" id="confirmUserDeletion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('¿Estás seguro de que quieres eliminar tu cuenta?') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            {{ __('Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán permanentemente. Ingresa tu contraseña para confirmar que quieres eliminar tu cuenta de forma permanente.') }}
                        </p>

                        <label for="password" class="visually-hidden">{{ __('Contraseña') }}</label>
                        <input id="password" name="password" type="password"
                            class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                            placeholder="{{ __('Contraseña') }}">
                        @error('password', 'userDeletion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Eliminar cuenta') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
