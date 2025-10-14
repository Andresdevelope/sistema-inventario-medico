<!-- Modales de Proveedores extraídos para limpieza -->

<!-- Toast container (puede quedar aquí centralizado si se reutiliza) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
  <div id="toastNotificacion" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMsg"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>

<!-- Modal Añadir Proveedor -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalProveedorLabel">Añadir Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formProveedor">
          @csrf
          <div class="mb-3">
            <label for="proveedor_nombre" class="form-label">Nombre De la Empresa</label>
            <input type="text" class="form-control" id="proveedor_nombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="proveedor_contacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="proveedor_contacto" name="contacto">
          </div>
          <div class="mb-3">
            <label for="proveedor_direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="proveedor_direccion" name="direccion">
          </div>
          <div class="mb-3">
            <label for="proveedor_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="proveedor_email" name="email" autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false">
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
        <div id="proveedorMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarProveedorLabel">Editar Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarProveedor">
          @csrf
          <div class="mb-3">
            <label for="editar_proveedor_nombre" class="form-label">Nombre De la Empresa</label>
            <input type="text" class="form-control" id="editar_proveedor_nombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="editar_proveedor_contacto" class="form-label">Contacto</label>
            <input type="text" class="form-control" id="editar_proveedor_contacto" name="contacto">
          </div>
            <div class="mb-3">
            <label for="editar_proveedor_direccion" class="form-label">Dirección</label>
            <input type="text" class="form-control" id="editar_proveedor_direccion" name="direccion">
          </div>
          <div class="mb-3">
            <label for="editar_proveedor_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="editar_proveedor_email" name="email" autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false">
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          </div>
        </form>
        <div id="editarProveedorMsg" class="mt-2"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Confirmar Eliminación Proveedor -->
<div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-labelledby="modalConfirmarEliminarLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalConfirmarEliminarLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <p class="fs-5">¿Estás seguro que deseas eliminar este proveedor?</p>
        <div class="d-flex justify-content-center gap-3 mt-3">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="btnConfirmarEliminarProveedor">Eliminar</button>
        </div>
      </div>
    </div>
  </div>
</div>
