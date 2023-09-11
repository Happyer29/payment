<?php

namespace App\Http\Controllers\Admin\CRUD;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bank\PaymentLink;
use App\Services\Bank\OrderService;
use App\Services\Bank\PaymentLinkService;
use Illuminate\Http\Request;

class PaymentLinkCrudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // int $page = 1, int $size = PaymentLink::PER_PAGE
        $page = max(1, intval($request->get('page', 1)));
        $size = max(1, intval($request->get('size', PaymentLink::PER_PAGE)));

//        $user = auth()->user();
//        $viewAny = $user->can('viewAny', PaymentLink::class);
//        $manage = $user->can('moderateAny', PaymentLink::class);
//        if($viewAny){
//            $info = OrderService::instance()->getLinks($page, $size, "DESC");
//        }else{
//            $info = OrderService::instance()->getLinks($page, $size, "DESC", $user?->id);
//        }
        $filters = PaymentLinkService::instance()->getFilters('index', $request);
        $info = PaymentLinkService::instance()->findLinks($page, $size, $filters);

        $links = $info['payment_links'] ?? [];

        $items = [];
        foreach($links as $linkEntity){
            /** @var $linkEntity PaymentLink */
            $link = $linkEntity->resolve();
            $shop = $linkEntity->getShop();

            $orderNumber = $linkEntity->getOrder()?->getNumber();
            $shopName = $shop?->getName();

            $items[] = [
                'id' => $link['id'],
                'entity' => $linkEntity,
                'data' => [
                    $link['id'],
                    [
                        'text' => $link['order_id'] . ($orderNumber ? " ($orderNumber)" : ''),
                        'link' => route('admin.order.show', $link['order_id']),
                    ],
                    [
                        'text' => $shop ? (
                            $shop->getId() . ($shopName ? " ($shopName)" : '')
                        ) : '?',
                        'link' => $shop ? route('admin.shop.show', $shop->getId()) : '',
                    ],
                    $link['card_type'],
                    $link['amount'],
                    //$link['url'],
                    $link['transaction_id'],
                    $link['date_paid'],
                    $link['status'],
                ],
            ];
        }
        $data = [
            'page_title' => 'Payment links',
            'content_title' => 'Payment links',
            'paginate' => [
                'total' => $info['total'] ?? 0,
                'page' => $page,
                'size' => $size,
            ],
            'items' => $items,
            //'datatable_config' => [],
            'heads' => [
                'ID' => null,
                'Order' => null,
                'Shop' => null,
                'Card type' => null,
                'Amount' => null,
                //'URL' => null,
                'Transaction ID' => null,
                'Date paid' => null,
                'Status' => null,
            ],
            'actions' => [
                'view' => 'admin.payment_link.show',
                'orders' => [
                    'title' => 'View orders',
                    'icon' => 'fas fa-fw fa-shopping-basket',
                    'btn_class' => 'text-info',
                    'func' => fn(PaymentLink $link) => route('admin.order.index', ['order_id' => $link->getOrderId()]),
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
        $linkEntity = OrderService::instance()->getLink($id);
        $link = $linkEntity->resolve();

        if(!$request->user()?->can('view', $linkEntity)){
            abort(403);
        }

        $shop = $linkEntity->getShop();
        $orderNumber = $linkEntity->getOrder()?->getNumber();
        $shopName = $shop?->getName();

        $data = [
            'id' => $id,
            'entity' => $linkEntity,
            'info' => [
                'ID' => $link['id'],
                'Order' => [
                    'text' => $link['order_id'] . ($orderNumber ? " ($orderNumber)" : ''),
                    'link' => route('admin.order.show', $link['order_id']),
                ],
                'Shop' => [
                    'text' => $shop ? (
                        $shop->getId() . ($shopName ? " ($shopName)" : '')
                    ) : '?',
                    'link' => $shop ? route('admin.shop.show', $shop->getId()) : '#',
                ],
                'URL' => [
                    'text' => $link['url'],
                    'link' => $link['url'] ?: null,
                    'target' => '_blank',
                ],
                'Transaction ID' => $link['transaction_id'],
                'Card type' => $link['card_type'],
                'Amount' => $link['amount'],
                'Date paid' => $link['date_paid'],
                'Status' => $link['status'],
            ],
            'alerts' => [],
            'page_title' => 'Payment link info',
            'content_title' => 'Payment link info: #' . $link['id'],
        ];
        return view('admin.crud.payment_link.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        abort(404);
    }
}
