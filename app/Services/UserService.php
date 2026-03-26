<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        UserLog::create([
            'user_id' => auth()->id(),
            'action' => 'create_user',
            'changes' => $user->toArray()
        ]);

        return $user;
    }

    public function updateUser($id, array $data)
    {
        $user = User::findOrFail($id);
        $old = $user->toArray();

        if(isset($data['password'])) $data['password'] = Hash::make($data['password']);

        $user->update($data);

        UserLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_user',
            'changes' => ['old' => $old, 'new' => $user->toArray()]
        ]);

        return $user;
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        UserLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete_user',
            'changes' => $user->toArray()
        ]);

        return true;
    }

    public function getUsers() { return User::all(); }

    public function getUser($id) { return User::findOrFail($id); }
}