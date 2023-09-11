<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function index()
    {
        return redirect('login');
//        return view("site.index", [
//            "title" => "payment-ae",
//        ]);
    }

    public function paymentForm()
    {
        return view('site/payment_form');
    }

//    public function approvePayment(Request $request)
//    {
//        $order_number = $request->input('order_number');
//        $success = false;
//        if($request->hasFile('approve')){
//            $file = $request->file('approve');
//            if($file->getSize() <= 2 * 1024 * 1024){ // в пределах 2Мб
//                $path = $file->storeAs('approve', "order-$order_number-approve." . $file->extension(), 'local');
//                $success = !!$path;
//            }
//        }
//        echo json_encode([
//            'success' => $success,
//        ]);
//    }
}
