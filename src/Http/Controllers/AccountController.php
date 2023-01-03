<?php

namespace dnj\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use dnj\Account\AccountManager;
use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Http\Requests\AccountRequest;
use dnj\Account\Http\Requests\CreateNewAccountRequest;
use dnj\Account\Http\Resources\AccountResource;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class AccountController extends Controller {
	public AccountManager $account_manager;
	
	public function __construct ( AccountManager $account_manager ) {
		$this->account_manager = $account_manager;
	}
	
	public function create ( CreateNewAccountRequest $request ) {
		dd("asdsa");
		$title = $request->get('title');
		$currency_id = $request->get('currency_id');
		$can_send = $request->get('can_send');
		$can_receive = $request->get('can_receive');
		$meta = $request->get('meta');
		$user_id = auth()->user() ? auth()->user()->id : null;
		$account = $this->account_manager->create($title , $currency_id , $user_id , AccountStatus::ACTIVE , $can_send , $can_receive , $meta);
		$account = AccountResource::make($account->load([
															'user' ,
															'currency' ,
															'holdings' ,
														]));
		
		return response()->json(compact('account'));
	}
	
	public function update ( AccountRequest $request ) {
		try {
			$account_id = $request->get('account_id');
			$data = $request->only([
									   'title' ,
									   'user_id' ,
									   'currency_id' ,
									   'canSend' ,
									   'canReceive' ,
									   'meta' ,
								   ]);
			if ( auth()->check() ) {
				$data[ 'user_id' ] = auth()->user()->id;
			}
			$this->account_manager->update($account_id , $data);
			$account = $this->account_manager->getByID($account_id);
			$account = AccountResource::make($account->load([
																'user' ,
																'currency' ,
																'holdings' ,
															]));
			
			return response()->json(compact('account'));
		}
		catch ( ModelNotFoundException $exception ) {
			abort(404);
		}
	}
	
	public function destroy ( AccountRequest $request ) {
		try {
			$account_id = $request->get('account_id');
			$this->account_manager->delete($account_id);
			
			return response()->json([
										'message' => 'Account deleted successfully' ,
									]);
		}
		catch ( ModelNotFoundException $exception ) {
			abort(404);
		}
	}
	
	public function filter ( Request $request ) {
		
		if ( auth()->check() ) {
			$user_id = auth()->user()->id;
		}
		else {
			$user_id = null;
		}
		$collection = $this->account_manager->findByUser($user_id);
		$collection = $collection->load([
											'user' ,
											'currency' ,
											'holdings' ,
										]);
		
		return response()->json([
									'accounts' => $collection ,
								]);
	}
}