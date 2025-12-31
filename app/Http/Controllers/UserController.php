<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Movimiento;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
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
                'role' => $u->role
            ];
        }));
    }
    // Mostrar todos los usuarios (solo admin)
    public function index()
    {
        $users = User::all();
        return view('usuarios.index', compact('users'));
    }

    // Crear usuario (admin)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required','string','min:16','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'color' => 'required|string|max:100',
            'animal' => 'required|string|max:100',
            'padre' => 'required|string|max:100',
            'role' => 'required|in:admin,operador',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser un texto.',
            'password.min' => 'La contraseña debe tener al menos 16 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.regex' => 'La contraseña debe contener al menos una letra y un número.',
            'color.required' => 'El color favorito es obligatorio.',
            'color.string' => 'El color favorito debe ser un texto.',
            'color.max' => 'El color favorito no puede superar los 100 caracteres.',
            'animal.required' => 'El animal favorito es obligatorio.',
            'animal.string' => 'El animal favorito debe ser un texto.',
            'animal.max' => 'El animal favorito no puede superar los 100 caracteres.',
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,operador',
            'password' => ['nullable','string','min:16','confirmed','regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'color_favorito' => 'nullable|string|max:100',
            'animal_favorito' => 'nullable|string|max:100',
            'padre_favorito' => 'nullable|string|max:100',
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
        if (Auth::id() == $user->id) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        // Validar contraseña del admin actual (segunda capa)
        $admin = Auth::user();
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