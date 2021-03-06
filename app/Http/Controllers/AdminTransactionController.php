<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use App\PaymentMethod;
use App\Mail\PaymentAccepted;

use Mail;
use Carbon\Carbon;

class AdminTransactionController extends Controller
{
	public function __construct(){
    	$this->middleware('isAdmin');
    }
    
    //view all transaction
    public function viewTransactions(){
    	$all_transactions = Transaction::orderBy('updated_at', 'desc')->get();
    	$data = array(
    		'all_transactions' => $all_transactions,
    	);
    	return view('admin.transaction_list')->with($data);
    }

    //view detail transaction
    public function viewDetailTransaction($id){
        $transaction = Transaction::where('id_transaction', $id)->first();
        $data = array(
            'transaction' => $transaction,
        );
        return view('admin.detail_transaction')->with($data);
    }

    //accept payment
    public function acceptPayment($id){
        $transaction = Transaction::where('id_transaction', $id)->first();
        $transaction->status = 2;
        $transaction->save();

        Mail::to($transaction->user->email)->send(new PaymentAccepted($transaction->user, $transaction));

        return back();
    }

    //reject payment
    public function rejectPayment($id){
        $transaction = Transaction::where('id_transaction', $id)->first();
        $transaction->status = 0;
        $transaction->save();
        
        $transaction->payment->forceDelete();
        return back();
    }

    public function viewAddPaymentMethod()
    {
        $payment_methods = PaymentMethod::all();
        $data = array(
            'payment_methods' => $payment_methods,
        );
        return view('admin.payment_methods')->with($data);   
    }

    public function addPaymentMethod(Request $request)
    {
    	$this->validate($request, [
	        'payment_method_name'  => 'required',
	        'payment_method_photo' => 'required|image',
	        'account_number'       => 'required',
	        'account_name'         => 'required',
	    ]);
        
        $payment_method                      = new PaymentMethod();
        $payment_method->payment_method_name = $request->payment_method_name;
        $payment_method->account_number      = $request->account_number;
        $payment_method->account_name        = $request->account_name;
        $payment_method->description         = $request->description;

        $ext = $request->file('payment_method_photo')->getClientOriginalExtension();
        $payment_method_photo = $request->file('payment_method_photo')->storeAs('public/images/payment_methods', 
                            $payment_method->payment_method_name . "-bank." . $ext);
            
        $payment_method->payment_method_photo = $payment_method_photo;
        $payment_method->save();

        return redirect()->route('view_add_payment_method');
    }
}
