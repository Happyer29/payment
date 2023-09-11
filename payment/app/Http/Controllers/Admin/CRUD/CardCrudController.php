<?php

namespace App\Http\Controllers\Admin\CRUD;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bank\Card;
use App\Services\Bank\CardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use function Webmozart\Assert\Tests\StaticAnalysis\float;

class CardCrudController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if(!$request->user()?->can('viewAny', Card::class)){
            abort(403);
        }

        $types = [Card::TypeWithNumber, Card::TypeWithPhone];
        if($request->get('type') === Card::TypeWithPhone){
            $types = [Card::TypeWithPhone];
        }elseif($request->get('type') === Card::TypeWithNumber){
            $types = [Card::TypeWithNumber];
        }

        $page = max(1, intval($request->get('page', 1)));
        $size = max(1, intval($request->get('size', Card::PER_PAGE)));

        $info = CardService::instance()->getCards($page, $size);
        $cards = $info['cards'] ?? [];

        $items = [];
        foreach($cards as $cardEntity){
            /** @var $cardEntity Card */
            if(!in_array($cardEntity->getType(), $types)){
                continue;
            }

            $card = $cardEntity->resolve();
            $items[] = [
                'id' => $card['id'],
                'entity' => $cardEntity,
                'data' => [
                    'id' => $card['id'],
                    'phone_prefix' => $card['phone_prefix'],
                    'phone_number' => $card['phone_number'],
                    'card_number' => $card['card_number'],
                    'balance' => !is_null($card['balance']) ? number_format($card['balance'], 5) : '?',
                    'status' => $card['status'],
                ],
            ];
        }
        $data = [
            'page_title' => 'Cards',
            'content_title' => 'Cards',
            'paginate' => [
                'total' => $info['total'] ?? 0,
                'page' => $page,
                'size' => $size,
            ],
            'items' => $items,
            //'datatable_config' => [],
            'heads' => [
                'ID' => null,
                'Phone prefix' => null,
                'Phone number' => null,
                'Card number' => null,
                'Balance (refresh to view actual)' => null,
                'Status' => ['searchable' => false, 'orderable' => false],
            ],
            'actions' => [
                'edit' => 'admin.card.edit',
                'delete' => 'admin.card.destroy',
                'view' => 'admin.card.show',
            ],
            'cards' => implode(',', array_map(fn($item) => $item['id'], $items)),
        ];
        return view('admin.crud.card.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if(!$request->user()?->can('create', Card::class)){
            abort(403);
        }

        $data = [
            'page_title' => 'Create card',
            'content_title' => 'Create card',
            'form' => ['action' => route('admin.card.store')],
            'fields' => [
                [
                    'name' => 'card_type',
                    'label' => 'Card type (select identifier)',
                    'type' => 'select',
                    'options' => [
                        ['value' => Card::TypeWithPhone, 'label' => 'Phone number'],
                        ['value' => Card::TypeWithNumber, 'label' => 'Card number'],
                    ],
                ],
                [
                    'name' => 'card_phone_prefix',
                    'label' => 'Phone prefix',
                    'type' => 'text',
                    'placeholder' => null,
                ],
                [
                    'name' => 'card_phone_number',
                    'label' => 'Phone number',
                    'type' => 'text',
                    'placeholder' => null,
                ],
                [
                    'name' => 'card_number',
                    'label' => 'Card number',
                    'type' => 'text',
                    'placeholder' => null,
                ],
            ],
        ];
        return view('admin.crud.card.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(!$request->user()?->can('create', Card::class)){
            abort(403);
        }

        // проверим тип карты
        $card_type = $request->input('card_type');
        $rules = ['card_type' => ['required', Rule::in([Card::TypeWithNumber, Card::TypeWithPhone])]];
        $validator = Validator::make(['card_type' => $card_type], $rules);
        if($validator->fails()){
            return redirect(route('admin.card.create'))
                ->withErrors($validator)
                ->withInput();
        }

        // проверим телефон или номер карты
        if($card_type === Card::TypeWithPhone){
            $data = [
                'type' => Card::TypeWithPhone,
                'phone_prefix' => $request->input('card_phone_prefix'),
                'phone_number' => $request->input('card_phone_number'),
            ];
            $rules = [
                'phone_prefix' => ['required', 'regex:/^[0-9]{5}$/'],
                'phone_number' => ['required', 'regex:/^[0-9]{7}$/'],
            ];
        }else{
            $data = [
                'type' => Card::TypeWithNumber,
                'card_number' => preg_replace('/[^0-9]+/', '', $request->input('card_number')),
            ];
            $rules = [
                'card_number' => ['required', 'regex:/^[0-9]+$/'],
            ];
        }
        $validator = Validator::make($data, $rules);

        // process the login
        if ($validator->fails()) {
            return redirect(route('admin.card.create'))
                ->withErrors($validator)
                ->withInput();
        }else{
            $card = Card::make($data)->resolve();
            $new = CardService::instance()->createCard($card);
            if(is_null($new)){
                return redirect(route('admin.card.index'))
                    ->with('error', "Error while creating card!");
            }
            $id = $new['id'];
            return redirect(route('admin.card.index'))
                ->with('message', "Successfully created card #$id!");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id)
    {
        $cardEntity = CardService::instance()->getCard($id);
        $card = $cardEntity->resolve();

        if(!$request->user()?->can('view', $cardEntity)){
            abort(403);
        }

        $data = [
            'id' => $id,
            'entity' => $cardEntity,
            'info' => [
                'ID' => $card['id'],
                'Balance' => $card['balance'],
                'Phone prefix' => $card['phone_prefix'],
                'Phone number' => $card['phone_number'],
                'Card number' => $card['card_number'],
                'Status' => $card['status'],
            ],
            'alerts' => [],
            'page_title' => 'Card info',
            'content_title' => 'Card info: ' . $card['id'],
            'show_change_balance' => false,
        ];

        if(is_null($cardEntity->getBalance())){
            unset($data['info']['Balance']);
            $data['alerts'][] = [
                'type' => 'danger',
                'message' => 'Unable to load card balance.',
            ];
        }else{
            $data['show_change_balance'] = true;
        }

        return view('admin.crud.card.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, int $id)
    {
        $cardEntity = CardService::instance()->getCard($id);
        $card = $cardEntity->resolve();

        if(!$request->user()?->can('update', $cardEntity)){
            abort(403);
        }

        $data = [
            'page_title' => 'Edit card',
            'content_title' => 'Edit card #' . $card['id'],
            'alerts' => [],
            'form' => ['action' => route('admin.card.update', $id)],
            'id' => $id,
            'entity' => $cardEntity,
            'fields' => [],
        ];

        if($cardEntity->isType(Card::TypeWithPhone)){
            $data['fields'][] = [
                'name' => 'card_phone_prefix',
                'label' => 'Phone prefix',
                'type' => 'text',
                'placeholder' => null,
                'value' => $card['phone_prefix'],
            ];
            $data['fields'][] = [
                'name' => 'card_phone_number',
                'label' => 'Phone number',
                'type' => 'text',
                'placeholder' => null,
                'value' => $card['phone_number'],
            ];
        }else{
            $data['fields'][] = [
                'name' => 'card_number',
                'label' => 'Card number',
                'type' => 'text',
                'placeholder' => null,
                'value' => $card['card_number'],
            ];
        }

        $data['fields'][] = [
            'name' => 'card_status',
            'label' => 'Card status',
            'type' => 'select',
            'options' => [
                ['value' => 'enabled', 'label' => 'Enabled'],
                ['value' => 'disabled', 'label' => 'Disabled'],
            ],
            'value' => $card['status'],
        ];

        return view('admin.crud.card.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $cardEntity = CardService::instance()->getCard($id);
        if(is_null($cardEntity)){
            return redirect(route('admin.card.index'))
                ->with('error', "Card #$id not found!");
        }

        if(!$request->user()?->can('update', $cardEntity)){
            abort(403);
        }

        // проверим телефон или номер карты
        if($cardEntity->isType(Card::TypeWithPhone)){
            $data = [
                'type' => Card::TypeWithPhone,
                'phone_prefix' => $request->input('card_phone_prefix'),
                'phone_number' => $request->input('card_phone_number'),
                'status' => $request->input('card_status'),
            ];
            $rules = [
                'phone_prefix' => ['required', 'regex:/^[0-9]{5}$/'],
                'phone_number' => ['required', 'regex:/^[0-9]{7}$/'],
                'status' => ['required', Rule::in(['enabled', 'disabled'])],
            ];
        }else{
            $data = [
                'type' => Card::TypeWithNumber,
                'card_number' => preg_replace('/[^0-9]+/', '', $request->input('card_number')),
                'status' => $request->input('card_status'),
            ];
            $rules = [
                'card_number' => ['required', 'regex:/^[0-9]+$/'],
                'status' => ['required', Rule::in(['enabled', 'disabled'])],
            ];
        }
        $validator = Validator::make($data, $rules);

        // process the login
        if ($validator->fails()) {
            return redirect(route('admin.card.edit', $id))
                ->withErrors($validator)
                ->withInput();
        }else{
            $card = $cardEntity->resolve();
            foreach($data as $key => $value){
                $card[$key] = $value;
            }
            $card = CardService::instance()->updateCard($card);
            if(is_null($card)){
                return redirect(route('admin.card.edit', $id))
                    ->with('error', "Error while updating card!");
            }
            $id = $card['id'];
            return redirect(route('admin.card.edit', $id))
                ->with('message', "Successfully updated card #$id!");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $id)
    {
        $cardEntity = CardService::instance()->getCard($id);
        if(!$request->user()?->can('delete', $cardEntity)){
            abort(403);
        }

        $error = CardService::instance()->deleteCard($id);
        if($error){
            return redirect()->back()
                ->withErrors(['bank_api' => $error])
                ->with('error', $error);
        }
        return redirect()->back()
            ->with('message', "Card #$id deleted!");
    }

    public function checkActivity(Request $request)
    {
        if(!$request->user()?->can('viewAny', Card::class)){
            abort(403);
        }

        $ids = $request->input('ids');
        $ids = array_map('intval', explode(',', $ids));
        $ids = array_filter($ids, fn($id) => $id > 0);
        if(count($ids) !== 0){
            $res = CardService::instance()->checkActivity($ids);
            if($res){
                echo json_encode($res);
            }
            exit;
        }
        exit;
    }

    public function changeBalance(Request $request, int $id) {
        $card = CardService::instance()->getCard($id);
        if(!$card){
            abort(404);
        }

        if(!$request->user()?->can('update', $card)){
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'operation' => 'required|string|in:increase,decrease',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // process the login
        if ($validator->fails()) {
            return redirect(route('admin.card.show', $id))
                ->withErrors($validator)
                ->withInput();
        }else{
            $operation = (string) $request->input('operation');
            $amount = floatval($request->input('amount'));

            $result = CardService::instance()->changeBalance($card->getId(), $operation, $amount);
            $success = $result['success'] ?? false;
            if(!$success or isset($result['error'])){
                return redirect(route('admin.card.show', $id))
                    ->with('error', $result['error'] ?? "Error while changing balance!");
            }
            $id = $card->getId();
            return redirect(route('admin.card.show', $id))
                ->with('message', "Successfully changed balance for card #$id!");
        }
    }
}
