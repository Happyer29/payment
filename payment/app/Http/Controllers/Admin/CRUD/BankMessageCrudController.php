<?php

namespace App\Http\Controllers\Admin\CRUD;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bank\BankMessage;
use App\Services\Bank\BankMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankMessageCrudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if(!$request->user()?->isAnyManager()){
            abort(403);
        }

        $page = max(1, intval($request->get('page', 1)));
        $size = max(1, intval($request->get('size', BankMessage::PER_PAGE)));

        $filters = BankMessageService::instance()->getFilters('index', $request);
        $info = BankMessageService::instance()->findMessages($page, $size, $filters);

        $messages = $info['bank_messages'] ?? [];

        $items = [];
        foreach($messages as $msgEntity){
            /** @var BankMessage $msgEntity */
            $items[] = [
                'id' => $msgEntity->getId(),
                'entity' => $msgEntity,
                'data' => [
                    $msgEntity->getId(),
                    $msgEntity->getOrderId() ? [
                        'text' => $msgEntity->getOrderId(),
                        'link' => route('admin.order.show', $msgEntity->getOrderId()),
                    ] : $msgEntity->getOrderId(),
                    $msgEntity->getShopId() ? [
                        'text' => $msgEntity->getShopId(),
                        'link' => route('admin.shop.show', $msgEntity->getShopId()),
                    ] : $msgEntity->getShopId(),
                    $msgEntity->getCreatedAt() ? date('d.m.Y H:i:sP', $msgEntity->getCreatedAt()) : '?',
                    $msgEntity->getSenderPhone() ?: '?',
                    $msgEntity->getReceiverPhone() ?: '?',
                    //substr($msgEntity->getRawMessage(), 0, 200),
                    $msgEntity->getCardNumber(),
                    number_format($msgEntity->getAmount(), 2) . ' AZN',
                    substr($msgEntity->getError(), 0, 120),
                    $msgEntity->getFormattedStatus(),
                ],
            ];
        }
        $data = [
            'page_title' => 'Bank messages',
            'content_title' => 'Bank messages',
            'paginate' => [
                'total' => $info['total'] ?? count($items),
                'page' => $page,
                'size' => $size,
            ],
            'items' => $items,
            //'datatable_config' => [],
            'heads' => [
                'ID' => null,
                'Order' => null,
                'Shop' => null,
                'Created' => null,
                'Sender' => null,
                'Receiver' => null,
                //'Message' => null,
                'Card' => null,
                'Amount' => null,
                'Error' => null,
                'Status' => null,
            ],
            'actions' => [
//                'view' => 'admin.bank_message.show',
                'view_message' => [
                    'title' => 'View',
                    'icon' => 'fas fa-fw fa-eye',
                    'btn_class' => 'text-info',
                    'func' => fn(BankMessage $msg) => route('admin.bank_message.show', $msg->getId()),
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
        $msg = BankMessageService::instance()->getMessage($id);

        if(!$request->user()?->isAnyManager()){
            abort(403);
        }

        $orderId = $msg->getOrderId();
        $shopId = $msg->getShopId();
        $data = [
            'id' => $id,
            'entity' => $msg,
            'info' => [
                'ID' => $msg->getId(),
                'Order' => $orderId ? [
                    'text' => $orderId,
                    'link' => route('admin.order.show', $orderId),
                ] : $orderId,
                'Shop' => $shopId ? [
                    'text' => $shopId,
                    'link' => route('admin.shop.show', $shopId),
                ] : $shopId,
                'Card' => $msg->getCardNumber(),
                'Date' => date('d.m.Y H:i:sP', $msg->getCreatedAt()),
                'Sender' => $msg->getSenderPhone(),
                'Receiver' => $msg->getReceiverPhone(),
                'Message' => $msg->getRawMessage(),
                'Amount' => number_format($msg->getAmount(), 2) . ' AZN',
                'Error' => $msg->getError(),
                'Status' => $msg->getFormattedStatus(),
            ],
            'alerts' => [],
            'page_title' => 'Bank message',
            'content_title' => 'Bank message: #' . $msg->getId(),
        ];

        return view('admin.crud.bank_message.show', $data);
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

    public function approve(Request $request, int $id): JsonResponse
    {
        if(!$request->user()?->can('approve-bank-messages')){
            abort(403);
        }

        $amount = floatval($request->input('amount'));
        if($amount <= 0){
            return response()->json([
                'success' => false,
                'error' => 'Amount is required and must be positive',
            ]);
        }

        $res = BankMessageService::instance()->approve($id, $amount);
        if(is_null($res)){
            $res = [
                'success' => false,
                'error' => 'Unexpected server error',
            ];
        }

        return response()->json($res);
    }

    public function decline(Request $request, int $id): JsonResponse
    {
        if(!$request->user()?->can('decline-bank-messages')){
            abort(403);
        }

        $res = BankMessageService::instance()->decline($id);
        if(is_null($res)){
            $res = [
                'success' => false,
                'error' => 'Unexpected server error',
            ];
        }

        return response()->json($res);
    }
}
