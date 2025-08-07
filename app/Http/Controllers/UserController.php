<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionServiceUser;
use App\Models\Person;
use App\Models\Service;
use App\Models\User;
use App\Models\Country;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function store(Request $request) {
        $request->validate([
            'nickname' => 'required|unique:users,nickname',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8'
        ]);
        try {
            DB::beginTransaction();
            $user = new User();
            $user->name = "";
            $user->nickname = $request->get('nickname');
            $user->email = $request->get('email');
            $user->password = bcrypt($request->get('password'));
            $user->save();

            $user->person()->create([
                'name' => "",
                'first_last_name' => "",
                'second_last_name' => "",
                'age' => null,
            ]);

            $user->person->addresses()->create([
                'street' => "",
                'outer_number' => "",
                'inner_number' => "",
                'zip_code' => "",
                'suburb' => "",
                'municipality' => "",
                'state' => "",
                'country_id' => $request->get('country_id'),
                'address_type_id' => $request->get('address_type_id'),
            ]);

            $permissions = PermissionServiceUser::where('user_id', $user->id)
                ->where('service_id', Service::XMl)
                ->first();

            if($permissions) {
                return response()->json([
                    'success' => false,
                    'message' => 'El servicio especificado ya esta asignado.',
                ], 500);
            }

            $user->services()->attach(Service::XMl, [
                'permission_id' => Permission::BASIC
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente'
            ], 201);
        }catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function storeFull(Request $request){
        $request->validate([
            'nickname' => 'required|unique:users,nickname',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',

            'name' => 'required|string',
            'first_last_name' => 'required|string',
            'second_last_name' => 'required|string',
            'age' => 'nullable|integer',

            'street' => 'required|string',
            'outer_number' => 'required|string',
            'inner_number' => 'nullable|string',
            'zip_code' => 'required|string',
            'suburb' => 'required|string',
            'municipality' => 'required|string',
            'state' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'address_type_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $user = new User();
            $user->name = $request->get('name');
            $user->nickname = $request->get('nickname');
            $user->email = $request->get('email');
            $user->password = bcrypt($request->get('password'));
            $user->save();

            $person = $user->person()->create([
                'name' => $request->get('name'),
                'first_last_name' => $request->get('first_last_name'),
                'second_last_name' => $request->get('second_last_name'),
                'age' => $request->get('age'),
                'gender' => $request->get('gender'),
                'phone' => $request->get('phone'),
            ]);

            $person->addresses()->create([
                'street' => $request->get('street'),
                'outer_number' => $request->get('outer_number'),
                'inner_number' => $request->get('inner_number'),
                'zip_code' => $request->get('zip_code'),
                'suburb' => $request->get('suburb'),
                'municipality' => $request->get('municipality'),
                'state' => $request->get('state'),
                'country_id' => $request->get('country_id'),
                'address_type_id' => $request->get('address_type_id'),
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Registro completo exitoso'
            ], 201);

        }catch(\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
}

    public function getCountries(){
        $countries = Country::get();

        return response()->json([
            'success' => true,
            'countries' => $countries
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El campo email es obligatorio.',
            'password.required' => 'El campo password es obligatorio.',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $user->save();

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user
            ]);
        }

        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout completado']);
    }
}