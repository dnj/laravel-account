<?php

namespace dnj\Account\Http\Controllers;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Http\Requests\AccountSearchRequest;
use dnj\Account\Http\Requests\AccountStoreRequest;
use dnj\Account\Http\Requests\AccountUpdateRequest;
use dnj\Account\Http\Resources\AccountResource;
use dnj\Account\Models\Account;
use dnj\UserLogger\Contracts\ILogger;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function __construct(
        protected IAccountManager $accountManager,
        protected ILogger $userLogger
    ) {
    }

    public function index(AccountSearchRequest $request)
    {
        $data = $request->validated();
        $q = Account::query();
        $q->orderBy('id', 'ASC');
        if (isset($data['title'])) {
            $q->where('title', 'like', "%{$data['title']}%");
        }
        if (isset($data['currency_id'])) {
            $q->where('currency_id', $data['currency_id']);
        }
        if (isset($data['user_id'])) {
            $q->where('user_id', $data['user_id']);
        }
        if (isset($data['can_send'])) {
            $q->where('can_send', $data['can_send']);
        }
        if (isset($data['can_receive'])) {
            $q->where('can_receive', $data['can_receive']);
        }
        if (isset($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (isset($data['created_from'])) {
            $q->where('created_at', '>=', $data['created_from']);
        }
        if (isset($data['created_to'])) {
            $q->where('created_at', '<', $data['created_to']);
        }
        if (isset($data['balance_from'])) {
            $q->where('balance', '>=', $data['balance_from']);
        }
        if (isset($data['balance_to'])) {
            $q->where('balance', '<', $data['balance_to']);
        }
        $q = $q->cursorPaginate();

        return AccountResource::collection($q);
    }

    public function store(AccountStoreRequest $request)
    {
        $data = $request->validated();
        $account = $this->accountManager->create(
            $data['title'],
            $data['currency_id'],
            $data['user_id'],
            AccountStatus::ACTIVE,
            $data['can_send'],
            $data['can_receive'],
            $data['meta'],
            true
        );

        return AccountResource::make($account);
    }

    public function update(Account $account, AccountUpdateRequest $request)
    {
        $data = $request->validated();
        $changes = [];
        foreach ($data as $key => $value) {
            $changes[Str::camel($key)] = $value;
        }

        $account = $this->accountManager->update($account->id, $changes, true);

        return AccountResource::make($account);
    }

    public function destroy(Account $account)
    {
        $changes = $account->toArray();
        $this->accountManager->delete($account->id, true);

        return response()->noContent();
    }
}
