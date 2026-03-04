<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use App\Services\MailcowAuth;
use App\Services\MailcowService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MailcowUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials)
    {
        $user = parent::retrieveByCredentials($credentials);

        if (!$user && isset($credentials['email'])) {
            // JIT Provisioning: Look up in Mailcow
            $mailcow = app(\App\Services\MailcowService::class);
            $user = $mailcow->syncSingleUser($credentials['email']);
        }

        return $user;
    }

    public function validateCredentials(UserContract $user, array $credentials)
    {
        $password = $credentials['password'];

        // Ensure user has email
        if (!method_exists($user, 'getEmailAttribute') && !isset($user->email)) {
            return false;
        }

        $userModel = $user;
        $email = $userModel->email;

        // 1. Try Local First (For admin or cached)
        if (Hash::check($password, $user->getAuthPassword())) {
            return true;
        }

        // 2. Try Mailcow via IMAP
        if (MailcowAuth::check($email, $password)) {
            // Update local password for caching if desired
            if ($user instanceof \Illuminate\Database\Eloquent\Model) {
                $user->password = Hash::make($password);
                $user->save();
            }
            return true;
        }

        return false;
    }
}
