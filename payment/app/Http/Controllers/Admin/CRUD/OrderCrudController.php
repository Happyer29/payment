<?php

namespace App\Http\Controllers\Admin\CRUD;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bank\Order;
use App\Services\Bank\CardService;
use App\Services\Bank\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class OrderCrudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // int $page = 1, int $size = PaymentLink::PER_PAGE
        $page = max(1, intval($request->get('page', 1)));
        $size = max(1, intval($request->get('size', Order::PER_PAGE)));

        $user = auth()->user();
        $viewAny = $user->can('viewAny', Order::class);
        $manage = $user->can('moderateAny', Order::class);
//        if($viewAny){
//            $info = OrderService::instance()->getOrders($page, $size, "DESC");
//        }else{
//            $info = OrderService::instance()->getOrders($page, $size, "DESC", $user?->id);
//        }

        $filters = OrderService::instance()->getFilters('index', $request);
        $info = OrderService::instance()->findOrders($page, $size, $filters);

        //$info = OrderService::instance()->getOrders($page, $size, "DESC");
        $orders = $info['orders'] ?? [];

        $items = [];
        foreach($orders as $orderEntity){
            /** @var $orderEntity Order $order */
            $order = $orderEntity->resolve();
            $shopName = $orderEntity->getShop()?->getName();
            $items[] = [
                'id' => $order['id'],
                'entity' => $orderEntity,
                'data' => [
                    $order['id'],
                    $order['number'],
                    [
                        'text' => $order['shop_id'] . ($shopName ? " ($shopName)" : ''),
                        'link' => route('admin.shop.show', $orderEntity->getShopId()),
                    ],
                    Order::PAYMENT_METHODS[$order['payment_method']] ?? '?',
                    $order['amount'],
                    $order['date_paid'],
                    $order['status'],
                ],
            ];
        }
        $data = [
            'page_title' => 'Orders',
            'content_title' => 'Orders',
            'paginate' => [
                'total' => $info['total'] ?? 0,
                'page' => $page,
                'size' => $size,
            ],
            'items' => $items,
            //'datatable_config' => [],
            'heads' => [
                'ID' => null,
                'Number' => null,
                'Shop' => null,
                'Payment method' => null,
                'Amount' => null,
                'Date paid' => null,
                'Status' => null,
            ],
            'actions' => [
                'edit' => $manage ? 'admin.order.edit' : null,
//                'delete' => 'admin.order.destroy',
                'view' => 'admin.order.show',
                'payment_link' => [
                    'title' => 'View payment links',
                    'icon' => 'fas fa-fw fa-link',
                    'btn_class' => 'text-info',
                    'func' => fn(Order $order) => route('admin.payment_link.index', ['order_id' => $order->getId()]),
                ],
            ],
            'filters' => $filters,
        ];
        return view('admin.crud.layout.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort(404);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $orderEntity = OrderService::instance()->getOrder($id);
        $order = $orderEntity->resolve();

        if(!$request->user()?->can('view', $orderEntity)){
            abort(403);
        }

        $shop = $orderEntity->getShop();
        $shopName = $shop?->getName();

        $data = [
            'id' => $id,
            'entity' => $orderEntity,
            'info' => [
                'ID' => $order['id'],
                'Number' => $order['number'],
                'Payload' => $order['payload'],
                'Shop' => [
                    'text' => $order['shop_id'] . ($shopName ? " ($shopName)" : ''),
                    'link' => $shop ? route('admin.shop.show', $shop->getId()) : null,
                ],
                'Card' => [
                    'text' => $order['card_id'],
                    'link' => route('admin.card.show', $order['card_id']),
                ],
                'Payment method' => Order::PAYMENT_METHODS[$order['payment_method']] ?? '?',
                'Amount' => $order['amount'],
                'Date paid (for completed/failed)' => $order['date_paid'],
                'Status' => $order['status'],
            ],
            'alerts' => [],
            'page_title' => 'Order info',
            'content_title' => 'Order info: #' . $order['id'],
        ];

        if(!$request->user()?->can('view', CardService::instance()->getCard($order['card_id']))){
            unset($data['info']['Card']);
        }

        return view('admin.crud.order.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $orderEntity = OrderService::instance()->getOrder($id);
        $order = $orderEntity->resolve();

        if(!$request->user()?->can('update', $orderEntity)) {
            abort(403);
        }

        $data = [
            'page_title' => 'Edit order',
            'content_title' => 'Edit order: #' . $order['id'],
            'alerts' => [],
            'id' => $id,
            'fields' => [
                [
                    'name' => 'order_number',
                    'label' => 'Number',
                    'type' => 'text',
                    'placeholder' => null,
                    'value' => $order['number'],
                    'disabled' => true,
                ],
                [
                    'name' => 'amount',
                    'label' => 'Amount',
                    'type' => 'text',
                    'placeholder' => null,
                    'value' => $order['amount'],
                    'disabled' => true,
                ],
                [
                    'name' => 'order_status',
                    'label' => 'Order status',
                    'type' => 'select',
                    'options' => array_map(function($status){
                        return ['value' => $status, 'label' => Order::STATUSES[$status]];
                    }, array_keys(Order::STATUSES)),
                    'value' => $order['status'],
                    'description' => 'If you complete the order manually, do not forget to increase the card balance!',
                ]
            ],
            'form' => ['action' => route('admin.order.update', $id)],
        ];
        return view('admin.crud.layout.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $orderEntity = OrderService::instance()->getOrder($id);
        if(is_null($orderEntity)){
            return redirect(route('admin.order.index'))
                ->with('error', "Order #$id not found!");
        }
        $order = $orderEntity->resolve();

        if(!$request->user()?->can('update', $orderEntity)) {
            abort(403);
        }

        $rules = array(
            'order_status' => ['required', Rule::in(array_keys(Order::STATUSES))],
        );
        $validator = Validator::make($request->all(), $rules);

        // process the login
        if ($validator->fails()) {
            return redirect(route('admin.order.edit', $id))
                ->withErrors($validator)
                ->withInput();
        }else{
            $order['status'] = $request->input('order_status');

            $order = OrderService::instance()->updateOrder($order);
            if(is_null($order)){
                return redirect(route('admin.order.edit', $id))
                    ->with('error', "Error while updating order!");
            }
            $id = $order['id'];
            return redirect(route('admin.order.edit', $id))
                ->with('message', "Successfully updated order #$id!");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        abort(404);
    }
}
