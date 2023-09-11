<?php

namespace App\Http\Controllers\Admin\CRUD;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bank\Shop;
use App\Models\User;
use App\Services\Bank\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class ShopCrudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = max(1, intval($request->get('page', 1)));
        $size = max(1, intval($request->get('size', 20)));

        $user = auth()->user();
        $filters = ShopService::instance()->getFilters('index', $request);

        $viewAny = $user->can('viewAny', Shop::class);
//        if($viewAny){
//            $info = ShopService::instance()->getShops($page, $size, $filters);
//        }else{
//            $info = ShopService::instance()->getShops($page, $size, $filters, $user?->id);
//        }
        $info = ShopService::instance()->findShops($page, $size, $filters);

        $items = [];
        foreach($info['shops'] ?? [] as $shopEntity){
            $shop = $shopEntity->resolve();
            $owner_id = $shopEntity->getOwnerId();
            $owner = User::find($owner_id);
            $owner_email = $owner?->email;

            $item = [$shop['id']];
            if($viewAny){
                $item[] = [
                    'text' => $owner_id . '. ' . ($owner_email ? "($owner_email)" : ''),
                    'link' => $owner ? route('admin.user.show', $owner_id) : '',
                ];
            }
            $item = array_merge($item, [
                $shop['name'],
                $shop['host'],
                $shop['active'] ? 'Active' : 'Inactive',
                $shop['host_validated'] ? 'Valid host' : 'Not validated',
                $shop['moderated'] ? 'Moderated' : 'On moderation',
            ]);
            $items[] = [
                'id' => $shop['id'],
                'entity' => $shopEntity,
                'data' => $item,
            ];
        }

        $heads = [
            'ID' => null,
            'Owner ID' => null,
            'Name' => null,
            'Host' => null,
            'Active' => ['searchable' => false, 'orderable' => false],
            'Host Validation' => ['searchable' => false, 'orderable' => false],
            'Moderated' => ['searchable' => false, 'orderable' => false],
        ];
        if(!$viewAny){
            unset($heads['Owner ID']);
        }

        $data = [
            'page_title' => $viewAny ? 'All shops' : 'Your shops',
            'content_title' => $viewAny ? 'All shops' : 'Your shops',
            'paginate' => [
                'total' => $info['total'] ?? 0,
                'page' => $page,
                'size' => $size,
            ],
            'items' => $items,
            //'datatable_config' => [],
            'heads' => $heads,
            'actions' => [
                'edit' => 'admin.shop.edit',
                'delete' => 'admin.shop.destroy',
                'view' => 'admin.shop.show',
                'orders' => [
                    'title' => 'View orders',
                    'icon' => 'fas fa-fw fa-shopping-basket',
                    'btn_class' => 'text-info',
                    'func' => fn(Shop $shop) => route('admin.order.index', ['shop_id' => $shop->getId()]),
                ],
                'payment_links' => [
                    'title' => 'View payment links',
                    'icon' => 'fas fa-fw fa-link',
                    'btn_class' => 'text-info',
                    'func' => fn(Shop $shop) => route('admin.payment_link.index', ['shop_id' => $shop->getId()]),
                ],
            ],
            'filters' => $filters,
            'bulk' => [
                'route' => 'admin.bulk.shop',
                'info' => ShopService::instance()->getBulkActions($request),
                'confirm' => time(),
            ],
        ];
        return view('admin.crud.layout.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if($request->user()?->cannot('create', Shop::class)){
            abort(403);
        }
        $data = [
            'page_title' => 'Create shop',
            'content_title' => 'Create shop',
            'form' => ['action' => route('admin.shop.store')],
            'fields' => [
                [
                    'name' => 'shop_name',
                    'label' => 'Shop name',
                    'type' => 'text',
                    'placeholder' => null,
                ],
                [
                    'name' => 'shop_host',
                    'label' => 'Shop host',
                    'type' => 'text',
                    'placeholder' => 'https://example.com',
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
        if($request->user()?->cannot('create', Shop::class)){
            abort(403);
        }

        $rules = array(
            'shop_name'       => 'required|min:3|max:50',
            'shop_host'      => 'required|url|min:12|max:250',
        );
        $validator = Validator::make($request->all(), $rules);

        // process the login
        if ($validator->fails()) {
            return redirect(route('admin.shop.create'))
                ->withErrors($validator)
                ->withInput();
        }else{
            $shop = Shop::make([
                'name' => $request->input('shop_name'),
                'host' => $request->input('shop_host'),
                'owner_id' => auth()?->user()?->id,
            ])->resolve();

            $new = ShopService::instance()->createShop($shop);
            if(!($new['shop'] ?? null)){
                return redirect(route('admin.shop.index'))
                    ->with('message', $new['error']);
            }

            $id = $new['shop']->getId();
            return redirect(route('admin.shop.index'))
                ->with('message', "Successfully created shop #$id!");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id)
    {
        $shopEntity = ShopService::instance()->getShop($id);
        $shop = $shopEntity->resolve();

        if(!auth()?->user()?->can('view', $shopEntity)){
            abort(403);
        }

        $moderation = [
            'type' => $shop['moderated'] ? 'success' : 'warning',
            'message' => $shop['moderated']
                    ? 'The shop is passed moderation.'
                    : 'The shop is awaiting moderation.',
        ];
        $owner_id = $shopEntity->getOwnerId();
        $owner = User::find($owner_id);
        $owner_email = $owner?->email;

        $data = [
            'id' => $id,
            'entity' => $shopEntity,
            'info' => [
                'ID' => $shop['id'],
                'Owner' => [
                    'text' => "#$owner_id $owner_email",
                    'link' => route('admin.user.show', $owner_id),
                ],
                'Name' => $shop['name'],
                'Host' => $shop['host'],
                'Active' => $shop['active'] ? 'Active' : 'Inactive',
                'Host validation' => $shop['host_validated'] ? 'Valid host' : 'Not validated',
                'Public key' => $shop['public_key'] ?? '',
                'Webhook "Link created"' => $shopEntity->getWebhook('link_created') ?? '',
                'Webhook "On success"' => $shopEntity->getWebhook('on_success') ?? '',
                'Webhook "On failure"' => $shopEntity->getWebhook('on_failure') ?? '',
                'Webhook "On withdraw update"' => $shopEntity->getWebhook('on_withdraw_updated') ?? '',
            ],
            'alerts' => [$moderation],
            'page_title' => 'Shop info',
            'content_title' => 'Shop info: ' . $shop['name'],
        ];

        if(!$request->user()?->can('viewAny', User::class)){
            unset($data['info']['Owner']);
        }

        return view('admin.crud.shop.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $id)
    {
        $shopEntity = ShopService::instance()->getShop($id);
        $shop = $shopEntity->resolve();

        if(!$request->user()?->can('update', $shopEntity)){
            abort(403);
        }

        $isManager = $request->user()?->can('moderate', $shopEntity);

        $data = [
            'page_title' => 'Edit shop',
            'content_title' => 'Edit shop: ' . $shop['name'],
            'alerts' => [],
            'form' => ['action' => route('admin.shop.update', $shop['id'])],
            'id' => $id,
            'entity' => $shopEntity,
            'fields' => [
                [
                    'name' => 'shop_name',
                    'label' => 'Shop name',
                    'type' => 'text',
                    'placeholder' => null,
                    'value' => $shop['name'] ?? '',
                ],
                [
                    'name' => 'shop_host',
                    'label' => 'Shop host',
                    'type' => 'text',
                    'placeholder' => 'https://example.com',
                    'value' => $shop['host'] ?? '',
                    'disabled' => true,
                ],
                [
                    'name' => 'shop_active',
                    'label' => 'Shop status',
                    'type' => 'select',
                    'options' => [
                        ['value' => '1', 'label' => 'Active'],
                        ['value' => '0', 'label' => 'Inactive'],
                    ],
                    'value' => $shop['active'] ? '1' : '0',
                    'disabled' => !$shop['moderated'],
                ],
                [
                    'name' => 'shop_webhook_link_created',
                    'label' => 'Webhook "Link created"',
                    'type' => 'text',
                    'placeholder' => '/api/webhook/link_created',
                    'value' => $shopEntity->getWebhook('link_created') ?? '',
                    'disabled' => !$shopEntity->isModerated(),
                ],
                [
                    'name' => 'shop_webhook_on_success',
                    'label' => 'Webhook "On success"',
                    'type' => 'text',
                    'placeholder' => '/api/webhook/on_success',
                    'value' => $shopEntity->getWebhook('on_success') ?? '',
                    'disabled' => !$shopEntity->isModerated(),
                ],
                [
                    'name' => 'shop_webhook_on_failure',
                    'label' => 'Webhook "On failure"',
                    'type' => 'text',
                    'placeholder' => '/api/webhook/on_failure',
                    'value' => $shopEntity->getWebhook('on_failure') ?? '',
                    'disabled' => !$shopEntity->isModerated(),
                ],
                [
                    'name' => 'shop_webhook_on_withdraw_updated',
                    'label' => 'Webhook "On withdraw updated"',
                    'type' => 'text',
                    'placeholder' => '/api/webhook/on_withdraw_updated',
                    'value' => $shopEntity->getWebhook('on_withdraw_updated') ?? '',
                    'disabled' => !$shopEntity->isModerated(),
                ],
            ],
        ];

        if($isManager){
            $data['fields'][] = [
                'name' => 'shop_moderated',
                'label' => 'Shop moderation status',
                'type' => 'select',
                'options' => [
                    ['value' => '1', 'label' => 'Moderated'],
                    ['value' => '0', 'label' => 'On moderation'],
                ],
                'value' => $shop['moderated'] ? '1' : '0',
            ];

            $data['fields'][] = [
                'name' => 'shop_validated',
                'label' => 'Shop validation status',
                'type' => 'select',
                'options' => [
                    ['value' => '1', 'label' => 'Validated'],
                    ['value' => '0', 'label' => 'Not validated'],
                ],
                'value' => $shop['host_validated'] ? '1' : '0',
                'disabled' => true,
            ];
        }

        $user = $request->user();
        if(!$shop['moderated'] && !$user->isAnyManager()){
            $data['alerts'][] = [
                'type' => 'warning',
                'message' => 'You will not be able to activate this shop until it passes moderation.'
            ];
        }

        return view('admin.crud.shop.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $shopEntity = ShopService::instance()->getShop($id);

        if(!$request->user()?->can('update', $shopEntity)){
            abort(403);
        }

        $isManager = $request->user()?->can('moderate', $shopEntity);
        $rules = array(
            'shop_name'       => 'required|min:3|max:50',
            'shop_active'      => 'boolean',
        );
        if($isManager){
            $rules['shop_moderated'] = 'boolean';
        }
        $validator = Validator::make($request->all(), $rules);

        // process the login
        if ($validator->fails()) {
            return redirect(route('admin.shop.edit', $id))
                ->withErrors($validator);
        }else{
            $shop = ShopService::instance()->getShop($id)?->resolve();
            $shop['name'] = $request->input('shop_name');
            $shop['moderated'] = $isManager ? !!$request->input('shop_moderated') : $shop['moderated'];
            $shop['active'] = $shop['moderated'] && !!$request->input('shop_active');
            $shop['webhooks'] = [
                'link_created' => $request->input('shop_webhook_link_created'),
                'on_success' => $request->input('shop_webhook_on_success'),
                'on_failure' => $request->input('shop_webhook_on_failure'),
                'on_withdraw_updated' => $request->input('shop_webhook_on_withdraw_updated'),
            ];

            $shop = ShopService::instance()->updateShop($shop);
            return redirect(route('admin.shop.edit', $id))
                ->with('message', "Shop \"".$shop['name']."\" updated!");
        }
    }

    /**
     * Regenerate shop private key
     */
    public function regeneratePrivateKey(Request $request, int $id)
    {
        $shopEntity = ShopService::instance()->getShop($id);

        if (!$request->user()?->can('update', $shopEntity)) {
            abort(403);
        }

        $res = ShopService::instance()->regeneratePrivateKey($id);

        return json_encode($res);
    }

    /**
     * Regenerate shop private key
     */
    public function validateHost(Request $request, int $id)
    {
        $shopEntity = ShopService::instance()->getShop($id);

        if (!$request->user()?->can('update', $shopEntity)) {
            abort(403);
        }

        $res = ShopService::instance()->validateHost($id);

        return json_encode($res);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $id)
    {
        $shopEntity = ShopService::instance()->getShop($id);

        if(!$request->user()?->can('delete', $shopEntity)){
            abort(403);
        }

        $shop = $shopEntity->resolve();
        $error = ShopService::instance()->deleteShop($id);

        if($error){
            return redirect()->back()
                ->withErrors(['bank_api' => $error])
                ->with('error', $error);
        }

        $identifier = $shop ? '"'.$shop['name'].'"' : "#$id";
        return redirect()->back()
            ->with('message', "Shop $identifier deleted!");

    }

    public function bulk(Request $request)
    {
        $redirect_to = $request->get('redirect_to') ?: route('admin.crud.shop.index');

        $actions = ShopService::instance()->getBulkActions($request);
        if(!$actions['selected']){
            return redirect($redirect_to)->with('message', 'Unknown action');
        }elseif(empty($actions['items'])){
            return redirect($redirect_to)->with('message', 'No items selected');
        }

        $res = null;
        $message = '';
        if($actions['selected'] === 'deactivate'){
            if(!$request->user()?->can('moderateAny', Shop::class)){
                abort(403);
            }
            $res = ShopService::instance()->deactivateShops($actions['items']);
            $count = count($actions['items']);
            $message = "The shops were successfully deactivated (count: $count).";
        }elseif($actions['selected'] === 'activate_all'){
            if(!$request->user()?->can('moderateAny', Shop::class)){
                abort(403);
            }
            $res = ShopService::instance()->activateShopsByModerationStatus($actions['items'], null);
            $count = count($actions['items']);
            $message = "The shops were successfully activated (count: $count).";
        }elseif($actions['selected'] === 'activate_unmoderated'){
            if(!$request->user()?->can('moderateAny', Shop::class)){
                abort(403);
            }
            $res = ShopService::instance()->activateShopsByModerationStatus($actions['items'], false);
            $count = count($actions['items']);
            $message = "Unmoderated shops were successfully activated (count: $count).";
        }else{
            return redirect($redirect_to)->with('message', 'Unknown action');
        }

        if(!empty($res['errors'] ?? null)){
            $redirect = redirect($redirect_to);
            foreach ($res['errors'] as $error){
                $redirect->with('message', $error);
            }
            return $redirect;
        }

        return redirect($redirect_to)->with('message', $message);
    }
}
