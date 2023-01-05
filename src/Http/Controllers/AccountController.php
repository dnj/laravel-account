<?php

namespace dnj\Account\Http\Controllers;

use dnj\Account\AccountManager;
use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Http\Requests\AccountRequest;
use dnj\Account\Http\Requests\CreateNewAccountRequest;
use dnj\Account\Http\Resources\AccountResource;
use dnj\Account\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller {
	protected AccountManager $accountManager;
	
	public function __construct ( AccountManager $accountManager ) {
		$this->accountManager = $accountManager;
	}
	
	public function index ( Request $request ) {
		$collection = $this->accountManager->findByUser(auth()->user()->id);
		
		return AccountResource::collection($collection);
	}
	
	public function store ( CreateNewAccountRequest $request ) {
		$title = $request->get('title');
		$currency_id = $request->get('currency_id');
		$can_send = $request->get('can_send');
		$can_receive = $request->get('can_receive');
		$meta = $request->get('meta');
		$user_id = auth()->user() ? auth()->user()->id : null;
		$account = $this->accountManager->create($title , $currency_id , $user_id , AccountStatus::ACTIVE , $can_send , $can_receive , $meta);
		
		return AccountResource::make($account);
	}
	
	public function update ( Account $account , AccountRequest $request ) {
		$data = $request->only([
								   'title' ,
								   'user_id' ,
								   'currency_id' ,
								   'canSend' ,
								   'canReceive' ,
								   'meta' ,
							   ]);
		$data[ 'user_id' ] = auth()->user()->id;
		$this->accountManager->update($account->id , $data);
		
		return AccountResource::make($account);
	}
	
	public function destroy ( Account $account ) {
		$this->accountManager->delete($account->id);
		
		return response()->json([
									'message' => 'Account deleted successfully' ,
								]);
	}
}