<?php

namespace App\Http\Controllers\Admin\CRUD;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bank\Withdraw;
use App\Models\User;
use App\Services\Admin\UserService;
use App\Services\Bank\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawCrudController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // int $page = 1, int $size = PaymentLink::PER_PAGE
        $page = max(1, intval($request->get('page', 1)));
        $size = max(1, intval($request->get('size', Withdraw::PER_PAGE)));

        $user = auth()->user();
        $viewAny = $user->can('viewAny', Withdraw::class);
        $manage = $user->can('moderateAny', Withdraw::class);
//        if($viewAny){
//            $info = OrderService::instance()->getOrders($page, $size, "DESC");
//        }else{
//            $info = OrderService::instance()->getOrders($page, $size, "DESC", $user?->id);
//        }

        $filters = WithdrawService::instance()->getFilters('index', $request);
        $info = WithdrawService::instance()->findWithdraws($page, $size, $filters);

        //$info = OrderService::instance()->getOrders($page, $size, "DESC");
        $withdraws = $info['withdraws'] ?? [];

        $items = [];
        foreach($withdraws as $withdraw){
            /** @var Withdraw $withdraw */
            $shopName = $withdraw->getShop()?->getName();

            $data = [$withdraw->getId()];
            if($manage){
                $data[] = $withdraw->getShop()->getOwnerId();
            }
            $data = array_merge($data, [
                $withdraw->getNumber(),
                [
                    'text' => $withdraw->getShopId() . ($shopName ? " ($shopName)" : ''),
                    'link' => route('admin.shop.show', $withdraw->getShopId()),
                ],
                Withdraw::TYPES[$withdraw->getType()],
                $withdraw->getRecipient(),
                $withdraw->getAmount(),
                date('d.m.Y H:i:s P', $withdraw->getCreatedAt()),
                $withdraw->getUpdatedAt()
                    ? date('d.m.Y H:i:s P', $withdraw->getUpdatedAt())
                    : '',
                $withdraw->getFinishedAt()
                    ? date('d.m.Y H:i:s P', $withdraw->getFinishedAt())
                    : '',
                Withdraw::STATUSES[$withdraw->getStatus()],
            ]);

            $items[] = [
                'id' => $withdraw->getId(),
                'entity' => $withdraw,
                'data' => $data,
            ];
        }
        $data = [
            'page_title' => 'Withdrawals',
            'content_title' => 'Withdrawals',
            'paginate' => [
                'total' => $info['total'] ?? 0,
                'page' => $page,
                'size' => $size,
            ],
            'items' => $items,
            //'datatable_config' => [],
            'heads' => [
                'ID' => null,
                'Owner ID' => null,
                'Number' => null,
                'Shop' => null,
                'Type' => null,
                'Recipient' => null,
                'Amount' => null,
                'Date created' => null,
                'Last update' => null,
                'Date finished' => null,
                'Status' => null,
            ],
            'actions' => [
                'edit' => $manage ? 'admin.withdraw.edit' : null,
                'view' => 'admin.withdraw.show',
            ],
            'filters' => $filters,
        ];

        if(!$manage){
            unset($data['heads']['Owner ID']);
        }

        return view('admin.crud.layout.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
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
        $withdraw = WithdrawService::instance()->getWithdraw($id);

        if(!$request->user()?->can('view', $withdraw)){
            abort(403);
        }
        $manage = !!$request->user()?->can('manage', $withdraw);

        $shop = $withdraw->getShop();
        $owner = User::find($shop->getOwnerId());
        $shopName = $shop->getName();
        $ownerEmail = $owner?->email ?: '';

        $data = [
            'id' => $id,
            'entity' => $withdraw,
            'info' => [
                'ID' => $withdraw->getId(),
                'Number' => $withdraw->getNumber(),
                'Owner' => [
                    'text' => sprintf("#%d: %s", $shop->getOwnerId(), $ownerEmail),
                    'link' => $owner ? route('admin.user.show', $shop->getOwnerId()) : null,
                ],
                'Shop' => [
                    'text' => $withdraw->getShopId() . ($shopName ? " ($shopName)" : ''),
                    'link' => route('admin.shop.show', $shop->getId()),
                ],
                'Type' => Withdraw::TYPES[$withdraw->getType()],
                'Amount' => number_format($withdraw->getAmount(), 2),
                'Card number' => $withdraw->getCardNumber(),
                'Card expiration' => $withdraw->getCardExpirationDate(),
                'Phone' => $withdraw->getPhone(),
                'Date created' => date('d.m.Y H:i:s P', $withdraw->getCreatedAt()),
                'Date of last update' => $withdraw->getUpdatedAt()
                    ? date('d.m.Y H:i:s P', $withdraw->getUpdatedAt())
                    : '',
                'Date finished (for completed/failed)' => $withdraw->getFinishedAt()
                    ? date('d.m.Y H:i:s P', $withdraw->getFinishedAt())
                    : '',
                'Status' => Withdraw::STATUSES[$withdraw->getStatus()],
            ],
            'alerts' => [],
            'page_title' => 'Withdrawal info',
            'content_title' => sprintf('Withdrawal #%d (%s)', $withdraw->getId(), $withdraw->getNumber()),
        ];

        if(!$manage){
            unset($data['info']['Owner']);
        }

        if($withdraw->isWaitingForApproval()){
            $data['alerts'][] = [
                'type' => 'warning',
                'message' => 'This withdraw is in processing by someone.'
                    . ' If it\'s not you, contact your colleagues.'
                    . ' Maybe someone is already transferring money.'
            ];
        }

        return view('admin.crud.withdraw.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
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
    public function destroy(Request $request, string $id)
    {
        abort(404);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        if(!$request->user()?->can('manageAny', Withdraw::class)){
            abort(403);
        }

        $res = WithdrawService::instance()->approve($id);
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
        if(!$request->user()?->can('manageAny', Withdraw::class)){
            abort(403);
        }

        $res = WithdrawService::instance()->decline($id);
        if(is_null($res)){
            $res = [
                'success' => false,
                'error' => 'Unexpected server error',
            ];
        }

        return response()->json($res);
    }

    public function process(Request $request, int $id): JsonResponse
    {
        if(!$request->user()?->can('manageAny', Withdraw::class)){
            abort(403);
        }

        $res = WithdrawService::instance()->process($id);
        if(is_null($res)){
            $res = [
                'success' => false,
                'error' => 'Unexpected server error',
            ];
        }

        return response()->json($res);
    }
}
