<?php

namespace App\Http\Controllers\Admin\CRUD;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserCrudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if(!$user?->can('viewAny', User::class)){
            abort(403);
        }

        $page = max(1, intval($request->get('page', 1)));
        $size = 40;//max(1, intval($request->get('size', 20)));

//        $users = User::all()
//            ->skip(($page - 1) * $size)->take($size);
        $filters = UserService::instance()->getFilters('index', $request);
        $info = UserService::instance()->findUsers($page, $size, $filters);
        $items = [];
        foreach($info['users'] as $user){
            /** @var User $user $item */
            $item = [
                $user->id,
                $user->name,
                $user->email,
                $user->getRole(),
            ];
            $items[] = [
                'id' => $user->id,
                'entity' => $user,
                'data' => $item,
            ];
        }

        $heads = [
            'ID' => null,
            'Name' => null,
            'Email' => null,
            'Role' => ['searchable' => false, 'orderable' => false],
        ];

        $data = [
            'page_title' => 'Users',
            'content_title' => 'Users',
            'paginate' => [
                'total' => $info['total'] ?? 0,
                'page' => $page,
                'size' => $size,
            ],
            'items' => $items,
            //'datatable_config' => [],
            'heads' => $heads,
            'actions' => [
                'edit' => 'admin.user.edit',
                'delete' => 'admin.user.destroy',
                'view' => 'admin.user.show',
                'shops' => [
                    'title' => 'View shops',
                    'icon' => 'fas fa-fw fa-store',
                    'btn_class' => 'text-info',
                    'func' => fn(User $user) => route('admin.shop.index', ['owner_email' => $user->email]),
                ],
                'orders' => [
                    'title' => 'View orders',
                    'icon' => 'fas fa-fw fa-shopping-basket',
                    'btn_class' => 'text-info',
                    'func' => fn(User $user) => route('admin.order.index', ['owner_email' => $user->email]),
                ],
                'payment_links' => [
                    'title' => 'View payment links',
                    'icon' => 'fas fa-fw fa-link',
                    'btn_class' => 'text-info',
                    'func' => fn(User $user) => route('admin.payment_link.index', ['owner_email' => $user->email]),
                ],
            ],
            'filters' => $filters,
        ];
        return view('admin.crud.layout.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if(!$request->user()?->can('create', User::class)){
            abort(403);
        }
        $data = [
            'page_title' => 'Create user',
            'content_title' => 'Create user',
            'form' => ['action' => route('admin.user.store')],
            'fields' => [
                [
                    'name' => 'user_name',
                    'label' => 'User name',
                    'type' => 'text',
                    'placeholder' => null,
                ],
                [
                    'name' => 'user_email',
                    'label' => 'User email',
                    'type' => 'text',
                    'placeholder' => 'someone@example.com',
                ],
                [
                    'name' => 'user_password',
                    'label' => 'User password',
                    'type' => 'text',
                    'value' => UserService::instance()->randomPassword(),
                    'description' => 'Copy this password and send to user!',
                ],
                [
                    'name' => 'user_role',
                    'label' => 'User role',
                    'type' => 'select',
                    'options' => [
                        ['value' => User::ROLE_USER, 'label' => 'User'],
                        ['value' => User::ROLE_MODERATOR, 'label' => 'Moderator'],
                        ['value' => User::ROLE_ADMIN, 'label' => 'Admin'],
                    ],
                    'value' => User::DEFAULT_ROLE,
                ],
            ],
        ];
        return view('admin.crud.layout.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(!$request->user()?->can('create', User::class)){
            abort(403);
        }
        $validator = Validator::make($request->all(), [
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'user_password' => ['required', 'string', 'min:8', 'max:255'],
            'user_role' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            return redirect(route('admin.user.create'))
                ->withErrors($validator)
                ->withInput();
        }else{
            $email = $request->input('user_email');
            try{
                $user = UserService::instance()->createUser(
                    $request->input('user_name'),
                    $email,
                    $request->input('user_password'),
                    $request->input('user_role'),
                );
            }catch (\Throwable $ex){
                return redirect(route('admin.user.index'))
                    ->with('error', "Error while creating user: ". $ex->getMessage());
            }
            $id = $user->id;
            return redirect(route('admin.user.index'))
                ->with('message', "Successfully created user #$id with email $email!");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id)
    {
        $user = User::find($id);
        if(!$user){
            abort(404);
        }

        if(!auth()?->user()?->can('view', $user)){
            abort(403);
        }

        $is_profile = auth()?->user()?->id === $id;

        $data = [
            'id' => $id,
            'entity' => $user,
            'info' => [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->getRole(),
            ],
            'alerts' => [],
            'page_title' => $is_profile ? 'Profile' : 'User info',
            'content_title' => $is_profile ? 'Your profile' : 'User info: ' . $user->email,
            'reset_password_fields' => [
                [
                    'name' => 'user_old_password',
                    'label' => 'Old password',
                    'type' => 'password',
                ],
                [
                    'name' => 'user_new_password',
                    'label' => 'New password',
                    'type' => 'password',
                ],
            ],
        ];
        return view('admin.crud.user.show', $data);
    }

    public function profile(Request $request)
    {
        $user_id = auth()?->user()?->id;
        if(!$user_id or $user_id <= 0){
            abort(403);
        }
        return redirect(route('admin.user.show', ['user' => $user_id]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $user = User::find($id);
        if(!$user){
            abort(404);
        }

        if(!auth()?->user()?->can('update', $user)){
            abort(403);
        }

        $data = [
            'page_title' => 'Edit user',
            'content_title' => 'Edit user: ' . $user->email,
            'alerts' => [],
            'form' => ['action' => route('admin.user.update', $user->id)],
            'id' => $id,
            'entity' => $user,
            'fields' => [
                [
                    'name' => 'user_role',
                    'label' => 'User role',
                    'type' => 'select',
                    'options' => [
                        ['value' => User::ROLE_USER, 'label' => 'User'],
                        ['value' => User::ROLE_MODERATOR, 'label' => 'Moderator'],
                        ['value' => User::ROLE_ADMIN, 'label' => 'Admin'],
                    ],
                    'value' => $user->getRole(),
                ]
            ],
        ];

        return view('admin.crud.layout.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if(!$user){
            abort(404);
        }

        if(!auth()?->user()?->can('update', $user)){
            abort(403);
        }

        $rules = array(
            'user_role'       => 'required',
        );
        $validator = Validator::make($request->all(), $rules);

        // process the login
        if ($validator->fails()) {
            return redirect(route('admin.user.edit', $id))
                ->withErrors($validator);
        }else{
            $user->setRole($request->input('user_role'));
            $user->save();
            return redirect(route('admin.user.edit', $id))
                ->with('message', "User \"".$user->email."\" updated!");
        }
    }

    public function resetPassword(Request $request, int $id)
    {
        /** @var User|null $user */
        $user = User::find($id);
        if(!$user){
            abort(404);
        }

        if(!$request->user()?->can('resetPassword', $user)){
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'user_old_password' => ['required', 'string', 'min:8'],
            'user_new_password' => ['required', 'string', 'min:8', 'max:255'],
        ]);
        if($validator->fails()){
            return redirect(route('admin.user.show', $id))
                ->withErrors($validator);
        }else{
            $old_password = $request->input('user_old_password');
            $new_password = $request->input('user_new_password');

            $result = UserService::instance()->resetPassword($user, $old_password, $new_password);
            if(is_array($result['error'] ?? null)){
                return redirect(route('admin.user.show', $id))
                    ->withErrors($result['error']);
            }elseif(is_string($result['error'] ?? null)){
                return redirect(route('admin.user.show', $id))
                    ->with('error', $result['error']);
            }

            return redirect(route('admin.user.show', $id))
                ->with('message', "The password has been changed!");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $id)
    {
        /** @var User|null $user */
        $user = User::find($id);
        if(!$user){
            abort(404);
        }

        if(!$request->user()?->can('delete', $user)){
            abort(403);
        }

        $deleted = !!$user->delete();
        if($deleted){
            return redirect(route('admin.user.index', $id))
                ->with('message', "User #$id with email \"".$user->email."\" deleted successfully!");
        }else{
            return redirect(route('admin.user.index', $id))
                ->with('error', "Error while deleting user #$id with email \"".$user->email."\"!");
        }
    }
}
