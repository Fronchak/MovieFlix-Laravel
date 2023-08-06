<?php

namespace App\Http\Controllers;

use App\Exceptions\UnauthorizedException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth')->only(['update', 'changePassword']);
    }

    public function login() {
        $credentials = request(['email', 'password']);
        return $this->authenticate($credentials);
    }

    protected function authenticate($credentials) {
        $token = auth()->attempt($credentials);
        if(!$token) {
            throw new UnauthorizedException('Invalid email or passowrd');
        }
        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token) {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer'
        ]);
    }

    public function me() {
        return response()->json(auth()->user());
    }

    public function logout() {
        auth()->logout();
        return response('', 204);
    }

    public function refresh() {
        return $this->responseWithToken(auth()->refresh());
    }

    public function register(Request $request) {
        $user = new User();

        $request->validate($user->rules(), $user->feedback());

        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $password = $request->get('password');

        $encrypted = bcrypt($password);
        $user->password = $encrypted;
        $user->save();

        return $this->authenticate([
            'email' => $user->email,
            'password' => $request->get('password')
        ]);
    }

    public function update(Request $request) {
        $user = auth()->user();
        $rules = $user->rules();
        $parameters = $request->all();
        $hasImage = array_key_exists('image', $parameters);

        unset($rules['email']);
        unset($rules['password']);
        unset($rules['confirm_password']);
        if(!$hasImage) {
            unset($rules['image']);
        }

        $request->validate($rules, $parameters);

        $oldImage = $user->image;
        $user->name = $request->get('name');
        if($hasImage) {
            $image = $request->file('image');
            $imageUrn = $image->store('imgs/users', 'public');
            $user->image = $imageUrn;
        }

        $user->update();

        if(!is_null($oldImage) && $hasImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return response('');
    }

    public function changePassword(Request $request) {
        $user = auth()->user();
        $rules = [
            'old_password' => 'required',
            'new_password' => 'required|min:4',
            'confirm_new_password' => 'required|same:new_password'
        ];
        $feedback = [
            'required' => 'The :attribute is required'
        ];
        $request->validate($rules, $feedback);

        $token = auth()->attempt([
            'email' => $user->email,
            'password' => $request->get('old_password')
        ]);

        if(!$token) {
            throw new UnauthorizedException('Invalid old password');
        }

        $encrypted = bcrypt($request->get('new_password'));
        $user->password = $encrypted;
        $user->update();

        return response('');
    }
}