<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\BaseFilterService;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UserService extends BaseFilterService
{

    use BaseService;

    private string $model = User::class;

    public function createUser(string $username, string $email, string $password, string $role): User
    {
        return User::create([
            'name' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::getRoleOrDefault($role)
        ]);
    }

    public function resetPassword(User $user, string $oldPassword, string $newPassword): array|null
    {
        $validated = auth()->validate([
            'email' => $user->email,
            'password' => $oldPassword,
        ]);
        if(!$validated){
            return ['error' => ['user_old_password' => "Old password is invalid!"]];
        }elseif($oldPassword === $newPassword){
            return ['error' => ['user_new_password' => "Passwords should be different!"]];
        }
        $user->password = Hash::make($newPassword);
        $user->password_refresh_date = now();
        if(!$user->save()){
            return ['error' => "Error while changing password."];
        }
        return null;
    }

    public function randomPassword(int $part_length = 4, int $part_count = 4): string {
        $pw = [];
        for($i = 0; $i < $part_count; $i++){
            $pw[] = Str::random($part_length);
        }
        return implode('-', $pw);
    }

    public function findUsers(int $page, int $size, array $filters): ?array
    {
        $page = max($page, 1);
        $size = max($size, 1);

        $query = $this->buildFindQuery($filters);

        $query->skip(($page - 1) * $size)->take($size);
        //dd($query->toSql());

        $users = $query->get();
        //dd($users);

        $total = $this->buildFindQuery($filters)->count();

        return [
            'total' => intval($total),
            'users' => $users ?: [],
        ];
    }

    protected function buildFindQuery(array $filters) {
        $args = $this->buildFindRequest($filters);
        $query = User::whereRaw('1');

        if(!empty($args['search'])){
            $search = $args['search'];
            $query->where(function ($query) use($search) {
                $query->where('email', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%");
            });
        }

        if(!empty($args['email'])){
            $query->wherein('email', $args['email']);
        }

        if(!empty($args['user_id'])){
            $query->wherein('id', $args['user_id']);
        }

        if(!empty($args['name'])){
            $query->wherein('name', $args['name']);
        }

        if(!empty($args['role'])){
            $query->wherein('role', $args['role']);
        }

        if(!empty($args['sort'])){
            $sort = $args['sort'];
            $query->orderby($sort['field'], $sort['direction']);
        }

        return $query;
    }

    protected function buildFindRequest(array $filters): array|null
    {
        $dto = [];

        $user = auth()?->user();
        if(!$user->id){
            return null;
        }

        // search
        $search = $filters['search']['value'] ?? null;
        if(is_string($search) and !empty($search)){
            $search = trim($search);
            if(strlen($search)){
                $dto['search'] = $search;
            }
        }

        // email
        $emails = $filters['email']['value'] ?? null;
        if(is_string($emails) and !empty($emails)){
            $emails = preg_split('/\s+/', trim($emails));
            if(is_array($emails)){
                $dto['email'] = $emails;
            }
        }

        // user_id
        $user_ids = $filters['user_id']['value'] ?? null;
        if(is_string($user_ids) and !empty($user_ids)){
            $user_ids = preg_split('/\s+/', trim($user_ids));
            $user_ids = array_map('intval', $user_ids);
            if(!empty($user_ids)){
                foreach ($user_ids as $user_id) {
                    if($user_id > 0){
                        $dto['user_id'][] = $user_id;
                    }
                }
            }
        }

        // name
        $names = $filters['name']['value'] ?? null;
        if(is_string($names) and !empty($names)){
            $names = preg_split('/\s+/', trim($names));
            if(is_array($names)){
                $dto['name'] = $names;
            }
        }

        // role
        if(isset($filters['role'])){
            $options = $filters['role']['options'] ?? [];
            $value = $filters['role']['value'] ?? null;
            $value = isset($options[$value]) ? $value : null;

            if(isset($filters['role']['default']) and $filters['role']['default'] === $value){
                $value = null;
            }

            if($value){
                $value = preg_split('/\s*,\s*/', $value);
                if($value){
                    $dto['role'] = $value;
                }
            }
        }

        // sort
        if(isset($filters['sort']['value'])){
            $sort = $filters['sort']['value'];
            $sorting = explode(',', $sort);
            if(isset($filters['sort']['options'][$sort]) and count($sorting) === 2){
                $dto['sort'] = [
                    'field' => $sorting[0],
                    'direction' => strtoupper($sorting[1]),
                ];
            }
        }

        return $dto;
    }

    public function getDefaultFilters(): array
    {
        return [
            'search' => [
                'label' => 'Search',
                'type' => 'text',
                'can' => ['viewAny', User::class],
            ],
            'email' => [
                'label' => 'User email',
                'type' => 'text',
                'can' => ['viewAny', User::class],
            ],
            'user_id' => [
                'label' => 'User ID',
                'type' => 'text',
                'can' => ['viewAny', User::class],
            ],
            'name' => [
                'label' => 'User name',
                'type' => 'text',
                'can' => ['viewAny', User::class],
            ],
            'role' => [
                'label' => 'Role',
                'type' => 'select',
                'can' => ['viewAny', User::class],
                'options' => [
                    'all' => 'Show all',
                    User::ROLE_USER => 'User',
                    User::ROLE_MODERATOR => 'Moderator',
                    User::ROLE_ADMIN => 'Admin',
                    implode(',', [User::ROLE_ADMIN, User::ROLE_MODERATOR]) => 'Admin or Moderator',
                ],
                'default' => 'all',
            ],
            'sort' => [
                'label' => 'Sort',
                'type' => 'select',
                'can' => ['viewAny', User::class],
                'options' => [
                    'default' => 'Default',
                    'id,asc' => 'ID',
                    'id,desc' => 'ID reverse',
                    'name,asc' => 'Name A-Z',
                    'name,desc' => 'Name Z-A',
                    'email,asc' => 'Email A-Z',
                    'email,desc' => 'Email Z-A',
                ],
                'default' => 'default',
            ],
        ];
    }

    public function addSupportedFilters(array &$filters): void
    {
        parent::addSupportedFilters($filters);
        $filters['size']['default'] = User::PER_PAGE;
    }

}
