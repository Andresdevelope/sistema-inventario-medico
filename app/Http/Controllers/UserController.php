<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use App\Models\Movimiento;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private function maxAdminsAllowed(): int
    {
        return (int) config('inventario.max_admins', 2);
    }

    private function adminLimitReached(?int $excludeUserId = null): bool
    {
        $query = User::where('role', 'admin');
        if ($excludeUserId !== null) {
            $query->where('id', '!=', $excludeUserId);
        }
        return $query->count() >= $this->maxAdminsAllowed();
    }

    private function superAdminId(): ?int
    {
        return User::orderBy('id')->value('id');
    }

    private function isSuperAdmin(User $user): bool
    {
        $superAdminId = $this->superAdminId();
        return $superAdminId !== null && (int) $user->id === (int) $superAdminId;
    }

    // Retornar lista de usuarios en formato JSON para AJAX
    public function listaAjax()
    {
        $users = User::all();
        // Retornar solo los campos necesarios
        return response()->json($users->map(function($u){
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'locked_until' => $u->locked_until,
            ];
        }));
    }
    // Mostrar todos los usuarios (solo admin)
    public function index()
    {
        $users = User::all();
        $permissionCatalog = config('permissions.catalog', []);
        $superAdminId = $this->superAdminId();
        return view('usuarios.index', compact('users', 'permissionCatalog', 'superAdminId'));
    }

    public function permissions($id)
    {
        $target = User::findOrFail($id);

        if ($target->role !== 'operador') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden gestionar permisos granulares para usuarios operadores.'
            ], 422);
        }

        $slugs = $target->permissions()->pluck('slug')->values();

        $this->logBitacora('usuario.permisos.ver', [
            'target_user_id' => $target->id,
            'target_role' => $target->role,
        ]);

        return response()->json([
            'success' => true,
            'permissions' => $slugs,
            'catalog' => config('permissions.catalog', []),
            'target' => [
                'id' => $target->id,
                'name' => $target->name,
                'role' => $target->role,
            ],
        ]);
    }

    public function updatePermissions(Request $request, $id)
    {
        $target = User::findOrFail($id);
        $actor = Auth::user();

        if ($target->role !== 'operador') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden gestionar permisos granulares para usuarios operadores.'
            ], 422);
        }

        if (!$request->filled('admin_password') || !$actor || !Hash::check($request->input('admin_password'), $actor->password)) {
            $this->logBitacora('usuario.permisos.actualizar_denegado', [
                'target_user_id' => $target->id,
                'motivo' => 'admin_password_invalida_o_ausente',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Debes confirmar tu contraseña de administrador para guardar permisos.'
            ], 422);
        }

        $catalogItems = collect(config('permissions.catalog', []))
            ->flatMap(fn($group) => array_keys($group['items'] ?? []))
            ->values();

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ], [
            'permissions.array' => 'El listado de permisos no tiene un formato válido.',
        ]);

        $requestedSlugs = collect($request->input('permissions', []))
            ->map(fn($p) => trim((string) $p))
            ->filter()
            ->unique()
            ->values();

        $invalid = $requestedSlugs->diff($catalogItems);
        if ($invalid->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Se detectaron permisos inválidos en la solicitud.',
                'invalid' => $invalid->values(),
            ], 422);
        }

        $permissionIds = Permission::whereIn('slug', $requestedSlugs)->pluck('id');
        $before = $target->permissions()->pluck('slug')->values();
        $target->permissions()->sync($permissionIds);
        $after = $target->permissions()->pluck('slug')->values();

        $this->logBitacora('usuario.permisos.actualizar', [
            'target_user_id' => $target->id,
            'before' => $before,
            'after' => $after,
            'added' => $after->diff($before)->values(),
            'removed' => $before->diff($after)->values(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados correctamente.',
        ]);
    }

    // Crear usuario (admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:45',
            'email' => 'required|email|max:60|unique:users,email',
            'password' => ['required','string','min:16','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'color' => 'required|string|max:40',
            'animal' => 'required|string|max:40',
            'padre' => 'required|string|max:40',
            'role' => 'required|in:admin,operador',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no puede superar los 45 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'email.max' => 'El correo electrónico no puede superar los 60 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser un texto.',
            'password.min' => 'La contraseña debe tener al menos 16 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.regex' => 'La contraseña debe contener al menos una letra y un número.',
            'color.required' => 'El color favorito es obligatorio.',
            'color.string' => 'El color favorito debe ser un texto.',
            'color.max' => 'El color favorito no puede superar los 40 caracteres.',
            'animal.required' => 'El animal favorito es obligatorio.',
            'animal.string' => 'El animal favorito debe ser un texto.',
            'animal.max' => 'El animal favorito no puede superar los 40 caracteres.',
            'padre.required' => 'El nombre del padre es obligatorio.',
            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol seleccionado no es válido.'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()
                ->route('usuarios.index')
                ->withErrors($validator)
                ->withInput()
                ->with('create_failed', true);
        }
            // Verificar límite de administradores antes de crear
        if ($request->input('role') === 'admin' && $this->adminLimitReached()) {
            $msg = 'No se pueden crear más administradores. El máximo permitido es '.$this->maxAdminsAllowed().'.';
            $this->logBitacora('usuario.admin_creacion_bloqueada_limite', [
                'target_email' => $request->input('email'),
                'max_admins' => $this->maxAdminsAllowed(),
            ]);
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['role' => [$msg]]], 422);
            }
            return redirect()
                ->route('usuarios.index')
                ->withErrors(['role' => $msg])
                ->withInput()
                ->with('create_failed', true);
        }
            // Validar contraseña del admin actual (segunda capa) si se intenta crear un admin
        if ($request->input('role') === 'admin') {
            $admin = Auth::user();
            if (!$request->filled('admin_password') || !$admin || !Hash::check($request->input('admin_password'), $admin->password)) {
                $msg = 'Debes confirmar tu contraseña de administrador para crear otro administrador.';
                $this->logBitacora('usuario.admin_creacion_bloqueada_autorizacion', [
                    'target_email' => $request->input('email'),
                    'motivo' => 'admin_password_invalida_o_ausente',
                ]);
                if ($request->expectsJson()) {
                    return response()->json(['errors' => ['admin_password' => [$msg]]], 422);
                }
                return redirect()
                    ->route('usuarios.index')
                    ->withErrors(['admin_password' => $msg])
                    ->withInput()
                    ->with('create_failed', true);
            }
        }
            // Crear el usuario con las respuestas de seguridad hasheadas
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'security_color_answer' => Hash::make($request->input('color')),
            'security_animal_answer' => Hash::make($request->input('animal')),
            'security_padre_answer' => Hash::make($request->input('padre')),
            'role' => $request->input('role'),
        ]);

        $this->logBitacora('usuario.crear', [
            'target_user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    // Mostrar formulario de edición de usuario
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('usuarios.edit', compact('user'));
    }

    // Actualizar usuario y rol
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $actor = Auth::user();

        if ($this->isSuperAdmin($user) && (!$actor || !$this->isSuperAdmin($actor))) {
            $this->logBitacora('usuario.superadmin_modificacion_denegada', [
                'target_user_id' => $user->id,
                'target_role' => $user->role,
                'motivo' => 'actor_no_superadmin',
            ]);
            return redirect()
                ->route('usuarios.index')
                ->withErrors(['role' => 'Solo el superadmin puede modificar las credenciales o rol del superadmin.'])
                ->withInput()
                ->with('edit_failed', true)
                ->with('edit_user_id', $user->id);
        }
            // Validar campos básicos y opcionales (contraseña y respuestas de seguridad son opcionales en edición)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:45',
            'email' => 'required|email|max:60|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,operador',
            'password' => ['nullable','string','min:16','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'color_favorito' => 'nullable|string|max:40',
            'animal_favorito' => 'nullable|string|max:40',
            'padre_favorito' => 'nullable|string|max:40',
        ], [
            'password.min' => 'La nueva contraseña debe tener al menos 16 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.regex' => 'La nueva contraseña debe contener al menos una letra y un número.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('usuarios.index')
                ->withErrors($validator)
                ->withInput()
                ->with('edit_failed', true)
                ->with('edit_user_id', $user->id);
        }

        $nuevoRol = $request->input('role');
        $promocionAAdmin = $nuevoRol === 'admin' && $user->role !== 'admin';
        if ($promocionAAdmin && $this->adminLimitReached()) {
            $this->logBitacora('usuario.admin_promocion_bloqueada_limite', [
                'target_user_id' => $user->id,
                'target_email' => $request->input('email'),
                'max_admins' => $this->maxAdminsAllowed(),
            ]);
            return redirect()
                ->route('usuarios.index')
                ->withErrors(['role' => 'No se puede asignar rol administrador. El máximo permitido es '.$this->maxAdminsAllowed().'.'])
                ->withInput()
                ->with('edit_failed', true)
                ->with('edit_user_id', $user->id);
        }
            // Si se intenta modificar un admin (incluyendo promoción a admin) o el usuario es admin, se requiere confirmación de contraseña del actor
        $operacionAdminSensible = ($user->id !== Auth::id()) && ($user->role === 'admin' || $nuevoRol === 'admin');
        if ($operacionAdminSensible) {
            if (!$request->filled('admin_password') || !$actor || !Hash::check($request->input('admin_password'), $actor->password)) {
                $this->logBitacora('usuario.admin_modificacion_bloqueada_autorizacion', [
                    'target_user_id' => $user->id,
                    'target_role_actual' => $user->role,
                    'target_role_nuevo' => $nuevoRol,
                    'motivo' => 'admin_password_invalida_o_ausente',
                ]);
                return redirect()
                    ->route('usuarios.index')
                    ->withErrors(['admin_password' => 'Debes confirmar tu contraseña de administrador para modificar usuarios con rol administrador.'])
                    ->withInput()
                    ->with('edit_failed', true)
                    ->with('edit_user_id', $user->id);
            }
        }

        $old = $user->only(['name','email','role']);

        // Actualizar campos básicos
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->role = $request->input('role');

        // Actualizar contraseña solo si fue proporcionada
        $passwordChanged = false;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
            $passwordChanged = true;
        }

        // Actualizar respuestas de seguridad solo si fueron proporcionadas
        $securityChanged = false;
        if ($request->filled('color_favorito')) {
            $user->security_color_answer = Hash::make($request->input('color_favorito'));
            $securityChanged = true;
        }
        if ($request->filled('animal_favorito')) {
            $user->security_animal_answer = Hash::make($request->input('animal_favorito'));
            $securityChanged = true;
        }
        if ($request->filled('padre_favorito')) {
            $user->security_padre_answer = Hash::make($request->input('padre_favorito'));
            $securityChanged = true;
        }

        $user->save();

        if ($promocionAAdmin) {
            $actorName = $actor?->name ?: 'Un administrador';
            Cache::put(
                'role_notice_user_'.$user->id,
                "Tu rol fue actualizado a administrador por {$actorName}.",
                now()->addDays(30)
            );
            $this->logBitacora('usuario.rol_promovido_admin', [
                'target_user_id' => $user->id,
                'target_email' => $user->email,
                'actor_user_id' => $actor?->id,
                'actor_name' => $actorName,
            ]);
        }

        $this->logBitacora('usuario.actualizar', [
            'target_user_id' => $user->id,
            'antes' => $old,
            'despues' => $user->only(['name','email','role']),
            'password_cambiada' => $passwordChanged,
            'seguridad_cambiada' => $securityChanged,
        ]);
        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    // Eliminar usuario
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $admin = Auth::user();

        if ($this->isSuperAdmin($user)) {
            $this->logBitacora('usuario.superadmin_eliminacion_denegada', [
                'target_user_id' => $user->id,
                'motivo' => 'superadmin_protegido',
            ]);
            return redirect()->route('usuarios.index')->with('error', 'El superadmin no puede ser eliminado.');
        }

        if ($user->role === 'admin' && (!$admin || !$this->isSuperAdmin($admin))) {
            $this->logBitacora('usuario.admin_eliminacion_denegada', [
                'target_user_id' => $user->id,
                'target_role' => $user->role,
                'motivo' => 'solo_superadmin_puede_eliminar_admin',
            ]);
            return redirect()->route('usuarios.index')->with('error', 'Solo el superadmin puede eliminar cuentas con rol administrador.');
        }

        if (Auth::id() == $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        // Validar contraseña del admin actual (segunda capa)
        if (!$request->filled('admin_password') || !\Illuminate\Support\Facades\Hash::check($request->input('admin_password'), $admin->password)) {
            return redirect()->route('usuarios.index')->with('error', 'Debes ingresar tu contraseña correctamente para eliminar un usuario.');
        }

        // Verificar dependencias antes de eliminar para no dejar datos huérfanos
        $tieneMovimientos = Movimiento::where('usuario_id', $user->id)->exists();
        $tieneProductos = Producto::where('created_by', $user->id)
            ->orWhere('updated_by', $user->id)
            ->exists();

        if ($tieneMovimientos || $tieneProductos) {
            $motivos = [];
            if ($tieneMovimientos) {
                $motivos[] = 'movimientos registrados en el sistema';
            }
            if ($tieneProductos) {
                $motivos[] = 'medicamentos/productos asociados';
            }
            $detalle = implode(' y ', $motivos);
            return redirect()
                ->route('usuarios.index')
                ->with('error', "No se puede eliminar este usuario porque tiene {$detalle}. Mantén el usuario o reasigna esos registros.");
        }

        $snapshot = $user->only(['id','name','email','role']);
        $user->delete();
        $this->logBitacora('usuario.eliminar', $snapshot);
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    // Desbloquear usuario (solo admin, requiere contraseña)
    public function unlock(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $admin = Auth::user();
        if (!$request->filled('admin_password') || !Hash::check($request->input('admin_password'), $admin->password)) {
            return redirect()->route('usuarios.index')->with('error', 'Debes ingresar tu contraseña correctamente para desbloquear un usuario.');
        }
        $user->locked_until = null;
        $user->login_attempts = 0;
        $user->save();
        $this->logBitacora('usuario.desbloquear', [
            'target_user_id' => $user->id,
            'name' => $user->name,
            'admin_id' => $admin->id,
        ]);
        return redirect()->route('usuarios.index')->with('success', 'Usuario desbloqueado correctamente.');
    }
}