<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Mail\NewUserAdminNotificationMail;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserService
{
    public function createUser(array $data): User
    {
        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        $user->refresh();

        // In production, dispatch mails to queue via ShouldQueue
        Mail::to($user->email)->send(new WelcomeUserMail($user));
        Mail::to(config('app.admin_email'))->send(new NewUserAdminNotificationMail($user));

        return $user;
    }

    public function listUsers(array $filters, User $authUser): array
    {
        $paginator = User::query()
            ->where('active', true)
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(
                    fn ($q) => $q->where('name', 'like', "%{$search}%")
                                 ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->when(
                $filters['sortBy'] ?? null,
                fn ($query, $column) => $query->orderBy($column),
                fn ($query) => $query->orderBy('created_at', 'desc')
            )
            ->withCount('orders')
            ->paginate(15, ['*'], 'page', $filters['page'] ?? 1);

        $users = $paginator->getCollection()->map(function (User $user) use ($authUser) {
            $user->setAttribute('can_edit', Gate::forUser($authUser)->allows('edit', $user));

            return $user;
        });

        return [
            'page'     => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total'    => $paginator->total(),
            'users'    => UserResource::collection($users)->resolve(),
        ];
    }
}
