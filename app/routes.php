<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


Route::get('/', function()
{

    $count = count(User::all());

    if($count == 0 ){

        return View::make('signup');
    }


	if (Confide::user()) {
            return Redirect::to('/dashboard');
        } else {
            return View::make('login');
        }
});



Route::get('/dashboard', function()
{
	if (Confide::user()) {


        if(Confide::user()->user_type == 'admin'){

            $members = Member::all();

            return View::make('dashboard', compact('members'));

        } 

        if(Confide::user()->user_type == 'teller'){

            $members = Member::all();

            return View::make('tellers.dashboard', compact('members'));

        } 


        if(Confide::user()->user_type == 'member'){

            $loans = Loanproduct::all();
            $products = Product::all();

            $rproducts = Product::getRemoteProducts();

            
            return View::make('shop.index', compact('loans', 'products', 'rproducts'));

        } 

      
        } else {
            return View::make('login');
        }
});
//



Route::get('transaudits', function(){

   
    $transactions = Loantransaction::all();

    return View::make('transaud', compact('transactions'));



});


Route::post('transaudits', function(){

    $date = Input::get('date');
    $type = Input::get('type');

    if($type == 'loan'){

        $transactions = DB::table('loantransactions')->where('date', '=', $date)->get();

        return View::make('transaudit', compact('transactions', 'type', 'date'));

   

    }



    if($type == 'savings'){

        $transactions = DB::table('savingtransactions')->where('date', '=', $date)->get();

        return View::make('transaudit', compact('transactions', 'type', 'date'));

   

    }
   
    


});






// Confide routes
Route::get('users/create', 'UsersController@create');
Route::get('users/edit/{user}', 'UsersController@edit');
Route::post('users/update/{user}', 'UsersController@update');
Route::post('users', 'UsersController@store');
Route::get('users/login', 'UsersController@login');
Route::post('users/login', 'UsersController@doLogin');
Route::get('users/confirm/{code}', 'UsersController@confirm');
Route::get('users/forgot_password', 'UsersController@forgotPassword');
Route::post('users/forgot_password', 'UsersController@doForgotPassword');
Route::get('users/reset_password/{token}', 'UsersController@resetPassword');
Route::post('users/reset_password', 'UsersController@doResetPassword');
Route::get('users/logout', 'UsersController@logout');
Route::resource('users', 'UsersController');
Route::get('users/activate/{user}', 'UsersController@activate');
Route::get('users/deactivate/{user}', 'UsersController@deactivate');
Route::get('users/destroy/{user}', 'UsersController@destroy');
Route::get('users/password/{user}', 'UsersController@Password');
Route::post('users/password/{user}', 'UsersController@changePassword');
Route::get('users/profile/{user}', 'UsersController@profile');
Route::get('users/add', 'UsersController@add');
Route::post('users/newuser', 'UsersController@newuser');

Route::get('tellers', 'UsersController@tellers');
Route::get('tellers/create/{id}', 'UsersController@createteller');
Route::get('tellers/activate/{id}', 'UsersController@activateteller');
Route::get('tellers/deactivate/{id}', 'UsersController@deactivateteller');

Route::get('members/profile', 'UsersController@password2');
Route::post('users/pass', 'UsersController@changePassword2');


Route::resource('roles', 'RolesController');
Route::get('roles/create', 'RolesController@create');
Route::get('roles/edit/{id}', 'RolesController@edit');
Route::post('roles/update/{id}', 'RolesController@update');
Route::get('roles/delete/{id}', 'RolesController@destroy');

Route::get('import', function(){

    return View::make('import');
});


Route::get('automated/loans', function(){

    
    $loanproducts = Loanproduct::all();

    return View::make('autoloans', compact('loanproducts'));
});


Route::post('automated/autoloans', function(){

    $data = Input::all();

    $period = array_get($data, 'period');
    $loanproductid = array_get($data, 'loanproduct_id');

    $loanproduct = Loanproduct::findOrFail($loanproductid);

    //check if loan has been processed



    if(Autoprocess::checkProcessed($period, 'loan', $loanproduct)){

        return Redirect::back()->with('notice', 'This period has already been processed');
    }

    

   

    $pr = explode('-', $period);
    $month = $pr[0];
    $year = $pr[1];
    $day = '21';

    $date = $year.'-'.$month.'-'.$day;

    $loanaccounts = DB::table('loanaccounts')->where('loanproduct_id', '=', $loanproductid)->get();

    return View::make('autoloan', compact('loanaccounts', 'date', 'period', 'loanproductid'));

});






Route::get('automated/savings', function(){

    
   $savingproducts = Savingproduct::all();

    return View::make('automated', compact('savingproducts'));
});



Route::post('automated/savin', function(){

    $data = Input::all();

    $period = array_get($data, 'period');

    $savingproductid = Input::get('savingproduct');

    $savingproduct = Savingproduct::findOrFail(Input::get('savingproduct'));
    //check if loan has been processed



    if(Autoprocess::checkProcessed($period, 'saving', $savingproduct)){

        return Redirect::back()->with('notice', 'This period has already been processed');
    }



    $pr = explode('-', $period);
    $month = $pr[0];
    $year = $pr[1];
    $day = '21';

    $date = $year.'-'.$month.'-'.$day;

    $members = Member::all();

    


    return View::make('savin', compact('members', 'date', 'period', 'savingproductid'));


});


Route::post('automated/savins', function(){

    $savingproduct = Savingproduct::findOrFail(Input::get('savingproduct_id'));

    $period = Input::get('period');

    $members = Input::get('member');
    $dates = Input::get('date');
    $amounts = Input::get('amount');

    
   $i=0;

    foreach($members as $member){

      
       
        $date = $dates[$i];
        $amount = $amounts[$i];

        $savingaccount = Member::getMemberAccount($member);
        $type = 'credit';
        $description = 'savings deposit';
        $transacted_by = Confide::user()->username;

        if(Savingtransaction::trasactionExists($date,$savingaccount) == false){

             Savingtransaction::transact($date, $savingaccount, $amount, $type, $description, $transacted_by);

        }
       

        $i++;


    }

    

    Autoprocess::record($period, 'saving', $savingproduct);

   return Redirect::to('automated/savings')->with('notice', 'saving transactions have been successfully saved');



});





Route::post('automated/autoloan', function(){



    $period = Input::get('period');

    $loanaccounts = Input::get('account');
    $dates = Input::get('date');
    $amounts = Input::get('amount');

    $loanproduct = Loanproduct::findOrFail(Input::get('loanproduct_id'));
   $i=0;

    foreach($loanaccounts as $loanaccount){

      
       
        $date = $dates[$i];
        $amount = $amounts[$i];

        $data = array('loanaccount_id' => $loanaccount, 'date' => $date, 'amount' => $amount );

        if(Loantransaction::trasactionExists($date,$loanaccount) == false){
            
            Loanrepayment::repayLoan($data);

        }
       

        $i++;


    }

    

   Autoprocess::record($period, 'loan', $loanproduct);

   return Redirect::to('automated/loans')->with('notice', 'loan repayment transactions have been successfully processed');



});









Route::post('automated', function(){

    $members = DB::table('members')->where('is_active', '=', true)->get();


    $category = Input::get('category');


    
    
    if($category == 'savings'){

        $savingproduct_id = Input::get('savingproduct');

        $savingproduct = Savingproduct::findOrFail($savingproduct_id);

        

            foreach($savingproduct->savingaccounts as $savingaccount){

                if(($savingaccount->member->is_active) && (Savingaccount::getLastAmount($savingaccount) > 0)){

                    
                    $data = array(
                        'account_id' => $savingaccount->id,
                        'amount' => Savingaccount::getLastAmount($savingaccount), 
                        'date' => date('Y-m-d'),
                        'type'=>'credit'
                        );

                    Savingtransaction::creditAccounts($data);
                    

                    

                }
 
                

            

    }

       Autoprocess::record(date('Y-m-d'), 'saving', $savingproduct); 
      

        

    } else {

        $loanproduct_id = Input::get('loanproduct');

        $loanproduct = Loanproduct::findOrFail($loanproduct_id);


        

        

            foreach($loanproduct->loanaccounts as $loanaccount){

                if(($loanaccount->member->is_active) && (Loanaccount::getEMP($loanaccount) > 0)){

                    
                    
                    $data = array(
                        'loanaccount_id' => $loanaccount->id,
                        'amount' => Loanaccount::getEMP($loanaccount), 
                        'date' => date('Y-m-d')
                        
                        );


                    Loanrepayment::repayLoan($data);
                    

                    
                   

                    

                }
            }


             Autoprocess::record(date('Y-m-d'), 'loan', $loanproduct);
            

    }


    

    return Redirect::back()->with('notice', 'successfully processed');
    

    
});




Route::get('system', function(){


    $organization = Organization::find(1);

    return View::make('system.index', compact('organization'));
});



Route::get('license', function(){


    $organization = Organization::find(1);

    return View::make('system.license', compact('organization'));
});




/**
* Organization routes
*/
Route::resource('organizations', 'OrganizationsController');
Route::post('organizations/update/{id}', 'OrganizationsController@update');
Route::post('organizations/logo/{id}', 'OrganizationsController@logo');

Route::get('language/{lang}', 
           array(
                  'as' => 'language.select', 
                  'uses' => 'OrganizationsController@language'
                 )
          );



Route::resource('currencies', 'CurrenciesController');
Route::get('currencies/edit/{id}', 'CurrenciesController@edit');
Route::post('currencies/update/{id}', 'CurrenciesController@update');
Route::get('currencies/delete/{id}', 'CurrenciesController@destroy');
Route::get('currencies/create', 'CurrenciesController@create');


Route::get('loanrepayments/offprint/{id}', 'LoanrepaymentsController@offprint');



/* 
* apartments routes
*/

Route::resource('apartments', 'ApartmentsController');
Route::get('apartments/list', 'ApartmentsController@list');
Route::get('apartments/create', 'ApartmentsController@create');
Route::get('apartments/edit/{id}', 'ApartmentsController@edit');
Route::post('apartments/update/{id}', 'ApartmentsController@update');
Route::get('apartments/show/{id}', 'ApartmentsController@show');
Route::get('apartments/delete/{id}', 'ApartmentsController@destroy');



/*
* branches routes
*/



Route::resource('branches', 'BranchesController');
Route::post('branches/update/{id}', 'BranchesController@update');
Route::get('branches/delete/{id}', 'BranchesController@destroy');
Route::get('branches/edit/{id}', 'BranchesController@edit');


Route::resource('groups', 'GroupsController');
Route::post('groups/update/{id}', 'GroupsController@update');
Route::get('groups/delete/{id}', 'GroupsController@destroy');
Route::get('groups/edit/{id}', 'GroupsController@edit');


Route::resource('members', 'MembersController');
Route::post('members/update/{id}', 'MembersController@update');
Route::get('members/delete/{id}', 'MembersController@destroy');
Route::get('members/edit/{id}', 'MembersController@edit');

Route::get('members/show/{id}', 'MembersController@show');
Route::get('members/loanaccounts/{id}', 'MembersController@loanaccounts');
Route::get('memberloans', 'MembersController@loanaccounts2');
Route::group(['before' => 'limit'], function() {

    Route::get('members/create', 'MembersController@create');
});

Route::resource('kins', 'KinsController');
Route::post('kins/update/{id}', 'KinsController@update');
Route::get('kins/delete/{id}', 'KinsController@destroy');
Route::get('kins/edit/{id}', 'KinsController@edit');
Route::get('kins/show/{id}', 'KinsController@show');
Route::get('kins/create/{id}', 'KinsController@create');


Route::resource('accounts', 'AccountsController');
Route::post('accounts/update/{id}', 'AccountsController@update');
Route::get('accounts/delete/{id}', 'AccountsController@destroy');
Route::get('accounts/edit/{id}', 'AccountsController@edit');
Route::get('accounts/show/{id}', 'AccountsController@show');
Route::get('accounts/create/{id}', 'AccountsController@create');




Route::resource('journals', 'JournalsController');
Route::post('journals/update/{id}', 'JournalsController@update');
Route::get('journals/delete/{id}', 'JournalsController@destroy');
Route::get('journals/edit/{id}', 'JournalsController@edit');
Route::get('journals/show/{id}', 'JournalsController@show');



Route::resource('charges', 'ChargesController');
Route::post('charges/update/{id}', 'ChargesController@update');
Route::get('charges/delete/{id}', 'ChargesController@destroy');
Route::get('charges/edit/{id}', 'ChargesController@edit');
Route::get('charges/show/{id}', 'ChargesController@show');
Route::get('charges/disable/{id}', 'ChargesController@disable');
Route::get('charges/enable/{id}', 'ChargesController@enable');

Route::resource('savingproducts', 'SavingproductsController');
Route::post('savingproducts/update/{id}', 'SavingproductsController@update');
Route::get('savingproducts/delete/{id}', 'SavingproductsController@destroy');
Route::get('savingproducts/edit/{id}', 'SavingproductsController@edit');
Route::get('savingproducts/show/{id}', 'SavingproductsController@show');




Route::resource('savingaccounts', 'SavingaccountsController');
Route::get('savingaccounts/create/{id}', 'SavingaccountsController@create');
Route::get('member/savingaccounts/{id}', 'SavingaccountsController@memberaccounts');



Route::get('savingtransactions/show/{id}', 'SavingtransactionsController@show');
Route::resource('savingtransactions', 'SavingtransactionsController');
Route::get('savingtransactions/create/{id}', 'SavingtransactionsController@create');
Route::get('savingtransactions/receipt/{id}', 'SavingtransactionsController@receipt');
Route::get('savingtransactions/statement/{id}', 'SavingtransactionsController@statement');
Route::get('savingtransactions/void/{id}', 'SavingtransactionsController@void');

Route::post('savingtransactions/import', 'SavingtransactionsController@import');

//Route::resource('savingpostings', 'SavingpostingsController');



Route::resource('shares', 'SharesController');
Route::post('shares/update/{id}', 'SharesController@update');
Route::get('shares/delete/{id}', 'SharesController@destroy');
Route::get('shares/edit/{id}', 'SharesController@edit');
Route::get('shares/show/{id}', 'SharesController@show');



Route::get('sharetransactions/show/{id}', 'SharetransactionsController@show');
Route::resource('sharetransactions', 'SharetransactionsController');
Route::get('sharetransactions/create/{id}', 'SharetransactionsController@create');





Route::post('license/key', 'OrganizationsController@generate_license_key');
Route::post('license/activate', 'OrganizationsController@activate_license');
Route::get('license/activate/{id}', 'OrganizationsController@activate_license_form');











Route::resource('loanproducts', 'LoanproductsController');
Route::post('loanproducts/update/{id}', 'LoanproductsController@update');
Route::get('loanproducts/delete/{id}', 'LoanproductsController@destroy');
Route::get('loanproducts/edit/{id}', 'LoanproductsController@edit');
Route::get('loanproducts/show/{id}', 'LoanproductsController@show');



Route::resource('loanguarantors', 'LoanguarantorsController');
Route::post('loanguarantors/update/{id}', 'LoanguarantorsController@update');
Route::get('loanguarantors/delete/{id}', 'LoanguarantorsController@destroy');
Route::get('loanguarantors/edit/{id}', 'LoanguarantorsController@edit');
Route::get('loanguarantors/create/{id}', 'LoanguarantorsController@create');
Route::get('loanguarantors/css/{id}', 'LoanguarantorsController@csscreate');

Route::post('loanguarantors/cssupdate/{id}', 'LoanguarantorsController@cssupdate');
Route::get('loanguarantors/cssdelete/{id}', 'LoanguarantorsController@cssdestroy');
Route::get('loanguarantors/cssedit/{id}', 'LoanguarantorsController@cssedit');



Route::resource('loans', 'LoanaccountsController');
Route::get('loans/apply/{id}', 'LoanaccountsController@apply');
Route::post('loans/apply', 'LoanaccountsController@doapply');
Route::post('loans/application', 'LoanaccountsController@doapply2');


Route::get('loantransactions/statement/{id}', 'LoantransactionsController@statement');
Route::get('loantransactions/receipt/{id}', 'LoantransactionsController@receipt');

Route::get('loans/application/{id}', 'LoanaccountsController@apply2');
Route::post('shopapplication', 'LoanaccountsController@shopapplication');

Route::get('loans/edit/{id}', 'LoanaccountsController@edit');
Route::post('loans/update/{id}', 'LoanaccountsController@update');

Route::get('loans/approve/{id}', 'LoanaccountsController@approve');
Route::post('loans/approve/{id}', 'LoanaccountsController@doapprove');


Route::get('loans/reject/{id}', 'LoanaccountsController@reject');
Route::post('rejectapplication', 'LoanaccountsController@rejectapplication');

Route::get('loans/disburse/{id}', 'LoanaccountsController@disburse');
Route::post('loans/disburse/{id}', 'LoanaccountsController@dodisburse');

Route::get('loans/show/{id}', 'LoanaccountsController@show');

Route::post('loans/amend/{id}', 'LoanaccountsController@amend');

Route::get('loans/reject/{id}', 'LoanaccountsController@reject');
Route::post('loans/reject/{id}', 'LoanaccountsController@rejectapplication');


Route::get('loanaccounts/topup/{id}', 'LoanaccountsController@gettopup');
Route::post('loanaccounts/topup/{id}', 'LoanaccountsController@topup');

Route::get('memloans/{id}', 'LoanaccountsController@show2');

Route::resource('loanrepayments', 'LoanrepaymentsController');

Route::get('loanrepayments/create/{id}', 'LoanrepaymentsController@create');
Route::get('loanrepayments/offset/{id}', 'LoanrepaymentsController@offset');
Route::post('loanrepayments/offsetloan', 'LoanrepaymentsController@offsetloan');





Route::get('reports', function(){

    return View::make('members.reports');
});

Route::get('reports/combined', function(){

    $members = Member::all();

    return View::make('members.combined', compact('members'));
});


Route::get('loanreports', function(){

    $loanproducts = Loanproduct::all();

    return View::make('loanaccounts.reports', compact('loanproducts'));
});


Route::get('savingreports', function(){

    $savingproducts = Savingproduct::all();

    return View::make('savingaccounts.reports', compact('savingproducts'));
});


Route::get('financialreports', function(){

    

    return View::make('pdf.financials.reports');
});



Route::get('reports/listing', 'ReportsController@members');
Route::get('reports/remittance', 'ReportsController@remittance');
Route::get('reports/blank', 'ReportsController@template');
Route::get('reports/loanlisting', 'ReportsController@loanlisting');

Route::get('reports/loanproduct/{id}', 'ReportsController@loanproduct');

Route::get('reports/savinglisting', 'ReportsController@savinglisting');

Route::get('reports/savingproduct/{id}', 'ReportsController@savingproduct');

Route::post('reports/financials', 'ReportsController@financials');



Route::get('portal', function(){

    $members = DB::table('members')->where('is_active', '=', TRUE)->get();
    return View::make('css.members', compact('members'));
});

Route::get('portal/activate/{id}', 'MembersController@activateportal');
Route::get('portal/deactivate/{id}', 'MembersController@deactivateportal');
Route::get('css/reset/{id}', 'MembersController@reset');








/*
* Vendor controllers
*/
Route::resource('vendors', 'VendorsController');
Route::get('vendors/create', 'VendorsController@create');
Route::post('vendors/update/{id}', 'VendorsController@update');
Route::get('vendors/edit/{id}', 'VendorsController@edit');
Route::get('vendors/delete/{id}', 'VendorsController@destroy');
Route::get('vendors/products/{id}', 'VendorsController@products');
Route::get('vendors/orders/{id}', 'VendorsController@orders');

/*
* products controllers
*/
Route::resource('products', 'ProductsController');
Route::post('products/update/{id}', 'ProductsController@update');
Route::get('products/edit/{id}', 'ProductsController@edit');
Route::get('products/create', 'ProductsController@create');
Route::get('products/delete/{id}', 'ProductsController@destroy');
Route::get('products/orders/{id}', 'ProductsController@orders');
Route::get('shop', 'ProductsController@shop');

/*
* orders controllers
*/
Route::resource('orders', 'OrdersController');
Route::post('orders/update/{id}', 'OrdersControler@update');
Route::get('orders/edit/{id}', 'OrdersControler@edit');
Route::get('orders/delete/{id}', 'OrdersControler@destroy');




Route::get('savings', function(){

    $mem = Confide::user()->username;

   

    $memb = DB::table('members')->where('membership_no', '=', $mem)->pluck('id');

    $member = Member::find($memb);

    
    

    return View::make('css.savingaccounts', compact('member'));
});


Route::post('loanguarantors', function(){

    
    $mem_id = Input::get('member_id');

        $member = Member::findOrFail($mem_id);

        $loanaccount = Loanaccount::findOrFail(Input::get('loanaccount_id'));


        $guarantor = new Loanguarantor;

        $guarantor->member()->associate($member);
        $guarantor->loanaccount()->associate($loanaccount);
        $guarantor->amount = Input::get('amount');
        $guarantor->save();
        


        return Redirect::to('memloans/'.$loanaccount->id);

});


Route::resource('audits', 'AuditsController');

Route::get('backups', function(){

   
    //$backups = Backup::getRestorationFiles('../app/storage/backup/');

    return View::make('backup');

});


Route::get('backups/create', function(){

    echo '<pre>';

    $instance = Backup::getBackupEngineInstance();

    print_r($instance);

    //Backup::setPath(public_path().'/backups/');

   //Backup::export();
    //$backups = Backup::getRestorationFiles('../app/storage/backup/');

    //return View::make('backup');

});


Route::get('memtransactions/{id}', 'MembersController@savingtransactions');

Route::get('convert', function(){




// get the name of the organization from the database
//$org_id = Confide::user()->organization_id;

$organization = Organization::findorfail(1);



$string =  $organization->name;

echo "Organization: ". $string."<br>";


$organization = new Organization;






$license_code = $organization->encode($string);

echo "License Code: ".$license_code."<br>";


$name2 = $organization->decode($license_code, 7);

echo "Decoded L code: ".$name2."<br>";





$license_key = $organization->license_key_generator($license_code);

echo "License Key: ".$license_key."<br>";

echo "__________________________________________________<br>";

$name4 = $organization->license_key_validator($license_key,$license_code,$string);

echo "Decoded L code: ".$name4."<br>";



});


Route::get('perms', function(){

    $perm = new Permission;

    $perm->name = 'edit_loan_product';
    $perm->display_name = 'edit loan products';
    $perm->category = 'Loanproduct';
    $perm->save();

    

    $perm = new Permission;

    $perm->name = 'view_loan_product';
    $perm->display_name = 'view loan products';
    $perm->category = 'Loanproduct';
    $perm->save();

    $perm = new Permission;

    $perm->name = 'delete_loan_product';
    $perm->display_name = 'delete loan products';
    $perm->category = 'Loanproduct';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'create_loan_account';
    $perm->display_name = 'create loan account';
    $perm->category = 'Loanaccount';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'view_loan_account';
    $perm->display_name = 'view loan account';
    $perm->category = 'Loanaccount';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'approve_loan_account';
    $perm->display_name = 'approve loan';
    $perm->category = 'Loanaccount';
    $perm->save();


    $perm = new Permission;

    $perm->name = 'disburse_loan';
    $perm->display_name = 'disburse loan';
    $perm->category = 'Loanaccount';
    $perm->save();



});


Route::get('rproducts', function(){

    Product::getRemoteProducts();


});



Route::get('reports/deduction', function(){

   return View::make('deduction');

});


Route::post('deductions', function(){

    $date = Input::get('date');

    $members = Member::all();

    $loanproducts = Loanproduct::all();

    $savingproducts = Savingproduct::all();

    return View::make('dedreport', compact('members', 'loanproducts', 'savingproducts', 'date'));
});


Route::post('import/savings', function(){

   if(Input::hasFile('savings')){

      $destination = storage_path().'/backup/';

      $filename = str_random(12);

      $ext = Input::file('savings')->getClientOriginalExtension();
      $file = $filename.'.'.$ext;
     
      Input::file('savings')->move($destination, $file);


    Excel::load(storage_path().'/backup/'.$file, function($reader){

          $results = $reader->get();    

        // Getting all results
        foreach($results as $result){

            $date = date('Y-m-d', strtotime($result->date));
            $savingaccount = Member::getMemberAccount($result->id);

            if(Savingtransaction::trasactionExists($date, $savingaccount) == false){


                     $amount = $result->amount;
            if($amount >= 0){
                $type = 'credit';
                $description = 'savings deposit';
            } else {
                $type = 'debit';
                $description = 'savings withdrawal';
                $amount = preg_replace('/[^0-9]+/', '', $amount);
            }
            $transacted_by = $result->member;
            


            Savingtransaction::transact($date, $savingaccount, $amount, $type, $description, $transacted_by);



            }
           
           
        }

    });





    return Redirect::back()->with('notice', 'savings have been imported');

} else {

    return Redirect::back()->with('error', 'You have not uploaded any file');

}



});


Route::post('import/loans', function(){


     if(Input::hasFile('loans')){

      $destination = storage_path().'/backup/';

      $filename = str_random(12);

      $ext = Input::file('loans')->getClientOriginalExtension();
      $file = $filename.'.'.$ext;
     
      Input::file('loans')->move($destination, $file);

      Excel::load(storage_path().'/backup/'.$file, function($reader){

            $results = $reader->get();    

            // Getting all results
            foreach($results as $result){


        $date = date('Y-m-d', strtotime($result->date));

        $member_id = $result->id;
        $loanproduct_id = $result->product;

        $amount = $result->amount;

        $member = Member::findorfail($member_id);

        $loanproduct = Loanproduct::findorfail($loanproduct_id);

        $loanaccount = new Loanaccount;
        $loanaccount->member()->associate($member);
        $loanaccount->loanproduct()->associate($loanproduct);
        $loanaccount->application_date = $date;
        $loanaccount->amount_applied = $amount;
        $loanaccount->repayment_duration = $result->period;

        
        $loanaccount->date_approved = $date;
        $loanaccount->amount_approved = $amount;
        $loanaccount->interest_rate = $result->rate;
        $loanaccount->period = $result->period;
        $loanaccount->is_approved = TRUE;
        $loanaccount->is_new_application = FALSE;

        $loanaccount->date_disbursed = $date;
        $loanaccount->amount_disbursed = $amount;
        $loanaccount->repayment_start_date = $date;
        $loanaccount->account_number = Loanaccount::loanAccountNumber($loanaccount);
        $loanaccount->is_disbursed = TRUE;
        
    
        $loanaccount->save();

        $loanamount = $amount + Loanaccount::getInterestAmount($loanaccount);
        Loantransaction::disburseLoan($loanaccount, $loanamount, $date);

            }

    });

  }


});


















